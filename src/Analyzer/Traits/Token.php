<?php


namespace src\Analyzer\Traits;


trait Token
{
    /**
     * @var array Array of tokens,
     */
    protected array $tokens = [];

    /**
     * Creates a token and adds it into array of tokens.
     *
     * @param string $id   Token ID
     * @param string $type Token type
     */
    protected function createToken(string $id, string $type)
    {
        $token = [
            "id" => $id,
            "type" => $type,
        ];

        array_push($this->tokens, $token);
    }

    /**
     * Creates a tokens and add them into array of tokens.
     *
     * @param array $loc The checked line of code
     */
    protected function createTokens(array $loc)
    {
        foreach ($loc as $instruction => $operands) {
            $this->createToken($instruction, 'INSTRUCTION');

            foreach ($operands as $operand) {
                $this->createToken($operand, 'EXPRESSION');
            }
        }

        $this->createToken('', 'NEWLINE');
    }

    /**
     * Check if expression is valid operand.
     *
     * @param string $instruction The name of instruction
     * @param string $expression  The checked token
     * @param int    $index       Index of the current expression
     *
     * @return array|false True if expression is valid otherwise false.
     */
    private function isValidOperandToken(string $instruction, string $expression, int $index)
    {
        // Get operand type
        $type = $this->getOperandType($instruction, $index);

        // Check for allowed escape characters
        if (!$this->isAllowedEscapeChar($expression)) return false;

        // Operand type is a var
        if ($type == "var") {
            // Non-terminal 'var' means a variable
            return $this->validateVarOperand($expression);
        }

        // Operand type is a symbol
        if ($type == "symb") {
            // Terminal 'symbol' means variable or constant
            return $this->validateSymbolOperand($expression);
        }

        // Operand type is a label
        if ($type == "label") {
            return $this->validateLabelOperand($expression);
        }

        // Operand type is a type
        if ($type == "type") {
            return $this->validateTypeOperand($expression);
        }

        // Unknown operand type
        return false;
    }

    /**
     * Check whether token is a newline.
     *
     * @param array $token The checked token
     *
     * @return bool True if token type is newline
     */
    protected function isNewlineToken(array $token) : bool
    {
        return $token['type'] == "NEWLINE";
    }

    /**
     * Check whether token is a header.
     *
     * @param array $token The checked token
     *
     * @return bool True if token type is header
     */
    protected function isHeaderToken(array $token) : bool
    {
        return $token['type'] == "HEADER";
    }

    /**
     * Check whether token is a instruction.
     *
     * @param array $token The checked token
     *
     * @return bool True if token type is instruction
     */
    protected function isInstructionToken(array $token) : bool
    {
        return $token['type'] == "INSTRUCTION";
    }

    /**
     * Check whether token is a expression.
     *
     * @param array $token The checked token
     *
     * @return bool True if token type is expression
     */
    protected function isExpressionToken(array $token) : bool
    {
        return $token['type'] == "EXPRESSION";
    }
}