<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use TusharKhan\DbErrorExplainer\Laravel\Tests\TestCase;

class LoggingTest extends TestCase
{
    private string $logFile;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->logFile = tempnam(sys_get_temp_dir(), 'dbx-log');
        $app['config']->set('logging.channels.dbx', ['driver' => 'single', 'path' => $this->logFile]);
        $app['config']->set('db-explainer.log', true);
        $app['config']->set('db-explainer.log_channel', 'dbx');
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
        parent::tearDown();
    }

    public function test_reported_query_exception_is_logged_with_its_explanation()
    {
        try {
            DB::table('orders')->get();
        } catch (QueryException $e) {
            app(ExceptionHandler::class)->report($e);
        }

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('Database error diagnosed', $log);
        $this->assertStringContainsString('table_not_found', $log);
    }
}
