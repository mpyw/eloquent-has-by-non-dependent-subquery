<?php

namespace Mpyw\EloquentHasByNonDependentSubquery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class EloquentHasByNonDependentSubqueryServiceProvider extends ServiceProvider
{
    /**
     * Register Eloquent\Builder::hasByNonDependentSubquery() macros.
     */
    public function boot(): void
    {
        Builder::macro('hasByNonDependentSubquery', function (...$args) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->has(...$args);
        });
        Builder::macro('orHasByNonDependentSubquery', function (...$args) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->orHas(...$args);
        });
        Builder::macro('doesntHaveByNonDependentSubquery', function (...$args) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->doesntHave(...$args);
        });
        Builder::macro('orDoesntHaveByNonDependentSubquery', function (...$args) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->orDoesntHave(...$args);
        });
    }
}
