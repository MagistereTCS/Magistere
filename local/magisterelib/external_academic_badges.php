<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

/*
 * Criteria type constant to class name mapping
 */
global $BADGE_CRITERIA_TYPES;
$BADGE_CRITERIA_TYPES = [
    BADGE_CRITERIA_TYPE_OVERALL   => 'overall',
    BADGE_CRITERIA_TYPE_ACTIVITY  => 'activity',
    BADGE_CRITERIA_TYPE_MANUAL    => 'manual',
    BADGE_CRITERIA_TYPE_SOCIAL    => 'social',
    BADGE_CRITERIA_TYPE_COURSE    => 'course',
    BADGE_CRITERIA_TYPE_COURSESET => 'courseset',
    BADGE_CRITERIA_TYPE_PROFILE   => 'profile',
    BADGE_CRITERIA_TYPE_BADGE     => 'badge',
    BADGE_CRITERIA_TYPE_COHORT    => 'cohort',
];

/**
 * Get badges for a specific user.
 *
 * @param int $userid User ID
 * @param int $courseid Badges earned by a user in a specific course
 * @param int $page The page or records to return
 * @param int $perpage The number of records to return per page
 * @param string $search A simple string to search for
 * @param bool $onlypublic Return only public badges
 * @return array of badges ordered by decreasing date of issue
 */
class external_academic_badges {
    static function badges_get_user_badges($userid, $courseid = 0, $page = 0, $perpage = 0, $search = '', $onlypublic = false) {
        global $CFG, $DB, $USER;

        $user = $DB->get_record('user', ['id' => $userid]);
        if ($courseid == 0) {
            $list_academy = get_magistere_academy_config();
            $badges = [];
            foreach ($list_academy as $academy_name => $data) {
                unset($acaDB);
                if ($academy_name == 'frontal' && $academy_name == 'hub') {
                    continue;
                }

                if (($acaDB = databaseConnection::instance()->get($academy_name)) === false) {
                    continue;
                }

                if ($CFG->academie_name == $academy_name) {
                    $userid = $user->id;
                } else if (($userexternal = $acaDB->get_record('user', ['username' => $user->username]))) {
                    $userid = $userexternal->id;
                } else {
                    continue;
                }

                $params = [
                    'userid' => $userid
                ];

                $sql = 'SELECT
                    bi.uniquehash,
                    bi.dateissued,
                    bi.dateexpire,
                    bi.id as issuedid,
                    bi.visible,
                    u.email,
                    CONCAT("'.$academy_name.'") as aca_name,
                    IF(b.timemodified>0,CONCAT(\'/Date(\',b.timemodified,\'000)/\'),\'\') as dateobtained,
                    b.*
                FROM
                    {badge} b,
                    {badge_issued} bi,
                    {user} u
                WHERE b.id = bi.badgeid
                    AND u.id = bi.userid
                    AND bi.userid = :userid';

                if (!empty($search)) {
                    $sql .= ' AND (' . $acaDB->sql_like('b.name', ':search', false) . ') ';
                    $params['search'] = '%'.$acaDB->sql_like_escape($search).'%';
                }

                if ($onlypublic) {
                    $sql .= ' AND (bi.visible = 1) ';
                }

                if ($CFG->academie_name != $academy_name) {
                    $sql .= ' AND (u.auth = "shibboleth") ';
                }

                if (empty($CFG->badges_allowcoursebadges)) {
                    $sql .= ' AND b.courseid IS NULL';
                } else if ($courseid != 0) {
                    $sql .= ' AND (b.courseid = :courseid) ';
                    $params['courseid'] = $courseid;
                }
                $sql .= ' ORDER BY bi.dateissued DESC';
                $result = $acaDB->get_records_sql($sql, $params, $page * $perpage, $perpage);
                $badges = array_merge($badges, $result);
            }
        } else {
            $params = [
                'userid' => $userid
            ];

            $sql = 'SELECT
                bi.uniquehash,
                bi.dateissued,
                bi.dateexpire,
                bi.id as issuedid,
                bi.visible,
                u.email,
                CONCAT("'.$CFG->academie_name.'") as aca_name,
                IF(b.timemodified>0,CONCAT(\'/Date(\',b.timemodified,\'000)/\'),\'\') as dateobtained,
                b.*
            FROM
                {badge} b,
                {badge_issued} bi,
                {user} u
            WHERE b.id = bi.badgeid
                AND u.id = bi.userid
                AND bi.userid = :userid';

            if (!empty($search)) {
                $sql .= ' AND (' . $DB->sql_like('b.name', ':search', false) . ') ';
                $params['search'] = '%'.$DB->sql_like_escape($search).'%';
            }
            if ($onlypublic) {
                $sql .= ' AND (bi.visible = 1) ';
            }

            if (empty($CFG->badges_allowcoursebadges)) {
                $sql .= ' AND b.courseid IS NULL';
            } else if ($courseid != 0) {
                $sql .= ' AND (b.courseid = :courseid) ';
                $params['courseid'] = $courseid;
            }
            $sql .= ' ORDER BY bi.dateissued DESC';
            return $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
        }

        return $badges;
    }

    /**
     * Fonction qui trie les données en fonction d'un type de colonne spécifique.
     * 
     * @param $userid
     * @param string $orderby The name of the column and the order (short key)
     * @return array
     */
    static function badges_sort_by_specific_column($userid, $orderby = '') {
        $badges = self::badges_get_user_badges($userid);
        $sort = explode(" ", $orderby);
        $order = $sort[1];
        $by = $sort[0];

        if ($order && $by) {
            usort($badges, ['external_academic_badges', "sort_badge_name_".strtolower($order)]);
        }

        return $badges;
    }

    /**
     * Fonction trie les badges par nom dans le sens descendant.
     * 
     * @param $a
     * @param $b
     * @return bool
     */
    static function sort_badge_name_desc($a, $b) {
        $al = strtolower($a->name);
        $bl = strtolower($b->name);
        return $al < $bl;
    }

    /**
     * Fonction trie les badges par nom dans le sens ascendant.
     * 
     * @param $a
     * @param $b
     * @return bool
     */
    static function sort_badge_name_asc($a, $b) {
        $al = strtolower($a->name);
        $bl = strtolower($b->name);
        return $al > $bl;
    }
}


/**
 * Class that represents badge assertion.
 *
 */
class external_academic_badges_assertion {
    /** @var string academic name */
    private $_aca_name;

    /** @var string hash badge */
    private $_hash;

    /** @var object Issued badge information from database */
    private $_data;

    /** @var moodle_url Issued badge url */
    private $_url;

    /**
     * external_academic_badges_assertion constructor. Constructs with issued badge unique hash.
     * 
     * @param $hash Badge unique hash from badge_issued table.
     * @param $aca_name Academic name
     * @throws moodle_exception
     */
    public function __construct($hash, $aca_name) {
        global $CFG;
        $this->_aca_name = $aca_name;
        $this->_hash = $hash;
        $this->_data = $this->get_badge_data();

        if ($this->_data) {
            $this->_url = new moodle_url($CFG->magistere_domaine.'/'.$this->_aca_name.'/badges/badge.php',
                ['hash' => $this->_data->uniquehash]);
        } else {
            $this->_url = new moodle_url($CFG->magistere_domaine.'/'.$this->_aca_name.'/badges/badge.php');
        }
    }

    /**
     * Fonction qui récupère les informations du badge sous la forme d'une requête SQL custom.
     *
     * @return bool
     */
    private function get_badge_data(){
        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            return null;
        }

        return $acaDB->get_record_sql('
            SELECT
                bi.dateissued,
                bi.dateexpire,
                bi.uniquehash,
                u.email,
                b.*,
                bb.email as backpackemail
            FROM
                {badge} b
                JOIN {badge_issued} bi
                    ON b.id = bi.badgeid
                JOIN {user} u
                    ON u.id = bi.userid
                LEFT JOIN {badge_backpack} bb
                    ON bb.userid = bi.userid
            WHERE ' . $acaDB->sql_compare_text('bi.uniquehash', 40) . ' = ' . $acaDB->sql_compare_text(':hash', 40),
            ['hash' => $this->_hash], IGNORE_MISSING);
    }

    /**
     * Factory method for creation of url pointing to plugin file.
     *
     * Please note this method can be used only from the plugins to
     * create urls of own files, it must not be used outside of plugins!
     *
     * @param int $contextid
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param string $pathname
     * @param string $filename
     * @param bool $forcedownload
     * @return moodle_url
     */
    private function make_pluginfile_url($contextid, $component, $area, $itemid, $pathname, $filename,
                                               $forcedownload = false) {
        global $CFG;
        $urlbase = $CFG->magistere_domaine.'/'.$this->_aca_name."/pluginfile.php";
        if ($itemid === null) {
            return moodle_url::make_file_url($urlbase, "/$contextid/$component/$area".$pathname.$filename, $forcedownload);
        } else {
            return moodle_url::make_file_url($urlbase, "/$contextid/$component/$area/$itemid".$pathname.$filename, $forcedownload);
        }
    }

    /**
     * Fonction qui récupère le contexte lié au badge.
     *
     * @return context_course|context_system|null|stdClass
     * @throws dml_exception
     */
    private function get_badge_context() {
        global $CFG;
        if ($CFG->academie_shortname == $this->_aca_name){
            if (empty($this->_data->courseid)) {
                return context_system::instance();
            } else {
                return context_course::instance($this->_data->courseid);
            }
        } else {
            if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
                return null;
            }
            if (empty($this->_data->courseid)) {
                $obj = new stdClass();
                $obj->id = 1;
                return $obj;
            } else {
                return $acaDB->get_record('context', [
                    'contextlevel' => CONTEXT_COURSE,
                    'instanceid' => $this->_data->courseid]);
            }
        }
    }

    /**
     * Get badge assertion.
     *
     * @return array Badge assertion.
     * @throws moodle_exception
     */
    public function get_badge_assertion() {
        global $CFG;
        $assertion = [];
        if ($this->_data) {
            $hash = $this->_data->uniquehash;
            $email = empty($this->_data->backpackemail) ? $this->_data->email : $this->_data->backpackemail;
            $assertionurl = new moodle_url('/badges/assertion.php', ['b' => $hash]);
            $classurl = new moodle_url('/badges/assertion.php', ['b' => $hash, 'action' => 1]);

            // Required.
            $assertion['uid'] = $hash;
            $assertion['recipient'] = [];
            $assertion['recipient']['identity'] = 'sha256$' . hash('sha256', $email . $CFG->badges_badgesalt);
            $assertion['recipient']['type'] = 'email'; // Currently the only supported type.
            $assertion['recipient']['hashed'] = true; // We are always hashing recipient.
            $assertion['recipient']['salt'] = $CFG->badges_badgesalt;
            $assertion['badge'] = $classurl->out(false);
            $assertion['verify'] = [];
            $assertion['verify']['type'] = 'hosted'; // 'Signed' is not implemented yet.
            $assertion['verify']['url'] = $assertionurl->out(false);
            $assertion['issuedOn'] = $this->_data->dateissued;
            // Optional.
            $assertion['evidence'] = $this->_url->out(false); // Currently issued badge URL.
            if (!empty($this->_data->dateexpire)) {
                $assertion['expires'] = $this->_data->dateexpire;
            }
        }
        return $assertion;
    }

    /**
     * Get badge class information.
     *
     * @return array Badge Class information.
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_badge_class() {
        $class = [];
        if ($this->_data) {
            $context = $this->get_badge_context();
            $issuerurl = new moodle_url('/badges/assertion.php', ['b' => $this->_data->uniquehash, 'action' => 0]);

            // Required.
            $class['name'] = $this->_data->name;
            $class['description'] = $this->_data->description;
            $class['image'] = $this->make_pluginfile_url($context->id, 'badges', 'badgeimage',
                $this->_data->id, '/', 'f1')->out(false);
            $class['criteria'] = $this->_url->out(false); // Currently issued badge URL.
            $class['issuer'] = $issuerurl->out(false);
        }
        return $class;
    }

    /**
     * Get badge issuer information.
     *
     * @return array Issuer information.
     */
    public function get_issuer() {
        $issuer = [];
        if ($this->_data) {
            // Required.
            $issuer['name'] = $this->_data->issuername;
            $issuer['url'] = $this->_data->issuerurl;
            // Optional.
            if (!empty($this->_data->issuercontact)) {
                $issuer['email'] = $this->_data->issuercontact;
            }
        }
        return $issuer;
    }
}

/**
 * An issued badges for badge.php page
 */
class external_academic_issued_badge implements renderable {
    /** @var string academic name */
    private $_aca_name;

    /** @var issued badge */
    public $issued;

    /** @var badge recipient */
    public $recipient;

    /** @var badge class */
    public $badgeclass;

    /** @var badge visibility to others */
    public $visible = 0;

    /** @var badge class */
    public $badgeid = 0;

    /**
     * external_academic_issued_badge constructor. Initializes the badge to display
     *
     * @param $hash Issued badge hash
     * @param $aca_name Academic name
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct($hash, $aca_name) {
        global $USER;

        $this->_aca_name = $aca_name;

        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            return null;
        }

        $assertion = new external_academic_badges_assertion($hash, $aca_name);
        $this->issued = $assertion->get_badge_assertion();
        $this->badgeclass = $assertion->get_badge_class();

        $rec = $acaDB->get_record_sql('SELECT userid, visible, badgeid
                FROM {badge_issued}
                WHERE ' . $acaDB->sql_compare_text('uniquehash', 40) . ' = ' . $acaDB->sql_compare_text(':hash', 40),
            ['hash' => $hash], IGNORE_MISSING);
        if ($rec) {
            // Get a recipient from database.
            $namefields = get_all_user_name_fields(true, 'u');
            $user = $acaDB->get_record_sql("SELECT u.id, $namefields, u.deleted, u.email, u.username
                        FROM {user} u WHERE u.username = :username", ['username' => $USER->username]);
            $this->recipient = $user;
            $this->visible = $rec->visible;
            $this->badgeid = $rec->badgeid;
        }
    }
}

/**
 * Class that represents external academic badge.
 */
class external_academic_badge {
    /** @var string academic name */
    private $_aca_name;

    /** @var object user */
    private $_user;

    /** @var int Badge id */
    public $id;

    /** Values from the table 'badge' */
    public $name;
    public $description;
    public $timecreated;
    public $timemodified;
    public $usercreated;
    public $usermodified;
    public $issuername;
    public $issuerurl;
    public $issuercontact;
    public $expiredate;
    public $expireperiod;
    public $type;
    public $courseid;
    public $message;
    public $messagesubject;
    public $attachment;
    public $notification;
    public $status = 0;
    public $nextcron;

    /**
     * external_academic_badge constructor. Constructs with badge details.
     *
     * @param $badgeid badge ID.
     * @param $aca_name Academic name.
     * @throws moodle_exception
     */
    public function __construct($badgeid, $aca_name) {
        global $USER, $CFG;
        $this->_aca_name = $aca_name;

        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            return null;
        }

        $this->id = $badgeid;
        $data = $acaDB->get_record('badge', ['id' => $badgeid]);

        if (empty($data)) {
            print_error('error:nosuchbadge', 'badges', $badgeid);
        }

        foreach ((array)$data as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }

        if ($CFG->academie_name != $this->_aca_name) {
            $this->_user = $acaDB->get_record('user', ['username' => $USER->username]);
        } else {
            $this->_user = $USER;
        }

    }

    /**
     * Return array of aggregation methods
     *
     * @return array
     * @throws coding_exception
     */
    public static function get_aggregation_methods() {
        return [
            BADGE_CRITERIA_AGGREGATION_ALL => get_string('all', 'badges'),
            BADGE_CRITERIA_AGGREGATION_ANY => get_string('any', 'badges'),
        ];
    }

    /**
     * Gets an array of completed criteria from 'badge_criteria_met' table.
     *
     * @param int $userid Completions for a user
     * @return array Records of criteria completions
     */
    public function get_criteria_completions($userid) {
        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            return [];
        }

        $sql = "SELECT bcm.id, bcm.critid
                FROM {badge_criteria_met} bcm
                    INNER JOIN {badge_criteria} bc ON bcm.critid = bc.id
                WHERE bc.badgeid = :badgeid AND bcm.userid = :userid ";
        $completions = $acaDB->get_records_sql($sql, ['badgeid' => $this->id, 'userid' => $userid]);

        return $completions;
    }

    /**
     * Get aggregation method for badge criteria
     *
     * @param int $criteriatype If none supplied, get overall aggregation method (optional)
     * @return int One of BADGE_CRITERIA_AGGREGATION_ALL or BADGE_CRITERIA_AGGREGATION_ANY
     */
    public function get_aggregation_method($criteriatype = 0) {
        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            return null;
        }

        $params = ['badgeid' => $this->id, 'criteriatype' => $criteriatype];
        $aggregation = $acaDB->get_field('badge_criteria', 'method', $params, IGNORE_MULTIPLE);

        if (!$aggregation) {
            return BADGE_CRITERIA_AGGREGATION_ALL;
        }

        return $aggregation;
    }

    /**
     * Use to get context instance of a badge.
     *
     * @return context_course|context_system|null|stdClass
     * @throws dml_exception
     */
    public function get_context() {
        global $CFG;
        if ($CFG->academie_shortname == $this->_aca_name) {
            if ($this->type == BADGE_TYPE_SITE) {
                return context_system::instance();
            } else if ($this->type == BADGE_TYPE_COURSE) {
                return context_course::instance($this->courseid);
            } else {
                debugging('Something is wrong...');
            }
        } else {
            if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
                return null;
            }
            if ($this->type == BADGE_TYPE_SITE) {
                $obj = new stdClass();
                $obj->id = 1;
                return $obj;
            } else if ($this->type == BADGE_TYPE_COURSE) {
                return $acaDB->get_record('context', ['contextlevel' => CONTEXT_COURSE,
                    'instanceid' => $this->courseid]);
            } else {
                debugging('Something is wrong...');
            }
        }
    }

    /**
     * Fonction qui permet de télécharger l'image du badge distant.
     * 
     * @param $hash
     * @throws coding_exception
     */
    public function download_badge_img_file($hash) {
        global $CFG;

        $name = str_replace(' ', '_', $this->name) . '.png';
        $name = clean_param($name, PARAM_FILE);

        if (($acaDB = databaseConnection::instance()->get($this->_aca_name)) === false) {
            error_log('local_myindex/image.php/'.$this->_aca_name.'/Database_connection_failed');
            send_file_not_found();
        }

        $user_context = $acaDB->get_record('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $this->_user->id));

        $img = databaseConnection::instance()->get($this->_aca_name)->get_record('files', [
            'contextid' => $user_context->id,
            'component' => 'badges',
            'filearea' => 'userbadge',
            'itemid' => $this->id,
            'filepath' => '/',
            'filename' => $hash . '.png'
        ]);

        $acas = get_magistere_academy_config();
        if (!array_key_exists($this->_aca_name, $acas)) {
            send_file_not_found();
        }

        $dataroot = substr($CFG->dataroot,0,strrpos($CFG->dataroot, '/'));

        $img_path = $dataroot.'/'.$acas[$this->_aca_name]['shortname'].'/filedir/'
            .substr($img->contenthash,0,2).'/'
            .substr($img->contenthash,2,2).'/'
            .$img->contenthash;

        if (file_exists($img_path)) {
            send_file($img_path, $name, null, 0, false, true);
        } else {
            send_file_not_found();
        }
    }
}

