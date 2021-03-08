<?php


namespace src;


use src\Support\Exception;
use src\Traits\PathChecker;

class TestApp extends App
{
    // TODO: Pass --errors into $arguments and other arguments into another array

    use PathChecker;

    /**
     * @var bool Indicates that parse only mode is on
     */
    private bool $parseOnlyMode = false;

    /**
     * @var bool Indicates that interpret only mode is on
     */
    private bool $intOnlyMode = false;

    /**
     * Listen for arguments.
     *
     * @param array $argv Array of arguments
     */
    public function listen(array $argv)
    {
        // Shifts the program name argument
        $this->programName = array_shift($argv);

        // Parse arguments
        $this->parseArguments($argv);
    }

    /**
     * Parse entered arguments.
     *
     * @param array $arguments The entered arguments
     */
    private function parseArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            try {
                // Check if argument is allowed
                if (!$this->isValidArgument($argument)) {
                    throw new Exception("Entered unknown argument {$argument}", 10);
                }

                // Print help
                if ($this->isHelpArgument($argument)) {
                    if (count($arguments) > 1) throw new Exception("Can not use more arguments with argument --help.", 10);
                    $this->printHelp();
                }

                // Check if argument is not used with bad combination
                if (!$this->isValidCombination($argument))
                    throw new Exception("Argument {$argument} is used inside bad combination.", 10);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Push argument
            $this->pushArgument($argument);
        }
    }

    /**
     * Check if argument is valid.
     *
     * @param string $argument The checked argument
     *
     * @return bool True if valid otherwise false.
     */
    private function isValidArgument(string $argument) : bool
    {
        foreach (self::$allowedArguments as $allowedArgument) {
            if ($this->getRealArgument($argument) == $this->getRealArgument($allowedArgument)) return true;
        }

        return false;
    }

    /**
     * Check if combination of arguments is valid.
     *
     * @param string $argument The checked argument
     *
     * @return bool True if valid otherwise false.
     */
    private function isValidCombination(string $argument) : bool
    {
        $parseOnly = ["--parse-only", "--parse-script"];
        $intOnly = ["--int-only", "--int-script"];

        if (in_array($this->getRealArgument($argument), $parseOnly)) $this->parseOnlyMode = true;
        if (in_array($this->getRealArgument($argument), $intOnly)) $this->intOnlyMode = true;

        if ($this->parseOnlyMode && $this->intOnlyMode) return false;

        return true;
    }

    /**
     * Check if argument contains '='.
     *
     * @param string $argument The checked argument.
     *
     * @return bool The position of '=' character or false if not found.
     */
    private function isAssignmentArgument(string $argument) : bool
    {
        return strpos($argument, "=");
    }

    /**
     * Get real argument (everything before '=')
     *
     * @param string $argument The checked argument.
     *
     * @return string Real argument.
     */
    private function getRealArgument(string $argument) : string
    {
        if ($this->isAssignmentArgument($argument) === false) {
            return $argument;
        } else {
            return substr($argument, 0, strpos($argument, "="));
        }
    }

    /**
     * Push parsed argument into arguments.
     *
     * @param string $argument The pushed argument
     */
    private function pushArgument(string $argument)
    {
        $key = substr($this->getRealArgument($argument), 2);

        if ($this->isAssignmentArgument($argument) === false) {
            array_push(self::$arguments, [
                $key => ['type' => 'argument']
            ]);
        } else {
            $path = $this->getPath($argument);

            array_push(self::$arguments, [
                $key => [
                    'type' => $this->getPathType($argument),
                    'path' => $path
                ]
            ]);
        }
    }

    /**
     * Prints help to output.
     */
    private function printHelp()
    {
        // Set default paths.
        $parsePath = "parse.php";
        $interpretPath = "interpret.py";
        $jexamxmlPath = "@merlin:/pub/courses/ipp/jexamxml/jexamxml.jar";
        $jexamxmlConfPath = "@merlin:/pub/courses/ipp/jexamxml/options";

        print("Automatic testing for parse.php and interpret.py.\n\n");
        print("Usage: php {$this->programName} [--help|--errors] [OPTIONS]\n");
        print("Arguments:\n");
        print("\t--help\t\tDisplay this help and exit.\n");
        print("\t--errors\tShows detailed error messages during analysis and generation.\n");
        print("OPTIONS:\t\n");
        print("\t--directory=path\tSearch for tests in entered directory (if missing checks current directory).\n");
        print("\t--recursive\t\tSearch for subdirectories.\n");
        print("\t--parse-script=file\tScript analyze file (if missing {$parsePath} used instead).\n");
        print("\t--int-script=file\tXML interpret file (if missing {$interpretPath} used instead)\n");
        print("\t--parse-only\t\tTest only script for code analysis.\n");
        print("\t--int-only\t\tTest only script for XML interpreter.\n");
        print("\t--jexamxml=file\t\tJAR file of A7Soft JExamXML tool (if missing {$jexamxmlPath} used instead).\n");
        print("\t--jexamcfg=file\t\tConfiguration file for A7Soft JExamXML tool (if missing {$jexamxmlConfPath} used instead).\n");
        exit(0);
    }
}