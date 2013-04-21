<?php

namespace Swaml;

abstract class Object
{
    public function apply(Array $data)
    {
        $class = new \ReflectionClass(get_class($this));
        $props = $class->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach($props as $prop) {

            $name = $prop->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            $setter = 'set' . \Doctrine\Common\Inflector\Inflector::classify($name);

            if (method_exists($this, $setter)) {
                $this->$setter($data[$name]);
            } else {
                $prop->setValue($this, $data[$name]);
            }

            unset($data[$name]);

        }

        if (count($data) > 0) {

            $keys = array_keys($data);
            $keys = implode(', ', $keys);

            throw new \Exception("Unrecognized value(s) in data for $this: $keys");
        }

    }

    public function toJSON()
    {
        $class = new \ReflectionClass(get_class($this));
        $props = $class->getProperties(\ReflectionProperty::IS_PUBLIC);

        $json = array();

        foreach($props as $prop) {

            $value = $prop->getValue($this);

            if ($value === null) {
                continue;
            }

            if ($value instanceof Object) {
                $value = $value->toJSON();
            } else if (is_object($value)) {
                $value = get_object_vars($value);
            }

            $json[$prop->getName()] = $value;

        }

        return $json;

    }

    public function __toString()
    {
        $class = str_replace('Swaml\\', '', get_class($this));
        $name = null;

        $keys = array('name', 'description', 'summary');
        foreach($keys as $key) {
            if (isset($this->$key)) {
                $name = $this->$key;
                break;
            }
        }

        if ($name === null) $name = spl_object_hash($this);

        return "$class '$name'";
    }

}