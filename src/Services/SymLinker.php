<?php

namespace PhpDeployer\Services;

use RuntimeException;

readonly class SymLinker
{
    /**
     * @param array<string, string> $linkableItems
     */
    private array $linkablePaths;

    public function __construct(
        private ProcessExecutor $processExecutor,
        private Logger $logger,
        string $linkableDir,
        string $symlinksFilePath,
    ) {
        if (!file_exists($symlinksFilePath)) {
            throw new RuntimeException(
                "The symlinks file [$symlinksFilePath] is not found"
            );
        }

        $linkableItems = json_decode(file_get_contents($symlinksFilePath), true);

        if (!is_array($linkableItems)) {
            throw new RuntimeException(
                "The symlinks file [$symlinksFilePath] is not valid. Array of strings expected."
            );
        }

        $linkablePaths = [];

        foreach (array_values(array_unique($linkableItems)) as $index => $linkableItem) {
            if (!is_string($linkableItem)) {
                throw new RuntimeException(
                    "The symlinks item with index [$index] is not a string in [$symlinksFilePath]."
                );
            }

            $linkablePath = $linkableDir . '/' . trim($linkableItem, ' /');

            if (!file_exists($linkablePath)) {
                throw new RuntimeException(
                    "The linkable path [$linkablePath] is not found"
                );
            }

            $linkablePaths[$linkableItem] = $linkablePath;
        }

        $this->linkablePaths = $linkablePaths;
    }

    public function create(string $workingDirPath): void
    {
        foreach ($this->linkablePaths as $linkableItem => $linkablePath) {
            $targetPath = rtrim($workingDirPath, '/') . '/' . $linkableItem;

            if (file_exists($targetPath)) {
                $this->logger->warn("Removing existing symlink: $targetPath");

                if (is_file($targetPath)) {
                    unlink($targetPath);
                } else {
                    $this->processExecutor->exec(
                        workingDir: $workingDirPath,
                        command: "rm -rf $targetPath"
                    );
                }
            }

            $this->createSymlink(
                workingDirPath: $workingDirPath,
                source: $linkablePath,
                target: $targetPath
            );
        }
    }

    private function createSymlink(string $workingDirPath, string $source, string $target): void
    {
        $this->logger->info("Creating symlink: $source -> $target");

        $this->processExecutor->exec(
            workingDir: $workingDirPath,
            command: "ln -sfn $source $target"
        );
    }

    /**
     * @return array<string, string>
     */
    public function getLinkablePaths(): array
    {
        return $this->linkablePaths;
    }
}
