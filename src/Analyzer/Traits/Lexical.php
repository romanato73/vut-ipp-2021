<?php


namespace src\Analyzer\Traits;


use src\Extensions\Statistics;
use src\Support\Exception;

trait Lexical
{
    use Token;

    /**
     * Clears the line from redundant characters.
     *
     * @param string $line The cleared line.
     *
     * @return string Cleared line from redundant characters and spaces.
     */
    protected function clearLine(string $line) : string
    {
        // Remove multi-spaces
        $line = preg_replace('/\s\s+/', ' ', $line);

        // Find hashtags (comment)
        $hasHashtag = strstr($line, '#', true);

        // Add comment into statistics
        if ($hasHashtag !== false) Statistics::add('comments');

        // STATP - Set line with hashtag or without
        $cleared = $hasHashtag === false ? $line : $hasHashtag;

        // Remove redundant spaces and return
        return trim($cleared);
    }

    /**
     * Checks if line is newline.
     *
     * @param string $line The checked line
     *
     * @return bool True if line is newline otherwise false.
     */
    protected function isNewline(string $line) : bool
    {
        return $line == "\r\n" || $line == "\n\r" || $line == "\n" || $line == "\r";
    }

    /**
     * Checks if line contains header (and validate it if yes)
     *
     * @param string $line The checked line
     *
     * @return bool True if line contains header, otherwise false.<br>
     *              After checking if it is header also validates it.
     */
    protected function isHeader(string $line) : bool
    {
        if (strtolower(substr($line, 0, 10)) == ".ippcode21") return $this->isHeaderValid($line);

        return false;
    }

    /**
     * Validates the header.
     *
     * @param string $header
     *
     * @return bool True if header is valid otherwise false.
     */
    private function isHeaderValid(string $header) : bool
    {
        try {
            // Check if header is not already set
            if ($this->header) throw new Exception("Header is already set.", 22);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        // Get string after header
        $string = trim(substr($header, 10));

        // Return false if string does not contain newline
        if (strlen($string) > 0) return false;

        return true;
    }

    /**
     * Checks if line of code is valid.
     *
     * @param string $line The checked line
     *
     * @return array Array of instruction with operands.
     *               If not valid throws an exception.
     */
    protected function isValidLineOfCode(string $line) : array
    {
        // Explode line into array
        $line = explode(' ', trim($line));

        // Set instruction
        $instruction = strtoupper(array_shift($line));

        // First should be instruction
        try {
            if (!$this->isInstruction($instruction))
                throw new Exception("Invalid instruction $instruction.", 22);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        return [$instruction => $line];
    }

    /**
     * Check if string contains allowed escape characters.
     *
     * @param string $string The checked string
     *
     * @return bool True if string contains allowed escape characters otherwise false.
     */
    protected function isAllowedEscapeChar(string $string) : bool
    {
        // Remove all correct escape sequences
        $removed = preg_replace('/\\\[0-9][0-9][0-9]/', '', $string);

        // Find backslash
        $hasBackslash = strpos($removed, '\\');

        if ($hasBackslash !== false) return false;

        return true;
    }
}