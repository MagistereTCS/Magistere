<?php

function object_column($data, $idKey, $valuesKey)
{
    $output = array();

    foreach ($data as $value) {
        if (is_array($valuesKey) && count($valuesKey) > 1) {
            $output[$value->$idKey] = array();
            foreach ($valuesKey as $key) {
                $output[$value->$idKey][$key] = $value->$key;
            }
        } else {
            $keys = (array)$valuesKey;
            $output[$value->$idKey] = $value->{$keys[0]};
        }
    }

    return $output;
}

function html_tag($tagName, $innerContent = null, array $attributes = array())
{
    $inlineAttributes = array();

    array_walk(
        $attributes,
        function ($v, $k) use (&$inlineAttributes) {
            if (null !== $v) {
                $inlineAttributes[] = sprintf(' %s="%s"', $k, $v);
            }
        }
    );

    if (null !== $innerContent) {

        return sprintf(
            '<%1$s%2$s>%3$s</%1$s>',
            $tagName,
            implode('', $inlineAttributes),
            $innerContent
        );
    }

    return sprintf(
        '<%s%s/>',
        $tagName,
        implode('', $inlineAttributes)
    );
}

function html_select(array $data, $current = null, array $attributes = array(), $optionAttrCallback = null)
{
    $options = array();

    foreach ($data as $key => $value) {
        $optionAttributes = array(
            'value'    => $key,
            'selected' => null !== $current && $current == $key ? 'selected' : null,
            '__text__' => $value
        );

        if (is_callable($optionAttrCallback)) {
            $optionAttributes = $optionAttrCallback($key, $value, $optionAttributes);
        }

        $text = $optionAttributes['__text__'];
        unset($optionAttributes['__text__']);

        $options[] = html_tag(
            'option',
            $text,
            $optionAttributes
        );
    }

    return html_tag(
        'select',
        implode('', $options),
        $attributes
    );
}

function html_checkbox_list(array $data, $currents = array(), array $attributes = array())
{
    return _html_input_list('checkbox', $data, $currents, $attributes);
}


function html_radio_list(array $data, $current = null, array $attributes = array())
{
    return _html_input_list('radio', $data, null !== $current ? array($current) : array(), $attributes);
}

function _html_input_list($type, array $data, $currents = array(), array $attributes = array())
{
    $items = array();

    foreach ($data as $key => $value) {
        $items[] = html_tag(
            'li',
            html_tag(
                'label',
                html_tag(
                    'input',
                    null,
                    array(
                        'type'    => $type,
                    	'checked' => isset($currents) && is_array($currents) && in_array($key, $currents) ? 'checked' : null,
                        'value'   => $key,
                        'id'      => !empty($attributes['id']) ? $attributes['id'] . '_' . $key : null,
                        'name'    => !empty($attributes['name']) ? $attributes['name'] : null
                    )
                ) . $value,
                array(
                    'for' => !empty($attributes['id']) ? $attributes['id'] . '_' . $key : null
                )
            )
        );
    }

    return html_tag(
        'ul',
        implode('', $items),
        array(
            'class' => $type . ' input-list'
        )
    );
}