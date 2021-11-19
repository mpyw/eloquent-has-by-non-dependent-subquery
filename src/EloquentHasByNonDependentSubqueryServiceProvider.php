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
        Builder::macro('hasByNonDependentSubquery', function ($relationMethod, ?callable ...$constraints): Builder {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->has($relationMethod, ...$constraints);
        });
        Builder::macro('orHasByNonDependentSubquery', function ($relationMethod, ?callable ...$constraints): Builder {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->orHas($relationMethod, ...$constraints);
        });
        Builder::macro('doesntHaveByNonDependentSubquery', function ($relationMethod, ?callable ...$constraints): Builder {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->doesntHave($relationMethod, ...$constraints);
        });
        Builder::macro('orDoesntHaveByNonDependentSubquery', function ($relationMethod, ?callable ...$constraints): Builder {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $this;
            return (new HasByNonDependentSubqueryMacro($query))->orDoesntHave($relationMethod, ...$constraints);
        });
    }
}
