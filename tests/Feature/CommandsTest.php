<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use TusharKhan\DbErrorExplainer\Laravel\Tests\TestCase;

class CommandsTest extends TestCase
{
    private string $migrations;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('items', fn(Blueprint $table) => $table->id());
        $this->migrations = sys_get_temp_dir() . '/dbx-migrations-' . uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->migrations);
        parent::tearDown();
    }

    public function test_explain_prints_the_explanation_and_fix()
    {
        $this->artisan('db:explain', ['error' => 'SQLSTATE[HY000]: General error: 1 table items has no column named nope (Connection: testing, SQL: insert into "items" ("nope") values (1))'])
            ->expectsOutputToContain('unknown_column')
            ->expectsOutputToContain('Unknown column: "nope" does not exist')
            ->expectsOutputToContain('ALTER TABLE "items" ADD COLUMN "nope" TEXT;')
            ->assertSuccessful();
    }

    public function test_no_migration_hint_for_diagnostic_queries()
    {
        $this->artisan('db:explain', ['error' => 'SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed'])
            ->expectsOutputToContain('PRAGMA foreign_key_check;')
            ->doesntExpectOutputToContain('db:fix-stub')
            ->assertSuccessful();
    }

    public function test_explain_as_json()
    {
        $this->artisan('db:explain', ['error' => 'SQLSTATE[HY000]: General error: 1 no such table: posts', '--json' => true])
            ->expectsOutputToContain('"error_type": "table_not_found"')
            ->assertSuccessful();
    }

    public function test_unmatched_error_fails()
    {
        $this->artisan('db:explain', ['error' => 'something that is not a database error'])
            ->expectsOutputToContain('No rule matched')
            ->assertFailed();
    }

    public function test_unsupported_connection_fails()
    {
        config(['database.connections.mssql' => ['driver' => 'sqlsrv', 'host' => '127.0.0.1', 'database' => 'x']]);

        $this->artisan('db:explain', ['error' => 'anything', '--connection' => 'mssql', '--no-schema' => true])
            ->expectsOutputToContain("Can't explain errors for the [mssql] connection")
            ->assertFailed();
    }

    public function test_explains_errors_from_another_database_with_connection_option()
    {
        config(['database.connections.pg' => ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x']]);

        $this->artisan('db:explain', [
            'error' => "SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"users_email_key\"\nDETAIL:  Key (email)=(a@b.c) already exists.",
            '--connection' => 'pg',
            '--no-schema' => true,
        ])
            ->expectsOutputToContain('23505')
            ->expectsOutputToContain('A row with email = "a@b.c" already exists')
            ->assertSuccessful();
    }

    public function test_fix_stub_writes_a_valid_migration()
    {
        $this->artisan('db:fix-stub', ['error' => 'SQLSTATE[HY000]: General error: 1 table items has no column named nope', '--path' => $this->migrations])
            ->expectsOutputToContain('Migration created')
            ->assertSuccessful();

        $files = File::files($this->migrations);
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('_fix_unknown_column.php', $files[0]->getFilename());

        $migration = require $files[0]->getPathname();
        $this->assertInstanceOf(Migration::class, $migration);

        // Running it really adds the column
        $migration->up();
        $this->assertTrue(Schema::hasColumn('items', 'nope'));
    }

    public function test_fix_stub_warns_about_alternatives_and_placeholders()
    {
        $this->artisan('db:fix-stub', ['error' => 'SQLSTATE[HY000]: General error: 1 no such index: idx_gone', '--path' => $this->migrations])
            ->assertSuccessful();

        $this->artisan('db:fix-stub', ['error' => 'SQLSTATE[HY000]: General error: 1 no such column: nope', '--path' => $this->migrations])
            ->expectsOutputToContain('Replace the placeholders before migrating: your_table')
            ->assertSuccessful();
    }

    public function test_fix_stub_without_sql_fix_fails()
    {
        $this->artisan('db:fix-stub', ['error' => 'SQLSTATE[HY000]: General error: 1 no such table: posts', '--path' => $this->migrations])
            ->expectsOutputToContain('There is no SQL fix for table_not_found')
            ->assertFailed();

        $this->assertDirectoryDoesNotExist($this->migrations);
    }
}
