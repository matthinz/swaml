<?php

namespace Swaml;

class Resource
{
    public $name = '';

    public $description = "";

    private $spec;

    /**
     * @internal
     * @param Spec   $spec [description]
     * @param [type] $name [description]
     */
    public function __construct(Spec $spec, $name)
    {
        $this->spec = $spec;
        $this->name = $name;
    }

    public function getEndpoints()
    {
        return $this->spec->getEndpointsForResource($this);
    }

    /**
     * @return Array All models used by endpoints in this response.
     */
    public function getModels()
    {

        $result = array();

        foreach($this->getEndpoints() as $endpoint) {

            foreach($endpoint->getOperations() as $op) {

                foreach($op->getModels() as $model) {
                    $result[$model->name] = $model;
                }

            }

        }

        return $result;

    }

    public function getSpec()
    {
        return $this->spec;
    }

    public function toJSON()
    {
        $result = array(
            'apiVersion' => $this->spec->apiVersion,
            'swaggerVersion' => $this->spec->swaggerVersion,
            'basePath' => $this->spec->apiBasePath,
            'resourcePath' => '/' . $this->name,
            'description' => $this->description,
            'apis' => array(),
            'models' => array(),
        );

        foreach($this->spec->getEndpointsForResource($this) as $endpoint) {
            $result['apis'][] = $endpoint->toJSON();

        }

        foreach($this->getModels() as $model) {
            $result['models'][$model->name] = $model->toJSON();
        }

        return $result;
    }

}