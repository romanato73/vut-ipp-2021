<?php


namespace src;


use src\Extensions\Statistics;
use src\Support\Exception;
use src\Traits\Instructions;
use src\Traits\Lexical;
use src\Traits\Token;

class Analyzer
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
        // Loop through a file
        while (!feof(STDIN)) {
            $char = addslashes(fgetc(STDIN));

            // Scan is at the end
            if ($this->scanEnd) break;

            try {
                // Char is a escape character
                if (!$this->isAllowedEscapeChar(STDIN, $char))
                    throw new Exception("Detected escape character.", 23);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Char is a new line
            if ($this->isNewlineChar($char)) {
                $this->createToken("", "NEWLINE");
                continue;
            }

            // Ignore spaces
            if ($this->isSpaceChar($char)) continue;

            // Ignore comments
            if ($this->isHashtagChar($char)) {
                $this->generateComment(STDIN);
                Statistics::add('comments');
                continue;
            }

            // Check for header
            if ($this->isDotChar($char)) {
                $this->generateAndValidateHeader(STDIN, $char);
                $this->header = true;
                continue;
            }

            try {
                // Check if header set
                if (!$this->header) throw new Exception("Header is not defined.", 21);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            // Generate word
            $buffer = $this->generateWord(STDIN, $char);

            // Check if word is instruction or expression
            if ($this->isInstruction($buffer)) {
                $this->createToken($buffer, "INSTRUCTION");

                if (Statistics::isJumpInstruction($buffer)) Statistics::add('jumps');
                if (Statistics::isLabelInstruction($buffer)) Statistics::add('labels');
                Statistics::add('loc');
            } else {
                $this->createToken($buffer, "EXPRESSION");
            }
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
                    throw new Exception("Found expression before instruction.", 22);
                }

                // Token is a instruction - check operands
                $operands = $this->checkOperands($tokens, $i);

                // Add instruction to registry
                $this->addToRegistry($token['id'], $operands);

                // Set offset for next item
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