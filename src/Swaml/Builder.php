<?php

namespace Swaml;

class Builder
{
    public function getSpec($dir )
    {
        $dir = rtrim($dir, '/') . '/';
        $modelsDir = $dir . 'models/';
        $resourcesDir = $dir . 'resources/';
        $errorsDir = $dir . 'errors/';

        $spec = new Spec();

        $this->loadErrors($spec, $errorsDir);
        $this->loadModels($spec, $modelsDir);
        $this->loadResources($spec, $resourcesDir);


        return $spec;
    }

    protected function loadErrors(Spec $spec, $errorsDir)
    {
        $files = glob($errorsDir . '*');

        foreach($files as $file) {

            $parser = $this->getParser($file);

            if (!$parser) {
                continue;
            }

            $data = file_get_contents($file);
            $data = $parser->parse($data);

            $name = pathinfo($file, PATHINFO_FILENAME);
            $error = $spec->addErrorResponse($name);
            $error->apply($data);

        }

    }

    protected function loadModels(Spec $spec, $modelsDir)
    {
        if (!is_dir($modelsDir)) {
            return;
        }

        $files = glob($modelsDir . '*');


        foreach($files as $file) {

            $parser = $this->getParser($file);

            if (!$parser) {
                continue;
            }

            $data = $parser->parse(file_get_contents($file));

            if (empty($data['name'])) {
                // Default name to the name of the file
                $data['name'] = pathinfo($file, PATHINFO_FILENAME);
            }

            $model = $spec->addModel($data['name']);

            if ($model) {
                $model->apply($data);
                $this->models[$model->name] = $model;
            }
        }

    }

    protected function loadOperations(Spec $spec, Resource $resource, Endpoint $endpoint, $operationsDir)
    {
        if (!is_dir($operationsDir)) {
            return;
        }

        $handle = opendir($operationsDir);

        while(($f = readdir($handle)) !== false) {

            if ($f === '.' || $f === '..') {
                continue;
            }

            $file = $operationsDir . '/' . $f;
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (is_dir($file)) {

                // This is a new endpoint
                $newEndpoint = $spec->addEndpoint($resource, $endpoint->getPath() . '/' . $name);
                $this->loadOperations($spec, $resource, $newEndpoint, $file);

            } elseif ($parser = $this->getParser($file)) {

                $data = file_get_contents($file);
                $data = $parser->parse($data);

                $operation = $endpoint->addOperation($name);
                $operation->apply($data);

            }

        }

        // var_dump($endpoint->getPath());
        // var_dump(count($endpoint->getOperations()));

        closedir($handle);
    }

    protected function loadResources(Spec $spec, $resourcesDir)
    {
        // Resources are the direct subdirectories in $resourcesDir

        if (!is_dir($resourcesDir)) {
            return;
        }

        $handle = opendir($resourcesDir);

        while(($f = readdir($handle)) !== false) {

            if ($f === '.' || $f === '..') {
                continue;
            }

            $dir = $resourcesDir . $f;

            if (is_dir($dir)) {

                $resource = $spec->addResource(pathinfo($dir, PATHINFO_FILENAME));
                $baseEndpoint = $spec->addEndpoint($resource, '/' . $resource->name);

                $this->loadOperations($spec, $resource, $baseEndpoint, $dir);

            }

        }

        closedir($handle);
    }

    protected function getParser($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch($ext) {

            case 'yaml':
            case 'yml':
                return YamlParser::instance();

            case 'json':
                return JsonParser::instance();

            default:
                return null;

        }

    }


}