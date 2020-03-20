<?php


namespace Installer\Command;


use Cz\Git\GitException;
use Installer\Aws\ECR\EcrService;
use Installer\Bamboo\BambooProjectExistsException;
use Installer\Bamboo\BambooService;
use Installer\Bitbucket\BitbucketService;
use Installer\Config;
use Installer\File\Manipulator;
use Installer\File\PathManager;
use Installer\Generator\NormalizedNameGenerator;
use Installer\Generator\VHostGenerator;
use Installer\Git\GitService;
use Installer\InstallerException;
use Installer\Java\JavaService;
use Installer\Kong\KongService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class SetupCommand
 * @package Installer\Command
 */
class SetupCommand extends Command
{
    /**
     * @var string
     */
    const SERVICE_NAME_PARAMETER_NAME = "serviceName";

    /**
     * @var string
     */
    protected static $defaultName = 'installer:setup';

    /**
     * @var null|SymfonyStyle
     */
    private $style = null;

    /**
     * @var Manipulator
     */
    private $fileManipulator;

    /**
     * @var PathManager
     */
    private $pathManager;

    /**
     * @var BitbucketService
     */
    private $bitbucketService;

    /**
     * @var EcrService
     */
    private $ecrService;

    /**
     * @var JavaService
     */
    private $javaService;

    /**
     * @var BambooService
     */
    private $bambooService;

    /**
     * @var GitService
     */
    private $gitService;

    /**
     * @var KongService
     */
    private $kongService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param KongService $kongService
     */
    public function setKongService(KongService $kongService): void
    {
        $this->kongService = $kongService;
    }

    /**
     * @param GitService $gitService
     */
    public function setGitService(GitService $gitService): void
    {
        $this->gitService = $gitService;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @param BambooService $bambooService
     */
    public function setBambooService(BambooService $bambooService): void
    {
        $this->bambooService = $bambooService;
    }

    /**
     * @param JavaService $javaService
     */
    public function setJavaService(JavaService $javaService): void
    {
        $this->javaService = $javaService;
    }

    /**
     * @param Manipulator $fileManipulator
     */
    public function setFileManipulator(Manipulator $fileManipulator): void
    {
        $this->fileManipulator = $fileManipulator;
    }

    /**
     * @param PathManager $pathManager
     */
    public function setPathManager(PathManager $pathManager): void
    {
        $this->pathManager = $pathManager;
    }

    /**
     * @param EcrService $ecrService
     */
    public function setEcrService(EcrService $ecrService): void
    {
        $this->ecrService = $ecrService;
    }

    /**
     * @param BitbucketService $bitbucketService
     */
    public function setBitbucketService(BitbucketService $bitbucketService): void
    {
        $this->bitbucketService = $bitbucketService;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->addArgument(self::SERVICE_NAME_PARAMETER_NAME, InputArgument::REQUIRED, 'The name of the microservice, choose carefully! Has to be url-conform!');
        $this->addOption("testing", "t", InputOption::VALUE_OPTIONAL);
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     * @throws \Exception
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $io = new SymfonyStyle($input, $output);

            $serviceName = $input->getArgument(self::SERVICE_NAME_PARAMETER_NAME);

            $this->changeConfigFiles($serviceName);

            $io->success("Config files have been setup correctly!");

            $repository = $this->bitbucketService->createRepository($serviceName);

            $io->success("Repository was successfully created: " . $repository->links->self[0]->href);

            $this->createLocalRepository($io, $repository);

            $uris = $this->createERCRepositories($serviceName);

            $io->success("AWS ECR Repositories where created successfully!");
            $io->writeln("Repository URIs:");
            $io->listing($uris);

            $result = true;

            if ($this->javaService->javaExists()) {
                $result = $this->createBambooBuild($io, $serviceName, $repository);
            } else {
                $io->warning('No java on this system, could not setup build/deployment in Bamboo!');
                $io->writeln('Please install the Java SDK 13.0.1 and Apache Maven!');
                $io->listing([
                    "https://www.oracle.com/technetwork/java/javase/downloads/jdk13-downloads-5672538.html",
                    "https://docs.wso2.com/display/IS323/Installing+Apache+Maven+on+Windows"
                ]);
            }

            $result = $this->setupApiGateways($io, $serviceName);

            if(!$result) {
                $io->warning("There where some ERRORS. Please check them BEFORE running 'vagrant up'!");
                return 1;
            }
        }catch(\Exception $exception){
            $io->error($exception->getMessage());
            $io->warning("There where some ERRORS. Please check them BEFORE running 'vagrant up'!");
            return 1;
        }

        $io->writeln("All done, you can now run 'vagrant up'!");

        return 0;
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if($input->getArgument(self::SERVICE_NAME_PARAMETER_NAME) === null){
            $io = $this->getStyle($input, $output);

            $serviceName = $io->ask("How should the new service be called? (Has to be URI-conform! e.g 'payment-service' NOT 'SüperDüperServiß')");

            $input->setArgument(self::SERVICE_NAME_PARAMETER_NAME, $serviceName);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return SymfonyStyle
     */
    private function getStyle(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        if($this->style === null){
            $this->style = new SymfonyStyle($input, $output);
            $this->style->title("ServiceDefinition");
        }

        return $this->style;
    }

    /**
     * @param $serviceName
     * @param string $vhost
     * @throws \Exception
     */
    protected function changeConfigFiles($serviceName): void
    {
        $vhostLocal = (new VHostGenerator())->generateLocal($serviceName);

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("Vagrantfile"),
            "#GiffitsStarterKit#",
            $serviceName
        );

        $this->fileManipulator->copyFile(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/symfony.conf"),
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/local.conf", false)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/local.conf"),
            "#symfony\.local#",
            $vhostLocal
        );

        $this->fileManipulator->copyFile(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/symfony.conf"),
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/testing.conf", false)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/testing.conf"),
            "#symfony\.local#",
            (new VHostGenerator())->generateTesting($serviceName)
        );

        $this->fileManipulator->copyFile(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/symfony.conf"),
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/production.conf", false)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/containers/apache/config/vhost/production.conf"),
            "#symfony\.local#",
            (new VHostGenerator())->generateProduction($serviceName)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/scripts/console.sh"),
            "#symfony\.local#",
            $vhostLocal
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/scripts/message.txt"),
            "#192\.168\.33\.10#",
            $vhostLocal
        );
    }

    /**
     * @param string $serviceName
     * @return string[]
     */
    protected function createERCRepositories(string $serviceName): array
    {
        $uris = [];

        $serviceName = (new NormalizedNameGenerator())->generate($serviceName);

        $uris["apache"] = $this->ecrService->createRepository("$serviceName/apache");
        $uris["php"] = $this->ecrService->createRepository("$serviceName/php");

       return $uris;
    }

    private function adjustSpecFiles(string $serviceName, \stdClass $repository)
    {
        $projectShortName = $this->bambooService->buildProjectKey($serviceName);

        if($this->bambooService->projectKeyExists($projectShortName)){
            throw new BambooProjectExistsException($projectShortName);
        }

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Project_Name_Short##/",
            $projectShortName
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Project_Name##/",
            $serviceName
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Service_Name##/",
            $serviceName
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Service_Deploy_Path##/",
            $this->bambooService->getDeployPath($serviceName)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Repository_Uri##/",
            $this->bitbucketService->findHttpUrl($repository->links)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Repository_Username##/",
            $this->config->get("[bitbucket][username]")
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("bamboo-specs/src/main/java/app/PlanSpec.java"),
            "/##Repository_Password##/",
            $this->config->get("[bitbucket][password]")
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/bamboo/build/build-container/setup-configs.sh"),
            "/&&SERVICE_NORMALIZED_NAME&&/",
            (new NormalizedNameGenerator())->generate($serviceName)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/bamboo/build/build-container/tag-and-push.sh"),
            "/&&SERVICE_NORMALIZED_NAME&&/",
            (new NormalizedNameGenerator())->generate($serviceName)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/bamboo/build/deploy-to-aws/deploy-config-to-stack.sh"),
            "/&&SERVICE_NORMALIZED_NAME&&/",
            (new NormalizedNameGenerator())->generate($serviceName)
        );

        $this->fileManipulator->replace(
            $this->pathManager->getAbsolutePath("infrastructure/bamboo/build/deploy-to-aws/pull-images.sh"),
            "/&&SERVICE_NORMALIZED_NAME&&/",
            (new NormalizedNameGenerator())->generate($serviceName)
        );
    }

    /**
     * @param SymfonyStyle $io
     * @param $serviceName
     * @param \stdClass $repository
     * @return bool
     * @throws \Exception
     */
    protected function createBambooBuild(SymfonyStyle $io, $serviceName, \stdClass $repository): bool
    {
        $io->writeln("Generating Bamboo-Build for repository '" . $this->bitbucketService->findHttpUrl($repository->links) . "'!");
        $io->writeln("");

        $skipping = false;

        try {
            $this->adjustSpecFiles($serviceName, $repository);

            $this->gitService->pushLocalRepoToBitbucket($this->gitService->getRepository());

            $this->javaService->executeMavenCommand(
                "-Ppublish-specs",
                function($type, $buffer) use($io){
                    if (Process::ERR === $type) {
                        $io->error($buffer);
                    } else {
                        $io->write($buffer);
                    }
                },
                $this->pathManager->getAbsolutePath('bamboo-specs')
            );
        } catch (ProcessFailedException $exception) {
            $io->error('Could not create build in bamboo!');
            return false;
        } catch (BambooProjectExistsException $exception) {
            $io->warning('A BambooBuild for this project already exists, skipping!');
            $skipping = true;
        }

        $io->writeln("");
        $io->writeln("Enabling Spec-Watch on Project!");

        try {
            $this->bambooService->enableBambooSpecs($serviceName);
            $io->success("Spec-Watch enabled successfully!");
        }catch(\Exception $exception){
            $io->success("Spec-Watch enabled successfully!");
        }

        try {
            $this->bambooService->scanForSpecs($serviceName);
            $io->success("Triggered scan for specs in repository!");
        }catch(\Exception $exception){
            $io->warning("Could not trigger scan for specs in repository! Probably a misconfigured repository-url!");
        }

        if(!$skipping) {
            $io->success("Bamboo Build created successfully!");
        }

        return true;
    }

    /**
     * @param SymfonyStyle $io
     * @param \stdClass $repository
     * @throws \Cz\Git\GitException
     */
    protected function createLocalRepository(SymfonyStyle $io, \stdClass $repository): void
    {
        if (!$this->gitService->isStarterkit()) {
            $io->writeln("Initializing local repository!");
            $gitRepository = $this->gitService->initRepository($this->bitbucketService->findHttpUrl($repository->links));
        } else {
            $gitRepository = $this->gitService->getRepository();
        }

        $io->writeln("Pushing local files to remote repository!");

        try {
            try {
                $this->gitService->pushLocalRepoToBitbucket($gitRepository);
            }catch(GitException $exception){
                $io->warning("Cant push to remote repository due to existing files!");
                if(true || $io->askQuestion(new ConfirmationQuestion("Force push? (WARNING: This will delete ALL history and files inside the REMOTE repository!", true))){
                    $this->gitService->pushLocalRepoToBitbucket($gitRepository, true);
                }else{
                    throw new GitException("Abort due to user input!");
                }
            }

            $io->success("All files pushed!");
        }catch(GitException $exception){
            $io->error("Could not push files to remote! Maybe you have already pushed something there?");
            throw new InstallerException("Could not push files to remote repository!");
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param $serviceName
     * @return \Exception
     */
    protected function setupApiGateways(SymfonyStyle $io, $serviceName): bool
    {
        $result = true;

        try {
            $io->writeln("Setting up API-Gateway-Entry for 'testing'! (https://sandbox.api.giffits.de/$serviceName)");
            $apiGateway = $this->kongService->setupApiGateway($serviceName, 'testing', $this->config->get("[kong][targets][testing]"));
            $io->writeln("");
            $io->writeln("Created credentials:");
            $io->listing(
              [
                  "consumer: " . $apiGateway->consumer->username,
                  "giffits-key: " . $apiGateway->consumer->key
              ]
            );
            $io->success("API-Gateway-Entry for 'testing' successfully installed!");
        } catch (\Exception $exception) {
            $result = false;
            $io->error($exception->getMessage());
            $io->warning("Could not setup testing-api-gateway!");
        }

        try {
            $io->writeln("Setting up API-Gateway-Entry for 'production'! (https://api.giffits.de/$serviceName)");
            $apiGateway = $this->kongService->setupApiGateway($serviceName, 'production', $this->config->get("[kong][targets][production]"));
            $io->writeln("");
            $io->writeln("Created credentials:");
            $io->listing(
                [
                    "consumer: " . $apiGateway->consumer->username,
                    "giffits-key: " . $apiGateway->consumer->key
                ]
            );
            $io->success("API-Gateway-Entry for 'production' successfully installed!");
        } catch (\Exception $exception) {
            $result = false;
            $io->error($exception->getMessage());
            $io->warning("Could not setup production-api-gateway!");
        }

        return $result;
    }
}
