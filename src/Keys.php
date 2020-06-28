<?php

namespace Mpyw\EloquentHasByNonDependentSubquery;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class Keys
 */
class Keys
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * @var string
     */
    protected $sourceKeyName;

    /**
     * @var string
     */
    protected $relatedKeyName;

    /**
     * @var null|string
     */
    protected $relatedMorphType;

    /**
     * @var null|string
     */
    protected $relatedMorphClass;

    /**
     * Keys constructor.
     *
     * @param Relation $relation
     */
    public function __construct(Relation $relation)
    {
        if ($relation instanceof HasOneOrMany) {
            $this->sourceKeyName = $this->ensureString($relation->getQualifiedParentKeyName());
            $this->relatedKeyName = $this->ensureString($relation->getQualifiedForeignKeyName());

            if ($relation instanceof MorphOneOrMany) {
                $this->relatedMorphType = $relation->getQualifiedMorphType();
                $this->relatedMorphClass = $relation->getMorphClass();
            }

            return;
        }

        if ($relation instanceof BelongsTo && !$relation instanceof MorphTo) {
            $this->sourceKeyName = $this->ensureString($relation->getQualifiedForeignKeyName());
            $this->relatedKeyName = $this->ensureString($relation->getQualifiedOwnerKeyName());
            return;
        }

        if ($relation instanceof BelongsToMany) {
            $this->sourceKeyName = $this->ensureString($relation->getQualifiedParentKeyName());
            $this->relatedKeyName = $this->ensureString($relation->getQualifiedForeignPivotKeyName());

            if ($relation instanceof MorphToMany) {
                $this->relatedMorphType = "{$relation->getTable()}.{$relation->getMorphType()}";
                $this->relatedMorphClass = $relation->getMorphClass();
            }

            return;
        }

        if ($relation instanceof HasManyThrough) {
            $this->sourceKeyName = $this->ensureString($relation->getQualifiedLocalKeyName());
            $this->relatedKeyName = $this->ensureString($relation->getQualifiedFirstKeyName());
            return;
        }

        throw new DomainException('Unsupported relation. Currently supported: HasOne, HasMany, BelongsTo, BelongsToMany, HasManyThrough, HasOneThrough, MorphOne, MorphMany and MorphToMany');
    }

    /**
     * Ensure the provided key is single.
     *
     * @param  mixed  $key
     * @return string
     */
    protected function ensureString($key): string
    {
        if (is_array($key)) {
            throw new DomainException('Multi-column relationships are not supported.');
        }

        return (string)$key;
    }

    /**
     * @return string
     */
    public function getQualifiedSourceKeyName(): string
    {
        return $this->sourceKeyName;
    }

    /**
     * @return string
     */
    public function getQualifiedRelatedKeyName(): string
    {
        return $this->relatedKeyName;
    }

    /**
     * @return null|string
     */
    public function getQualifiedRelatedMorphType(): ?string
    {
        return $this->relatedMorphType;
    }

    /**
     * @return null|string
     */
    public function getRelatedMorphClass(): ?string
    {
        return $this->relatedMorphClass;
    }

    /**
     * @return bool
     */
    public function needsPolymorphicRelatedConstraints(): bool
    {
        return (bool)$this->relatedMorphClass;
    }
}
