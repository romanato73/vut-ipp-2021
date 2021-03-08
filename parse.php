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
use src\Analyzer;
use src\App;
use src\Extensions\Statistics;
use src\XMLGenerator;

/**
 * Initialize new application.
 */
$app = new App;

// Register arguments
$app->registerArguments($app->getArguments('parse'));

// Listen for arguments
$app->listen($argv);

/**
 * Analyze code
 */
$analyzer = new Analyzer;

// Lexical check
$tokens = $analyzer->lexicalAnalysis();

// Syntax check
$registry = $analyzer->syntaxAnalysis($tokens);

/**
 * Initialize XML generator
 */
$generator = new XMLGenerator("1.0", "UTF-8", true);

// Generate XML file from registry
$xml = $generator->generateXML($registry);

// Save XML file
$generator->saveXMLToFile('ippcode21', $xml);

/**
 * Bonus
 */
// Save statistics (if they are allowed)
Statistics::generateStatistics();

// Terminate the program
$app->terminate();