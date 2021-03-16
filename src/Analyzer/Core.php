<?php


namespace src\Analyzer;


use src\Analyzer\Traits\Instructions;
use src\Analyzer\Traits\Lexical;
use src\Analyzer\Traits\Token;
use src\Extensions\Statistics;
use src\Support\Exception;

class Core
{
    use Instructions;
    use Token;
    use Lexical;

    /**
     * @var bool Header flag
     */
    private bool $header = false;

    /**
     * @var bool Scan end flag
     */
    private bool $scanEnd = false;

    /**
     * Creates tokens from source file.
     *
     * @return array Parsed tokens
     */
    public function lexicalAnalysis() : array
    {
        while (!feof(STDIN)) {
            // Get line
            $line = fgets(STDIN);

            // Remove comments
            $line = $this->clearLine($line);

            // Line contains new line
            if (empty($line) || $this->isNewline($line)) {
                $this->createToken("", "NEWLINE");
                continue;
            }

            // Check for header
            if ($this->isHeader($line)) {
                $this->createToken(".IPPcode21", "HEADER");
                $this->createToken("", "NEWLINE");
                $this->header = true;
                continue;
            }

            try {
                // Header is not set so we can not continue
                if (!$this->header) throw new Exception("Header is not set.", 21);

                // Validate line of code
                $loc = $this->isValidLineOfCode($line);

                // Check if line of code is valid
                if ($loc === false) throw new Exception("Line of code is not valid.", 23);

                // Create tokens from line of code
                $this->createTokens($loc);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // STATP - Count lines of code
            Statistics::add('loc');
        }


        try {
            if (!$this->header) throw new Exception("File is empty.", 21);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        return $this->tokens;
    }

    /**
     * Performs syntax analysis.
     *
     * @param array $tokens Array of tokens
     *
     * @return array Registered instructions.
     */
    public function syntaxAnalysis(array $tokens) : array
    {
        // Headers counter
        $headers = 0;
        $needNewlineToken = false;

        for ($i = 0; $i < count($tokens); $i++) {
            // Set current token and next token
            $token = $tokens[$i];

            try {
                // Exit with error if more than 1 header
                if ($headers > 1) throw new Exception("More than 1 header defined.", 23);

                // Check if new line is needed (after operation code)
                if ($needNewlineToken && !$this->isNewlineToken($token)) {
                    throw new Exception("Excepted token type NEWLINE, but {$token['type']} given.", 23);
                }
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Check for header
            if ($this->isHeaderToken($token)) {
                $headers++;
                $needNewlineToken = true;
                continue;
            }

            // Check for newline
            if ($this->isNewlineToken($token)) {
                // Set new line token to false (we do not need new line anymore)
                $needNewlineToken = false;
                continue;
            }

            // Check if token is a instruction
            try {
                if (!$this->isInstructionToken($token)) {
                    throw new Exception("Unknown instruction or operation code.", 22);
                }

                // Token is a instruction - check operands
                $operands = $this->checkOperands($tokens, $i);

                // Add instruction to registry
                $this->addToRegistry($token['id'], $operands);

                // STATP - Add jumps if jump instruction is set
                if (Statistics::isJumpInstruction($token['id'])) Statistics::add('jumps');
                // STATP - Add labels if label instruction is set
                if (Statistics::isLabelInstruction($token['id'])) Statistics::add('labels');

                // Set offset for next instruction
                $i += $this->countInstructionOperands($token['id']);

                // Set needNewlineToken flag after operation code
                $needNewlineToken = true;
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }
        }

        // Calculate fwjumps, backjumps, badjumps
        Statistics::calculateJumpTypes($this->registry);

        return $this->registry;
    }
}