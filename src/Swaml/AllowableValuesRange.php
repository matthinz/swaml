<?php

namespace Swaml;

class AllowableValuesRange extends Object {

    public $min;

    public $max;

    public function toJSON()
    {
        return array_merge(
            array('valueType' => 'RANGE'),
            parent::toJSON()
        );
    }

}