<?php


namespace src\Analyzer;


use src\Extensions\Statistics;
use src\Support\Exception;
use src\Support\Facade;

class App extends Facade
{
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
        for ($i = 0; $i < count($arguments); $i++) {
            // Set current argument
            $argument = $arguments[$i];

            try {
                // Check if argument is allowed
                if (!in_array($argument, self::$allowedArguments) && !Statistics::isAllowedArgument($argument)) {
                    throw new Exception(
                        "Entered unknown argument {$argument}.",
                        10
                    );
                }

                // Print help
                if ($this->isHelpArgument($argument)) {
                    if (count($arguments) > 1) throw new Exception("Can not use more arguments with argument --help.", 10);
                    $this->printHelp();
                }

                // Check if argument is not already used
                if (in_array($argument, self::$arguments) && !Statistics::isAllowedArgument($argument)) {
                    throw new Exception("Can not use one argument multiple times (except stats arguments).", 10);
                }
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Push argument
            array_push(self::$arguments, $argument);
        }

        try {
            // Validate statistic sequence
            if (!Statistics::isValidArgumentSequence(self::$arguments))
                throw new Exception("Invalid sequence of --stats arguments.", 10);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        // Create statistics sequences
        Statistics::createSequences(self::$arguments);

        // Clear --stats arguments from files
        Statistics::clearStatsArguments(self::$arguments);
    }

    /**
     * Check if argument is set.
     *
     * @param string $name Name of argument
     *
     * @return bool True if argument is set otherwise false.
     */
    public static function isArgumentSet(string $name) : bool
    {
        return in_array("--" . $name, self::$arguments);
    }

    /**
     * Prints help to output.
     */
    private function printHelp()
    {
        print("Generate a XML representation of imperative language IPPcode21.\n\n");
        print("Usage: php {$this->programName} [--help] <file> [--errors] [STATP...]\n");
        print("Arguments:\n");
        print("\tfile\t\tIPPcode21 source file as standard input.\n");
        print("\t--help\t\tDisplay this help and exit.\n");
        print("\t--errors\tShows detailed error messages during analysis and generation.\n");
        print("STATP:\tArguments must begin with --stats.\n");
        print("\t--stats=file\tSave statistics into file.\n");
        print("\t--loc\t\tWrites number of lines of code into file.\n");
        print("\t--comments\tWrites number of comments into file.\n");
        print("\t--labels\tWrites number of labels into file.\n");
        print("\t--jumps\t\tWrites number of jumps into file.\n");
        print("\t--fwjumps\tWrites number of forward jumps into file.\n");
        print("\t--backjumps\tWrites number of backward jumps into file.\n");
        print("\t--badjumps\tWrites number of bad jumps into file.\n");
        exit(0);
    }
}