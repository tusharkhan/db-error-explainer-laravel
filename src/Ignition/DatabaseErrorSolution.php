<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Ignition;

use Spatie\ErrorSolutions\Contracts\Solution;
use TusharKhan\DbErrorExplainer\DTO\Diagnosis;

class DatabaseErrorSolution implements Solution
{
    public function __construct(private Diagnosis $diagnosis) {}

    public function getSolutionTitle(): string
    {
        return 'Database error explained: ' . str_replace('_', ' ', $this->diagnosis->errorType);
    }

    public function getSolutionDescription(): string
    {
        $parts = [$this->diagnosis->explanation];

        $fix = $this->diagnosis->migrationSuggestion['migration_suggestion'] ?? '';
        if ($fix !== '') {
            $parts[] = "Suggested fix:\n```sql\n{$fix}\n```";
        }

        $other = $this->diagnosis->migrationSuggestion['other_suggestions'] ?? '';
        if ($other !== '') {
            $parts[] = $other;
        }

        return implode("\n\n", $parts);
    }

    public function getDocumentationLinks(): array
    {
        return [];
    }
}
