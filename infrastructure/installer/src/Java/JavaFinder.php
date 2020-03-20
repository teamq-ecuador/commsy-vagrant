<?php


namespace Installer\Java;


/**
 * Class JavaFinder
 * @package Installer\Java
 */
class JavaFinder
{
    /**
     * @return string|null
     */
    public function findExecutable(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = 'for %i in (java.exe) do @echo.   %~$PATH:i';
        }else{
            $command = 'which java';
        }

        $output = trim(shell_exec($command));

        if(strpos($output, "java") !== false && file_exists($output)){
            return $output;
        }

        return null;
    }
}
