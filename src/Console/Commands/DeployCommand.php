<?php

namespace PhpDeployer\Console\Commands;

use PhpDeployer\Enum\ExitStatusCodeEnum;
use PhpDeployer\Helpers\BuildExecutor;
use PhpDeployer\Helpers\Logger;
use RuntimeException;

readonly class DeployCommand
{
    public function __construct(
        private Logger $logger,
        private string $repository,
        private string $branch,
        private BuildExecutor $buildExecutor,
    ) {
        if (!$this->repository) {
            throw new RuntimeException('Repository is not provided');
        }
        if (!$this->branch) {
            throw new RuntimeException('Branch is not provided');
        }
    }

    public function handle(): ExitStatusCodeEnum
    {
        $this->buildExecutor->init();

        $this->logger->putLogFilePath(
            $this->buildExecutor->getReleaseDirPath() . '/deploy.log'
        );

        try {
            $this->onHandle();
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());

            return ExitStatusCodeEnum::ERROR;
        } finally {
            $this->logger->popLogFile();
        }

        return ExitStatusCodeEnum::SUCCESS;
    }

    private function onHandle(): void
    {
        $this->logger->alert('Deploying application...');

        $this->logger->info('repository: ' . $this->maskRepository());
        $this->logger->info("branch: $this->branch");
        $this->logger->info("Build directory name: {$this->buildExecutor->getBuildDirName()}");

        $this->buildExecutor->clone($this->repository, $this->branch);
        $this->buildExecutor->generateShareSymlinks();
        $this->buildExecutor->runAfterCloneScript();
        $this->buildExecutor->replaceActiveLink();
        $this->buildExecutor->runAfterSwitchActiveSymlinkScript();

        $this->logger->alert('Application has been deployed');
    }

    private function maskRepository(): string
    {
        return preg_replace('/^.*@/', '***@', $this->repository);
    }
}
