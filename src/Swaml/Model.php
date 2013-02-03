<?php

namespace Swaml;

class Model extends object
{
    public $name;

    private $includes = array();
    private $properties = array();

    private $spec;

    /**
     * @internal
     */
    public function __construct(Spec $spec, $name)
    {
        $this->spec = $spec;
        $this->name = $name;
    }

    public function addProperty($name)
    {
        $prop = new Property($this->spec, $name);
        $this->properties[$prop->name] = $prop;
        return $prop;
    }

    /**
     * @internal
     * @param  Array  $data [description]
     * @return [type]       [description]
     */
    public function apply(Array $options)
    {
        if (isset($options['include'])) {
            if (!is_array($options['include'])) {
                $options['include'] = explode(' ', $options['include']);
            }
            foreach($options['include'] as $inc) {
                $this->includeModel($inc);
            }
            unset($options['include']);
        }

        if (isset($options['properties'])) {
            foreach($options['properties'] as $name => $propertyOptions) {

                $prop = $this->addProperty($name);

                if ($prop) {

                    // Allow "propertyname: type" in yaml
                    if (is_string($propertyOptions)) {
                        $propertyOptions = array('type' => $propertyOptions);
                    }

                    $prop->apply($propertyOptions);
                }

            }
            unset($options['properties']);
        }

        parent::apply($options);
    }

    public function getModels()
    {
        $result = array(
            $this->name => $this
        );

        foreach($this->getProperties() as $prop) {

            if ($prop->type) {
                $model = $this->spec->getModel($prop->type);

                if ($model) {
                    $result[$model->name] = $model;
                }

            }

        }

        return $result;

    }

    /**
     * Mixes another model into this one.
     * @param  String $model
     */
    public function includeModel($model)
    {
        $this->includes[] = $model;
    }

    /**
     * @return Array Models that are mixed into this model.
     */
    public function getIncludes()
    {
        $result = array();

        foreach($this->includes as $inc)
        {
            $model = $this->spec->getModel($inc);
            if (!$model) {
                throw new \RuntimeException("Included model '$inc' not found.");
            }
            $result[] = $model;
        }

        return $result;
    }

    /**
     * @return  Array All properties (including those included)
     */
    public function getProperties()
    {
        $result = array();

        foreach($this->getIncludes() as $model) {
            foreach($model->getProperties() as $prop) {
                $result[$prop->name] = $prop;
            }
        }

        foreach($this->properties as $prop) {
            $result[$prop->name] = $prop;
        }

        return $result;
    }

    public function toJSON()
    {
        $result = array(
            'id' => $this->name,
            'properties' => array(),
        );

        foreach($this->getProperties() as $prop) {
            $result['properties'][$prop->name] = $prop->toJSON();
        }

        return $result;
    }

    public static function fromArray(Array $data)
    {
        if (empty($data['name'])) {
            throw new \Exception("model name is required.");
        }

        $model = new Model();
        $model->name = $data['name'];


        if (isset($data['properties'])) {

            foreach($data['properties'] as $id => $options) {

                $options['name'] = $id;
                $prop = Property::fromArray($options);

                if ($prop) {
                    $model->properties[$prop->name] = $prop;
                }

            }

        }

        return $model;
    }

}