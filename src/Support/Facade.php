<?php


namespace src\Support;


class Facade
{
    /**
     * @var string Program name.
     */
    public string $programName;

    /**
     * @var array Array of arguments.
     */
    public static array $arguments = [];

    /**
     * @var array|string[] Array of allowed arguments.
     */
    public static array $allowedArguments = [
        "--help",
    ];

    /**
     * Adds arguments into registry.
     *
     * @param array $arguments Arguments to register.
     */
    public function registerArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            array_push(self::$allowedArguments, $argument);
        }
    }

    /**
     * Terminates the program execution.
     */
    public function terminate()
    {
        $runtime = substr(microtime(true) - PROGRAM_START, 0, 6);

        try {
            throw new Exception("Execution time: {$runtime}ms", 0);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }
    }

    /**
     * Check if argument is equal to --help.
     *
     * @param string $argument The checked argument
     *
     * @return bool True if argument is help argument otherwise false.
     */
    protected function isHelpArgument(string $argument) : bool
    {
        return $argument == "--help";
    }
}