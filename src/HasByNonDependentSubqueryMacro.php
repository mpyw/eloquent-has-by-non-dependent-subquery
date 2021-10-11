<?php

namespace Mpyw\EloquentHasByNonDependentSubquery;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionNamedType;
use ReflectionType;

/**
 * Class HasByNonDependentSubqueryMacro
 *
 * Convert has() and whereHas() constraints to non-dependent subqueries.
 */
class HasByNonDependentSubqueryMacro
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * HasByNonDependentSubqueryMacro constructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param  string|string[]                       $relationMethod
     * @param  callable[]|null[]                     $constraints
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function has($relationMethod, ?callable ...$constraints): Builder
    {
        return $this->apply($relationMethod, 'whereIn', ...$constraints);
    }

    /**
     * @param  string|string[]                       $relationMethod
     * @param  callable[]|null[]                     $constraints
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function orHas($relationMethod, ?callable ...$constraints): Builder
    {
        return $this->apply($relationMethod, 'orWhereIn', ...$constraints);
    }

    /**
     * @param  string|string[]                       $relationMethod
     * @param  callable[]|null[]                     $constraints
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function doesntHave($relationMethod, ?callable ...$constraints): Builder
    {
        return $this->apply($relationMethod, 'whereNotIn', ...$constraints);
    }

    /**
     * @param  string|string[]                       $relationMethod
     * @param  callable[]|null[]                     $constraints
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function orDoesntHave($relationMethod, ?callable ...$constraints): Builder
    {
        return $this->apply($relationMethod, 'orWhereNotIn', ...$constraints);
    }

    /**
     * Parse nested constraints and iterate them to apply.
     *
     * @param  string|string[]                       $relationMethod
     * @param  string                                $whereInMethod
     * @param  callable[]|null[]                     $constraints
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function apply($relationMethod, string $whereInMethod, ?callable ...$constraints): Builder
    {
        // Extract dot-chained expressions
        $relationMethods = \is_string($relationMethod) ? \explode('.', $relationMethod) : \array_values($relationMethod);

        // Pick the first relation if exists
        if ($currentRelationMethod = \array_shift($relationMethods)) {
            $this->applyForCurrentRelation(
                $currentRelationMethod,
                $whereInMethod,
                function (Relation $query) use ($relationMethods, $whereInMethod, $constraints) {
                    // Apply optional constraints
                    if ($currentConstraints = \array_shift($constraints)) {
                        $currentConstraints($this->adjustArgumentTypeOfOptionalConstraints($currentConstraints, $query));
                    }
                    // Apply relations nested under
                    if ($relationMethods) {
                        (new static($query->getQuery()))->apply($relationMethods, $whereInMethod, ...$constraints);
                    }
                }
            );
        }

        return $this->query;
    }

    /**
     * Apply the current relation as a non-dependent subquery.
     *
     * @param string        $relationMethod
     * @param string        $whereInMethod
     * @param null|callable $constraints
     */
    protected function applyForCurrentRelation(string $relationMethod, string $whereInMethod, callable $constraints): void
    {
        // Unlike a JOIN-based approach, you don't need give table aliases.
        // Table names are never conflicted.
        if (\preg_match('/\s+as\s+/i', $relationMethod)) {
            throw new DomainException('Table aliases are not supported.');
        }

        // Create a Relation instance
        $relation = $this->query->getRelation($relationMethod);

        // Validate the relation and recognize key names
        $keys = new Keys($relation);

        // Apply optional constraints and relations nested under
        $constraints($relation);

        // Add an "whereIn" constraints for a non-dependent subquery
        $relation->select($keys->getQualifiedRelatedKeyName());
        if ($keys->needsPolymorphicRelatedConstraints()) {
            $relation->where($keys->getQualifiedRelatedMorphType(), $keys->getRelatedMorphClass());
        }
        $this->query->{$whereInMethod}($keys->getQualifiedSourceKeyName(), $relation->getQuery());
    }

    /**
     * From v1.1:
     *   Relation will be automatically converted to Builder to prevent common mistakes on demand.
     *
     * @param  callable                                                                               $constraint
     * @param  \Illuminate\Database\Eloquent\Relations\Relation                                       $relation
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function adjustArgumentTypeOfOptionalConstraints(callable $constraint, Relation $relation)
    {
        $reflection = ReflectionCallable::from($constraint);

        return $reflection->getNumberOfParameters() > 0
            && ($parameter = $reflection->getParameters()[0])->hasType()
            && $this->mustExtractEloquentBuilder($parameter->getType())
                ? $relation->getQuery()
                : $relation;
    }

    /**
     * @param  \ReflectionNamedType|\ReflectionType|\ReflectionUnionType $type
     * @return bool
     */
    protected function mustExtractEloquentBuilder(ReflectionType $type): bool
    {
        /* @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        return $type instanceof \ReflectionUnionType
            ? $this->onlyIncludesBuilderType($type)
            : $this->namedTypeIs($type, Builder::class);
    }

    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

    /**
     * @param  \ReflectionUnionType $types
     * @return bool
     */
    protected function onlyIncludesBuilderType(\ReflectionUnionType $types): bool
    {
        $includesRelationType = false;
        $includesBuilderType = false;

        foreach ($types->getTypes() as $type) {
            $includesRelationType = $includesRelationType || $this->namedTypeIs($type, Relation::class);
            $includesBuilderType = $includesBuilderType || $this->namedTypeIs($type, Builder::class);
        }

        return !$includesRelationType && $includesBuilderType;
    }

    /**
     * @param  \ReflectionNamedType $type
     * @param  string               $class
     * @return bool
     */
    protected function namedTypeIs(ReflectionNamedType $type, string $class): bool
    {
        return \is_a($type->getName(), $class, true);
    }
}
