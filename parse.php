<?php

ini_set('display_errors', 'stderr');
define('PROGRAM_START', microtime(true));

/**
 * Register the autoloader.
 */
require_once __DIR__ . '/src/autoload.php';

/**
 * Import Classes
 */

use src\Analyzer\App;
use src\Analyzer\Core;
use src\Analyzer\XMLGenerator;
use src\Extensions\Statistics;

/**
 * Initialize new application.
 */
$app = new App;

// Register arguments
$app->registerArguments([
    '--errors',
]);

// Listen for arguments
$app->listen($argv);

/**
 * Analyze code
 */
$analyzer = new Core;

// Lexical check
$tokens = $analyzer->lexicalAnalysis();

// Syntax check
$registry = $analyzer->syntaxAnalysis($tokens);

/**
 * Initialize XML generator
 */
$generator = new XMLGenerator("1.0", "UTF-8", true);

// Generate XML from registry
$generator->generateXML($registry);

/**
 * STATP Extension
 */
// Save statistics
Statistics::generateStatistics();

// Terminate the program
$app->terminate();