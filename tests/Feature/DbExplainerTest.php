<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use TusharKhan\DbErrorExplainer\Laravel\DbExplainer;
use TusharKhan\DbErrorExplainer\Laravel\Facades\DbExplainer as DbExplainerFacade;
use TusharKhan\DbErrorExplainer\Laravel\Tests\TestCase;
use TusharKhan\DbErrorExplainer\Lib\RuleIds;

class DbExplainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });
        DB::table('users')->insert(['email' => 'a@b.c']);
    }

    public function test_config_is_merged()
    {
        $this->assertTrue(config('db-explainer.enabled'));
        $this->assertSame([], config('db-explainer.custom_rules'));
    }

    public function test_explains_a_query_exception_from_the_connection_it_came_from()
    {
        $diagnosis = DbExplainerFacade::explain($this->duplicateEmail());

        $this->assertSame(RuleIds::DUPLICATE_ENTRY, $diagnosis->metadata['rule_id']);
        $this->assertSame('sqlite', $diagnosis->metadata['driver']);
        $this->assertSame('users', $diagnosis->context['table']);
        $this->assertSame(['id', 'email'], $diagnosis->context['table_columns']);
    }

    public function test_service_is_a_singleton()
    {
        $this->assertSame(app(DbExplainer::class), app(DbExplainer::class));
    }

    public function test_non_database_exception_is_ignored()
    {
        $this->assertNull(DbExplainerFacade::explain(new RuntimeException('nope')));
        $this->assertFalse(DbExplainerFacade::canExplain(new RuntimeException('nope')));
    }

    public function test_disabled_returns_null()
    {
        config(['db-explainer.enabled' => false]);
        $this->app->forgetInstance(DbExplainer::class);

        $this->assertNull(DbExplainerFacade::explain($this->duplicateEmail()));
    }

    public function test_unsupported_connection_is_skipped_without_connecting()
    {
        config(['database.connections.mssql' => ['driver' => 'sqlsrv', 'host' => '127.0.0.1', 'database' => 'x']]);

        $this->assertNull(app(DbExplainer::class)->explainer('mssql'));
    }

    public function test_unknown_connection_name_returns_null()
    {
        $this->assertNull(app(DbExplainer::class)->explainer('does_not_exist'));
    }

    public function test_connection_failure_is_explained_without_a_pdo()
    {
        config(['database.connections.broken' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x', 'password' => 'x',
        ]]);

        try {
            DB::connection('broken')->select('select 1');
            $this->fail('Expected a QueryException');
        } catch (QueryException $e) {
            $diagnosis = DbExplainerFacade::explain($e);

            $this->assertSame(RuleIds::CONNECTION_REFUSED, $diagnosis->metadata['rule_id']);
            $this->assertSame('mysql', $diagnosis->metadata['driver']);
        }
    }

    private function duplicateEmail(): QueryException
    {
        try {
            DB::table('users')->insert(['email' => 'a@b.c']);
        } catch (QueryException $e) {
            return $e;
        }

        $this->fail('Expected a QueryException');
    }
}
