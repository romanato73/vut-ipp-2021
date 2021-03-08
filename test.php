<?php

define('PROGRAM_START', microtime(true));

/**
 * Register the autoloader.
 */
require_once __DIR__ . '/src/autoload.php';

/**
 * Import Classes
 */
use src\TestApp;
use src\Tester;

/**
 * Initialize new application
 */
$app = new TestApp();

// Register arguments
$app->registerArguments($app->getArguments('test'));

// Listen for arguments
$app->listen($argv);

/**
 * Initialize new Tester instance
 */
$tester = new Tester();

print_r(TestApp::$arguments);

// Perform tests
$tester->performTests(TestApp::$arguments);

// Terminate the program
$app->terminate();