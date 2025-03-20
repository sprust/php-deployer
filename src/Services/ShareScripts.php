<?php

namespace PhpDeployer\Services;

use RuntimeException;

readonly class ShareScripts
{
    private ?string $prepareScriptPath;
    private ?string $preReleaseScriptPath;
    private ?string $releasedScriptPath;

    public function __construct(
        private ProcessExecutor $executor,
        private Logger $logger,
        private string $shareScriptsDirPath,
        ?string $prepareScriptFileName,
        ?string $preReleaseFileName,
        ?string $releasedFileName,
    ) {
        if (!is_dir($shareScriptsDirPath)) {
            throw new RuntimeException('The share scripts directory is not found');
        }

        if (!$prepareScriptFileName) {
            $this->prepareScriptPath = null;
        } else {
            $this->prepareScriptPath = $shareScriptsDirPath
                . '/' . $prepareScriptFileName;

            if (!file_exists($this->prepareScriptPath)) {
                throw new RuntimeException('The prepare script is not found');
            }
        }

        if (!$preReleaseFileName) {
            $this->preReleaseScriptPath = null;
        } else {
            $this->preReleaseScriptPath = $shareScriptsDirPath
                . '/' . $preReleaseFileName;

            if (!file_exists($this->preReleaseScriptPath)) {
                throw new RuntimeException('The pre-release script is not found');
            }
        }

        if (!$releasedFileName) {
            $this->releasedScriptPath = null;
        } else {
            $this->releasedScriptPath = $shareScriptsDirPath
                . '/' . $releasedFileName;

            if (!file_exists($this->releasedScriptPath)) {
                throw new RuntimeException('The released script is not found');
            }
        }
    }

    public function runPrepareScript(string $workingDir): void
    {
        if (!$this->prepareScriptPath) {
            $this->logger->warn('The prepare script is not provided');

            return;
        }

        $this->logger->alert('Running prepare scripts...');

        $this->logScript($this->prepareScriptPath);

        $this->executor->exec(
            workingDir: $workingDir,
            command: "sh $this->prepareScriptPath"
        );
    }

    public function runPreReleaseScript(string $workingDir): void
    {
        if (!$this->preReleaseScriptPath) {
            $this->logger->warn('The pre-release script is not provided');

            return;
        }

        $this->logger->alert('Running pre-release scripts...');

        $this->logScript($this->preReleaseScriptPath);

        $this->executor->exec(
            workingDir: $workingDir,
            command: "sh $this->preReleaseScriptPath"
        );
    }

    public function runReleasedScript(string $workingDir): void
    {
        if (!$this->releasedScriptPath) {
            $this->logger->warn('The released script is not provided');

            return;
        }

        $this->logger->alert('Running released scripts...');

        $this->logScript($this->releasedScriptPath);

        $this->executor->exec(
            workingDir: $workingDir,
            command: "sh $this->releasedScriptPath"
        );
    }

    public function getShareScriptsDirPath(): string
    {
        return $this->shareScriptsDirPath;
    }

    public function getPrepareScriptContent(): ?string
    {
        return $this->prepareScriptPath ? file_get_contents($this->prepareScriptPath) : null;
    }

    public function getPreReleaseScriptContent(): ?string
    {
        return $this->preReleaseScriptPath ? file_get_contents($this->preReleaseScriptPath) : null;
    }

    public function getReleasedScriptContent(): ?string
    {
        return $this->releasedScriptPath ? file_get_contents($this->releasedScriptPath) : null;
    }

    private function logScript(string $scriptPath): void
    {
        $this->logger->info(trim(file_get_contents($scriptPath), PHP_EOL));
    }
}
