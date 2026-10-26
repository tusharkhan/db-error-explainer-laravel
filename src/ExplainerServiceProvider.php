<?php

namespace TusharKhan\DbErrorExplainer\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;
use Spatie\ErrorSolutions\Contracts\SolutionProviderRepository;
use TusharKhan\DbErrorExplainer\Laravel\Ignition\DatabaseErrorSolutionProvider;

class ExplainerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/db-explainer.php', 'db-explainer');

        $this->app->singleton(DbExplainer::class, fn($app) => new DbExplainer(
            $app['db'],
            $app['config']['db-explainer'],
            $app['config']['db-explainer.log'] ? $app['log']->channel($app['config']['db-explainer.log_channel']) : null,
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/db-explainer.php' => config_path('db-explainer.php'),
        ], 'db-explainer-config');

        if (config('db-explainer.enabled') && config('db-explainer.ignition')) {
            $this->registerIgnitionSolutions();
        }

        if (config('db-explainer.enabled') && config('db-explainer.log')) {
            // The explainer logs each diagnosis itself; reporting continues as normal
            $this->callAfterResolving(ExceptionHandler::class, function ($handler) {
                $handler->reportable(fn(QueryException $e) => $this->app->make(DbExplainer::class)->explain($e));
            });
        }
    }

    private function registerIgnitionSolutions(): void
    {
        if (!interface_exists(SolutionProviderRepository::class)) {
            return;
        }

        // Ignition builds one repository eagerly and another lazily from config, so feed both
        config(['ignition.solution_providers' => array_values(array_unique([
            ...config('ignition.solution_providers', []),
            DatabaseErrorSolutionProvider::class,
        ]))]);

        $this->callAfterResolving(SolutionProviderRepository::class, function (SolutionProviderRepository $repository) {
            $repository->registerSolutionProvider(DatabaseErrorSolutionProvider::class);
        });
    }
}
