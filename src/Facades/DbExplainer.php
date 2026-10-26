<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \TusharKhan\DbErrorExplainer\DTO\Diagnosis|null explain(\Throwable $exception, ?string $connection = null)
 * @method static bool canExplain(\Throwable $exception)
 * @method static \TusharKhan\DbErrorExplainer\Explainer|null explainer(?string $connection = null)
 *
 * @see \TusharKhan\DbErrorExplainer\Laravel\DbExplainer
 */
class DbExplainer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TusharKhan\DbErrorExplainer\Laravel\DbExplainer::class;
    }
}
