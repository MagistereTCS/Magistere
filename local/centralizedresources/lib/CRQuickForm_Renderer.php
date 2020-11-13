<?php

class CRQuickForm_Renderer extends MoodleQuickForm_Renderer
{
    public function __construct()
    {
        parent::__construct();

        $this->_elementTemplates['default'] =
            '<div id="{id}" class="field {advanced}<!-- BEGIN required --> required<!-- END required --> {type}" {aria-live}>'.
                '<!-- BEGIN label --><label>{label}<!-- BEGIN required -->{req}<!-- END required -->{advancedimg}&nbsp;{help} </label><!-- END label -->'.
                '<div class="felement {type}<!-- BEGIN error --> error<!-- END error -->">'.
                    '<!-- BEGIN error --><span class="error">{error}</span><br /><!-- END error -->'.
                    '{element}'.
                '</div>'.
            '</div>';

    }

    function renderElement(&$element, $required, $error){
        // Make sure the element has an id.
        $element->_generateId();

        //adding stuff to place holders in template
        //check if this is a group element first
        if (($this->_inGroup) and !empty($this->_groupElementTemplate)) {
            // so it gets substitutions for *each* element
            $html = $this->_groupElementTemplate;
        }
        elseif (method_exists($element, 'getElementTemplateType')){
            $html = $this->_elementTemplates[$element->getElementTemplateType()];
        }else{
            $html = $this->_elementTemplates['default'];
        }
        //if ($this->_showAdvanced){
            $advclass = ' advanced';
        //} else {
        //    $advclass = ' advanced hide';
        //}
        if (isset($this->_advancedElements[$element->getName()])){
            $html =str_replace(' {advanced}', $advclass, $html);
        } else {
            $html =str_replace(' {advanced}', '', $html);
        }
        if (isset($this->_advancedElements[$element->getName()])||$element->getName() == 'mform_showadvanced'){
            $html =str_replace('{advancedimg}', $this->_advancedHTML, $html);
        } else {
            $html =str_replace('{advancedimg}', '', $html);
        }
        
        $html = str_replace(' {aria-live}', '', $html);

        if (in_array($element->getType(), array('customcheckbox', 'customcheckboxlist'))) {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN label -->(\s|\S)*<!-- END label -->([ \t\n\r]*)?/i", '', $html);
        } else {
            $html = str_replace('<!-- BEGIN label -->', '', $html);
            $html = str_replace('<!-- END label -->', '', $html);
        }

        $html =str_replace('{id}', 'fitem_' . $element->getAttribute('id'), $html);
        $html =str_replace('{type}', $element->getType(), $html);
        $html =str_replace('{name}', $element->getName(), $html);
        if (method_exists($element, 'getHelpButton')){
            $html = str_replace('{help}', $element->getHelpButton(), $html);
        }else{
            $html = str_replace('{help}', '', $html);

        }
        if (($this->_inGroup) and !empty($this->_groupElementTemplate)) {
            $this->_groupElementTemplate = $html;
        }
        elseif (!isset($this->_templates[$element->getName()])) {
            $this->_templates[$element->getName()] = $html;
        }

        parent::renderElement($element, $required, $error);
    }

}