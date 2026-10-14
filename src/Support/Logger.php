<?php

declare(strict_types=1);

namespace Signclave\Support;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Settings\Options;

/**
 * Append-only logs under uploads/signclave-sso.
 *
 * Filenames carry a random suffix stored in the plugin options, so the log path
 * is not guessable on servers that ignore the directory `.htaccess` (nginx).
 */
final class Logger
{
    private ?string $dir = null;

    /** @var array{log:string,audit:string}|null */
    private ?array $filenames = null;

    /**
     * @param Options $options Plugin options, used to persist log filenames.
     */
    public function __construct(
        private readonly Options $options
    ) {
    }

    /**
     * Directory that holds the configured log files.
     */
    private function directory(): string
    {
        if (null !== $this->dir) {
            return $this->dir;
        }

        $upload = wp_upload_dir();
        $base = trailingslashit((string) ($upload['basedir'] ?? ''));
        $current = $base . 'signclave-sso';
        $this->dir = $current;

        return $current;
    }

    /**
     * Write an informational log entry.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context, $this->logFilenames()['log']);
    }

    /**
     * Write an error log entry.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context, $this->logFilenames()['log']);
    }

    /**
     * Write a security audit log entry.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Structured context data.
     */
    public function security(string $message, array $context = []): void
    {
        $this->write('SECURITY', $message, $context, $this->logFilenames()['audit']);
    }

    /**
     * Return recent SAML ACS rejection lines from the audit log.
     *
     * @param int $limit Maximum number of entries to return (1–20).
     *
     * @return list<string>
     */
    public function getRecentAuditEntries(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $path = trailingslashit($this->directory()) . $this->logFilenames()['audit'];
        if (!is_readable($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || empty($lines)) {
            return [];
        }

        $entries = [];
        foreach (array_slice($lines, -50) as $line) {
            if (false === stripos($line, 'SAML ACS rejected login')) {
                continue;
            }
            $entries[] = $line;
        }

        return array_slice($entries, -$limit);
    }

    /**
     * Drop log and audit lines whose ISO-8601 timestamp is older than $days.
     *
     * Empty files after pruning are left in place. Unreadable or missing files
     * are skipped. Lines without a parseable timestamp are kept.
     *
     * @param int $days Retention window (already sanitized by the caller).
     */
    public function prune(int $days): void
    {
        $days = max(1, $days);
        $cutoff = time() - ($days * 86400);
        $names = $this->logFilenames();
        foreach (['log', 'audit'] as $kind) {
            $this->pruneFile(trailingslashit($this->directory()) . $names[$kind], $cutoff);
        }
    }

    /**
     * Rewrite a log file, keeping lines at or after the cutoff timestamp.
     *
     * @param string $path   Absolute log path.
     * @param int    $cutoff Unix timestamp; older lines are dropped.
     */
    private function pruneFile(string $path, int $cutoff): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return;
        }

        $kept = [];
        foreach ($lines as $line) {
            if ($this->lineIsWithinRetention($line, $cutoff)) {
                $kept[] = $line;
            }
        }

        $payload = [] === $kept ? '' : implode("\n", $kept) . "\n";
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- append-only log rewrite under uploads/.
        file_put_contents($path, $payload, LOCK_EX);
        if (is_file($path)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- restrict log file to owner.
            chmod($path, 0600);
        }
    }

    /**
     * Whether a log line should be kept based on its leading ISO-8601 timestamp.
     *
     * @param string $line   Raw log line.
     * @param int    $cutoff Unix timestamp cutoff (inclusive).
     */
    private function lineIsWithinRetention(string $line, int $cutoff): bool
    {
        if (!preg_match('/^\[([^\]]+)\]/', $line, $matches)) {
            return true;
        }

        $stamp = strtotime($matches[1]);
        if (false === $stamp) {
            return true;
        }

        return $stamp >= $cutoff;
    }

    /**
     * Resolve (and persist on first use) the randomized log filenames.
     *
     * @return array{log:string,audit:string}
     */
    private function logFilenames(): array
    {
        if (null !== $this->filenames) {
            return $this->filenames;
        }

        $all = $this->options->getAll();
        $log = (string) ($all['general']['log_file'] ?? '');
        $audit = (string) ($all['general']['audit_log_file'] ?? '');

        if ('' === $log || '' === $audit) {
            if ('' === $log) {
                $log = self::randomLogFilename('sc');
            }
            if ('' === $audit) {
                $audit = self::randomLogFilename('sc-audit');
            }
            $all['general']['log_file'] = $log;
            $all['general']['audit_log_file'] = $audit;
            $this->options->update($all);
        }

        $this->filenames = [
            'log' => $log,
            'audit' => $audit,
        ];

        return $this->filenames;
    }

    /**
     * Build a random, unguessable log filename.
     *
     * @param string $prefix Filename prefix, e.g. `sc` or `sc-audit`.
     */
    public static function randomLogFilename(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(16)) . '.log';
    }

    /**
     * Append a formatted log line to disk.
     *
     * @param string               $level    Log level label.
     * @param string               $message  Log message.
     * @param array<string, mixed> $context  Structured context data.
     * @param string               $filename Target log filename.
     */
    private function write(string $level, string $message, array $context, string $filename): void
    {
        $this->ensureDirectory();
        $path = trailingslashit($this->directory()) . $filename;
        $line = sprintf(
            "[%s] %s %s %s\n",
            wp_date('c'),
            $level,
            $message,
            wp_json_encode($this->sanitizeContext($context))
        );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- append-only audit/app logs under uploads/.
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        if (is_file($path)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- restrict log file to owner.
            chmod($path, 0600);
        }
    }

    /**
     * Create the log directory with web-server guards if it is missing.
     */
    private function ensureDirectory(): void
    {
        if (!file_exists($this->directory())) {
            wp_mkdir_p($this->directory());
        }
        $index = trailingslashit($this->directory()) . 'index.php';
        if (!file_exists($index)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- directory index guard in uploads/.
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
        $htaccess = trailingslashit($this->directory()) . '.htaccess';
        if (!file_exists($htaccess)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Apache deny rule in uploads/.
            file_put_contents($htaccess, "Require all denied\n");
        }
    }

    /**
     * Redact sensitive keys and truncate oversized string values.
     *
     * @param array<string, mixed> $context Raw context array.
     *
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $danger = ['password', 'bind_password', 'saml_response', 'assertion', 'private_key', 'token', 'secret'];
        foreach ($context as $key => $value) {
            foreach ($danger as $needle) {
                if (false !== stripos((string) $key, $needle)) {
                    $context[$key] = '[REDACTED]';
                    continue 2;
                }
            }
            if (is_string($value) && strlen($value) > 2000) {
                $context[$key] = substr($value, 0, 2000) . '…[truncated]';
            }
        }

        return $context;
    }
}
