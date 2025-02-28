<?php

namespace PhpDeployer\Helpers;

use DateTime;

class Logger
{
    private const RESET  = "\033[0m";
    private const RED    = "\033[31m";
    private const GREEN  = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE   = "\033[34m";

    /**
     * @var string[]
     */
    private array $logFilePaths = [];

    public function putLogFilePath(string $logFilePath): void
    {
        $this->logFilePaths[] = $logFilePath;
    }

    public function popLogFile(): ?string
    {
        return array_pop($this->logFilePaths);
    }

    public function info(mixed $message): void
    {
        $this->writeLn(self::GREEN, __FUNCTION__, $this->prepareMessage($message));
    }

    public function warn(mixed $message): void
    {
        $this->writeLn(self::YELLOW, __FUNCTION__, $this->prepareMessage($message));
    }

    public function error(mixed $message): void
    {
        $this->writeLn(self::RED, __FUNCTION__, $this->prepareMessage($message));
    }

    public function __destruct()
    {
        print "\n";
    }

    private function prepareMessage(mixed $message): string
    {
        if (is_array($message) || is_object($message)) {
            return json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $message;
    }

    private function writeLn(string $prefix, string $level, string $message): void
    {
        $level = mb_strtoupper($level);

        $logMessage = $this->makeDateTime() . ' ' . $level . ' ' . $message;

        foreach ($this->logFilePaths as $logFilePath) {
            if (!file_exists($logFilePath)) {
                touch($logFilePath);
            }

            file_put_contents($logFilePath, $logMessage . "\n", FILE_APPEND);
        }

        print  "\n" . $prefix . $logMessage . self::RESET;
    }

    private function makeDateTime(): string
    {
        return (new DateTime())->format('Y-m-d H:i:s.u');
    }
}
