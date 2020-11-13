<?php


class editor_tinymce_json_setting_textarea_fontawesome  extends admin_setting_configtext {
    private $rows;
    private $cols;

    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param mixed $defaultsetting string or array
     * @param mixed $paramtype
     * @param string $cols The number of columns to make the editor
     * @param string $rows The number of rows to make the editor
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $cols='60', $rows='8') {
        $this->rows = $rows;
        $this->cols = $cols;
        parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype);
    }

    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query='') {
        global $OUTPUT, $CFG, $PAGE;

        $default = $this->get_defaultsetting();
        $defaultinfo = $default;
        if (!is_null($default) and $default !== '') {
            $defaultinfo = "\n".$default;
        }

        $paths = [
            "fab" => $CFG->dirroot.'/filter/fontawesome/fonts/fa-brands-400.svg',
            "fas" => $CFG->dirroot.'/filter/fontawesome/fonts/fa-solid-900.svg',
            "far" => $CFG->dirroot.'/filter/fontawesome/fonts/fa-regular-400.svg'
        ];



        $error = null;
        foreach ($paths as $type => $path) {
            if (file_exists($path)) {
                $cssfontawesome = file_get_contents($path);

                $firstpartpreg = null;
                preg_match_all('|glyph-name="(.*)".*unicode="(.*)"|sU', trim($cssfontawesome), $firstpartpreg, PREG_SET_ORDER);

                foreach ($firstpartpreg as $item) {
                    if(isset( $item[1]) && $item[2] ){
                        $classesFA[] = array(
                            "type" => $type,
                            "name" => $item[1],
                            "unicode" => htmlentities($item[2])
                        );
                    }
                }
            } else {
                $error = get_string("error1","atto_fontawesomepicker");
            }
        }


        $name = array_column($classesFA, 'name');
        array_multisort($name, SORT_ASC, $classesFA);

        $context = (object) [
            'cols' => $this->cols,
            'rows' => $this->rows,
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $data,
            'forceltr' => $this->get_force_ltr(),
            'error' => $error,
            'icons' => $classesFA
        ];
        $element = $OUTPUT->render_from_template('atto_fontawesomepicker/setting_configfontawesome', $context);

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);
    }
}
