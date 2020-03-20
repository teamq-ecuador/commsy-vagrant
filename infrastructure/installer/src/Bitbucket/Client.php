<?php


namespace Installer\Bitbucket;


use Installer\Bitbucket\Auth\AuthenticatorFactory;

/**
 * Class Client
 * @package Installer\Bitbucket
 */
class Client extends \Bitbucket\API\Http\Client
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;

    /**
     * @var AuthenticatorFactory
     */
    private $authenticatorFactory;

    /**
     * @param AuthenticatorFactory $authenticatorFactory
     */
    public function setAuthenticatorFactory(AuthenticatorFactory $authenticatorFactory): void
    {
        if($this->authenticatorFactory === null) {
            $this->authenticatorFactory = $authenticatorFactory;
            $this->addListener($this->authenticatorFactory->createListener());
        }
    }

    /**
     * @param \GuzzleHttp\Client $guzzle
     */
    public function setGuzzle(\GuzzleHttp\Client $guzzle): void
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $key
     * @return \stdClass
     */
    public function getProject(string $key): \stdClass
    {
        return json_decode($this->get("projects/$key")->getContent());
    }

    /**
     * @param string $projectKey
     * @param string $serviceName
     * @return bool
     */
    public function repositoryExists(string $projectKey, string $serviceName): bool
    {
        $repos = json_decode($this->get("projects/$projectKey/repos")->getContent());

        if($repos->size === 0){
            return false;
        }

        foreach($repos->values as $repo){
            if($repo->name === $serviceName){
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $projectKey
     * @param string $serviceName
     * @return \stdClass
     */
    public function createRepository(string $projectKey, string $serviceName): \stdClass
    {
        $response = $this->post("projects/$projectKey/repos", json_encode([
            "name" => $serviceName,
            "scmId" => "git"
        ]),
            ["Content-Type" => "application/json"]);

        return json_decode($response->getContent());
    }

    /**
     * @param string $projectKey
     * @param string $repoKey
     * @param string $groupName
     * @param string $permission
     * @return bool
     */
    public function grantPermissionToGroup(string $projectKey, string $repoKey, string $groupName, string $permission = RepositoryPermissions::WRITE): bool
    {
        $response = $this->guzzle->put(
            "1.0/projects/$projectKey/repos/$repoKey/permissions/groups?name=$groupName&permission=$permission",
            ['auth' => [$this->authenticatorFactory->getUsername(), $this->authenticatorFactory->getPassword()]]
        );

        return $response->getStatusCode() === 204;
    }

    /**
     * @param string $projectKey
     * @param string $serviceName
     * @return \stdClass|null
     */
    public function getRepository(string $projectKey, string $serviceName): ?\stdClass
    {
        $repos = json_decode($this->get("projects/$projectKey/repos")->getContent());

        if($repos->size === 0){
            return false;
        }

        foreach($repos->values as $repo){
            if($repo->name === $serviceName){
                return $repo;
            }
        }

        return null;
    }
}
