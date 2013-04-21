<?php
$usage = <<<END

    Usage: php swaml.phar [options] input_dir output_dir

    Options:

    --api-base <URL>
        Base URL used when making API requests.
    --doc-base <URL>
        Base URL marking where your documentation is stored.
    --base <URL>
        Equivalent to setting --api-base and --doc-base to the same thing.

END;


    $autoloadPaths = array(
        'vendor/autoload.php',
        '../vendor/autoload.php',
        '../../autoload.php',
    );
    foreach($autoloadPaths as $file) {

        $file = __DIR__ . '/' . $file;

        if (is_file($file)) {
            require_once($file);
            break;
        }
    }

    if (!class_exists('Swaml\Spec')) {
        echo("\n\nswaml.php must live either in the root or in the bin/ directory.\n\n");
        exit(1337);
    }

    array_shift($argv); // remove script name

    $inputDir = null;
    $outputDir = null;
    $apiBasePath = $docBasePath = null;

    if (count($argv) < 2) {
        die("\n$usage\n");
    }

    $outputDir = array_pop($argv);
    $inputDir = array_pop($argv);

    while(($arg = array_shift($argv)) !== null) {

        $normalArg = strtolower($arg);

        switch($normalArg) {

            case '--api-base':
            case '--doc-base':
            case '--base':

                $path = array_shift($argv);

                if ($path === null) {
                    echo("\n\n$arg requires an argument.\n\n");
                    exit(2);
                }

                if ($normalArg === '--api-base') {
                    $apiBasePath = $path;
                } else if ($normalArg === '--doc-base') {
                    $docBasePath = $path;
                } else {
                    $apiBasePath = $docBasePath = $path;
                }

                break;

            default:

                echo("\n\nUnrecognized argument: $arg\n\n");
                exit(2);
        }

    }

    if ($apiBasePath === null && $docBasePath === null) {
        echo("\n\nYou must specify --base OR both --api-base and --doc-base.\n\n");
        exit(2);
    } else if ($apiBasePath !== null && $docBasePath === null) {
        $docBasePath = $apiBasePath;
    } else if ($docBasePath !== null && $apiBasePath === null) {
        $apiBasePath = $docBasePath;
    }

    if (!is_dir($inputDir)) {

        echo "\n\nInput directory does not exist: $inputDir\n\n";
        exit(1);

    }

    $gen = new Swaml\Builder();
    $spec = $gen->getSpec($inputDir);
    $spec->apiBasePath = $apiBasePath;
    $spec->docBasePath = $docBasePath;
    $spec->writeOut($outputDir);