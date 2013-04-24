<?php
/**
 * build.php
 * Generates the executable swaml.phar file.
 */

    $outputDir = __DIR__;

    if (!is_dir($outputDir)) {
        mkdir($outputDir);
    }

    $pharFile = $outputDir . '/swaml.phar';
    if (is_file($pharFile)) {
        unlink($pharFile);
    }

    $phar = new Phar($pharFile);
    $phar->buildFromDirectory(
        __DIR__,
        '#((src|vendor)/.*|swaml\.php)$#'
    );
    $phar->setDefaultStub('src/swaml.php');