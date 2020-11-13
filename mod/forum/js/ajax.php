<?php
require_once('../../../config.php');
require_once('../lib.php');
require_once($CFG->libdir.'/completionlib.php');

require_login();

$postid = required_param("postid", PARAM_INT);
$liked = optional_param("liked", 0, PARAM_INT);
$favorited = optional_param("favorited", 0, PARAM_INT);
$action = optional_param("action", "", PARAM_ALPHANUM);
$count = 0;

if($action == "like"){
    // Lorsque l'on clique sur un bouton Like
    if(!$liked){
        if(!forum_get_popularity_by_user_and_post($postid)) {
            forum_add_popularity($postid);
            $liked = 1;
            
            $post = $DB->get_record('forum_posts',array('id'=>$postid));
            $discussion = $DB->get_record('forum_discussions',array('id'=>$post->discussion));
            $forum = $DB->get_record('forum',array('id'=>$discussion->forum));
            $cm = get_coursemodule_from_instance('forum', $forum->id);
            $course = get_course($discussion->course);
            
            // Update completion status.
            $completion = new completion_info($course);
            if($completion->is_enabled($cm) && $forum->completionpopularity) {
                $completion->update_state($cm,COMPLETION_COMPLETE);
            }
        }
    } else {
        forum_delete_popularity($postid);
        $liked = 0;
    }
}

if($action == "favorite"){
    // Lorsque l'on clique sur un bouton Favorite
    if(!$favorited){
        if(!forum_get_favorite_by_user_and_post($postid)) {
            forum_add_favorite($postid);
            $favorited = 1;
        }
    } else {
        forum_delete_favorite($postid);
        $favorited = 0;
    }
}

$data_array = array(
    'count' => count(forum_get_popularities_by_post_id($postid)),
    'liked' => $liked,
    'favorited' => $favorited,
    'postid' => $postid
);


// Envoi des données selon le cas
$data = json_encode($data_array);
echo $data;