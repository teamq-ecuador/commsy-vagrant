<?php


namespace Installer\Kong;


use Doctrine\Common\Inflector\Inflector;
use Installer\Generator\NormalizedNameGenerator;
use Installer\Generator\VHostGenerator;

/**
 * Class KongService
 * @package Installer\Kong
 */
class KongService
{
    /**
     * @var Api
     */
    private $api;

    /**
     * KongService constructor.
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $serviceName
     * @param string $environment
     * @param array $targets
     * @return object
     */
    public function setupApiGateway(string $serviceName, string $environment, array $targets): object
    {
        $vhost = (new VHostGenerator)->generate($serviceName, $environment);

        if($environment === "testing"){
            //for testing branch dynamic
            $vhost = str_replace("##testing_branch##", "master", $vhost);
        }
        $apiGateway = new \stdClass();

        $apiGateway->upstream = $this->addUpstream($vhost, $targets);

        $apiGateway->service = $this->addService($serviceName, $environment, $vhost);

        $apiGateway->consumer = $this->addConsumer($serviceName);

        return $apiGateway;
    }

    /**
     * @param string $name
     * @param array $targets
     * @return object
     */
    private function addUpstream(string $name, array $targets): object
    {
        $upstream =  $this->api->createUpstream($name);

        $currentTargets = $this->api->getTargets($upstream->id);

        foreach ($currentTargets as $target){
            $this->api->deleteTarget($upstream->id, $target->id);
        }

        foreach ($targets as $target){
            $this->api->addTarget($upstream->id, $target);
        }

        return $upstream;
    }

    /**
     * @param string $serviceName
     * @param string $environment
     * @param string $vhost
     */
    private function addService(string $serviceName, string $environment, string $vhost): object
    {
        $service = $this->api->createService(ucfirst(Inflector::camelize($serviceName)) . "-" . ucfirst($environment), $vhost);

        $this->api->createRoute($service, $serviceName, $environment);

        $plugins = $this->api->getEnabledPlugins();

        foreach($plugins as $pluginName){
            if($pluginName === "request-transformer"){
                $this->api->addPluginToService($pluginName, $service->id, [
                    "add" => [
                        "headers" => [
                            "Host: $vhost"
                        ]
                    ],
                    "append" => [
                        "headers" => [
                            "Host: $vhost"
                        ]
                    ],
                    "replace" => [
                        "headers" => [
                            "Host: $vhost"
                        ]
                    ]
                ]);
            }

            if($pluginName === "key-auth"){
                $this->api->addPluginToService($pluginName, $service->id, [
                    "key_names" => ["giffits-key"]
                ]);
            }
        }

        return $service;
    }

    /**
     * @param string $serviceName
     * @return object
     */
    private function addConsumer(string $serviceName)
    {
        $serviceName = (new NormalizedNameGenerator())->generate($serviceName);

        $consumer = $this->api->createConsumer($serviceName);

        $key = $this->api->generateKey($consumer->id);

        $consumer->key = $key;

        return $consumer;
    }
}
