<?php


namespace Installer\Git;


use Cz\Git\GitException;
use Cz\Git\GitRepository;
use Installer\File\PathManager;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class GitService
 * @package Installer\Git
 */
class GitService
{
    /**
     * @var PathManager
     */
    private $pathManager;

    /**
     * GitService constructor.
     * @param PathManager $pathManager
     */
    public function __construct(PathManager $pathManager)
    {
        $this->pathManager = $pathManager;
    }

    /**
     * @throws \Exception
     */
    public function removeGitRepository(): void
    {
        $filesystem = new Filesystem();
        $gitRepoPath = $this->pathManager->getAbsolutePath('.git');

        if($filesystem->exists($gitRepoPath)) {
            $filesystem->remove($gitRepoPath);
        }
    }

    /**
     * @param string $remoteUrl
     * @return GitRepository
     * @throws GitException
     */
    public function initRepository(string $remoteUrl): GitRepository
    {
        $repo = GitRepository::init($this->pathManager->getRootPath());

        $repo->addRemote("origin", $remoteUrl);

        return $repo;
    }

    /**
     * @param GitRepository $repository
     * @param bool $force
     * @throws GitException
     */
    public function pushLocalRepoToBitbucket(GitRepository $repository, bool $force = false): void
    {
        if(!$repository->hasChanges()){
            return;
        }

        $repository->addAllChanges();

        $repository->commit('Installer auto-commit');

        if(!$force) {
            $repository->push('-u origin', ['master']);
        }else{
            $repository->push('-u origin', ['master', '--force']);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isStarterkit(): bool
    {
        try{
            $gitConfig = file_get_contents($this->pathManager->getAbsolutePath('.git/config'));
        }catch (\Exception $exception) {
            return false;
        }

        return strpos($gitConfig, "starterkit.git") !== false;
    }

    /**
     * @return GitRepository
     * @throws GitException
     */
    public function getRepository(): GitRepository
    {
        return new GitRepository($this->pathManager->getRootPath());
    }
}
