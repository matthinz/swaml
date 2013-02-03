<?php

namespace Swaml;

class ErrorResponse extends Object
{
    public $code = 400;

    public $reason = '';

    public static function compare(ErrorResponse $a, ErrorResponse $b)
    {
        if ($a->code != $b->code) {
            return $a->code - $b->code;
        }

        return strcasecmp($a->reason, $b->reason);

    }

}