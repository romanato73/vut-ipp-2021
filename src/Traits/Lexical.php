<?php


namespace src\Traits;


use src\Support\Exception;

trait Lexical
{
    use Token;

    /**
     * Get next character from stream.
     *
     * @param mixed $stream Stream of characters.
     *
     * @return mixed Next character.
     */
    protected function nextChar($stream)
    {
        // Set next character
        $nextChar = fgetc($stream);

        // Set pointer to current - 1 position
        fseek($stream, -1, SEEK_CUR);

        return $nextChar;
    }

    /**
     * Check if character is new line.
     *
     * @param string $char The checked char
     *
     * @return bool True if new line character otherwise false.
     */
    protected function isNewlineChar(string $char) : bool
    {
        return $char == "\r\n" || $char == "\n\r" || $char == "\n" || $char == "\r";
    }

    /**
     * Check if character is space.
     *
     * @param string $char The checked char
     *
     * @return bool True if space character otherwise false.
     */
    protected function isSpaceChar(string $char) : bool
    {
        return $char == " ";
    }

    /**
     * Check if character is hashtag.
     *
     * @param string $char The checked char
     *
     * @return bool True if hashtag character otherwise false.
     */
    protected function isHashtagChar(string $char) : bool
    {
        return $char == "#";
    }

    /**
     * Check if character is dot.
     *
     * @param string $char The checked char
     *
     * @return bool True if dot character otherwise false.
     */
    protected function isDotChar(string $char) : bool
    {
        return $char == ".";
    }

    /**
     * Check if character is interrupt character from new command (newline, space or comment)
     *
     * @param string $char The checked char
     *
     * @return bool True if is separator char otherwise false.
     */
    protected function isInterruptChar(string $char) : bool
    {
        // Check if character is newline, space or comment
        if (in_array($char, $this->interruptTokens)) return true;

        return false;
    }

    /**
     * Check if character (and next characters) are escape characters.
     *
     * @param mixed  $stream Stream of characters
     * @param string $char   Current character
     *
     * @return bool True if is escape char otherwise false.
     */
    protected function isAllowedEscapeChar($stream, string $char) : bool
    {
        // Detect escape sequence
        if ($char == '\\') {
            // Get next 3 characters
            $characters = [fgetc($stream), fgetc($stream), fgetc($stream)];
            // Check if next 3 characters are numbers from 0-9
            if (!preg_grep('/[0-9]/', $characters)) return false;
            // Go back if characters are correct
            fseek($stream, -3, SEEK_CUR);
        }

        return true;
    }

    /**
     * Check if header is valid.
     *
     * @param string $buffer The checked buffer
     *
     * @return bool True if header valid otherwise false.
     */
    protected function isHeaderValid(string $buffer) : bool
    {
        return strtolower($buffer) == ".ippcode21";
    }

    /**
     * Generate tokens required when comment found.
     *
     * @param mixed $stream Stream of characters
     */
    protected function generateComment($stream)
    {
        // Go to the end of the line
        while (!feof($stream)) {
            // Check if next token is end of file
            if (is_bool($this->nextChar($stream))) {
                $this->scanEnd = true;
                break;
            }

            // Check if next token is a new line
            if ($this->isNewlineChar($this->nextChar($stream))) {
                break;
            }

            // Get next character
            fgetc($stream);
        }
    }

    /**
     * Generates header token.
     *
     * @param mixed  $stream Stream of characters
     * @param string $char   Current character
     */
    protected function generateAndValidateHeader($stream, string $char)
    {
        $buffer = [];

        while (!feof($stream)) {
            // Push character into buffer
            array_push($buffer, $char);

            if (is_bool($this->nextChar($stream))) $this->scanEnd = true;

            // Check if next character is interrupt character
            if ($this->scanEnd || $this->isInterruptChar($this->nextChar($stream))) {
                // Check if header valid
                $buffer = trim(implode('', $buffer));

                try {
                    if (!$this->isHeaderValid($buffer)) throw new Exception(
                        "Header is not correct (expected '.IPPcode21', given '{$buffer}' given).",
                        21
                    );
                } catch (Exception $exception) {
                    die($exception->terminateProgram());
                }

                $this->createToken($buffer, "HEADER");
                break;
            }

            // Get next character
            $char = fgetc($stream);
        }
    }

    /**
     * Generates a word.
     *
     * @param mixed  $stream Stream of characters
     * @param string $char   Current character
     *
     * @return string Generated word.
     */
    protected function generateWord($stream, string $char) : string
    {
        $buffer = [];

        // Continue analyzing
        while (!feof($stream)) {
            try {
                // Check for escape characters
                if (!$this->isAllowedEscapeChar($stream, $char)) {
                    throw new Exception('Detected escape character.', 23);
            }
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Push character into buffer
            array_push($buffer, $char);

            // Check if character is bool (end of file)
            if (is_bool($this->nextChar($stream))) {
                $this->scanEnd = true;
                break;
            }

            // Check if next token is newline, space or #
            if (in_array($this->nextChar($stream), $this->interruptTokens)) break;

            // Get next character
            $char = fgetc($stream);
        }

        // Clear and return the buffer
        return trim(implode('', $buffer));
    }
}