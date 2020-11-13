<?php

require_once($CFG->dirroot . '/badges/renderer.php');
require_once($CFG->dirroot . '/local/magisterelib/external_academic_badges.php');

/**
 * Class theme_magistere_core_badges_renderer
 */
class theme_magistere_core_badges_renderer extends core_badges_renderer {
    /**
     * Outputs badges list.
     *
     * @param $badges
     * @param $userid
     * @param bool $profile
     * @param bool $external
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function print_badges_list($badges, $userid, $profile = false, $external = false) {
        global $USER, $CFG, $PAGE, $OUTPUT;
        foreach ($badges as $badge) {
            if(!$profile){
                $notexpiredbadge = (empty($badge->dateexpire) || $badge->dateexpire > time());
                $backpackexists = badges_user_has_backpack($USER->id);
                if (!empty($CFG->badges_allowexternalbackpack) && $notexpiredbadge && $backpackexists) {
                    $assertion = new moodle_url('/badges/assertion.php', ['b' => $badge->uniquehash]);
                    $action = new component_action('click',
                        'addtobackpack',
                        ['assertion' => $assertion->out(false)]);
                    $OUTPUT->add_action_handler($action, 'addbutton_' . $badge->id);
                }
            } else {
                if (!$external) {
                    $bname = $badge->name;
                    $url = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));

                    if(isset($badge->aca_name)){
                        $url = new moodle_url($CFG->magistere_domaine.'/'.$badge->aca_name.'/badges/badge.php', [
                            'hash' => $badge->uniquehash
                        ]);
                        $url = $url->out(false);
                    }

                    if(!isset($badge->aca_name)){
                        $badge->aca_name = $CFG->academie_name;
                    }

                    $assertion = new external_academic_badges_assertion($badge->uniquehash, $badge->aca_name);
                    $badgeclass = $assertion->get_badge_class();
                    $imageurl = $badgeclass['image'];

                } else {
                    $bname = s($badge->assertion->badge->name);
                    $imageurl = $badge->imageUrl;
                    $hash = hash('md5', $badge->hostedUrl);
                    $url = new moodle_url('/badges/external.php', array('hash' => $hash, 'user' => $userid));
                }

                $name = html_writer::tag('span', $bname, array('class' => 'badge-name'));
                $image = html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image'));
                $items[] = html_writer::link($url, $image . $name, array('title' => $bname));
            }
        }

        if(!$profile){
            $ajax_url = $CFG->wwwroot.'/badges/mybadges_ajax.php';
            $strs = [
                'name' => get_string('jtheader_name', 'badges'),
                'description' => get_string('jtheader_description', 'badges'),
                'dateobtained' => get_string('jtheader_dateobtained', 'badges'),
                'visibility' => get_string('jtheader_visibility', 'badges'),
                'actions' => get_string('jtheader_actions', 'badges'),
            ];

            $PAGE->requires->js_call_amd('core_badges/mybadges', 'init', [
                $ajax_url,
                $strs
            ]);
            $PAGE->requires->js_call_amd('core_badges/badge', 'init');

            $html = html_writer::div('','', ['id' => 'MyBadgesTable']);
            $html .= html_writer::div('', '', ['id' => 'dialog_confirm']);
            return $html;
        } else {
            return html_writer::alist($items, array('class' => 'badges'));
        }
    }

    /**
     * Displays the user badges.
     *
     * @param badge_user_collection $badges
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function render_badge_user_collection(badge_user_collection $badges) {
        global $CFG, $USER;

        $backpack = $badges->backpack;
        $mybackpack = new moodle_url('/badges/mybackpack.php');

        // Local badges.
        $localhtml = html_writer::start_tag('div', ['id' => 'issued-badge-table', 'class' => 'generalbox']);
        $heading = get_string('tcs_mybadges', 'badges');
        $localhtml .= $this->output->heading($heading, '3', 'badges');
        if ($badges->badges) {
            $htmllist = $this->print_badges_list($badges->badges, $USER->id);
            $localhtml .= $htmllist;
        } else {
            $localhtml .= $this->output->notification(get_string('nobadges', 'badges'));
        }
        $localhtml .= html_writer::end_tag('div');

        // External badges.
        $externalhtml = "";
        if (!empty($CFG->badges_allowexternalbackpack)) {
            $externalhtml .= html_writer::start_tag('div', ['class' => 'generalbox']);
            $externalhtml .= $this->output->heading_with_help(get_string('externalbadges', 'badges'),
                'externalbadges',
                'badges');
            if (!is_null($backpack)) {
                if ($backpack->totalcollections == 0) {
                    $externalhtml .= get_string('nobackpackcollections', 'badges', $backpack);
                } else {
                    if ($backpack->totalbadges == 0) {
                        $externalhtml .= get_string('nobackpackbadges', 'badges', $backpack);
                    } else {
                        $externalhtml .= get_string('backpackbadges', 'badges', $backpack);
                        $externalhtml .= '<br/><br/>'
                            . $this->print_badges_list($backpack->badges, $USER->id, true, true);
                    }
                }
            } else {
                $externalhtml .= get_string('externalconnectto', 'badges', $mybackpack->out());
            }

            $externalhtml .= html_writer::end_tag('div');
        }

        return $localhtml . $externalhtml;
    }

    /**
     * Outputs issued badge with actions available.
     *
     * @param issued_badge $ibadge
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function render_issued_badge(issued_badge $ibadge) {
        global $USER, $CFG, $DB, $SITE, $PAGE;

        $PAGE->requires->js_call_amd('core_badges/badge', 'init');

        $issued = $ibadge->issued;
        $userinfo = $ibadge->recipient;
        $badgeclass = $ibadge->badgeclass;
        $badge = new badge($ibadge->badgeid);

        $now = time();
        $expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;

        $output = '';
        $output .= html_writer::start_tag('div', ['id' => 'badge']);

        $output .= html_writer::start_tag('div', ['id' => 'badge-image']);
        $output .= html_writer::start_tag('div', ['id' => 'badge-image-content']);
        $output .= html_writer::start_tag('div', ['id' => 'badge-visible']);
        if($USER->id == $userinfo->id && !empty($CFG->enablebadges)){
            if($ibadge->visible){
                $extraclasses = 'visible';
                $badge_state = get_string('public_state', 'badges');
            } else {
                $extraclasses = 'hide';
                $badge_state = get_string('private_state', 'badges');
            }
            $output .= html_writer::tag('i', '', ['class' => 'fas fa-circle '.$extraclasses]);
            $output .= html_writer::span($badge_state);
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['id' => 'badge-img-src']);
        $output .= html_writer::empty_tag('img', ['src' => $badgeclass['image'],
            'alt' => $badge->name,
            'id' => 'badge-img']);
        if ($expiration < $now) {
            $output .= $this->output->pix_icon('i/expired',
                get_string('expireddate', 'badges', userdate($issued['expires'])),
                'moodle',
                ['class' => 'expireimage']);
        }
        $output .= html_writer::end_tag('div');

        if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
            // Visibility action
            $output .= html_writer::start_div('badge-action');
            $output .= html_writer::start_tag('form',
                ['method' => 'POST',
                    'action' => new moodle_url('/badges/badge.php')]);
            if ($ibadge->visible) {
                $status = 'Rendre privé';
                $fa_icon = $this->icon_fontawesome('eye-slash');
                $output .= html_writer::input_hidden_params(new moodle_url('/badges/badge.php',
                    ['hide' => true, 'hash' => $issued['uid'], 'sesskey' => sesskey()]));
            } else {
                $status = 'Rendre public';
                $fa_icon = $this->icon_fontawesome('eye');
                $output .= html_writer::input_hidden_params(new moodle_url('/badges/badge.php',
                    ['show' => true, 'hash' => $issued['uid'], 'sesskey' => sesskey()]));
            }
            $output .= html_writer::tag('button',
                $fa_icon . $status,
                ['type'=>'submit']);
            $output .= html_writer::end_tag('form');
            $output .= html_writer::end_div();

            // Print action
            $link = new moodle_url('/badges/prettyview.php', ['hash' => $issued['uid']]);
            $output .= html_writer::start_div('badge-action');
            $output .= $this->output->action_link($link,
                $this->icon_fontawesome('print') . get_string('print', 'glossary'),
                new popup_action('click', $link),
                ['class' => 'print-button btn']);
            $output .= html_writer::end_div();

            // Export to bagpack action
            if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now)
                && badges_user_has_backpack($USER->id)) {
                $assertion = new moodle_url('/badges/assertion.php', ['b' => $issued['uid']]);
                $action = new component_action('click',
                    'addtobackpack',
                    ['assertion' => $assertion->out(false)]);
                $attributes = [
                    'type'  => 'button',
                    'id'    => 'addbutton',
                    'value' => get_string('addtobackpack', 'badges')
                ];
                $output .= html_writer::start_div('badge-action');
                $this->output->add_action_handler($action, 'addbutton');
                $output .= html_writer::tag('button',
                $this->icon_fontawesome('share-square')
                    . get_string('addtobackpack', 'badges'),
                $attributes);
                $output .= html_writer::end_div();
            }

            // URL Copy action
            $link = new moodle_url('/badges/assertion.php', ['b' => $issued['uid']]);
            $js = "navigator.clipboard.writeText('".$link."').then(function() { alert('Lien copié avec succès')})";
            $output .= html_writer::start_div('badge-action');
            $output .= html_writer::tag('button',
                $this->icon_fontawesome('copy') . get_string('copylink'),
                ['onclick' => $js]);
            $output .= html_writer::end_div();

            // Download action
            $output .= html_writer::start_div('badge-action');
            $output .= html_writer::start_tag('form', [
                'method' => 'POST',
                'action' => new moodle_url('/badges/badge.php')
            ]);
            $output .= html_writer::input_hidden_params(new moodle_url('/badges/badge.php',
                ['hash' => $issued['uid'], 'bake' => true]));
            $output .= html_writer::tag('button',
                $this->icon_fontawesome('download') . get_string('download'),
                ['type'=>'submit']);
            $output .= html_writer::end_tag('form');
            $output .= html_writer::end_div();

            // Delete action
            $output .= html_writer::start_div('badge-action');
            $output .= html_writer::start_tag('form', [
                'method' => 'POST',
            ]);
            $output .= html_writer::input_hidden_params(new moodle_url('/badges/badge.php',
                ['hash' => $issued['uid'], 'delete' => true]));
            $output .= html_writer::tag('button',
                $this->icon_fontawesome('trash-alt') . get_string('delete'), [
                    'type'=>'submit',
                    'class' => 'delete-badge-btn'
                ]);
            $output .= html_writer::end_tag('form');
            $output .= html_writer::end_div();

        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', ['id' => 'badge-details']);
        // Recipient information.
        $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
        $dl = [];
        if ($userinfo->deleted) {
            $strdata = new stdClass();
            $strdata->user = fullname($userinfo);
            $strdata->site = format_string($SITE->fullname, true, ['context' => context_system::instance()]);

            $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
        } else {
            $dl[get_string('name')] = fullname($userinfo);
        }
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
        $dl = [];
        $dl[get_string('issuername', 'badges')] = $badge->issuername;
        if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
            $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
        }
        $dl[get_string('origin', 'badges')] = $SITE->fullname; // $CFG->academie_name
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
        $dl = [];
        $dl[get_string('name')] = $badge->name;
        $dl[get_string('description', 'badges')] = $badge->description;

        if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
            $coursename = $DB->get_field('course', 'fullname', ['id' => $badge->courseid]);
            $dl[get_string('course')] = $coursename;
        }
        $dl[get_string('bcriteria', 'badges')] = $this->print_badge_criteria($badge);
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
        $dl = [];
        $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
        if (isset($issued['expires'])) {
            if ($issued['expires'] < $now) {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires'])
                    . get_string('warnexpired', 'badges');

            } else {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
            }
        }

        // Print evidence.
        $agg = $badge->get_aggregation_methods();
        $evidence = $badge->get_criteria_completions($userinfo->id);
        $eids = array_map(function($o) {
            return $o->critid;
        }, $evidence);
        unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

        $items = [];
        foreach ($badge->criteria as $type => $c) {
            if (in_array($c->id, $eids)) {
                if (count($c->params) == 1) {
                    $items[] = get_string('criteria_descr_single_' . $type , 'badges')
                        . $c->get_details();
                } else {
                    $items[] = get_string('criteria_descr_' . $type , 'badges',
                            core_text::strtoupper($agg[$badge->get_aggregation_method($type)]))
                        . $c->get_details();
                }
            }
        }

        $dl[get_string('evidence', 'badges')] = get_string('completioninfo', 'badges')
            . html_writer::alist($items, [], 'ul');
        $output .= $this->definition_list($dl);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::div('', '', ['id' => 'dialog_confirm']);

        return $output;
    }

    /**
     * Renders a definition list
     *
     * @param array $items the list of items to define
     * @param array $attributes
     * @return string
     */
    protected function definition_list(array $items, array $attributes = []) {
        $output = html_writer::start_tag('dl', $attributes);
        $i=1;
        foreach ($items as $label => $value) {
            $output .= html_writer::tag('dt', $label);
            $output .= html_writer::tag('dd', $value);
            if($i < count($items)){
                $output .= html_writer::div('', 'border-line');
            }
            $i++;
        }
        $output .= html_writer::end_tag('dl');
        return $output;
    }

    /**
     * Fonction qui génère un tag html de type <i> pour inclure une icone font awesome.
     *
     * @param $icon_name
     * @return string
     */
    protected function icon_fontawesome($icon_name){
        return html_writer::tag('i','', ['class' => 'fas fa-'.$icon_name]);
    }
}