<?php


namespace src\TestFrame;


use src\Support\Exception;
use src\Support\Facade;
use src\TestFrame\Traits\PathChecker;

class App extends Facade
{
    use PathChecker;

    /**
     * @var array Active modes.
     */
    public static array $modes = [];

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
    public function parseArguments(array $arguments)
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

                // Check if mode arguments are correct
                if (!$this->modeArgumentsAreCorrect($argument))
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
     * Check if mode argument is set and check its validity.
     *
     * @param string $argument The checked argument
     *
     * @return bool True if valid otherwise false.
     */
    private function modeArgumentsAreCorrect(string $argument) : bool
    {
        if ($this->isModeArgument($argument)) {
            array_push(self::$modes, $this->getModeArgument($argument));
        }

        // Check if parse && int mode are not together
        if (self::getMode('parse') && self::getMode('int')) return false;

        return true;
    }

    /**
     * Checks if argument is mode argument.
     *
     * @param string $argument The checked argument
     *
     * @return bool True if argument is mode argument otherwise false.
     */
    private function isModeArgument(string $argument) : bool
    {
        $parseOnlyArguments = ["--parse-only", "--parse-script"];
        $intOnlyArguments = ["--int-only", "--int-script"];

        $argument = $this->getRealArgument($argument);

        if (in_array($argument, $parseOnlyArguments)) return true;

        if (in_array($argument, $intOnlyArguments)) return true;

        if ($argument == "--recursive") return true;

        return false;
    }

    /**
     * Gets plain mode argument.
     *
     * @param string $argument The passed argument
     *
     * @return string Plain mode argument.
     */
    private function getModeArgument(string $argument) : string
    {
        // Remove "--"
        $argument = substr($argument, 2);

        // Get everything before "="
        if (strpos($argument, '=') !== false) {
            $argument = strstr($argument, '=', true);
        }

        return $argument;
    }

    /**
     * Get mode from modes if exists.
     *
     * @param string $name
     *
     * @return bool True if mode found otherwise false.
     */
    public static function getMode(string $name) : bool
    {
        return in_array($name, self::$modes);
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
        // Check if argument already exists
        foreach (self::$arguments as $arg) {
            $argumentName = $this->getRealArgument($argument);
            try {
                if ((is_string($arg) && $argumentName == $arg) || (is_array($arg) && $arg['name'] == $argumentName))
                    throw new Exception("This argument is already used.", 10);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }
        }

        if ($this->isAssignmentArgument($argument) === false) {
            array_push(self::$arguments, $argument);
        } else {
            $path = $this->getPath($argument);
            $name = $this->getRealArgument($argument);

            array_push(self::$arguments, [
                'name' => $name,
                'type' => $this->getPathType($argument),
                'path' => $path,
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