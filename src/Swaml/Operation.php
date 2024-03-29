<?php

namespace Swaml;

class Operation extends Object
{
    public $httpMethod = 'GET';

    public $summary = null;

    public $nickname = null;

    public $notes = null;

    public $responseClass = null;

    private $endpoint;
    private $parameters = array();
    private $errorResponses = array();

    public function __construct(Endpoint $endpoint, $httpMethod)
    {
        $this->endpoint = $endpoint;
        $this->httpMethod = $httpMethod;
    }

    public function addParameter($name, $paramType = null)
    {
        if ($name instanceof Parameter) {

            $param = $name;

        } else {

            if ($paramType === null) {

                if ($this->endpoint->getName() === '{' . $name . '}') {
                    // parameter has same name as latest path component, so assume
                    // it is a path parameter
                    $paramType = 'path';
                }

            }

            if ($paramType === 'path') {
                $param = new PathParameter($this->getSpec(), $name);
            } else {
                $param = new Parameter($this->getSpec(), $name, $paramType);
            }

        }

        return ($this->parameters[$param->name] = $param);
    }

    public function apply(Array $data)
    {
        $spec = $this->getSpec();

        if (isset($data['include'])) {

            $models = $data['include'];
            if (!is_array($models)) $models = array($models);

            foreach($models as $name) {

                $model = $spec->getModel($name);

                if (!$model) {
                    throw new \Exception("Model not found: '$name'");
                }

                $this->includeModelPropertiesAsParameters($model);

            }

            unset($data['include']);

        }

        if (isset($data['parameters'])) {

            foreach($data['parameters'] as $name => $options) {

                // allow 'name: type' in yaml
                if (is_string($options)) {
                    $options = array('type' => $options);
                }

                $param = $this->addParameter($name, isset($options['paramType']) ? $options['paramType'] : null);
                $param->apply($options);
            }

            unset($data['parameters']);
        }

        if (isset($data['errorResponses'])) {

            foreach($data['errorResponses'] as $respData) {

                if (is_string($respData)) {

                    // try and get error response by name
                    $spec = $this->getSpec();
                    $resp = $spec->getErrorResponse($respData);

                    if (!$resp) {
                        throw new \Exception("Error response not found: $respData");
                    }

                } else {
                    $resp = new ErrorResponse($this);
                    $resp->apply($respData);
                }

                $this->errorResponses[] = $resp;

            }

            unset($data['errorResponses']);
        }

        parent::apply($data);

    }

    /**
     * @return Array
     */
    public function getModels()
    {
        $result = array();

        foreach($this->getParameters() as $param) {

            foreach($param->getModels() as $model) {
                $result[$model->name] = $model;
            }

        }

        $rc = $this->getResponseClass();
        if ($rc) {
            $result[$rc->name] = $rc;
            foreach($rc->getModels() as $model) {
                $result[$model->name] = $model;
            }
        }

        return $result;

    }

    public function getParameters($inherit = true)
    {
        if (!$inherit) {
            return $this->parameters;
        }

        $result = array();

        foreach($this->endpoint->getPathParameters() as $param) {
            $result[$param->name] = $param;
        }

        return array_merge($result, $this->parameters);
    }

    /**
     * @return Swaml\Model|null
     */
    public function getResponseClass()
    {
        if (!$this->responseClass) {
            return null;
        }

        $spec = $this->endpoint->getSpec();
        return $spec->getModel($this->responseClass);

    }

    public function getSpec()
    {
        return $this->endpoint->getSpec();
    }

    public function toJSON()
    {
        $json = parent::toJSON();

        $params = $this->getParameters();
        if (count($params) > 0) {

            usort($params, $this->createParameterComparer($params));

            $json['parameters'] = array();

            foreach($params as $param) {
                $json['parameters'][] = $param->toJSON();
            }
        }

        if (count($this->errorResponses) > 0) {

            usort($this->errorResponses, 'Swaml\ErrorResponse::compare');

            $json['errorResponses'] = array();

            foreach($this->errorResponses as $resp) {
                $json['errorResponses'][] = $resp->toJSON();
            }

        }

        return $json;
    }

    public static function compare(Operation $a, Operation $b)
    {
        static $methods = array(
            'GET', 'POST', 'PUT', 'DELETE',
        );

        $aIndex = array_search($a->httpMethod, $methods);
        $bIndex = array_search($b->httpMethod, $methods);

        if ($aIndex === false && $bIndex === false) {
            return strcasecmp($a->httpMethod, $b->httpMethod);
        } else if ($aIndex === false) {
            return 1;
        } else if ($bIndex === false) {
            return -1;
        } else {
            return $aIndex - $bIndex;
        }

    }

    private function createParameterComparer(Array $params)
    {
        static $typeOrder = array('path', 'header', 'query');

        // this is required to keep parameters in natural sort order if they
        // are not moved for other reasons
        $params = array_values($params);

        return function(Parameter $a, Parameter $b) use($params, $typeOrder) {

            if ($a->required && !$b->required) {
                return -1;
            } else if ($b->required && !$a->required) {
                return 1;
            }

            $aType = array_search(strtolower($a->paramType), $typeOrder);
            $bType = array_search(strtolower($b->paramType), $typeOrder);

            if ($aType !== false && $bType !== false) {
                if ($aType != $bType) {
                    return $aType - $bType;
                }
            } else if($aType === false && $bType !== false) {
                return 1;
            } else if ($bType === false && $aType !== false) {
                return -1;
            }

            $aPos = array_search($a, $params, true);
            $bPos = array_search($b, $params, true);

            return $aPos - $bPos;
        };
    }



    private function includeModelPropertiesAsParameters(Model $model) {

        foreach($model->getProperties() as $property) {

            foreach($property->toParameters($this) as $parameter) {
                $this->addParameter($parameter);
            }

        }

    }

    private function inheritPathParameters($endpoint, Array &$params, Array &$seen)
    {
        if (!$endpoint) {
            return;
        }

        foreach($endpoint->getOperations() as $op) {

            if ($op === $this || in_array($op, $seen)) {
                continue;
            }

            $seen[] = $op;

            foreach($op->internalGetParameters($seen) as $param) {

                if ($param instanceof PathParameter) {
                    $params[$param->name] = $param;
                }

            }

        }

    }

    private function internalGetParameters(Array &$seen = array())
    {
        $parameters = array();

        $this->inheritPathParameters($this->endpoint->getParent(), $parameters, $seen);
        $this->inheritPathParameters($this->endpoint, $parameters, $seen);

        return array_merge($parameters, $this->parameters);

    }
}