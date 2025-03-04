<?php

namespace PhpDeployer\Console;

use PhpDeployer\Console\Commands\DeployCommand;
use PhpDeployer\Enum\ExitStatusCodeEnum;
use PhpDeployer\Helpers\Releaser;
use PhpDeployer\Helpers\EnvReader;
use PhpDeployer\Helpers\Logger;
use Throwable;

readonly class Kernel
{
    private string $baseDir;

    private Logger $logger;
    private EnvReader $envReader;

    /**
     * @var array<string, string>
     */
    private array $env;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');

        $this->logger    = new Logger();
        $this->envReader = new EnvReader($this->baseDir . '/.env');
        $this->env       = $this->envReader->get();
    }

    public function run(array $args): ExitStatusCodeEnum
    {
        $command = $args[1] ?? null;

        $this->logger->putLogFilePath($this->baseDir . '/logs/app.log');

        if (!$command) {
            $this->logger->error('Command is not provided');

            return ExitStatusCodeEnum::ERROR;
        }

        if ($command === 'deploy') {
            $command = $this->getDeployCommand(isTest: false);

            return $command->handle();
        }

        if ($command === 'test') {
            $command = $this->getDeployCommand(isTest: true);

            $result = $command->handle();

            if ($result === ExitStatusCodeEnum::SUCCESS) {
                $this->logger->info('The deployment script is correct');
            } else {
                $this->logger->error('The deployment script is incorrect');
            }

            return ExitStatusCodeEnum::SUCCESS;
        }

        $this->logger->error("Unknown command: $command");

        return ExitStatusCodeEnum::ERROR;
    }

    private function getDeployCommand(bool $isTest): DeployCommand
    {
        return new DeployCommand(
            logger: $this->logger,
            repository: $this->env['REPOSITORY'] ?? '',
            branch: $this->env['BRANCH'] ?? '',
            releaser: $this->getReleaser($isTest)
        );
    }

    private function getReleaser(bool $isTest): Releaser
    {
        return new Releaser(
            isTest: $isTest,
            shareLinkableDirPath: $this->baseDir . '/share/linkable',
            shareScriptsDirPath: $this->baseDir . '/share/scripts',
            afterCloneScriptFileName: $this->env['SCRIPT_NAME_AFTER_CLONE'] ?? '',
            afterSwitchActiveReleaseFileName: $this->env['SCRIPT_NAME_AFTER_SWITCH_ACTIVE_SYMLINK'] ?? '',
            activeReleaseLinkPath: $this->env['ACTIVE_RELEASE_SYMLINK_FULL_PATH'] ?? '',
            logger: $this->logger,
            releasesDirPath: $this->baseDir . '/releases',
        );
    }
}
