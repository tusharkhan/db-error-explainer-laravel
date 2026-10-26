<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Support;

use PDOException;

// Rebuilds a PDOException from an error message copied out of a log or error page.
final class PastedError
{
    public static function toException(string $text, ?string $sqlState = null, ?int $code = null): PDOException
    {
        // Laravel appends " (Connection: mysql, SQL: insert ...)"; the rules expect the bare driver message
        $message = trim(preg_replace('/\s*\(Connection: .*\)\s*$/s', '', trim($text)));

        $detail = $message;
        if (preg_match('/^SQLSTATE\[(\w+)\] \[(\d+)\] (.*)$/s', $message, $m)) {
            // Connection errors: SQLSTATE[HY000] [2002] Connection refused
            [$sqlState, $code, $detail] = [$sqlState ?? $m[1], $code ?? (int) $m[2], $m[3]];
        } elseif (preg_match('/^SQLSTATE\[(\w+)\]: [^:]+: (\d+) (.*)$/s', $message, $m)) {
            // Query errors: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ...
            [$sqlState, $code, $detail] = [$sqlState ?? $m[1], $code ?? (int) $m[2], $m[3]];
        } elseif (preg_match('/^SQLSTATE\[(\w+)\]: (.*)$/s', $message, $m)) {
            [$sqlState, $detail] = [$sqlState ?? $m[1], $m[2]];
        }

        $e = new PDOException($message, $code ?? 0);
        $e->errorInfo = [$sqlState ?? 'HY000', $code ?? 0, $detail];

        return $e;
    }
}
