<?php

return [
    // Turn explanations off without uninstalling the package
    'enabled' => env('DB_EXPLAINER_ENABLED', true),

    // Read-only schema lookups (columns, keys) to make explanations specific
    'allow_introspection' => env('DB_EXPLAINER_INTROSPECTION', true),

    // Hide values of keys like password / token / api_key in the diagnosis context
    'redact_sensitive_data' => env('DB_EXPLAINER_REDACT', true),

    // Write a log entry with the explanation whenever a database error is reported
    'log' => env('DB_EXPLAINER_LOG', false),

    // Log channel for those entries; null uses the default channel
    'log_channel' => env('DB_EXPLAINER_LOG_CHANNEL'),

    // Show the explanation and fix as a solution on the Ignition error page (needs spatie/laravel-ignition)
    'ignition' => env('DB_EXPLAINER_IGNITION', true),

    // Extra rules in the same array format as the built-in ones, checked after them
    'custom_rules' => [],
];
