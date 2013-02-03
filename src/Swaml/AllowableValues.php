<?php

namespace Swaml;

abstract class AllowableValues extends Object
{

    /**
     * @internal
     */
    public static function parse($data)
    {
        if (!is_array($data)) {
            throw new \Exception("Invalid allowableValues value: $data");
        }

        if (!isset($data['valueType'])) {

            if (isset($data['min']) || isset($data['max'])) {
                $data['valueType'] = 'RANGE';
            } else {
                $data = array(
                    'values' => $data,
                    'valueType' => 'LIST',
                );
            }

        }

        switch(strtoupper($data['valueType'])) {

            case 'LIST':
                $av = new AllowableValuesList();
                break;

            case 'RANGE':
                $av = new AllowableValuesRange();
                break;

            default:
                throw new \Exception("Invalid valueType: '{$data['valueType']}'");

        }

        unset($data['valueType']);
        $av->apply($data);

        return $av;

    }

}