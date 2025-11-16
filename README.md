# DB Error Explainer for Laravel

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel integration for DB Error Explainer - beautiful Ignition panels, service providers, and artisan commands.

## Features

- 🎨 **Ignition Integration**: Beautiful error panels in Laravel's exception page
- ⚡ **Auto-Registration**: Service provider auto-discovers and registers
- 🛠️ **Artisan Commands**: Generate migration stubs from errors
- ⚙️ **Configurable**: Publish and customize configuration
- 📝 **Query Exception Handling**: Automatically catches and explains database errors

## Requirements

- PHP ^8.2
- Laravel ^11.0
- Spatie Laravel Ignition ^2.0|^3.0

## Installation

```bash
composer require tusharkhan/db-error-explainer-laravel
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=db-explainer-config
```

This creates `config/db-explainer.php`:

```php
return [
    'enabled' => env('DB_EXPLAINER_ENABLED', true),
    'allow_introspection' => env('DB_EXPLAINER_INTROSPECTION', true),
    'redact_sensitive' => env('DB_EXPLAINER_REDACT', true),
    'log_errors' => env('DB_EXPLAINER_LOG', false),
    'custom_rules' => [],
];
```

## Usage

### Automatic Error Explanation

Once installed, the package automatically intercepts `QueryException` errors and displays explanations in Ignition:

```php
// This error will be automatically explained
User::create([
    'name' => 'John',
    'email' => null, // NOT NULL violation
]);
```

### Generate Migration Stub

When you encounter an error, use the artisan command to generate a fix:

```bash
php artisan db:explain-error
```

This will show recent errors and generate migration stubs.

### Programmatic Usage

```php
use TusharKhan\DbErrorExplainer\Laravel\Facades\DbExplainer;

try {
    // Database operation
} catch (\Illuminate\Database\QueryException $e) {
    $diagnosis = DbExplainer::explain($e);
    
    Log::info($diagnosis->explanation);
    
    // Generate migration file
    if ($diagnosis->migrationStub) {
        File::put(
            database_path('migrations/' . date('Y_m_d_His') . '_fix_error.php'),
            $diagnosis->migrationStub
        );
    }
}
```

## Ignition Panel

The package adds a custom "Database Error Diagnosis" panel to Ignition showing:

- 🔴 **Error Classification**: Type of database error
- 📝 **Human Explanation**: What went wrong in plain English
- 🛠️ **Suggested Fix**: Migration code using Blueprint syntax
- 📊 **Schema Context**: Related table/column information

## Artisan Commands

### `db:explain-error`

Interactively explains database errors and generates migration stubs.

```bash
php artisan db:explain-error --last
```

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `enabled` | `true` | Enable/disable the explainer |
| `allow_introspection` | `true` | Allow schema introspection queries |
| `redact_sensitive` | `true` | Redact sensitive data from logs |
| `log_errors` | `false` | Log all explained errors |
| `custom_rules` | `[]` | Add your own error rules |

## Custom Rules

Add custom rules in `config/db-explainer.php`:

```php
'custom_rules' => [
    [
        'id' => 'my_custom_rule',
        'error_code' => [1234],
        'message_regex' => '/my pattern/',
        'explanation' => 'Custom explanation',
        'migration' => fn($ctx) => "// Custom migration",
    ],
],
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed by [Tushar Khan](https://github.com/tusharkhan)

