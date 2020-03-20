<?php


namespace Installer;

use Installer\Aws\ECR\EcrService;
use Installer\Bamboo\BambooService;
use Installer\Bitbucket\Api;
use Installer\Bitbucket\Auth\AuthenticatorFactory;
use Installer\Bitbucket\BitbucketService;
use Installer\Bitbucket\Client;
use Installer\Command\SetupCommand;
use Installer\File\Manipulator;
use Installer\File\PathManager;
use Installer\Git\GitService;
use Installer\Java\JavaFinder;
use Installer\Java\JavaService;
use Installer\Kong\KongService;

/**
 * Class Application
 * @package Installer
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Application constructor.
     * @param string $rootPath
     * @throws \Exception
     */
    public function __construct(string $rootPath)
    {
        parent::__construct("Giffits Microservice Installer", "1.0");

        $setupCommand = new SetupCommand();

        $pathManager = new PathManager($rootPath);

        $setupCommand->setFileManipulator(new Manipulator());
        $setupCommand->setPathManager($pathManager);

        $config = new Config($pathManager);

        $guzzleClient = new \GuzzleHttp\Client([
            'base_uri' => "http://" . $config->get("[bitbucket][base_uri]") . "/",
            'timeout'  => $config->get("[bitbucket][timeout]"),
        ]);

        $apiClient = new Client([
            "base_url" => $config->get("[bitbucket][base_uri]")
        ]);

        $apiClient->setGuzzle($guzzleClient);

        $apiClient->setAuthenticatorFactory(new AuthenticatorFactory(
            $config->get("[bitbucket][username]"),
            $config->get("[bitbucket][password]")
        ));

        $setupCommand->setBitbucketService(new BitbucketService(new Api([], $apiClient)));

        $setupCommand->setEcrService(new EcrService($config));

        $javaService = new JavaService(new JavaFinder());

        $setupCommand->setJavaService($javaService);

        $bambooService = new BambooService(new \Installer\Bamboo\Client(
            new \GuzzleHttp\Client([
                'base_uri' => "http://" . $config->get("[bamboo][base_uri]") . "/",
                'timeout'  => $config->get("[bamboo][timeout]"),
            ]),
            $config->get("[bamboo][username]"),
            $config->get("[bamboo][password]")
        ));

        $setupCommand->setBambooService($bambooService);

        $setupCommand->setConfig($config);

        $setupCommand->setGitService(new GitService($pathManager));

        $setupCommand->setKongService(
            new KongService(
                new \Installer\Kong\Api(
                    new \GuzzleHttp\Client([
                        "base_uri" => $config->get("[kong][base_uri]"),
                        "timeout" => $config->get("[kong][timeout]")
                    ])
                )
            )
        );

        $this->add($setupCommand);

        $this->setDefaultCommand($setupCommand->getName(), true);
    }
}
