<?php

namespace PhpDeployer\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

readonly class ProcessExecutor
{
    public function __construct(private Logger $logger)
    {
    }

    public function exec(string $workingDir, string $command): void
    {
        $process = Process::fromShellCommandline($command)
            ->setWorkingDirectory($workingDir)
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

        $this->logger->info('--> command executed successfully');
    }

    private function logProcess(Process $process): void
    {
        if ($output = trim($process->getOutput())) {
            $this->logger->proc($output);
        }

        if ($errorOutput = trim($process->getErrorOutput())) {
            $this->logger->proc($errorOutput);
        }

        $process->clearOutput();
        $process->clearErrorOutput();
    }
}
