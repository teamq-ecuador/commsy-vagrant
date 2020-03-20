<?php


namespace Installer\File;


use Installer\InstallerException;
use Throwable;

/**
 * Class CouldNotReplacePlaceholderException
 * @package Installer\File
 */
class CouldNotReplacePlaceholderException extends InstallerException
{
    /**
     * CouldNotReplacePlaceholderException constructor.
     * @param string $placeholder
     * @param string $replacement
     * @param string $file
     */
    public function __construct(string $placeholder, string $replacement, string $file)
    {
        parent::__construct("Could not replace '$placeholder' with value '$replacement' in file '$file'");
    }
}
