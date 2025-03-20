<?php

namespace PhpDeployer\Services;

use RuntimeException;

readonly class EnvReader
{
    /**
     * @var array<string, string>
     */
    private array $env;

    public function __construct(private string $filePath)
    {
        if (!file_exists($this->filePath)) {
            throw new RuntimeException('Environment file is not found');
        }

        $env = file_get_contents($this->filePath);

        $env = explode("\n", $env);

        $env = array_filter($env, fn($line) => !empty($line));

        if ($env === false) {
            throw new RuntimeException('Environment file is empty');
        }

        $env = array_map(fn($line) => explode('=', $line), $env);

        $env = array_map(fn($line) => [$line[0], $line[1] ?? ''], $env);

        $this->env = array_combine(array_column($env, 0), array_column($env, 1));
    }

    /**
     * @return array<string, string>
     */
    public function get(): array
    {
        return $this->env;
    }
}
