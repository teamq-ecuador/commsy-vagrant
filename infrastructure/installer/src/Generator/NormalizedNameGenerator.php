<?php


namespace Installer\Generator;


use Doctrine\Common\Inflector\Inflector;

/**
 * Class NormalizedNameGenerator
 * @package Installer\Generator
 */
class NormalizedNameGenerator
{
    /**
     * @param string $serviceName
     * @return string
     */
    public function generate(string $serviceName): string
    {
        $serviceName = preg_replace('#[^\\w]#', '', $serviceName);

        return Inflector::tableize($serviceName);
    }
}
