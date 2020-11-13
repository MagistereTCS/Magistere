<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');


class UserProfileSynchronisation
{

    function __construct()
    {

    }

    function updateLocalUser()
    {
        $this->mergeLocalUserWithMemcachedUser();
    }


    private function reloadUserInMemcached($user)
    {
        global $CFG;
        $key = str_replace('%MMID%', get_mmid(), $CFG->userProfileSynchronisationKey);
        $key = str_replace('%USERNAME%', $user->username, $key);
        mmcached_set($key, $user, $CFG->userProfileSynchronisationExpireDelay);
    }


    private function mergeLocalUserWithMemcachedUser($userToUpdate=null)
    {
        global $CFG, $USER, $DB;

        if ($userToUpdate !== null)
        {
            $useru = $userToUpdate;
        }else{
            $useru =& $USER;
        }

        if (!isset($useru->username))
        {
            return $useru;
        }

        $key = str_replace('%MMID%', get_mmid(), $CFG->userProfileSynchronisationKey);
        $key = str_replace('%USERNAME%', $useru->username, $key);
        $frontal_user = mmcached_get($key);


        if ($frontal_user === false)
        {
            if (($frontalDB = databaseConnection::instance()->get('frontal')) === false){error_log('Base du frontal non trouvee'); return;}
            $frontal_user = $frontalDB->get_record('user',array('username'=>$useru->username));

            if ($frontal_user !== false)
            {
                $frontal_user_context = $frontalDB->get_record('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $frontal_user->id));

                $tags = $frontalDB->get_records_sql('SELECT ti.id, t.name
FROM {tag_instance} ti 
INNER JOIN {tag} t ON t.id=ti.tagid
WHERE ti.component = "core" AND ti.itemtype = "user" AND ti.itemid = '.$frontal_user->id.' AND contextid = '.$frontal_user_context->id);

                $frontal_user->interests = array();
                foreach($tags as $t){
                    $frontal_user->interests[] = $t->name;
                }

                $this->reloadUserInMemcached($frontal_user);
            }
        }

        if (isset($useru->username))
        {
            $username = $useru->username;
        }
        else if (isset($useru->idnumber) && strlen($useru->idnumber) > 20)
        {
            $username = $useru->idnumber;
        }


        if ($frontal_user !== false && isset($username) && strlen($username) > 2)
        {
            $local_user = $DB->get_record('user',array('username'=>$username));
            if ($local_user === false){return $useru;}

            if(!isset($frontal_user->interests)){
                $frontal_user->interests = array();
            }

            if(!isset($useru->interests)){
                $useru->interests = array();
            }

            // sync interests
            if($frontal_user->interests != $useru->interests)
            {
                sort($frontal_user->interests);
                sort($useru->interests);

                if($frontal_user->interests != $useru->interests)
                {
                    $DB->delete_records('tag_instance', array(
                        'component' => 'core', 'itemtype' => 'user',
                        'itemid' => $useru->id, 'contextid' => context_user::instance($useru->id)->id));


                    if (($frontalDB = databaseConnection::instance()->get('frontal')) === false){error_log('Base du frontal non trouvee'); return;}
                    $frontal_user_context = $frontalDB->get_record('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $frontal_user->id));

                    $tags = $frontalDB->get_records('tag_instance', array(
                        'component' => 'core', 'itemtype' => 'user',
                        'itemid' => $frontal_user->id, 'contextid' => $frontal_user_context->id));

                    foreach($tags as $tag){
                        unset($tag->id);
                        $tag->itemid = $useru->id;
                        $tag->contextid = context_user::instance($useru->id)->id;

                        $DB->insert_record('tag_instance', $tag);
                    }
                }
            }

            // sync profile
            foreach($CFG->ws_user_profile_allowed_fields as $field)
            {
                if (isset($frontal_user->{$field}))
                {
                    $useru->{$field} = $frontal_user->{$field};
                    $local_user->{$field} = $frontal_user->{$field};
                }
            }

            $DB->update_record('user',$local_user);
        }
        return $useru;
    }

    function updateFrontalUser($source_user = null)
    {
        global $CFG, $USER;

        if ($source_user == null ){$source_user = $USER;}

        if (!isset($source_user->id))
        {
            return false;
        }

        try
        {
            if (($frontalDB = databaseConnection::instance()->get('frontal')) === false){error_log('Base du frontal non trouvee'); return;}

            $frontal_user = $frontalDB->get_record('user',array('username'=>$source_user->username));

            if ($frontal_user !== false)
            {
                foreach($CFG->ws_user_profile_allowed_fields as $field)
                {
                    if (isset($source_user->{$field}))
                    {
                        $frontal_user->{$field} = $source_user->{$field};
                    }
                }

                $frontalDB->update_record('user',$frontal_user);
                $this->updateFrontalUserTag($USER->id, $frontal_user->id, $frontalDB);

                $this->reloadUserInMemcached($frontal_user);
            }

        } catch (moodle_exception $e) {
            error_log('UserProfileSynchronisation->updateFrontalUser()#'.$e);
        }
    }

    function updateFrontalUserTag($aca_user_id, $frontal_user_id, $frontalDB)
    {
        global $DB;

        $aca_user_context = context_user::instance($aca_user_id);
        $frontal_user_context = $frontalDB->get_record('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $frontal_user_id));

        $tags = $DB->get_records('tag_instance', array(
            'component' => 'core', 'itemtype' => 'user',
            'itemid' => $aca_user_id, 'contextid' => $aca_user_context->id));

        $frontalDB->delete_records('tag_instance', array(
            'component' => 'core', 'itemtype' => 'user',
            'itemid' => $frontal_user_id, 'contextid' => $frontal_user_context->id));

        foreach($tags as $tag){
            unset($tag->id);
            $tag->itemid = $frontal_user_id;
            $tag->contextid = $frontal_user_context->id;

            $frontalDB->insert_record('tag_instance', $tag);
        }
    }
}
