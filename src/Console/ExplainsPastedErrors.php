<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Console;

use Illuminate\Support\Facades\DB;
use Throwable;
use TusharKhan\DbErrorExplainer\DTO\Diagnosis;
use TusharKhan\DbErrorExplainer\Laravel\DbExplainer;
use TusharKhan\DbErrorExplainer\Laravel\Support\PastedError;

// Shared by the commands that take an error message copied from a log.
trait ExplainsPastedErrors
{
    protected function diagnose(DbExplainer $explainer): ?Diagnosis
    {
        $connection = $this->option('connection') ?: DB::getDefaultConnection();

        // Connect so schema details can be added; a pasted connection error still works without it
        if (!$this->option('no-schema')) {
            try {
                DB::connection($connection)->getPdo();
            } catch (Throwable) {
            }
        }

        $exception = PastedError::toException(
            $this->argument('error'),
            $this->option('sqlstate') ?: null,
            $this->option('code') !== null ? (int) $this->option('code') : null,
        );

        $diagnosis = $explainer->explain($exception, $connection);

        if ($diagnosis === null) {
            $this->components->error("Can't explain errors for the [{$connection}] connection (unsupported driver or explainer disabled).");
        } elseif (($diagnosis->metadata['rule_id'] ?? 'none') === 'none') {
            $this->components->warn("No rule matched this {$diagnosis->metadata['driver']} error. Is --connection the database it came from?");
            $diagnosis = null;
        }

        return $diagnosis;
    }

    protected static function pastedErrorSignature(): string
    {
        return '{error : The error message, e.g. copied from storage/logs/laravel.log}
            {--connection= : Connection whose database produced the error (default connection when omitted)}
            {--sqlstate= : SQLSTATE, if the message does not start with SQLSTATE[...]}
            {--code= : Driver error code, if the message does not include it}
            {--no-schema : Do not connect to look up table and column details}';
    }
}
