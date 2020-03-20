<?php


namespace Installer\Bamboo;


use Installer\InstallerException;
use Throwable;

/**
 * Class BambooProjectExistsException
 * @package Installer\Bamboo
 */
class BambooProjectExistsException extends InstallerException
{
    /**
     * @var string
     */
    private $porjectKey;

    /**
     * BambooProjectExistsException constructor.
     * @param string $porjectKey
     */
    public function __construct(string $porjectKey)
    {
        $this->porjectKey = $porjectKey;

        parent::__construct("A project with the key '$porjectKey' already exists!");
    }
}
