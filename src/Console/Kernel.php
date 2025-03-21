<?php

namespace PhpDeployer\Console;

use PhpDeployer\Console\Commands\DeployCommand;
use PhpDeployer\Enum\ExitStatusCodeEnum;
use PhpDeployer\Services\EnvReader;
use PhpDeployer\Services\Logger;
use PhpDeployer\Services\ProcessExecutor;
use PhpDeployer\Services\Releaser;
use PhpDeployer\Services\ShareScripts;
use PhpDeployer\Services\SymLinker;

readonly class Kernel
{
    private string $baseDir;

    private Logger $logger;

    /**
     * @var array<string, string>
     */
    private array $env;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');

        $this->logger = new Logger();
        $this->env    = (new EnvReader($this->baseDir . '/.env'))->get();
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
        $processExecutor = $this->getProcessExecutor();

        return new Releaser(
            isTest: $isTest,
            shareScripts: new ShareScripts(
                executor: $processExecutor,
                logger: $this->logger,
                shareScriptsDirPath: $this->baseDir . '/share/scripts',
                prepareScriptFileName: $this->env['SCRIPT_NAME_PREPARE'] ?? null,
                preReleaseFileName: $this->env['SCRIPT_NAME_PRERELEASE'] ?? null,
                releasedFileName: $this->env['SCRIPT_NAME_RELEASED'] ?? null,
            ),
            symLinker: new SymLinker(
                processExecutor: $processExecutor,
                logger: $this->logger,
                linkableDir: $this->baseDir . '/share/linkable',
                symlinksFilePath: $this->baseDir . '/symlinks.json',
            ),
            processExecutor: $processExecutor,
            logger: $this->logger,
            shareLinkableDirPath: $this->baseDir . '/share/linkable',
            activeReleaseLinkPath: $this->env['ACTIVE_RELEASE_SYMLINK_FULL_PATH'] ?? '',
            releasesDirPath: $this->baseDir . '/releases',
        );
    }

    private function getProcessExecutor(): ProcessExecutor
    {
        return new ProcessExecutor(
            logger: $this->logger
        );
    }
}
