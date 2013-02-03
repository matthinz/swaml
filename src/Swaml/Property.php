<?php

namespace Swaml;

class Property extends Object
{
    const DEFAULT_TYPE = 'string';

    public $name;

    public $description = '';

    public $type = self::DEFAULT_TYPE;

    public $items = null;

    /**
     * Not part of swagger spec.
     * @var boolean
     */
    public $readonly = null;

    /**
     * Not part of swagger spec. Used when copying properties as parameters.
     * @var boolean|string Either true/false or comma-separated list of
     * HTTP methods in which it is required.
     */
    public $required = null;

    /**
     * Not part of swagger spec
     * @var Integer
     */
    public $maxlength = null;

    public $allowableValues = null;

    private $spec;

    /**
     * @internal
     */
    public function __construct(Spec $spec, $name)
    {
        $this->spec = $spec;
        $this->name = $name;
    }

    public function apply(Array $data)
    {
        if (isset($data['allowableValues'])) {
            $this->allowableValues = AllowableValues::parse($data['allowableValues']);
            unset($data['allowableValues']);
        }

        parent::apply($data);

    }

    public function setType($type)
    {
        list($this->type, $this->items) = Util::parseType($type);
    }

    /**
     * Converts this property to one or more parameter objects
     * @return Array
     */
    public function toParameters(Operation $op)
    {
        $result = array();

        if ($this->readonly) {
            return $result;
        }

        $param = new Parameter($this->spec, $this->name);
        $param->description = $this->description;
        $param->type = $this->type;
        $param->required = $this->isRequiredForOperation($op);
        $param->paramType = null;
        $param->allowableValues = $this->allowableValues;

        $result[] = $param;

        return $result;

    }

    private function isRequiredForOperation(Operation $op)
    {
        if (!$this->required) {
            return false;
        } else if ($this->required === true) {
            return true;
        }

        $methods = is_array($this->required) ? $this->required : explode(',', $this->required);
        $methods = array_filter($methods);
        $methods = array_map('strtoupper', $methods);

        return in_array($op->httpMethod, $methods);

    }
}