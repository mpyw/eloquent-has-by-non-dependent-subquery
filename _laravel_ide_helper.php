<?php

namespace Illuminate\Database\Eloquent
{
    if (false) {
        class Builder
        {
            /**
             * Convert has() and whereHas() constraints to non-dependent subqueries.
             *
             * @param  string|string[]   $relationMethod
             * @param  callable[]|null[] $constraints
             * @return $this
             * @see \Mpyw\EloquentHasByNonDependentSubquery\HasByNonDependentSubqueryMacro
             */
            public function hasByNonDependentSubquery($relationMethod, ?callable ...$constraints)
            {
                return $this;
            }

            /**
             * Convert has() and whereHas() constraints to non-dependent subqueries.
             *
             * @param  string|string[]   $relationMethod
             * @param  callable[]|null[] $constraints
             * @return $this
             * @see \Mpyw\EloquentHasByNonDependentSubquery\HasByNonDependentSubqueryMacro
             */
            public function orHasByNonDependentSubquery($relationMethod, ?callable ...$constraints)
            {
                return $this;
            }

            /**
             * Convert has() and whereHas() constraints to non-dependent subqueries.
             *
             * @param  string|string[]   $relationMethod
             * @param  callable[]|null[] $constraints
             * @return $this
             * @see \Mpyw\EloquentHasByNonDependentSubquery\HasByNonDependentSubqueryMacro
             */
            public function doesntHaveByNonDependentSubquery($relationMethod, ?callable ...$constraints)
            {
                return $this;
            }

            /**
             * Convert has() and whereHas() constraints to non-dependent subqueries.
             *
             * @param  string|string[]   $relationMethod
             * @param  callable[]|null[] $constraints
             * @return $this
             * @see \Mpyw\EloquentHasByNonDependentSubquery\HasByNonDependentSubqueryMacro
             */
            public function orDoesntHaveByNonDependentSubquery($relationMethod, ?callable ...$constraints)
            {
                return $this;
            }
        }
    }
}
