<?php


namespace Installer\Bamboo;


use Installer\Generator\NormalizedNameGenerator;

/**
 * Class Client
 * @package Installer\Bamboo
 */
class Client
{
    /**
     *
     */
    const MAX_RESULTS = 100;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * Client constructor.
     * @param \GuzzleHttp\Client $client
     * @param string $username
     * @param string $password
     */
    public function __construct(\GuzzleHttp\Client $client, string $username, string $password)
    {
        $this->client = $client;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return array
     */
    public function getProjects(): array
    {
        $response = json_decode((string) $this->client->get("project.json?max-result=".(string) self::MAX_RESULTS, [
            "auth" => [$this->username, $this->password]
        ])->getBody());

        return $response->projects->project;
    }

    /**
     * @param string $serviceName
     */
    public function enableCi(string $serviceName): void
    {
        $serviceName = (new NormalizedNameGenerator())->generate($serviceName);

        $response = json_decode((string) $this->client->get("repository.json?searchTerm=" . $serviceName, [
            "auth" => [$this->username, $this->password]
        ])->getBody());

        $repositoryId = $response->searchResults[0]->id;

        $this->client->put("repository/" . $repositoryId . "/enableCi", [
            "auth" => [$this->username, $this->password],
            "headers" => ["Content-Type" => "application/json"],
            \GuzzleHttp\RequestOptions::JSON => ["enable" => true]
        ]);

        $this->client->put("repository/" . $repositoryId . "/enableAllRepositoriesAccess", [
            "auth" => [$this->username, $this->password],
            "headers" => ["Content-Type" => "application/json"],
            \GuzzleHttp\RequestOptions::JSON => ["enable" => true]
        ]);
    }

    /**
     * @param string $serviceName
     */
    public function scanForSpecs(string $serviceName): void
    {
        $serviceName = (new NormalizedNameGenerator())->generate($serviceName);

        $response = json_decode((string) $this->client->get("repository.json?searchTerm=" . $serviceName, [
            "auth" => [$this->username, $this->password]
        ])->getBody());

        $repositoryId = $response->searchResults[0]->id;

        $this->client->post("repository/$repositoryId/scanNow", [
            "auth" => [$this->username, $this->password]
        ]);
    }
}
