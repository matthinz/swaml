<?php

namespace Swaml;

/**
 * Parser that uses YAML source files.
 */
class YamlParser extends Parser
{
    private $parser = null;

    public function parse($text)
    {
        if ($this->parser === null) {
            $this->parser = new \Symfony\Component\Yaml\Parser();
        }

        return $this->parser->parse($text);
    }

    public static function instance()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new YamlParser();
        }

        return $instance;

    }

}