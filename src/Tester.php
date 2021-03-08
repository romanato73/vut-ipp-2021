<?php


namespace src;


use src\Support\Exception;
use src\Traits\PathChecker;

class Tester
{
    use PathChecker;

    private array $flags = [];

    private array $paths = [];

    /**
     * @var string
     */
    private string $directory;

    public function performTests(array $options)
    {
        // Set arguments
        $this->setArguments($options);

        // Check if file/path exists for options
        $this->checkPaths($options);

        // Set directory
        $this->setDirectory($this->getDirectory());

        // Perform tests

    }

    private function setArguments(array $options)
    {
        $flags = [];

        foreach ($options as $option) {
            foreach ($option as $key => $data) {
                if ($data['type'] == 'argument') {
                    array_push($flags, $key);
                }
            }
        }

        $this->setFlags($flags);
    }

    private function checkPaths(array $options)
    {
        $paths = [];

        foreach ($options as $option) {
            foreach ($option as $key => $data) {
                if ($data['type'] == 'argument') continue;

                try {
                    if (!$this->pathExists($data['path'], $data['type']))
                        throw new Exception("Path or file in {$key} is invalid.", 41);
                } catch (Exception $exception) {
                    die($exception->terminateProgram());
                }

                $paths[$key] = $data['path'];
            }
        }

        $this->setPaths($paths);
    }

    /**
     * @param array $flags
     */
    private function setFlags(array $flags) : void
    {
        $this->flags = $flags;
    }

    /**
     * @param array $paths
     */
    private function setPaths(array $paths) : void
    {
        $this->paths = $paths;
    }

    /**
     * @param string $directory
     */
    private function setDirectory(string $directory) : void
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    private function getDirectory() : string
    {
        $this->directory = isset($this->paths['directory']) ? $this->paths['directory'] : '.';

        return $this->directory;
    }
}