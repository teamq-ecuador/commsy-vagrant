<?php

namespace Installer\Aws\ECR;

use Aws\Credentials\Credentials;
use Aws\Ecr\EcrClient;
use Aws\Ecr\Exception\EcrException;
use Aws\Result;
use Installer\Config;

/**
 * Class EcrService
 * @package Installer\Aws\ECR
 */
class EcrService
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var EcrClient
     */
    private $client;

    /**
     * EcrService constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $string
     * @return string
     */
    public function createRepository(string $name): string
    {
        if($this->repositoryExists($name)){
            $result = $this->getClient()->describeRepositories([
                'repositoryNames' => [$name],
            ]);

            return $result->get("repositories")[0]["repositoryUri"];
        }

        $result = $this->getClient()->createRepository([
            'imageScanningConfiguration' => [
                'scanOnPush' => true,
            ],
            'imageTagMutability' => 'MUTABLE',
            'repositoryName' => $name
        ]);

        return $result->get("repository")["repositoryUri"];
    }

    private function getClient(): EcrClient
    {
        if($this->client === null){
            $credentials = new Credentials($this->config->get("[aws][access_key]"), $this->config->get("[aws][secret]"));

            $this->client = new EcrClient([
                "version" => $this->config->get("[aws][version]"),
                "region" => $this->config->get("[aws][region]"),
                'credentials' => $credentials,
                'http' => [ 'verify' => false ]
            ]);
        }

        return $this->client;
    }

    public function repositoryExists(string $name): bool
    {
        try {
            $result = $this->getClient()->describeRepositories([
                'repositoryNames' => [$name],
            ]);
        }catch(EcrException $exception){
            if($exception->getAwsErrorCode() === "RepositoryNotFoundException"){
                return false;
            }

            throw $exception;
        }

        return true;
    }
}
