<?php

namespace Swaml;

class AllowableValuesList extends Object {

    public $values = array();

    public function toJSON()
    {
        return array_merge(
            array('valueType' => 'LIST'),
            parent::toJSON()
        );
    }

}