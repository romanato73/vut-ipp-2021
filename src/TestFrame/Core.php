<?php


namespace src\TestFrame;


use DirectoryIterator;
use src\Support\Exception;
use src\TestFrame\Traits\PathChecker;

class Core
{
    use PathChecker;

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

    private HTMLGenerator $generator;

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

        if (App::getMode('parse-only')) {
            $type = "Parse";
            $this->runParseTest();
        } else if (App::getMode('int-only')) {
            $type = "Interpret";
        } else {
            $type = "All";
            $this->runParseTest();
        }

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
                    'in' => "{$name}.in",
                    'out' => "{$name}.out",
                    'rc' => "{$name}.rc",
                ];

                // Check for in, out and rc
                foreach ($files as $type => $item) {
                    $fileWithPath = "{$path}/{$item}";
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
    private function runParseTest()
    {
        // Initialize variables
        $parseScript = $this->getPathArgument('parse-script');
        $jexamXmlJar = $this->getPathArgument('jexamxml');
        $jexamXmlCfg = $this->getPathArgument('jexamcfg');
        $tmpOutput = uniqid('temp_') . '.tmp';
        $tmpDiff = uniqid('temp_') . '_diff.tmp';

        foreach ($this->tests as $dir => $tests) {
            foreach ($tests as $test) {
                $output = [];

                $src = $dir . '/' . $test . '.src';
                $in = $dir . '/' . $test . '.in';
                $out = $dir . '/' . $test . '.out';
                $rc = $dir . '/' . $test . '.rc';

                // Run parse.php
                exec("php.exe {$parseScript} < {$src}", $output, $retval);

                // Output to string
                $output = implode(PHP_EOL, $output);

                // Save to tmp file
                file_put_contents($tmpOutput, $output);

                // Run jexamxml or diff depends on mode
                if (App::getMode('parse-only')) {
                    // Run jexamxml
                    exec(
                        "java -jar {$jexamXmlJar} {$tmpOutput} {$out} {$tmpDiff} /D {$jexamXmlCfg}",
                        $diffOutput,
                        $diffRetval
                    );

                    // Get output difference and parse special characters
                    $output_diff = htmlspecialchars(file_get_contents($tmpDiff));
                } else {
                    // Run diff
                    exec(
                        "diff -E -Z -b -B {$tmpOutput} {$out}",
                        $diffOutput,
                        $diffRetval
                    );

                    // Get output difference make string and parse special characters
                    $output_diff = htmlspecialchars(implode(PHP_EOL, $diffOutput));
                }

                $state = $retval == file_get_contents($rc) && $diffRetval == 0 ? 'OK' : 'FAILED';
                $stateColor = $state == 'OK' ? 'text-success' : 'text-danger';

                // Generate HTML
                $this->generator->generateRow([
                    'id' => uniqid('test-'),
                    'state' => $state,
                    'state_color' => $stateColor,
                    'dir' => $dir,
                    'test_name' => $test,
                    'ret_val_color' => $retval == file_get_contents($rc) ? 'text-success' : 'text-danger',
                    'ret_val_status' => $retval == file_get_contents($rc) ? 'passed' : 'error',
                    'output_color' => $diffRetval == 0 ? 'text-success' : 'text-danger',
                    'output_status' => $diffRetval == 0 ? 'passed' : 'error',
                    'expected_ret_val' => file_get_contents($rc),
                    'returned_ret_val' => $retval,
                    'output' => htmlspecialchars($output),
                    'output_diff' => $output_diff,
                ]);

                // Unset some variables that are not cleared
                unset($retval);
                unset($diffRetval);
                unset($diffOutput);
            }
        }

        exec('rm -f ' . $tmpOutput);
        exec('rm -f ' . $tmpDiff);
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
}