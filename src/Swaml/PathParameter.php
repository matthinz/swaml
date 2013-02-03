<?php

namespace Swaml;

/**
 *
 */
class PathParameter extends Parameter
{

    public $required = true;

    public function toJSON()
    {
        $result = parent::toJSON();
        $result['paramType'] = 'path';
        $result['required'] = true;

        return $result;
    }

}