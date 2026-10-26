<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TusharKhan\DbErrorExplainer\Laravel\Support\PastedError;

class PastedErrorTest extends TestCase
{
    public static function messages(): array
    {
        return [
            'laravel log line' => [
                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'a' for key 'users.users_email_unique' (Connection: mysql, SQL: insert into `users` (`email`) values (a))",
                ['23000', 1062, "Duplicate entry 'a' for key 'users.users_email_unique'"],
            ],
            'pdo query error' => [
                'SQLSTATE[HY000]: General error: 1 no such table: posts',
                ['HY000', 1, 'no such table: posts'],
            ],
            'pdo connection error' => [
                'SQLSTATE[HY000] [2002] Connection refused',
                ['HY000', 2002, 'Connection refused'],
            ],
            'postgres multi-line' => [
                "SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"users_email_key\"\nDETAIL:  Key (email)=(a) already exists.",
                ['23505', 7, "ERROR:  duplicate key value violates unique constraint \"users_email_key\"\nDETAIL:  Key (email)=(a) already exists."],
            ],
            'bare message' => [
                "Duplicate entry 'a' for key 'email'",
                ['HY000', 0, "Duplicate entry 'a' for key 'email'"],
            ],
        ];
    }

    #[DataProvider('messages')]
    public function test_error_info_is_rebuilt(string $text, array $errorInfo)
    {
        $this->assertSame($errorInfo, PastedError::toException($text)->errorInfo);
    }

    public function test_laravel_suffix_is_removed_from_the_message()
    {
        $e = PastedError::toException('SQLSTATE[HY000]: General error: 1 no such table: posts (Connection: sqlite, SQL: select * from "posts")');

        $this->assertSame('SQLSTATE[HY000]: General error: 1 no such table: posts', $e->getMessage());
    }

    public function test_options_fill_in_what_the_message_lacks()
    {
        $this->assertSame(['23000', 1062, "Duplicate entry 'a' for key 'email'"], PastedError::toException("Duplicate entry 'a' for key 'email'", '23000', 1062)->errorInfo);
    }
}
