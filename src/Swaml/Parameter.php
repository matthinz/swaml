<?php

namespace Swaml;

class Parameter extends Object
{
    public $name = '';

    public $type = 'string';

    public $paramType = 'body';

    public $description = '';

    public $required = false;

    public $allowMultiple = false;

    public $allowableValues = null;

    protected $spec;

    /**
     * @internal
     */
    public function __construct(Spec $spec, $name, $paramType = 'body')
    {
        $this->name = $name;
        $this->paramType = $paramType;
        $this->spec = $spec;
    }

    public function apply(Array $data)
    {
        if (isset($data['allowableValues'])) {
            $this->allowableValues = AllowableValues::parse($data['allowableValues']);
            unset($data['allowableValues']);
        }

        // normalize 'dataType' to 'type'
        if (isset($data['dataType'])) {
            $data['type'] = $data['dataType'];
            unset($data['dataType']);
        }

        return parent::apply($data);
    }

    public function getModels()
    {
        $result = array();

        $model = $this->spec->getModel($this->type);
        if ($model) {
            $result[$model->name] = $model;
        }

        return $result;

    }

    public function setType($type)
    {
        list($type, $items) = Util::parseType($type);

        if ($items) {
            $itemType = $items['$ref'];
            $this->type = "{$type}[{$itemType}]";
        } else {
            $this->type = $type;
        }

    }

    public function toJSON()
    {
        $json = parent::toJSON();
        $json['dataType'] = $json['type'];
        unset($json['type']);

        if (empty($json['name'])) {
            unset($json['name']);
        }

        return $json;

    }

}