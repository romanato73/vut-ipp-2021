<?php

namespace src\Analyzer\Traits;


use src\Support\Exception;

trait Instructions
{
    use Token;

    /**
     * @var array Registry of instructions.
     */
    protected array $registry = [];

    /**
     * @var array|int[] Allowed instructions with number of operands.
     */
    private array $instructions = [
        // Frames, functions
        "MOVE" => ["var", "symb"],
        "CREATEFRAME" => 0,
        "PUSHFRAME" => 0,
        "POPFRAME" => 0,
        "DEFVAR" => ["var"],
        "CALL" => ["label"],
        "RETURN" => 0,
        "PUSHS" => ["symb"],
        "POPS" => ["var"],
        // Arithmetic
        "ADD" => ["var", "symb", "symb"],
        "SUB" => ["var", "symb", "symb"],
        "MUL" => ["var", "symb", "symb"],
        "IDIV" => ["var", "symb", "symb"],
        "LT" => ["var", "symb", "symb"],
        "GT" => ["var", "symb", "symb"],
        "EQ" => ["var", "symb", "symb"],
        "AND" => ["var", "symb", "symb"],
        "OR" => ["var", "symb", "symb"],
        "NOT" => ["var", "symb"],
        "INT2CHAR" => ["var", "symb"],
        "STRI2INT" => ["var", "symb", "symb"],
        // I/O
        "READ" => ["var", "type"],
        "WRITE" => ["symb"],
        // Strings
        "CONCAT" => ["var", "symb", "symb"],
        "STRLEN" => ["var", "symb"],
        "GETCHAR" => ["var", "symb", "symb"],
        "SETCHAR" => ["var", "symb", "symb"],
        // Type
        "TYPE" => ["var", "symb"],
        // Program handle
        "LABEL" => ["label"],
        "JUMP" => ["label"],
        "JUMPIFEQ" => ["label", "symb", "symb"],
        "JUMPIFNEQ" => ["label", "symb", "symb"],
        "EXIT" => ["symb"],
        // Debugging
        "DPRINT" => ["symb"],
        "BREAK" => 0,
    ];

    /**
     * @var array|string[] Allowed frames for variables
     */
    private array $allowedFrames = [
        "LF", "TF", "GF",
    ];

    /**
     * @var array|string[] Allowed types for constants
     */
    private array $allowedTypes = [
        "int", "bool", "string", "nil",
    ];

    /**
     * Adds a instruction into registry.
     *
     * @param string $instruction The added instruction
     * @param array  $operands    Array of operands
     */
    protected function addToRegistry(string $instruction, array $operands)
    {
        $operationCode = [
            "name" => $instruction,
            "operands" => $operands,
        ];

        array_push($this->registry, $operationCode);
    }

    /**
     * Find instruction in registry.
     *
     * @param string $instruction The searched instruction.
     *
     * @return bool True if instruction was found otherwise false.
     */
    protected function isInstruction(string $instruction) : bool
    {
        return array_key_exists($instruction, $this->instructions);
    }

    /**
     * Counts number of operands for instruction.
     *
     * @param string $instruction The searched instruction.
     *
     * @return int Number of allowed operands.
     */
    protected function countInstructionOperands(string $instruction) : int
    {
        return is_array($this->instructions[$instruction]) ? count($this->instructions[$instruction]) : 0;
    }

    /**
     * Gets the operand type.
     *
     * @param string $instruction Instruction name
     * @param int    $index       Operand index
     *
     * @return string Operand type
     */
    protected function getOperandType(string $instruction, int $index) : string
    {
        return $this->instructions[$instruction][$index];
    }

    /**
     * Check if var operand is valid
     *
     * @param string $expression The checked expression
     *
     * @return array|false Var array if success if error occurred false.
     */
    protected function validateVarOperand(string $expression)
    {
        $var = [
            "prefix" => substr($expression, 0, 2),
            "separator" => $expression[2],
            "value" => substr($expression, 3, strlen($expression)),
            "type" => "var",
        ];

        // Check if it has frame tag (LF, TF, GF)
        if (!in_array($var['prefix'], $this->allowedFrames)) return false;

        // Check if 3rd (2nd) character is @
        if ($var['separator'] != '@') return false;

        // Check if value contains valid characters
        if (!$this->isOperandValueValid($var['value'], 'frame')) return false;

        return $var;
    }

    /**
     * Check if symbol operand is valid.
     *
     * @param string $expression The checked expression
     *
     * @return array|false Symbol array if success if error occurred false.
     */
    protected function validateSymbolOperand(string $expression)
    {
        // Set separator
        $separator = strpos($expression, '@') ? strpos($expression, '@') : false;

        // Set symbol array
        $symbol = [
            "prefix" => substr($expression, 0, strpos($expression, '@')),
            "separator" => $separator ? $expression[strpos($expression, '@')] : false,
            "value" => substr($expression, strpos($expression, '@') + 1, strlen($expression)),
        ];

        // Check if prefix is allowed
        if (!$this->isAllowedFrame($symbol['prefix']) && !$this->isAllowedType($symbol['prefix'])) return false;

        // Check if separator is set
        if ($separator === false) return false;

        // Check if value of symbol is correct
        if (in_array($symbol['prefix'], $this->allowedFrames)) {
            // Value of symbol is with frame
            if (!$this->isOperandValueValid($symbol['value'], 'frame')) return false;

            $symbol['type'] = 'var';
        } else {
            // Value of symbol is with type
            if (!$this->isOperandValueValid($symbol['value'], 'type', $symbol['prefix'])) return false;

            $symbol['type'] = $symbol['prefix'];
        }

        return $symbol;
    }

    /**
     * Check if label operand is valid.
     *
     * @param string $expression The checked expression
     *
     * @return array|false Label array if success if error occurred false.
     */
    protected function validateLabelOperand(string $expression)
    {
        if (!$this->isOperandValueValid($expression, 'label')) return false;

        return [
            'type' => 'label',
            'value' => $expression,
        ];
    }

    /**
     * Check if type operand is valid.
     *
     * @param string $expression The checked expression
     *
     * @return array|false Type array if success if error occurred false.
     */
    protected function validateTypeOperand(string $expression)
    {
        if (!$this->isOperandValueValid($expression, 'dataType')) return false;

        return [
            'type' => 'type',
            'value' => $expression,
        ];
    }

    /**
     * Check operands of instruction.
     *
     * @param array $tokens Array of tokens
     * @param int   $index  Position of instruction in array of tokens
     *
     * @return array Array of operands
     */
    private function checkOperands(array $tokens, int $index) : array
    {
        // Operands array
        $operands = [];

        // Current instruction
        $instruction = $tokens[$index];

        // Operand counter
        $operandsCounter = $this->countInstructionOperands($instruction['id']);

        // Set operand index, offset of operands and next index
        $operandIndex = 0;
        $offset = 0;
        $index++;

        try {
            if (count($tokens) < ($index + $operandsCounter))
                throw new Exception("Instruction {$instruction['id']} has invalid operands.", 23);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        // Loop through operands
        for ($j = $index; $j < $index + $operandsCounter; $j++) {
            // Increment offset
            $offset++;

            // Set current token
            $token = $tokens[$j];

            try {
                // Next token must be an expression otherwise syntax error
                if (!$this->isExpressionToken($token)) throw new Exception(
                    "{$instruction['id']} has invalid operand/s.",
                    23
                );

                $validatedOperand = $this->isValidOperandToken($instruction['id'], $token['id'], $operandIndex++);

                if (!$validatedOperand)
                    throw new Exception(
                        "{$instruction['id']}'s operand contains invalid character or incorrect frame/type.",
                        23
                    );
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            array_push($operands, $validatedOperand);
        }

        return $operands;
    }

    /**
     * Check if value of operand is valid.
     *
     * @param string $value  The checked value
     * @param string $prefix Prefix of checked value
     * @param string $type   Type of prefix
     *
     * @return bool True if value is valid otherwise false.
     */
    private function isOperandValueValid(string $value, string $prefix, string $type = "") : bool
    {
        // Prefix is frame
        if ($prefix == 'frame' || $prefix == 'label') {
            // Check if value starts with letter or special symbol
            $isAllowed = preg_match("/[a-zA-Z?!*%$&_-]/", $value[0]);
            if (!$isAllowed) return false;

            // Check if value contains only alphanumeric characters or allowed symbols
            $isAllowed = preg_match("/^([a-zA-Z0-9?!*%$&_-])*$/", $value);
            if (!$isAllowed) return false;
        }

        // Prefix is type
        if ($prefix == 'type') {
            if ($type == "int") {
                // Symbol is integer - check if it contains only numbers
                if (!is_numeric($value)) return false;
            } else if ($type == "bool") {
                // Symbol is boolean - check if it contains only true/false
                $isBoolean = $value == "true" || $value == "false";
                if (!$isBoolean) return false;
            } else if ($type == "nil") {
                if ($value != "nil") return false;
            } else {
                // Symbol is string
                if (!is_string($value)) return false;
            }
        }

        // Prefix is data type
        if ($prefix == 'dataType') {
            return in_array($value, $this->allowedTypes);
        }

        return true;
    }

    /**
     * Check if frame is allowed.
     *
     * @param string $frame The checked frame
     *
     * @return bool True if frame is allowed otherwise false.
     */
    private function isAllowedFrame(string $frame) : bool
    {
        return in_array($frame, $this->allowedFrames);
    }

    /**
     * Check if type is allowed
     *
     * @param string $type The checked type
     *
     * @return bool True if type is allowed otherwise false.
     */
    private function isAllowedType(string $type) : bool
    {
        return in_array($type, $this->allowedTypes);
    }
}