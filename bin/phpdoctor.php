<?php

/** @noinspection InvertedIfElseConstructsInspection */
/** @noinspection TransitiveDependenciesUsageInspection */

declare(strict_types=1);

namespace voku\PHPDoctor;

use Symfony\Component\Console\Application;

(static function () {
    error_reporting(E_ALL);
    ini_set('display_errors', 'stderr');
    gc_disable(); // performance boost

    \define('__PHPDOCTOR_RUNNING__', true);

    /** @noinspection UsingInclusionOnceReturnValueInspection */
    /** @noinspection UsingInclusionReturnValueInspection */
    $devOrPharLoader = require_once __DIR__ . '/../vendor/autoload.php';
    $devOrPharLoader->unregister();

    $requireProjectAutoloaderIfNeeded = static function (string $autoloadFile): void {
        $autoloadRealPath = \dirname($autoloadFile) . '/composer/autoload_real.php';
        if (\is_file($autoloadRealPath)) {
            $autoloadRealContents = \file_get_contents($autoloadRealPath);
            if (\is_string($autoloadRealContents)) {
                \preg_match('/class\s+(ComposerAutoloaderInit[a-fA-F0-9]+)\b/', $autoloadRealContents, $matches);
                if (isset($matches[1]) && \class_exists($matches[1], false)) {
                    return;
                }
            }
        }

        /** @noinspection PhpIncludeInspection */
        require_once $autoloadFile;
    };

    $autoloaderInWorkingDirectory = getcwd() . '/vendor/autoload.php';
    if (is_file($autoloaderInWorkingDirectory)) {
        $requireProjectAutoloaderIfNeeded($autoloaderInWorkingDirectory);
    }

    $autoloadProjectAutoloaderFile = static function (string $file) use ($requireProjectAutoloaderIfNeeded): void {
        $path = \dirname(__DIR__) . $file;
        if (!\extension_loaded('phar')) {
            if (is_file($path)) {
                $requireProjectAutoloaderIfNeeded($path);
            }
        } else {
            $pharPath = \Phar::running(false);
            if ($pharPath === '') {
                if (\is_file($path)) {
                    $requireProjectAutoloaderIfNeeded($path);
                }
            } else {
                $path = \dirname($pharPath) . $file;
                if (\is_file($path)) {
                    $requireProjectAutoloaderIfNeeded($path);
                }
            }
        }
    };

    $autoloadProjectAutoloaderFile('/../../autoload.php');

    $devOrPharLoader->register(true);

    $app = new Application('PHPDoctor');

    /** @noinspection UnusedFunctionResultInspection */
    $app->add(new \voku\PHPDoctor\CliCommand\PhpDoctorCommand());

    /** @noinspection PhpUnhandledExceptionInspection */
    $app->run();
})();
