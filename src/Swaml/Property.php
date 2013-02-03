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
}