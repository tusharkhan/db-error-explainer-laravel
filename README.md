# DB Error Explainer for Laravel

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Explains Laravel database errors in plain language, with a suggested fix, right on the error page.

Works with **MySQL / MariaDB, PostgreSQL and SQLite** connections.

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Optional: `spatie/laravel-ignition` to see explanations on the error page

## Installation

```bash
composer require tusharkhan/db-error-explainer-laravel
```

The service provider and the `DbExplainer` facade are auto-discovered.

## What you get

### On the Ignition error page

When a `QueryException` reaches the error page, Ignition shows a **"Database error explained"** solution with:

- what went wrong, using the table and column names from your schema
- a SQL fix when there is one
- a Laravel tip (e.g. `foreignId()->constrained()`, `Schema::defaultStringLength(191)`, `firstOrCreate()`)

Nothing to configure; it is enabled when Ignition is installed.

### In code

```php
use Illuminate\Database\QueryException;
use TusharKhan\DbErrorExplainer\Laravel\Facades\DbExplainer;

try {
    User::create(['email' => $email]);
} catch (QueryException $e) {
    $diagnosis = DbExplainer::explain($e);

    $diagnosis->errorType;      // "duplicate_entry"
    $diagnosis->explanation;    // "Duplicate entry: A record with the value ... already exists ..."
    $diagnosis->migrationSuggestion['migration_suggestion'];  // SQL fix, may be empty
    $diagnosis->migrationSuggestion['other_suggestions'];     // Laravel tip, may be empty
}
```

`explain()` uses the connection the exception came from. It returns `null` for non-database exceptions, unsupported drivers (e.g. `sqlsrv`) or when disabled. It never throws.

Connection failures (wrong password, server down) are explained too, without trying to reconnect.

### In your logs

Set `DB_EXPLAINER_LOG=true` and every reported `QueryException` also writes a `Database error diagnosed` entry with the error type and explanation.

## Configuration

```bash
php artisan vendor:publish --tag=db-explainer-config
```

| Key | Env | Default | |
|---|---|---|---|
| `enabled` | `DB_EXPLAINER_ENABLED` | `true` | Turn everything off |
| `allow_introspection` | `DB_EXPLAINER_INTROSPECTION` | `true` | Read-only schema lookups for more specific explanations |
| `redact_sensitive_data` | `DB_EXPLAINER_REDACT` | `true` | Hide `password`, `token`, `api_key`, ... values |
| `log` | `DB_EXPLAINER_LOG` | `false` | Log an explanation for each reported database error |
| `log_channel` | `DB_EXPLAINER_LOG_CHANNEL` | `null` | Channel for those entries (default channel when null) |
| `ignition` | `DB_EXPLAINER_IGNITION` | `true` | Add the solution to the Ignition error page |
| `custom_rules` | | `[]` | Your own rules, same array format as the built-in ones |

## License

MIT
