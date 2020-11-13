<?php

require_once("HTML/QuickForm/element.php");
require_once("HTML_QuickForm_customcheckbox.php");

class HTML_QuickForm_customcheckboxlist extends HTML_QuickForm_element
{
    protected $elements;

    public function __construct($name = null, array $elements = array())
    {
        $this->elements = array();

        foreach ($elements as $element) {
            $this->elements[] = new HTML_QuickForm_customcheckbox(
                $element['name'],
                $element['label'],
                @$element['text'],
                @$element['attributes']
            );
        }

        //HTML_QuickForm_element::HTML_QuickForm_element($name);
        HTML_QuickForm_element::__construct($name);

        $this->_type  = 'customcheckboxlist';
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
        return sprintf(
            '<ul class="input-list checkbox">%s</ul>',
            implode(
                '',
                array_map(
                    function ($el) {
                        return '<li>' . $el->toHtml() . '</li>';
                    },
                    $this->elements
                )
            )
        );
    }


}