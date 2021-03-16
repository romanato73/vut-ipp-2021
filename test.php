<?php

define('PROGRAM_START', microtime(true));

/**
 * Register the autoloader.
 */
require_once __DIR__ . '/src/autoload.php';

/**
 * Import Classes
 */

use src\TestFrame\App;
use src\TestFrame\Core;
use src\TestFrame\HTMLGenerator;

/**
 * Initialize new application
 */
$app = new App();

// Register arguments
$app->registerArguments([
    "--errors",
    "--directory=path",
    "--recursive",
    "--parse-script=file",
    "--int-script=file",
    "--parse-only",
    "--int-only",
    "--jexamxml=file",
    "--jexamcfg=file",
]);

// Listen for arguments
$app->listen($argv);

$html = new HTMLGenerator('src/TestFrame/web');

/**
 * Initialize new Tester instance
 */
$tester = new Core($html);

// Perform tests
$type = $tester->performTests(App::$arguments);

$html->generate($type);

// Terminate the program
$app->terminate();