<?php
/**
 * A button that is used in my page
 *
 * @copyright 2019 TCS
 */

class local_myindex_button extends single_button {

    /**
     * Constructor
     * @param moodle_url $url
     * @param string $label button text
     * @param string $method get or post submit method
     * @param string $class CSS class of the button
     */
    public function __construct(moodle_url $url, $label, $method='post', $class = 'singlebutton') {
        parent::__construct($url, $label, $method);
        $this->class = $class;
    }

}