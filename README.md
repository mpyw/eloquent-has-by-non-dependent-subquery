# Eloquent Has By Non-dependent Subquery [![Build Status](https://github.com/mpyw/eloquent-has-by-non-dependent-subquery/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/mpyw/eloquent-has-by-non-dependent-subquery/actions) [![Coverage Status](https://coveralls.io/repos/github/mpyw/eloquent-has-by-non-dependent-subquery/badge.svg?branch=master)](https://coveralls.io/github/mpyw/eloquent-has-by-non-dependent-subquery?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpyw/eloquent-has-by-non-dependent-subquery/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpyw/eloquent-has-by-non-dependent-subquery/?branch=master)

Convert `has()` and `whereHas()` constraints to non-dependent subqueries.

**NOTICE: Postgres' optimizer is very smart and covers JOIN optimization for dependent (correlated) subqueries. Therefore, this library is mainly targeted at MySQL which has a poor optimizer.**

## Requirements

- PHP: `^7.3 || ^8.0`
- Laravel: `^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0`

## Installing

```bash
composer require mpyw/eloquent-has-by-non-dependent-subquery
```

#### Suggestion

You can install **[wimski/laravel-ide-helper-hook-eloquent-has-by-non-dependent-subquery](https://github.com/wimski/laravel-ide-helper-hook-eloquent-has-by-non-dependent-subquery)** to work with [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper).

## Motivation

Suppose you have the following relationship:

```php
class Post extends Model
{
    use SoftDeletes;

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```


```php
class Comment extends Model
{
    use SoftDeletes;
}
```

If you use `has()` constraints, your actual query would have **dependent subqueries**.

```php
$posts = Post::has('comments')->get();
```

```sql
select * from `posts` where exists (
  select * from `comments`
  where `posts`.`id` = `comments`.`post_id`
    and `comments`.`deleted_at` is null
) and `posts`.`deleted_at` is null
``` 

These subqueries may cause performance degradations.
This package provides **`Illuminate\Database\Eloquent\Builder::hasByNonDependentSubquery()`** macro to solve this problem:
you can easily transform dependent subqueries into non-dependent ones.

```php
$posts = Post::hasByNonDependentSubquery('comments')->get();
```

```sql
select * from `posts`
where `posts`.`id` in (
  select `comments`.`post_id` from `comments`
  where `comments`.`deleted_at` is null
)
and `posts`.`deleted_at` is null
```

## API

### Signature

```php
Illuminate\Database\Eloquent\Builder::hasByNonDependentSubquery(string|string[] $relationMethod, ?callable ...$constraints): $this
```

```php
Illuminate\Database\Eloquent\Builder::orHasByNonDependentSubquery(string|string[] $relationMethod, ?callable ...$constraints): $this
```

```php
Illuminate\Database\Eloquent\Builder::doesntHaveByNonDependentSubquery(string|string[] $relationMethod, ?callable ...$constraints): $this
```

```php
Illuminate\Database\Eloquent\Builder::orDoesntHaveByNonDependentSubquery(string|string[] $relationMethod, ?callable ...$constraints): $this
```

### Arguments

#### `$relationMethod`

A relation method name that returns a **`Relation`** instance except `MorphTo`.

```php
Builder::hasByNonDependentSubquery('comments')
```

You can pass nested relations as an array or a string with dot-chain syntax. 

```php
Builder::hasByNonDependentSubquery(['comments', 'author'])
```

```php
Builder::hasByNonDependentSubquery('comments.author')
```

#### `$constraints`

Additional `callable` constraints for relations that take **`Illuminate\Database\Eloquent\Relation`** as the first argument.

```php
Builder::hasByNonDependentSubquery('comments', fn (HasMany $query) => $query->withTrashed())
```

If you are using a union type as of PHP 8.0, the order of types does not matter.

```php
// This will work
Builder::hasByNonDependentSubquery('comments', fn (HasMany|Comment $query) => $query->withTrashed())
// and so will this
Builder::hasByNonDependentSubquery('comments', fn (Comment|HasMany $query) => $query->withTrashed())
```

The first closure corresponds to `comments` and the second one corresponds to `author`.

```php
Builder::hasByNonDependentSubquery(
    'comments.author',
    fn (HasMany $query) => $query->withTrashed(),
    fn (BelongsTo $query) => $query->whereKey(123)
)
```

## Feature Comparison

| Feature | [`mpyw/eloquent-has-by-join`](https://github.com/mpyw/eloquent-has-by-join) | `mpyw/eloquent-has-by-non-dependent-subquery` |
|:----|:---:|:---:|
| Minimum Laravel version | 5.6 | 5.8 |
| Argument of optional constraints | `Illuminate\Database\Eloquent\Builder` | `Illuminate\Database\Eloquent\Relations\*`<br>(`Builder` can be also accepted by specifying argument type) |
| [Compoships](https://github.com/topclaudy/compoships) support | ✅ | ❌ |
| No subqueries | ✅ | ❌<br>(Performance depends on database optimizers) |
| No table collisions | ❌<br>(Sometimes you need to give aliases) | ✅ |
| No column collisions | ❌<br>(Sometimes you need to use qualified column names) | ✅ |
| OR conditions | ❌ | ✅ |
| Negative conditions | ❌ | ✅ |
| Counting conditions | ❌ | ❌ |
| `HasOne` | ✅ | ✅ |
| `HasMany` | ❌ | ✅ |
| `BelongsTo` | ✅ | ✅ |
| `BelongsToMany` | ❌ | ✅ |
| `MorphOne` | ✅ | ✅ |
| `MorphMany` | ❌ | ✅ |
| `MorphTo` | ❌ | ❌ |
| `MorphMany` | ❌ | ✅ |
| `MorphToMany` | ❌ | ✅ |
| `HasOneThrough` | ❌ | ✅ |
| `HasManyThrough` | ❌ | ✅ |
