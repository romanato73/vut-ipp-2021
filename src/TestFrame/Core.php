<?php


namespace src\TestFrame;


use DirectoryIterator;
use src\Support\Exception;
use src\TestFrame\Traits\PathChecker;

class Core
{
    use PathChecker;

    /**
     * @var string Set the php CLI
     * @todo: Default php7.4
     */
    private string $phpCLI = "php.7.4";

    /**
     * @var string Set the python CLI
     * @todo: Default python3.8
     */
    private string $pyCLI = "python3.8";

    /**
     * @var array Array of paths.
     */
    private array $paths = [
        'directory' => '.',
        'parse-script' => 'parse.php',
        'int-script' => 'interpret.py',
        'jexamxml' => '/pub/courses/ipp/jexamxml/jexamxml.jar',
        'jexamcfg' => '/pub/courses/ipp/jexamxml/options',
    ];

    /**
     * @var HTMLGenerator Generator instance
     */
    private HTMLGenerator $generator;

    /**
     * @var int Passed tests
     */
    private int $passed = 0;

    /**
     * @var int Failed tests
     */
    private int $failed = 0;

    /**
     * @var array Outputs from parse (for both testing)
     */
    private array $parseOutputs = [];

    /**
     * @var array Files with tests.
     */
    private array $tests = [];

    public function __construct(HTMLGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Initialize paths, directory and run tests.
     *
     * @param array $arguments
     *
     * @return string
     */
    public function performTests(array $arguments) : string
    {
        // Set paths and check if they exists
        $this->setPaths($arguments);

        // Initialize tests files
        $this->initializeTests();

        // Run tests
        if (App::getMode('parse-only')) {
            $type = "Parse";
            $this->runParseTest();
        } else if (App::getMode('int-only')) {
            $type = "Interpret";
            $this->runInterpretTest();
        } else {
            $type = "All";
            $this->runAll();
        }

        // Generate progress
        $this->generator->generateProgress($this->passed, $this->failed);

        return $type;
    }

    /**
     * Get directories into array.
     *
     * @param string $directory The checked directory
     */
    private function initializeTests(string $directory = '')
    {
        // If directory is not set, set default
        if (empty($directory)) $directory = $this->getPathArgument('directory');

        // Iterate through directory
        foreach (new DirectoryIterator($directory) as $file) {
            // If file is a directory and recursive mode is active search deeper
            if ($file->isDir() && !$file->isDot() && App::getMode('recursive')) {
                $this->initializeTests($file->getPathname());
            }

            // If .src file found add it into tests registry
            if ($file->isFile() && $file->getExtension() == "src") {
                $path = $file->getPath();
                $name = $file->getBasename('.src');

                // Initialize files for source
                $files = [
                    'in' => "$name.in",
                    'out' => "$name.out",
                    'rc' => "$name.rc",
                ];

                // Check for in, out and rc
                foreach ($files as $type => $item) {
                    $fileWithPath = "$path/$item";
                    // Fill files if not exists
                    if (!file_exists($fileWithPath)) {
                        if ($type == 'rc') file_put_contents($fileWithPath, '0');
                        else file_put_contents($fileWithPath, '');
                    }
                }

                // Create new directory setup
                if (!array_key_exists($path, $this->tests)) $this->tests[$path] = [];

                // Push directory into array
                array_push($this->tests[$path], $name);
            }
        }
    }

    /**
     * Perform parse tests
     */
    private function runParseTest($type = 'only-parse')
    {
        $this->debug('Running parse tests...');

        // Initialize variables
        $script = $this->getPathArgument('parse-script');
        $jexamXmlJar = $this->getPathArgument('jexamxml');
        $jexamXmlCfg = $this->getPathArgument('jexamcfg');
        $tmpOutput = uniqid('temp_') . '.tmp';
        $tmpDiff = uniqid('temp_') . '_diff.tmp';

        // Initialize files
        file_put_contents($tmpOutput, '');
        file_put_contents($tmpDiff, '');

        foreach ($this->tests as $dir => $tests) {
            $this->debug('DIR: ' . $dir);
            foreach ($tests as $test) {
                $this->debug("\tTEST: " . $test);

                $src = $dir . '/' . $test . '.src';
                $in = $dir . '/' . $test . '.in';
                $out = $dir . '/' . $test . '.out';
                $rc = $dir . '/' . $test . '.rc';

                // Run parse.php
                exec("$this->phpCLI $script < $src", $output, $retval);

                // Output to string
                $output = implode(PHP_EOL, $output);

                // Save to tmp file
                file_put_contents($tmpOutput, $output);

                // Run jexamxml or diff depends on mode
                if (App::getMode('parse-only')) {
                    // Skip tests that have empty output
                    if (empty(file_get_contents($out))) continue;

                    // Run jexamxml
                    exec(
                        "java -jar $jexamXmlJar $tmpOutput $out $tmpDiff /D $jexamXmlCfg",
                        $diffOutput,
                        $diffRetval
                    );

                    // Get output difference and parse special characters
                    $output_diff = htmlspecialchars(file_get_contents($tmpDiff));
                } else {
                    // Run diff
                    exec(
                        "diff -E -Z -b -B $tmpOutput $out",
                        $diffOutput,
                        $diffRetval
                    );

                    // Get output difference make string and parse special characters
                    $output_diff = htmlspecialchars(implode(PHP_EOL, $diffOutput));
                }

                // Run interpret script if allowed
                if ($type == 'all') {
                    $this->debug("\tINTERPRET: Interpreting the output...");
                    $this->runInterpretScript([
                        'dir' => $dir,
                        'test' => $test,
                        'src' => $tmpOutput,
                        'in' => $in,
                        'out' => $out,
                        'rc' => $rc,
                    ]);
                } else {
                    $state = $retval == file_get_contents($rc) && $diffRetval == 0;

                    $state ? $this->passed++ : $this->failed++;

                    // Generate HTML
                    $this->generator->generateRecord([
                        'id' => uniqid('test-'),
                        'state' => $state ? 'OK' : 'FAILED',
                        'state_color' => $state ? 'text-success' : 'text-danger',
                        'dir' => $dir,
                        'test_name' => $test,
                        'ret_val_color' => $retval == file_get_contents($rc) ? 'text-success' : 'text-danger',
                        'ret_val_status' => $retval == file_get_contents($rc) ? 'passed' : 'error',
                        'output_color' => $diffRetval == 0 ? 'text-success' : 'text-danger',
                        'output_status' => $diffRetval == 0 ? 'passed' : 'error',
                        'expected_ret_val' => file_get_contents($rc),
                        'returned_ret_val' => $retval,
                        'tool_name' => App::getMode('parse-only') ? 'JExamXML' : 'diff',
                        'tool_ret_val' => $diffRetval,
                        'output' => htmlspecialchars($output),
                        'output_diff' => $output_diff,
                    ]);
                }

                // Unset some variables that are not cleared
                unset($retval);
                unset($diffRetval);
                unset($diffOutput);
            }
        }

        exec('rm -f ' . $tmpOutput);
        exec('rm -f ' . $tmpDiff);
        exec('rm -f *.tmp.log');
    }

    /**
     * Performs interpret tests.
     */
    private function runInterpretTest()
    {
        foreach ($this->tests as $dir => $tests) {
            foreach ($tests as $test) {
                $this->runInterpretScript([
                    'dir' => $dir,
                    'test' => $test,
                    'src' => $dir . '/' . $test . '.src',
                    'in' => $dir . '/' . $test . '.in',
                    'out' => $dir . '/' . $test . '.out',
                    'rc' => $dir . '/' . $test . '.rc',
                ]);
            }
        }
    }

    /**
     * Runs all tests
     */
    private function runAll()
    {
        $this->runParseTest('all');
    }

    /**
     * Runs the interpret script.
     *
     * @param array $data Data provided
     */
    private function runInterpretScript(array $data)
    {
        $script = $this->getPathArgument('int-script');

        // Run interpret.php
        exec("$this->pyCLI $script --source={$data['src']} --input={$data['in']}", $output, $retval);

        // Output into string
        $output = implode(PHP_EOL, $output);

        // Get state
        $state = $retval == file_get_contents($data['rc']) && $output == file_get_contents($data['out']);

        // Update passed and failed tests
        $state ? $this->passed++ : $this->failed++;

        // Generate HTML
        $this->generator->generateRecord([
            'id' => uniqid('test-'),
            'state' => $state ? 'OK' : 'FAILED',
            'state_color' => $state ? 'text-success' : 'text-danger',
            'dir' => $data['dir'],
            'test_name' => $data['test'],
            'ret_val_color' => $retval == file_get_contents($data['rc']) ? 'text-success' : 'text-danger',
            'ret_val_status' => $retval == file_get_contents($data['rc']) ? 'passed' : 'error',
            'output_color' => $output == file_get_contents($data['out']) ? 'text-success' : 'text-danger',
            'output_status' => $output == file_get_contents($data['out']) ? 'passed' : 'error',
            'expected_ret_val' => file_get_contents($data['rc']),
            'returned_ret_val' => $retval,
            'tool_name' => 'none',
            'tool_ret_val' => '-',
            'output' => htmlspecialchars($output, ENT_HTML5, 'ISO-8859-1'),
            'output_diff' => htmlspecialchars(file_get_contents($data['out']), ENT_HTML5, 'ISO-8859-1'),
        ]);
    }

    /**
     * Set program paths.
     *
     * @param array $arguments Passed arguments.
     */
    private function setPaths(array $arguments)
    {
        foreach ($arguments as $argument) {
            if (is_string($argument)) continue;

            try {
                if (!$this->pathExists($argument['path'], $argument['type']))
                    throw new Exception(ucfirst($argument['type']) . " in {$argument['name']} is invalid.", 41);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            $this->setPathArgument($argument['name'], $argument['path']);
        }
    }

    /**
     * Get path for argument.
     *
     * @param string $name Name of the argument that holds path
     *
     * @return string
     */
    private function getPathArgument(string $name) : string
    {
        return $this->paths[$name];
    }

    /**
     * Set path for argument.
     *
     * @param string $name Name of the argument
     * @param string $path Path of the argument
     */
    private function setPathArgument(string $name, string $path) : void
    {
        $name = substr($name, 2);

        $this->paths[$name] = $path;
    }

    /**
     * Prints a debug message into console.
     *
     * @param string $message Message that is written into console.
     */
    private function debug(string $message)
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}