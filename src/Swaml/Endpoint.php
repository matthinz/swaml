<?php

namespace Swaml;

/**
 */
class Endpoint
{
    private $resource;
    private $name;
    private $parent;
    private $operations = array();

    /**
     * @internal
     */
    public function __construct(Resource $resource, $name, Endpoint $parent = null)
    {
        $this->resource = $resource;
        $this->name = $name;
        $this->parent = $parent;
    }

    public function addOperation($httpMethod)
    {
        $httpMethod = strtoupper($httpMethod);

        if (!isset($this->operations[$httpMethod])) {
            $this->operations[$httpMethod] = new Operation($this, $httpMethod);
        }

        return $this->operations[$httpMethod];
    }

    public function getName() {
        return $this->name;
    }

    public function getOperations()
    {
        return $this->operations;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getPath()
    {
        if ($this->parent) {
            return $this->parent->getPath() . '/' . $this->name;
        } else {
            return '/' . $this->name;
        }
    }

    /**
     * @return Array
     */
    public function getPathParameters()
    {
        $params = $this->parent ? $this->parent->getPathParameters() : array();

        foreach($this->getOperations() as $op) {

            foreach($op->getParameters(false) as $param) {

                if ($param instanceof PathParameter) {
                    // TODO: warn if path parameter redefined
                    $params[$param->name] = $param;
                }

            }

        }

        return $params;

    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getSpec()
    {
        return $this->resource->getSpec();
    }

    public function toJSON()
    {
        $result = array(
            'path' => $this->getPath(),
            'operations' => array(),
        );

        $ops = $this->getOperations();
        usort($ops, 'Swaml\Operation::compare');

        foreach($ops as $op) {
            $result['operations'][] = $op->toJSON();
        }

        return $result;

    }

}