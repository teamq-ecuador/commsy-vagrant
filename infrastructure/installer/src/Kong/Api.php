<?php


namespace Installer\Kong;


use Doctrine\Common\Inflector\Inflector;
use GuzzleHttp\Client;

/**
 * Class Api
 * @package Installer\Kong
 */
class Api
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Api constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $vhost
     * @param array $targets
     * @return array
     */
    public function createUpstream(string $vhost): object
    {
        return json_decode((string) $this->client->put("upstreams/$vhost", [
            \GuzzleHttp\RequestOptions::JSON => [
                'name' => $vhost,
                'algorithm' => 'least-connections',
                'host_header' => $vhost
            ]
        ])->getBody());
    }

    /**
     * @param string $upstreamId
     * @param string $target
     */
    public function addTarget(string $upstreamId, string $target): void
    {
        $this->client->post("/upstreams/" . $upstreamId . "/targets", [
            \GuzzleHttp\RequestOptions::JSON => [
                'target' => $target
            ]
        ]);
    }

    /**
     * @param $upstreamId
     * @return array
     */
    public function getTargets(string $upstreamId): array
    {
        $response = json_decode((string) $this->client->get("/upstreams/" . $upstreamId . "/targets")->getBody());

        return $response->data;
    }

    /**
     * @param string $upstreamId
     * @param string $target
     */
    public function deleteTarget(string $upstreamId, string $target): void
    {
        $this->client->delete("/upstreams/" . $upstreamId . "/targets/".$target);
    }

    /**
     * @param string $serviceName
     * @param string $vhost
     * @return object
     */
    public function createService(string $serviceName, string $vhost): object
    {
        return json_decode((string) $this->client->put("services/$serviceName", [
            \GuzzleHttp\RequestOptions::JSON => [
                'name' => $serviceName,
                'protocol' => 'http',
                'host' => $vhost,
                'port' => 80
            ]
        ])->getBody());
    }

    /**
     * @param object $service
     * @param string $serviceName
     * @param string $environment
     * @return mixed
     */
    public function createRoute(object $service, string $serviceName, string $environment): object
    {
        $routeName = ucfirst(Inflector::camelize($serviceName)) . "-" . ucfirst($environment) . "-Default-Route";

        $host = ($environment === "testing") ? "sandbox.api.giffits.de": "api.giffits.de";

        return json_decode((string) $this->client->put("services/" . $service->id . "/routes/" . $routeName, [
              \GuzzleHttp\RequestOptions::JSON => [
                  'name' => $routeName,
                  'protocols' => ['http', 'https'],
                  'hosts' => [$host],
                  'paths' => ["/$serviceName"],
                  "preserve_host" => true,
                  "strip_path"  => true
              ]
        ])->getBody());
    }

    /**
     * @return array
     */
    public function getEnabledPlugins(): array
    {
        $response = json_decode((string) $this->client->get("plugins/enabled")->getBody());

        return $response->enabled_plugins;
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        $response = json_decode((string) $this->client->get("plugins")->getBody());

        return $response->data;
    }

    /**
     * @param string $pluginName
     * @param string $serviceId
     * @param string $pluginId
     * @param array|null $config
     * @return object
     */
    public function addPluginToService(string $pluginName, string $serviceId, ?array $config = null): object
    {
        $params = [
            'name' => $pluginName,
            'run_on' => "first",
            "protocols" => ["http", "https"]
        ];

        if($config !== null){
            $params["config"] = $config;
        }

        $allServicePlugins = json_decode((string) $this->client->get("services/$serviceId/plugins")->getBody());

        foreach($allServicePlugins->data as $servicePlugin){
            if($servicePlugin->name === $pluginName){
                $this->client->delete("services/$serviceId/plugins/" . $servicePlugin->id);
            }
        }

        $body = (string) $this->client->post("services/$serviceId/plugins", [
            \GuzzleHttp\RequestOptions::JSON => $params
        ])->getBody();

        return json_decode($body);
    }

    /**
     * @param string $serviceName
     * @return object
     */
    public function createConsumer(string $serviceName): object
    {
        return json_decode((string) $this->client->put("consumers/$serviceName", [
            \GuzzleHttp\RequestOptions::JSON => [
                "username"  => $serviceName
            ]
        ])->getBody());
    }

    /**
     * @param string $consumerId
     * @return string
     */
    public function generateKey(string $consumerId): string
    {
        if(($key = $this->getKey($consumerId)) !== null){
            return $key;
        }
        return json_decode((string) $this->client->post("consumers/$consumerId/key-auth")->getBody())->key;
    }

    /**
     * @param string $consumerId
     * @return string|null
     */
    private function getKey(string $consumerId): ?string
    {
        $data = json_decode((string) $this->client->get("consumers/$consumerId/key-auth")->getBody())->data;

        if(count($data) === 0){
            return null;
        }

        $credential = array_pop($data);

        return $credential->key;
    }
}
