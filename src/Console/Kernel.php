<?php

namespace PhpDeployer\Console;

use PhpDeployer\Console\Commands\DeployCommand;
use PhpDeployer\Enum\ExitStatusCodeEnum;
use PhpDeployer\Helpers\Releaser;
use PhpDeployer\Helpers\EnvReader;
use PhpDeployer\Helpers\Logger;

readonly class Kernel
{
    private string $baseDir;

    private Logger $logger;
    private EnvReader $envReader;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');

        $this->logger    = new Logger();
        $this->envReader = new EnvReader($this->baseDir . '/.env');
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
            $env = $this->envReader->get();

            $command = new DeployCommand(
                logger: $this->logger,
                repository: $env['REPOSITORY'] ?? '',
                branch: $env['BRANCH'] ?? '',
                releaser: new Releaser(
                    shareLinksDirPath: $this->baseDir . '/share/links',
                    shareScriptsDirPath: $this->baseDir . '/share/scripts',
                    afterCloneScriptFileName: $env['SCRIPT_NAME_AFTER_CLONE'] ?? '',
                    afterSwitchActiveReleaseFileName: $env['SCRIPT_NAME_AFTER_SWITCH_ACTIVE_SYMLINK'] ?? '',
                    activeReleaseLinkPath: $env['ACTIVE_RELEASE_SYMLINK_FULL_PATH'] ?? '',
                    logger: $this->logger,
                    releasesDirPath: $this->baseDir . '/releases',
                )
            );

            return $command->handle();
        }

        $this->logger->error("Unknown command: $command");

        return ExitStatusCodeEnum::ERROR;
    }
}
