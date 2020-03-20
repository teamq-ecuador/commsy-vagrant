<?php


namespace Installer\File;


use Symfony\Component\Filesystem\Filesystem;
use function GuzzleHttp\Psr7\str;

/**
 * Class Manipulator
 * @package Installer\File
 */
class Manipulator
{
    /**
     * @param string $fileName
     * @param string $pattern
     * @param string $replacement
     * @return bool|int
     */
    public function replace(string $fileName, string $pattern, string $replacement)
    {
        $string = file_get_contents($fileName);

        $replacedString = preg_replace($pattern, $replacement, $string);

        if(empty($replacedString)){
            throw new CouldNotReplacePlaceholderException($pattern, $replacement, $fileName);
        }

        return file_put_contents($fileName, $replacedString);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function dumpIntoFile(string $filePath, string $content): void
    {
        (new Filesystem())->dumpFile($filePath, $content);
    }

    /**
     * @param string $filePath
     * @param string $targetPath
     */
    public function copyFile(string $filePath, string $targetPath): void
    {
        (new Filesystem())->copy($filePath, $targetPath);
    }
}
