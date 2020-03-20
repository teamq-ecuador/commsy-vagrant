<?php


namespace Installer\Java;


use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class JavaService
 * @package Installer\Java
 */
class JavaService
{
    /**
     * @var JavaFinder
     */
    private $finder;

    /**
     * JavaService constructor.
     * @param JavaFinder $finder
     */
    public function __construct(JavaFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * @return bool
     */
    public function javaExists(): bool
    {
        return $this->finder->findExecutable() !== null;
    }

    /**
     * @param string $commandString
     * @param string|null $customWorkingDir
     * @return string
     */
    public function executeMavenCommand(string $commandString, callable $feedbackCallback, string $customWorkingDir = null): string
    {
        $process = new Process(['mvn', $commandString], $customWorkingDir, [
            "JAVA_HOME" => getenv("JAVA_HOME")
        ]);

        $process->run($feedbackCallback);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
