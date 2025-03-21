<?php

namespace PhpDeployer\Services;

use DateTime;
use RuntimeException;

class Releaser
{
    private bool $initialized = false;

    private string $releaseDirPath = '';
    private string $releaseDirName = '';
    private string $releaseAppDirPath = '';

    public function __construct(
        private readonly bool $isTest,
        private readonly ShareScripts $shareScripts,
        private readonly SymLinker $symLinker,
        private readonly ProcessExecutor $processExecutor,
        private readonly Logger $logger,
        private readonly string $shareLinkableDirPath,
        private readonly string $activeReleaseLinkPath,
        private readonly string $releasesDirPath
    ) {
        if (!is_dir($this->shareLinkableDirPath)) {
            throw new RuntimeException('The share links directory is not found');
        }

        if (!is_dir($this->releasesDirPath)) {
            throw new RuntimeException('The releases directory is not found');
        }
    }

    public function getReleaseDirName(): string
    {
        return $this->releaseDirName;
    }

    public function getReleaseDirPath(): string
    {
        return $this->releaseDirPath;
    }

    public function init(): void
    {
        $this->releaseDirName = sprintf(
            'release_%s%s_%s',
            $this->isTest ? 'test_' : '',
            (new DateTime())->format('Ymd_His'),
            uniqid()
        );

        $this->releaseDirPath    = rtrim($this->releasesDirPath, '/') . '/' . $this->releaseDirName;
        $this->releaseAppDirPath = $this->releaseDirPath . '/app';

        mkdir($this->releaseDirPath);
        mkdir($this->releaseAppDirPath);

        $this->initialized = true;
    }

    public function release(string $repository, string $branch): void
    {
        if (!$this->initialized) {
            throw new RuntimeException('The releaser is not initialized');
        }

        $this->clone($repository, $branch);

        $this->symLinker->create($this->releaseAppDirPath);

        if (!$this->isTest) {
            $this->shareScripts->runPrepareScript(
                workingDir: $this->releaseAppDirPath
            );

            if (file_exists($this->activeReleaseLinkPath)) {
                $this->shareScripts->runPreReleaseScript(
                    workingDir: $this->activeReleaseLinkPath
                );
            }

            $this->createSymlink(
                source: $this->releaseAppDirPath,
                target: $this->activeReleaseLinkPath
            );

            $this->shareScripts->runReleasedScript(
                workingDir: $this->activeReleaseLinkPath
            );
        }

        $this->saveState();
    }

    private function clone(string $repository, string $branch): void
    {
        $this->logger->alert('Cloning repository...');

        $this->processExecutor->exec(
            workingDir: $this->releaseAppDirPath,
            command: "git clone --branch $branch $repository ."
        );
    }

    private function createSymlink(string $source, string $target): void
    {
        $this->logger->info("Creating symlink: $source -> $target");

        $this->processExecutor->exec(
            workingDir: $this->releaseAppDirPath,
            command: "ln -sfn $source $target"
        );
    }

    private function saveState(): void
    {
        $stateData = [
            'time'                  => (new DateTime())->format('Y-m-d H:i:s.u'),
            'releaseDirPath'        => $this->releaseDirPath,
            'releaseDirName'        => $this->releaseDirName,
            'releaseAppDirPath'     => $this->releaseAppDirPath,
            'activeReleaseLinkPath' => $this->activeReleaseLinkPath,
            'shareLinkableDirPath'  => $this->shareLinkableDirPath,
            'shareScripts'          => [
                'dirPath'          => $this->shareScripts->getShareScriptsDirPath(),
                'prepareScript'    => $this->shareScripts->getPrepareScriptContent(),
                'preReleaseScript' => $this->shareScripts->getPreReleaseScriptContent(),
                'releasedScript'   => $this->shareScripts->getReleasedScriptContent(),
            ],
            'symlinks'              => $this->symLinker->getLinkablePaths(),
            'releasesDirPath'       => $this->releasesDirPath,
        ];

        file_put_contents(
            $this->releaseDirPath . '/state.json',
            json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if (!$this->isTest) {
            file_put_contents(
                $this->releasesDirPath . '/current-state.json',
                json_encode($stateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
    }
}
