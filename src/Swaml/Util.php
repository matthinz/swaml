<?php

namespace Swaml;

final class Util
{
    private function __construct() { }

    public static function normalizeType($type)
    {
        $type = trim($type);

        switch(strtolower($type)) {

            case 'bool':
            case 'boolean':
                return 'boolean';

            case 'date':
                return 'Date';

            case 'integer':
            case 'int':
                return 'int';

            case 'double':
            case 'float':
            case 'long':
            case 'string':
                return $type;

            default:
                return $type;

        }

    }

    /**
     * [parseType description]
     * @param  [type] $type [description]
     * @return Array [actualType, itemsValue]
     */
    public static function parseType($type)
    {
        $type = self::normalizeType($type);

        if (preg_match('/^(Array|Set|List)[<\[](.+)[>\]]$/i', $type, $m)) {
            return array($m[1], array('$ref' => $m[2]));
        } else {
            return array($type, null);
        }

    }

}