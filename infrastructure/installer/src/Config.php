<?php


namespace Installer;


use Installer\File\PathManager;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 * @package Installer
 */
class Config
{
    /**
     * @var array
     */
    private $values;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private $accessor;


    /**
     * Config constructor.
     * @param PathManager $pathManager
     * @throws \Exception
     */
    public function __construct(PathManager $pathManager)
    {
        $this->values = Yaml::parseFile($pathManager->getAbsolutePath("infrastructure/installer/config/config.yaml"));
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function get(string $path)
    {
        return $this->accessor->getValue($this->values, $path);
    }
}
