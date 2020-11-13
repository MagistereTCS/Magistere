<?php

require_once("HTML/QuickForm/checkbox.php");

class HTML_QuickForm_customcheckbox extends HTML_QuickForm_checkbox
{

    public function __construct($elementName=null, $elementLabel=null, $text='', $attributes=null)
    {
        //HTML_QuickForm_checkbox::HTML_QuickForm_checkbox($elementName, $elementLabel, $text, $attributes);
        HTML_QuickForm_checkbox::__construct($elementName, $elementLabel, $text, $attributes);
        $this->_type = 'customcheckbox';
    }

    /**
     * Returns the checkbox element in HTML
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        $this->_generateId(); // Seems to be necessary when this is used in a group.

        return '<label for="' . $this->getAttribute('id') . '">' . HTML_QuickForm_input::toHtml() . $this->getLabel() . '</label>';
    }


}