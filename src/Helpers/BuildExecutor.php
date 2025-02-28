<?php

namespace PhpDeployer\Helpers;

use DateTime;
use RuntimeException;
use Symfony\Component\Process\Process;

class BuildExecutor
{
    private string $releaseDirPath = '';
    private string $buildDirName = '';
    private string $releaseAppDirPath = '';

    public function __construct(
        private readonly Logger $logger,
        private readonly string $buildDirPath
    ) {
    }

    public function getBuildDirName(): string
    {
        return $this->buildDirName;
    }

    public function getReleaseDirPath(): string
    {
        return $this->releaseDirPath;
    }

    public function init(): void
    {
        $this->buildDirName      = 'build_' . (new DateTime())->format('Ymd_His') . '_' . uniqid();
        $this->releaseDirPath    = rtrim($this->buildDirPath, '/') . '/' . $this->buildDirName;
        $this->releaseAppDirPath = $this->releaseDirPath . '/app';

        mkdir($this->releaseDirPath);
        mkdir($this->releaseAppDirPath);
    }

    public function clone(string $repository, string $branch): void
    {
        $this->exec("git clone --branch $branch $repository .");
    }

    private function exec(string $command): void
    {
        $process = Process::fromShellCommandline($command)
            ->setWorkingDirectory($this->releaseAppDirPath)
            ->setTimeout(null);

        $process->start();

        while ($process->isRunning()) {
            $this->logProcess($process);

            sleep(1);
        }

        $this->logProcess($process);

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Command failed');
        }

        $this->logger->info('Command executed successfully');
    }

    private function logProcess(Process $process): void
    {
        if ($output = trim($process->getOutput())) {
            $this->logger->info($output);
        }

        if ($errorOutput = trim($process->getErrorOutput())) {
            $this->logger->error($errorOutput);
        }

        $process->clearOutput();
        $process->clearErrorOutput();
    }
}
