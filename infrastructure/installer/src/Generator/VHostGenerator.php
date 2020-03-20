<?php


namespace Installer\Generator;


use Installer\InstallerException;

/**
 * Class VHostGenerator
 * @package Installer\Generator
 */
class VHostGenerator
{
    /**
     * @param string $serviceName
     * @return string
     */
    public function generateLocal(string $serviceName): string
    {
        return (new NormalizedNameGenerator())->generate($serviceName)  . ".local";
    }

    /**
     * @param string $serviceName
     * @return string
     */
    public function generateTesting(string $serviceName): string
    {
        return "##testing_branch##." . (new NormalizedNameGenerator())->generate($serviceName) . ".tools.testing.giffits.de";
    }

    /**
     * @param string $serviceName
     * @return string
     */
    public function generateProduction(string $serviceName): string
    {
        return (new NormalizedNameGenerator())->generate($serviceName)  . ".tools.giffits.de";
    }

    /**
     * @param string $name
     * @param string $environment
     * @return string
     */
    public function generate(string $name, string $environment): string
    {
        $methodName = "generate" . ucfirst($environment);

        if(method_exists($this, $methodName)){
            return $this->$methodName($name);
        }

        throw new InstallerException("Could not find vhost-generation for environment '$environment'!");
    }
}
