<?php


namespace Installer\File;


/**
 * Class PathManager
 * @package Installer\File
 */
class PathManager
{
    /**
     * @var string
     */
    private $rootPath;

    /**
     * PathManager constructor.
     * @param string $rootPath
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @param string $path
     * @param bool $throwException
     * @return string
     * @throws \Exception
     */
    public function getAbsolutePath(string $path, bool $throwException = true): string
    {
        $modifiedPath = ltrim($path, DIRECTORY_SEPARATOR);

        $modifiedPath = rtrim($modifiedPath, DIRECTORY_SEPARATOR);

        $modifiedPath = ($throwException) ? realpath($this->rootPath . DIRECTORY_SEPARATOR . $modifiedPath): $this->rootPath . DIRECTORY_SEPARATOR . $modifiedPath;

        if($modifiedPath === false){
            throw new \Exception("Path does not exist! (" . $this->rootPath . DIRECTORY_SEPARATOR . $path . ")");
        }

        return $modifiedPath;
    }
}
