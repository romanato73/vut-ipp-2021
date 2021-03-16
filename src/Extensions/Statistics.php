<?php


namespace src\Extensions;


use src\Support\Exception;

class Statistics
{
    /**
     * @var array|string[] Array of allowed arguments for statistics
     */
    protected static array $allowedArguments = [
        "--loc", "--comments", "--labels", "--jumps", "--fwjumps", "--backjumps", "--badjumps",
    ];

    /**
     * @var array|string[] Array of jump instructions
     */
    private static array $jumpInstructions = [
        "JUMP", "JUMPIFEQ", "JUMPIFNEQ", "CALL", "RETURN",
    ];

    /**
     * @var array Array of files with requested arguments
     */
    public static array $files = [];

    /**
     * @var array Array of statistics
     */
    public static array $statistics = [];

    /**
     * Check if sequence of stats arguments is valid.
     *
     * @param array $arguments The checked arguments
     *
     * @return bool True if it is valid argument sequence otherwise false.
     */
    public static function isValidArgumentSequence(array $arguments) : bool
    {
        $statsFlag = false;

        $sequences = [];
        $sequenceID = 0;

        // If first argument is errors shift arguments
        if (count($arguments) > 0 && $arguments[0] == "--errors") array_shift($arguments);

        // Loop through arguments
        foreach ($arguments as $argument) {
            // If argument is stats set stats flag
            if (self::isStatsArgument($argument)) {
                $statsFlag = true;

                $sequenceID++;
                $sequences[$sequenceID] = [];

                continue;
            }

            // Check if stats arguments are inside --stats argument
            if ($statsFlag && !in_array($argument, self::$allowedArguments)) return false;

            // Check if stats arguments are not used outside of stats sequence
            if (!$statsFlag && in_array($argument, self::$allowedArguments)) return false;

            // If stats flag active create a sequence of commands
            if ($statsFlag) array_push($sequences[$sequenceID], $argument);
        }

        return true;
    }

    /**
     * Check if argument is allowed.
     *
     * @param string $argument The checked argument.
     *
     * @return bool True if argument allowed otherwise false.
     */
    public static function isAllowedArgument(string $argument) : bool
    {
        if (self::isStatsArgument($argument)) return true;

        if (!in_array($argument, self::$allowedArguments)) return false;

        return true;
    }

    /**
     * Check if --stats argument passed
     *
     * @param string $argument The checked argument
     *
     * @return bool True if --stats argument passed otherwise false.
     */
    public static function isStatsArgument(string $argument) : bool
    {
        return substr($argument, 0, 7) == "--stats";
    }

    /**
     * Creates a sequence of stats arguments.
     *
     * @param array $arguments Array of arguments
     */
    public static function createSequences(array $arguments)
    {
        $file = "";

        foreach ($arguments as $argument) {
            // Check if argument is --stats
            if (self::isStatsArgument($argument)) {
                $file = substr($argument, strpos($argument, "=") + 1);

                try {
                    // Check if file already exists
                    if (array_key_exists($file, self::$files)) {
                        throw new Exception("This file already exists.", 12);
                    }
                } catch (Exception $exception) {
                    die($exception->terminateProgram());
                }

                self::$files[$file] = [];

                continue;
            }

            // Skips non-stats arguments
            if (!in_array($argument, self::$allowedArguments)) continue;

            // Push into files
            array_push(self::$files[$file], $argument);

            // Push requested statistic argument into statistics
            if (!array_key_exists($argument, self::$statistics)) {
                $argument = substr($argument, 2);

                self::$statistics[$argument] = 0;
            }
        }
    }

    /**
     * Clears stats arguments from array.
     *
     * @param array $arguments The checked arguments
     */
    public static function clearStatsArguments(array &$arguments)
    {
        for ($i = 0; $i < count($arguments); $i++) {
            if (self::isStatsArgument($arguments[$i])) {
                $arguments[$i] = substr($arguments[$i], 0, 7);
            }
        }
    }

    /**
     * Increments the count of requested statistic.
     *
     * @param string $statistic Incremented statistic
     */
    public static function add(string $statistic)
    {
        if (isset(self::$statistics[$statistic])) {
            self::$statistics[$statistic]++;
        }
    }

    /**
     * Check if instruction performs jump.
     *
     * @param string $instruction The checked instruction
     *
     * @return bool True if its jump instruction otherwise false.
     */
    public static function isJumpInstruction(string $instruction) : bool
    {
        if (in_array($instruction, self::$jumpInstructions)) return true;

        return false;
    }

    /**
     * Check if its label instruction.
     *
     * @param string $instruction The checked instruction
     *
     * @return bool True if its label instruction otherwise false.
     */
    public static function isLabelInstruction(string $instruction) : bool
    {
        if ($instruction == "LABEL") return true;

        return false;
    }

    /**
     * Calculate forward, backwards jumps and bad jumps
     *
     * @param array $instructions
     */
    public static function calculateJumpTypes(array $instructions)
    {
        $jumpInstructions = [];
        $labels = [];
        $order = 0;

        // Fill jump instructions and labels
        foreach ($instructions as $instruction) {
            if (in_array($instruction['name'], self::$jumpInstructions)) {
                array_push($jumpInstructions, [
                    "name" => $instruction['name'],
                    "label" => !empty($instruction['operands']) ? $instruction['operands'][0]['value'] : "",
                    "order" => $order,
                ]);
            }

            if ($instruction['name'] == "LABEL") {
                array_push($labels, [
                    "name" => $instruction['name'],
                    "value" => $instruction['operands'][0]['value'],
                    "order" => $order,
                ]);
            }

            $order++;
        }

        Statistics::calculateForwardJumps($jumpInstructions, $labels);
        Statistics::calculateBackwardJumps($jumpInstructions, $labels);
        Statistics::calculateBadJumps($jumpInstructions, $labels);
    }

    /**
     * Calculates forward jumps if active
     *
     * @param array $instructions Jump instructions
     * @param array $labels       Labels
     */
    private static function calculateForwardJumps(array $instructions, array $labels)
    {
        if (self::active('fwjumps')) {
            foreach ($instructions as $instruction) {
                foreach ($labels as $label) {
                    if ($instruction['label'] == $label['value'] && $instruction['order'] < $label['order']) {
                        Statistics::add('fwjumps');
                    }
                }
            }
        }
    }

    /**
     * Calculates backward jumps if active
     *
     * @param array $instructions Jump instructions
     * @param array $labels       Labels
     */
    private static function calculateBackwardJumps(array $instructions, array $labels)
    {
        if (self::active('backjumps')) {
            foreach ($instructions as $instruction) {
                foreach ($labels as $label) {
                    if ($instruction['label'] == $label['value'] && $instruction['order'] > $label['order']) {
                        Statistics::add('backjumps');
                    }
                }
            }
        }
    }

    /**
     * Calculates bad jumps if active
     *
     * @param array $instructions Jump instructions
     * @param array $labels       Labels
     */
    private static function calculateBadJumps(array $instructions, array $labels)
    {
        if (self::active('badjumps')) {
            $returns = 0;
            $calls = 0;

            foreach ($instructions as $instruction) {
                // Count RETURNs
                if ($instruction['name'] == "RETURN") {
                    $returns++;
                    continue;
                }

                // Count CALLs
                if ($instruction['name'] == "CALL") $calls++;

                $labelExist = false;

                // Loop through labels
                foreach ($labels as $label) {
                    if ($instruction['label'] == $label['value']) {
                        $labelExist = true;
                    }
                }

                if (!$labelExist) Statistics::add('badjumps');
            }

            // Calculate bad jumps using CALL/RETURN
            for ($i = $calls; $i < $returns; $i++) Statistics::add('badjumps');
        }
    }

    /**
     * Checks if statistics are active.
     *
     * @param string $statistic Check if statistic is active (optional)
     *
     * @return bool True if statistic/s is/are active otherwise false.
     */
    private static function active(string $statistic = "") : bool
    {
        return empty($statistic) ? !empty(self::$statistics) : isset(self::$statistics[$statistic]);
    }

    /**
     * Generate file/s with requested statistics.
     */
    public static function generateStatistics()
    {
        if (self::active()) {
            // Check if paths exists
            foreach (array_keys(self::$files) as $file) {
                try {
                    $path = dirname(dirname(dirname(__DIR__)) . "/" . $file);

                    if (!is_dir($path)) throw new Exception("Directory does not exists.", 12);
                } catch (Exception $exception) {
                    die($exception->terminateProgram());
                }
            }

            // Generate and save statistics
            foreach (self::$files as $file => $arguments) {
                try {
                    // Check if arguments set
                    if (count($arguments) < 1)
                        throw new Exception("No statistics defined for file: $file", 10);

                    $stream = fopen($file, 'w');

                    if (!$stream) throw new Exception('Can not save to stats output file.', 12);

                    $written = self::writeIntoFile($stream, $arguments);

                    fclose($stream);

                    if (!($written > 0)) throw new Exception('Can not save to stats output file.', 12);
                } catch (Exception $exception) {
                    die($exception->terminateProgram());
                }
            }
        }
    }

    /**
     * Writes data into file.
     *
     * @param mixed $stream    Stream of characters
     * @param array $arguments Statistics to be written.
     *
     * @return false|int the number of bytes written, or false on error.
     */
    private static function writeIntoFile($stream, array $arguments)
    {
        $output = [];

        foreach ($arguments as $argument) {
            $argument = substr($argument, 2);

            array_push($output, self::$statistics[$argument]);
        }

        return fwrite($stream, implode("\n", $output));
    }
}