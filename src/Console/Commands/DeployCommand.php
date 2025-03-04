<?php

namespace PhpDeployer\Console\Commands;

use PhpDeployer\Enum\ExitStatusCodeEnum;
use PhpDeployer\Helpers\Releaser;
use PhpDeployer\Helpers\Logger;
use RuntimeException;

readonly class DeployCommand
{
    public function __construct(
        private Logger $logger,
        private string $repository,
        private string $branch,
        private Releaser $releaser,
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
        $this->releaser->init();

        $this->logger->putLogFilePath(
            $this->releaser->getReleaseDirPath() . '/deploy.log'
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
        $this->logger->info("Release dir name: {$this->releaser->getReleaseDirName()}");

        $this->releaser->release($this->repository, $this->branch);

        $this->logger->alert('Application has been deployed');
    }

    private function maskRepository(): string
    {
        return preg_replace('/^.*@/', '***@', $this->repository);
    }
}
