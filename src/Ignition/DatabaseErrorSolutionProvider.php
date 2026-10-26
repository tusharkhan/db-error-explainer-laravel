<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Ignition;

use Spatie\ErrorSolutions\Contracts\HasSolutionsForThrowable;
use Throwable;
use TusharKhan\DbErrorExplainer\Laravel\DbExplainer;

class DatabaseErrorSolutionProvider implements HasSolutionsForThrowable
{
    private DbExplainer $explainer;

    // Ignition sometimes creates providers with `new $class()`, so fall back to the container
    public function __construct(?DbExplainer $explainer = null)
    {
        $this->explainer = $explainer ?? app(DbExplainer::class);
    }

    public function canSolve(Throwable $throwable): bool
    {
        return $this->explainer->canExplain($throwable);
    }

    public function getSolutions(Throwable $throwable): array
    {
        $diagnosis = $this->explainer->explain($throwable);

        // Nothing useful to add when no rule matched
        if ($diagnosis === null || ($diagnosis->metadata['rule_id'] ?? 'none') === 'none') {
            return [];
        }

        return [new DatabaseErrorSolution($diagnosis)];
    }
}
