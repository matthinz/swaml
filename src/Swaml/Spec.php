<?php

namespace Swaml;

/**
 * Root object of a swagger specification.
 */
class Spec
{
    /**
     * Base URL used when making API requests.
     * @var string
     */
    public $apiBasePath = '';

    /**
     * Base URL used when requesting JSON api documents.
     * @var string
     */
    public $docBasePath = '';

    public $apiVersion = '1.0';

    public $swaggerVersion = '1.1';

    private $models = array();
    private $resources = array();
    private $endpoints = array();
    private $errorResponses = array();

    /**
     * @param Resource $resource [description]
     * @param String   $path     complete path.
     */
    public function addEndpoint(Resource $resource, $path)
    {
        $path = trim($path, '/');
        $parent = null;

        if (isset($this->endpoints[$path])) {
            return $this->endpoints[$path];
        }

        // To allow for inheritance across paths, add paths as children of
        // their parents

        $pos = strrpos($path, '/');
        if ($pos !== false) {

            $parentPath = substr($path, 0, $pos);
            $name = substr($path, $pos + 1);

            $parent = $this->addEndpoint($resource, $parentPath);

            if ($parent->getResource() !== $resource) {
                throw new \Exception("Parent of path '$path' belongs to a different resource.");
            }
        } else {
            $name = $path;
        }

        $endpoint = new Endpoint($resource, $name, $parent);

        return ($this->endpoints[$path] = $endpoint);
    }

    public function addErrorResponse($name)
    {
        if (isset($this->errors[$name])) {
            return $this->errors[$name];
        }

        $resp = new ErrorResponse($name);

        return ($this->errorResponses[$name] = $resp);
    }

    /**
     * @param [type] $path [description]
     */
    public function addResource($name)
    {
        $name = trim($name, '/');

        if (isset($this->resources[$name])) {
            return $this->resources[$name];
        }

        $resource = new Resource($this, $name);
        return ($this->resources[$name] = $resource);
    }

    /**
     * @param String $name
     * @return Swaml\Model
     */
    public function addModel($name)
    {
        if (isset($this->models[$name])) {
            throw new \RuntimeException("Model named '$name' already defined.");
        }

        $model = new Model($this, $name);
        $this->models[$name] = $model;

        return $model;
    }

    public function getEndpointsForResource(Resource $resource) {

        $result = array();
        $pattern = '/^' . preg_quote($resource->name, '/') . '(\/|$)/';

        foreach($this->endpoints as $path => $endpoint) {

            if (preg_match($pattern, $path)) {
                $result[] = $endpoint;
            }

        }

        return $result;

    }

    public function getErrorResponse($name)
    {
        return isset($this->errorResponses[$name]) ? $this->errorResponses[$name] : null;
    }

    public function getModel($name)
    {
        return isset($this->models[$name]) ? $this->models[$name] : null;
    }

    public function getModels()
    {
        return $this->models;
    }

    public function toJSON()
    {
        $result = array(
            'api-docs' => array(
                'apiVersion' => $this->apiVersion,
                'swaggerVersion' => $this->swaggerVersion,
                'basePath' => $this->docBasePath,
                'apis' => array(),
                'models' => array(),
            )
        );

        foreach($this->resources as $resource) {

            $result['api-docs']['apis'][] = array(
                'path' => '/' . $resource->name . '.{format}',
                'description' => $resource->description,
            );
            $result[$resource->name] = $resource->toJSON();

        }

        return $result;
    }

    public function writeOut($outputDir)
    {
        $outputDir = rtrim($outputDir, '/') . '/';
        $jsonPretty = new \Camspiers\JsonPretty\JsonPretty();

        foreach($this->toJSON() as $file => $json) {

            $file = $outputDir . $file . '.json';
            $dir = dirname($file);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $json = $jsonPretty->prettify($json);
            file_put_contents($file, $json);

        }

    }

}