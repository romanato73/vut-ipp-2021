<?php


namespace src\Support;


use src\Analyzer\App;
use Throwable;

class Exception extends \Exception
{
    /**
     * @var array|string[] Array of error codes
     */
    private array $codes = [];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->codes = json_decode(file_get_contents('src/Support/errors.json'), true);
    }

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