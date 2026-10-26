<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Spatie\ErrorSolutions\Contracts\SolutionProviderRepository;
use TusharKhan\DbErrorExplainer\Laravel\Ignition\DatabaseErrorSolution;
use TusharKhan\DbErrorExplainer\Laravel\Ignition\DatabaseErrorSolutionProvider;
use TusharKhan\DbErrorExplainer\Laravel\Tests\TestCase;

class IgnitionSolutionTest extends TestCase
{
    public function test_provider_is_registered_with_ignition()
    {
        $this->assertContains(DatabaseErrorSolutionProvider::class, config('ignition.solution_providers'));
    }

    public function test_ignition_shows_the_explanation_as_a_solution()
    {
        $solutions = app(SolutionProviderRepository::class)->getSolutionsForThrowable($this->missingTable());
        $ours = array_values(array_filter($solutions, fn($s) => $s instanceof DatabaseErrorSolution));

        $this->assertCount(1, $ours);
        $this->assertSame('Database error explained: table not found', $ours[0]->getSolutionTitle());
        $this->assertStringContainsString('"orders" does not exist', $ours[0]->getSolutionDescription());
    }

    public function test_error_page_shows_the_solution()
    {
        config(['app.debug' => true]);
        Route::get('/broken', fn() => DB::table('orders')->get());

        $this->get('/broken')
            ->assertStatus(500)
            ->assertSee('Database error explained: table not found');
    }

    public function test_no_solution_for_other_exceptions()
    {
        $this->assertFalse(app(DatabaseErrorSolutionProvider::class)->canSolve(new RuntimeException('nope')));
    }

    private function missingTable(): QueryException
    {
        try {
            DB::table('orders')->get();
        } catch (QueryException $e) {
            return $e;
        }

        $this->fail('Expected a QueryException');
    }
}
