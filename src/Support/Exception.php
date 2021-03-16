<?php


namespace src\Support;


use src\Analyzer\App;

class Exception extends \Exception
{
    /**
     * @var array|string[] Array of error codes
     */
    private array $codes = [
        0 => "Program executed successfully.",
        10 => "Program arguments error.",
        11 => "Can not open input file (file does not exists or you do not have permissions).",
        12 => "Can not write into a output file (you do not have permissions or unknown error).",
        99 => "Internal error.",
        // parse.php
        21 => "Unknown or incorrect header in source file of IPPcode21.",
        22 => "Unknown or incorrect operation code in source file of IPPcode21.",
        23 => "Lexical or Syntax error in source file of IPPcode21.",
        // test.php
        41 => "Path or file in arguments does not exist or you do not have permissions.",
    ];

    /**
     * Prints an exception code.
     */
    public function terminateProgram()
    {
        if (!array_key_exists($this->code, $this->codes)) exit(99);

        if (App::isArgumentSet('errors')) $this->terminateWithNote();

        exit($this->code);
    }

    /**
     * Prints an exception with message.
     */
    private function terminateWithNote()
    {
        if (!empty($this->getMessage())) {
            echo $this->code . ": " . $this->codes[$this->code] . PHP_EOL . "Details: " . $this->getMessage();
            exit($this->code);
        }

        echo $this->code . ": " . $this->codes[$this->code];
        exit($this->code);
    }
}