<?php

declare(strict_types=1);

namespace Scotch\Log;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    public function __construct(
        private readonly string $logPath,
        private readonly ?string $timezone = null,
    ) {}

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelStr = is_string($level) ? $level : (is_scalar($level) || $level instanceof \Stringable ? (string) $level : 'info');
        $messageStr = (string) $message;

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timezoneObj = null;
        if ($this->timezone !== null && $this->timezone !== '') {
            try {
                $timezoneObj = new \DateTimeZone($this->timezone);
            } catch (\Exception) {}
        }

        $dt = new \DateTimeImmutable('now', $timezoneObj);
        $timestamp = $dt->format('Y-m-d H:i:s');
        $contextStr = '';
        if ($context !== []) {
            $contextStr = ' ' . (string) json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $formatted = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($levelStr), $messageStr, $contextStr);

        file_put_contents($this->logPath, $formatted, FILE_APPEND | LOCK_EX);
    }
}
