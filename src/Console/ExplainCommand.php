<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Console;

use Illuminate\Console\Command;
use TusharKhan\DbErrorExplainer\Laravel\DbExplainer;

class ExplainCommand extends Command
{
    use ExplainsPastedErrors;

    protected $description = 'Explain a database error message and suggest a fix';

    public function __construct()
    {
        $this->signature = 'db:explain ' . self::pastedErrorSignature() . '
            {--json : Print the full diagnosis as JSON}';

        parent::__construct();
    }

    public function handle(DbExplainer $explainer): int
    {
        $diagnosis = $this->diagnose($explainer);
        if ($diagnosis === null) {
            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line($diagnosis->toJson());

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Type</>', $diagnosis->errorType);
        $this->components->twoColumnDetail('<fg=gray>Code</>', $diagnosis->errorCode . ($diagnosis->sqlState ? " (SQLSTATE {$diagnosis->sqlState})" : ''));
        $this->components->twoColumnDetail('<fg=gray>Database</>', $diagnosis->metadata['driver']);
        $this->newLine();
        $this->line($diagnosis->explanation);

        $fix = $diagnosis->migrationSuggestion['migration_suggestion'];
        if ($fix !== '') {
            $this->newLine();
            $this->line('<options=bold>Suggested fix</>');
            $this->line("<fg=cyan>{$fix}</>");
        }

        $tip = $diagnosis->migrationSuggestion['other_suggestions'];
        if ($tip !== '') {
            $this->newLine();
            $this->line('<options=bold>Tip</>');
            $this->line($tip);
        }

        // Only schema changes belong in a migration, not diagnostic queries like SHOW or PRAGMA
        if (preg_match('/\b(ALTER|CREATE|DROP)\s/i', $fix)) {
            $this->newLine();
            $this->line('<fg=gray>Turn the fix into a migration with: php artisan db:fix-stub "..."</>');
        }

        return self::SUCCESS;
    }
}
