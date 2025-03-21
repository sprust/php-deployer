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
            throw new RuntimeException(
                "Environment file is not found by $this->filePath"
            );
        }

        $envData = explode("\n", file_get_contents($this->filePath));

        $env = [];

        foreach ($envData as $envString) {
            if (!$envString || !str_contains($envString, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $envString);

            if (str_starts_with($value, '#')) {
                continue;
            }

            $env[$key] = $value;
        }

        $this->env = $env;
    }

    /**
     * @return array<string, string>
     */
    public function get(): array
    {
        return $this->env;
    }
}
