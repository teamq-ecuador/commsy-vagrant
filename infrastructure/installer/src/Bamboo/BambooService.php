<?php


namespace Installer\Bamboo;


use Doctrine\Common\Inflector\Inflector;
use Installer\Generator\NormalizedNameGenerator;

/**
 * Class BambooService
 * @package Installer\Bamboo
 */
class BambooService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * BambooService constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
       $this->client = $client;
    }

    /**
     * @param string $projectKey
     * @return bool
     */
    public function projectKeyExists(string $projectKey): bool
    {
        $projects = $this->client->getProjects();

        foreach($projects as $project){
            if($project->key === $projectKey){
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $serviceName
     * @return string
     */
    public function buildProjectKey(string $serviceName): string
    {
        return strtoupper(substr($serviceName, 0, 3));
    }

    /**
     * @param string $serviceName
     * @return string
     */
    public function getDeployPath(string $serviceName): string
    {
        return (new NormalizedNameGenerator())->generate($serviceName);
    }

    /**
     * @param string $serviceName
     */
    public function enableBambooSpecs(string $serviceName): void
    {
        $this->client->enableCi($serviceName);
    }

    /**
     * @param string $serviceName
     */
    public function scanForSpecs(string $serviceName): void
    {
        $this->client->scanForSpecs($serviceName);
    }
}
