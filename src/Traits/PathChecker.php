<?php


namespace src\Traits;


trait PathChecker
{
    /**
     * @var string Current argument
     */
    private string $argument;

    /**
     * Get path from argument.
     *
     * @param string $argument The checked argument
     *
     * @return string Path from checked argument.
     */
    protected function getPath(string $argument) : string
    {
        return substr($argument, strpos($argument, "=") + 1);
    }

    /**
     * Check if path exists.
     *
     * @param string $path The checked path
     * @param string $type Type of path
     *
     * @return bool If type is file and file exists return true.<br>
     *              If type is path and dir exists and it is a dir return true.<br>
     *              Otherwise false.
     */
    protected function pathExists(string $path, string $type) : bool
    {
        if ($type == 'file') {
            // Path is a file
            if (file_exists($path) && !is_dir($path)) return true;
        }

        if ($type == 'path') {
            // Path is a directory (path)
            if (file_exists($path) && is_dir($path)) return true;
        }

        return false;
    }

    /**
     * Get path type from argument.
     *
     * @param string $argument
     *
     * @return false|string Type of path, if fails returns FALSE.
     */
    protected function getPathType(string $argument)
    {
        foreach (self::$allowedArguments as $allowedArgument) {
            if ($this->getRealArgument($argument) == $this->getRealArgument($allowedArgument)) {
                return substr($allowedArgument, strpos($allowedArgument, "=") + 1);
            }
        }

        return false;
    }
}