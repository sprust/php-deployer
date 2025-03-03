<?php

namespace PhpDeployer\Helpers;

use DateTime;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;

class BuildExecutor
{
    private string $releaseDirPath = '';
    private string $buildDirName = '';
    private string $releaseAppDirPath = '';

    public function __construct(
        private readonly string $shareLinksDirPath,
        private readonly string $shareScriptsDirPath,
        private readonly string $afterCloneScriptFileName,
        private readonly string $afterSwitchActiveSymlinkFileName,
        private readonly string $activeBuildLinkFullPath,
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
        $this->logger->alert('Cloning repository...');

        $this->exec("git clone --branch $branch $repository .");
    }

    public function generateShareSymlinks(): void
    {
        $this->logger->alert('Generating share symlinks...');

        $paths = $this->getFileAndDirPathsRecursive($this->shareLinksDirPath);

        foreach ($paths as $path) {
            $source = "$this->shareLinksDirPath/$path";
            $target = "$this->releaseAppDirPath/$path";

            if (file_exists($target)) {
                $this->logger->warn("The target path already exists: $target");

                if (is_dir($target)) {
                    $this->logger->warn("Removing the directory: $target");

                    $this->deleteDirRecursive($target);
                } else {
                    $this->logger->warn("Removing the file: $target");

                    unlink($target);
                }
            }

            $this->createSymlink($source, $target);
        }
    }

    public function runAfterCloneScript(): void
    {
        $this->logger->alert('Running after clone scripts...');

        if (!$this->afterCloneScriptFileName) {
            $this->logger->warn('After clone script is not provided');

            return;
        }

        $scriptPath = $this->shareScriptsDirPath . '/' . $this->afterCloneScriptFileName;

        $this->logger->info("Path [$scriptPath]");

        $this->exec("sh $this->shareScriptsDirPath/$this->afterCloneScriptFileName");
    }

    public function replaceActiveLink(): void
    {
        $this->createSymlink($this->releaseAppDirPath, $this->activeBuildLinkFullPath);
    }

    public function runAfterSwitchActiveSymlinkScript(): void
    {
        $this->logger->alert('Running after switch active symlink scripts...');

        if (!$this->afterSwitchActiveSymlinkFileName) {
            $this->logger->warn('After switch active symlink script is not provided');

            return;
        }

        $scriptPath = $this->shareScriptsDirPath . '/' . $this->afterSwitchActiveSymlinkFileName;

        $this->logger->info("Path [$scriptPath]");

        $this->exec("sh $scriptPath");
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
        $this->logger->info('--> command executed successfully');
    }

    private function createSymlink(string $source, string $target): void
    {
        $this->logger->info("Creating symlink: $source -> $target");

        $this->exec("ln -sfn $source $target");
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

    private function getFileAndDirPathsRecursive(string $linksDirPath): array
    {
        if (!is_dir($linksDirPath)) {
            throw new RuntimeException("The provided path is not a directory");
        }

        $linksDirPathLen = strlen($linksDirPath);

        $result = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($linksDirPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->getFileName() === '.gitkeep') {
                continue;
            }

            if (!$item->isFile()
                && iterator_count(new FilesystemIterator($item->getPathname())) !== 0
            ) {
                continue;
            }

            $result[] = substr($item->getPathname(), $linksDirPathLen + 1);
        }

        return $result;
    }

    private function deleteDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("The provided path is not a directory");
        }

        $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->deleteDirRecursive($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
