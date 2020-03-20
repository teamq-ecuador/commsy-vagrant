<?php


namespace Installer\Bitbucket;

use Installer\Bitbucket\Auth\AuthenticatorFactory;

/**
 * Class BitbucketService
 * @package Installer\Bitbucket
 */
class BitbucketService
{


    /**
     * @var Api
     */
    private $api;

    /**
     * BitbucketService constructor.
     * @param AuthenticatorFactory $authenticatorFactory
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $serviceName
     * @param string $projectKey
     * @return \stdClass
     * @throws \Exception
     */
    public function createRepository(string $serviceName, string $projectKey = "MIC"): \stdClass
    {
        $repoExists = $this->api->getClient()->repositoryExists($projectKey, $serviceName);

        if(!$repoExists){
            $repository = $this->api->getClient()->createRepository($projectKey, $serviceName);
        }else{
            $repository = $this->api->getClient()->getRepository($projectKey, $serviceName);
        }

        if(!$this->api->getClient()->grantPermissionToGroup($projectKey, $repository->slug, "Giffits Entwickler")){
            throw new \Exception("Could not set group-rights for group 'Giffits Entwickler' on repository $serviceName in project $projectKey");
        }

        if(!$this->api->getClient()->grantPermissionToGroup($projectKey, $repository->slug, "Admin", RepositoryPermissions::ADMIN)){
            throw new \Exception("Could not set group-rights for group 'Admin' on repository $serviceName in project $projectKey");
        }

        return $repository;
    }

    /**
     * @param object $links
     * @return string
     */
    public function findHttpUrl(object $links): string
    {
        foreach($links->clone as $cloneUrl){
            if($cloneUrl->name === "http" || $cloneUrl->name === "https"){
                return $cloneUrl->href;
            }
        }
    }
}
