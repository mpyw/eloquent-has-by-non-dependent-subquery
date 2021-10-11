<?php

namespace Mpyw\EloquentHasByNonDependentSubquery\Tests;

use DateTime;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Mpyw\EloquentHasByNonDependentSubquery\EloquentHasByNonDependentSubqueryServiceProvider;
use Mpyw\EloquentHasByNonDependentSubquery\ReflectionCallable;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Category;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Comment;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Post;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Tag;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\User;
use NilPortugues\Sql\QueryFormatter\Formatter;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionFunction;
use ReflectionMethod;

class Test extends BaseTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            EloquentHasByNonDependentSubqueryServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        config(['database.default' => 'testing']);

        Relation::morphMap([
            'user' => User::class,
            'post' => Post::class,
            'comment' => Comment::class,
            'category' => Category::class,
            'tag' => Tag::class,
        ]);
    }

    /**
     * @param string                                $expectedSql
     * @param \Illuminate\Database\Eloquent\Builder $actualQuery
     */
    protected function assertQueryEquals(string $expectedSql, $actualQuery): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            $formatter->format($expectedSql),
            $formatter->format(Str::replaceArray(
                '?',
                array_map(
                    function ($v) {
                        return is_string($v)
                            ? sprintf("'%s'", addcslashes($v, "\\'"))
                            : (int)$v;
                    },
                    (clone $actualQuery)->getBindings()
                ),
                (clone $actualQuery)->toSql()
            ))
        );
    }

    public function testCommentsHavingPost(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."deleted_at" is null
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()->hasByNonDependentSubquery('post')
        );
    }

    public function testCommentsOnlyTrashedHavingPostWithTrashed(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."deleted_at" is not null
                )
EOD
            ,
            Comment::query()->hasByNonDependentSubquery(
                'post',
                function (BelongsTo $query) {
                    $query->onlyTrashed();
                }
            )->withTrashed()
        );
    }

    public function testCommentsHavingAuthorFromPostInstance(): void
    {
        $post = new Post();
        $post->forceFill([
            'id' => 123,
            'author_id' => 456,
            'deleted_at' => null,
        ])->syncOriginal()->exists = true;

        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" = 123
            and "comments"."post_id" is not null
            and "comments"."author_id" in (
                    select
                        "users"."id"
                    from
                        "users"
                )
            and "comments"."deleted_at" is null
EOD
            ,
            $post->comments()->hasByNonDependentSubquery('author')
        );
    }

    public function testCommentsHavingAuthor(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."author_id" in (
                    select
                        "users"."id"
                    from
                        "users"
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()->hasByNonDependentSubquery('author')
        );
    }

    public function testCommentsHavingPostAuthor(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."author_id" in (
                            select
                                "users"."id"
                            from
                                "users"
                        )
                    and "posts"."deleted_at" is null
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()->hasByNonDependentSubquery('post.author')
        );
    }

    public function testCommentsHavingPostAuthorUsingCustomConstraints(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."author_id" in (
                            select
                                "users"."id"
                            from
                                "users"
                            where
                                "users"."id" = 999
                        )
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()->hasByNonDependentSubquery(
                'post.author',
                function (BelongsTo $query) {
                    $query->withTrashed();
                },
                function (BelongsTo $query) {
                    $query->whereKey(999);
                }
            )
        );
    }

    public function testCommentsHavingPostAuthorAndHavingCommentAuthorUsingTableAliases(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."author_id" in (
                            select
                                "users"."id"
                            from
                                "users"
                        )
                    and "posts"."deleted_at" is null
                )
            and "comments"."author_id" in (
                    select
                        "users"."id"
                    from
                        "users"
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()
                ->hasByNonDependentSubquery('post.author')
                ->hasByNonDependentSubquery('author')
        );
    }

    public function testUsersHavingPinnedPost(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "posts"
                    where
                        "posts"."pinned" = 1
                    and "posts"."deleted_at" is null
                )
EOD
            ,
            User::query()->hasByNonDependentSubquery('pinnedPost')
        );
    }

    public function testUsersHavingPinnedPostInGeneralCategory(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "posts"
                        inner join
                            "categories"
                        on  "posts"."category_id" = "categories"."id"
                        and "categories"."slug" = 'general'
                    where
                        "posts"."pinned" = 1
                    and "posts"."deleted_at" is null
                )
EOD
            ,
            User::query()->hasByNonDependentSubquery('pinnedPostInGeneralCategory')
        );
    }

    public function testPostHavingComments(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "posts"
            where
                "posts"."id" in (
                    select
                        "comments"."post_id"
                    from
                        "comments"
                    where
                        "comments"."deleted_at" is null
                )
            and "posts"."deleted_at" is null
EOD
            ,
            Post::query()->hasByNonDependentSubquery('comments')
        );
    }

    public function testPostHavingTags(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "posts"
            where
                "posts"."id" in (
                    select
                        "tag_references"."post_id"
                    from
                        "tags"
                        inner join
                            "tag_references"
                        on  "tags"."id" = "tag_references"."tag_id"
                    where
                        "tags"."deleted_at" is null
                )
            and "posts"."deleted_at" is null
EOD
            ,
            Post::query()->hasByNonDependentSubquery('tags')
        );
    }

    public function testPostHavingSpecificTags(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "posts"
            where
                "posts"."id" in (
                    select
                        "tag_references"."post_id"
                    from
                        "tags"
                        inner join
                            "tag_references"
                        on  "tags"."id" = "tag_references"."tag_id"
                    where
                        "tag_references"."type" in (1, 2, 3)
                        and "tags"."slug" = 'something'
                        and "tags"."deleted_at" is null
                )
            and "posts"."deleted_at" is null
EOD
            ,
            Post::query()->hasByNonDependentSubquery(
                'tags',
                function (BelongsToMany $query) {
                    $query->wherePivotIn('type', [1, 2, 3]);
                    $query->where('tags.slug', 'something');
                }
            )
        );
    }

    public function testUserHavingReceivedComments(): void
    {
        if (version_compare($this->app->version(), '7', '<')) {
            $query = <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "comments"
                        inner join
                            "posts"
                        on  "posts"."id" = "comments"."post_id"
                    where
                        "posts"."deleted_at" is null
                    and "comments"."deleted_at" is null
                )
EOD;
        } else {
            $query = <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "comments"
                        inner join
                            "posts"
                        on  "posts"."id" = "comments"."post_id"
                    where
                        "comments"."deleted_at" is null
                    and "posts"."deleted_at" is null
                )
EOD;
        }

        $this->assertQueryEquals($query, User::query()->hasByNonDependentSubquery('receivedComments'));
    }

    public function testUserHavingSelfRepliedComments(): void
    {
        if (version_compare($this->app->version(), '7', '<')) {
            $query = <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "comments"
                        inner join
                            "posts"
                        on  "posts"."id" = "comments"."post_id"
                    where
                        "posts"."deleted_at" is null
                    and "posts"."user_id" = "comments"."user_id"
                    and "comments"."deleted_at" is null
                )
EOD;
        } else {
            $query = <<<'EOD'
            select
                *
            from
                "users"
            where
                "users"."id" in (
                    select
                        "posts"."user_id"
                    from
                        "comments"
                        inner join
                            "posts"
                        on  "posts"."id" = "comments"."post_id"
                    where
                        "posts"."user_id" = "comments"."user_id"
                    and "comments"."deleted_at" is null
                    and "posts"."deleted_at" is null
                )
EOD;
        }

        $this->assertQueryEquals(
            $query,
            User::query()->hasByNonDependentSubquery(
                'receivedComments',
                function (HasManyThrough $query) {
                    $query->whereColumn('posts.user_id', 'comments.user_id');
                }
            )
        );
    }

    public function testPostHavingPolymorphicComments(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "posts"
            where
                "posts"."id" in (
                    select
                        "comments"."commentable_id"
                    from
                        "comments"
                    where
                        "comments"."commentable_type" = 'post'
                    and "comments"."deleted_at" is null
                )
            and "posts"."deleted_at" is null
EOD
            ,
            Post::query()->hasByNonDependentSubquery('polymorphicComments')
        );
    }

    public function testPostHavingPolymorphicTags(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "posts"
            where
                "posts"."id" in (
                    select
                        "tag_references"."taggable_id"
                    from
                        "tags"
                        inner join
                            "tag_references"
                        on  "tags"."id" = "tag_references"."tag_id"
                    where
                        "tag_references"."taggable_type" = 'post'
                    and "tags"."deleted_at" is null
                )
            and "posts"."deleted_at" is null
EOD
            ,
            Post::query()->hasByNonDependentSubquery('polymorphicTags')
        );
    }

    public function testPolymorphicTagHavingPosts(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "tags"
            where
                "tags"."id" in (
                    select
                        "tag_references"."tag_id"
                    from
                        "posts"
                        inner join
                            "tag_references"
                        on  "posts"."id" = "tag_references"."taggable_id"
                    where
                        "tag_references"."taggable_type" = 'post'
                    and "posts"."deleted_at" is null
                )
            and "tags"."deleted_at" is null
EOD
            ,
            Tag::query()->hasByNonDependentSubquery('posts')
        );
    }

    public function testOrHas(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                (
                    "comments"."user_id" = 123
                or  "comments"."post_id" in (
                        select
                            "posts"."id"
                        from
                            "posts"
                        where
                            "posts"."deleted_at" is null
                    )
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()
                ->where('comments.user_id', 123)
                ->orHasByNonDependentSubquery('post')
        );
    }

    public function testDoesntHave(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."user_id" = 123
            and "comments"."post_id" not in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."deleted_at" is null
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()
                ->where('comments.user_id', 123)
                ->doesntHaveByNonDependentSubquery('post')
        );
    }

    public function testOrDoesntHave(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                (
                    "comments"."user_id" = 123
                or  "comments"."post_id" not in (
                        select
                            "posts"."id"
                        from
                            "posts"
                        where
                            "posts"."deleted_at" is null
                    )
                )
            and "comments"."deleted_at" is null
EOD
            ,
            Comment::query()
                ->where('comments.user_id', 123)
                ->orDoesntHaveByNonDependentSubquery('post')
        );
    }

    public function testAcceptBuilderArgument(): void
    {
        $this->assertQueryEquals(
            <<<'EOD'
            select
                *
            from
                "comments"
            where
                "comments"."post_id" in (
                    select
                        "posts"."id"
                    from
                        "posts"
                    where
                        "posts"."deleted_at" is not null
                )
EOD
            ,
            Comment::query()->hasByNonDependentSubquery(
                'post',
                function (Builder $query) {
                    $query->onlyTrashed();
                }
            )->withTrashed()
        );
    }

    public function testAcceptUnionArgument(): void
    {
        if (version_compare(phpversion(), '8.0', '>=')) {
            include __DIR__ . '/includes/test_accept_union_argument.php';
        } else {
            $this->markTestSkipped('Union types are only supported in PHP >= 8.0');
        }
    }

    public function testMultiColumnRelation(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Multi-column relationships are not supported.');

        Comment::query()->hasByNonDependentSubquery('sameAuthorPost');
    }

    public function testAliasedRelation(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Table aliases are not supported.');

        Comment::query()->hasByNonDependentSubquery('post as aliased_posts');
    }

    public function testMorphToRelation(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unsupported relation. Currently supported: HasOne, HasMany, BelongsTo, BelongsToMany, HasManyThrough, HasOneThrough, MorphOne, MorphMany and MorphToMany');

        Comment::query()->hasByNonDependentSubquery('commentable');
    }

    public function testReflectionCallable(): void
    {
        $this->assertInstanceOf(ReflectionFunction::class, ReflectionCallable::from('strpos'));
        $this->assertInstanceOf(ReflectionFunction::class, ReflectionCallable::from(function () {}));

        $this->assertInstanceOf(ReflectionMethod::class, ReflectionCallable::from('DateTime::createFromFormat'));
        $this->assertInstanceOf(ReflectionMethod::class, ReflectionCallable::from([DateTime::class, 'createFromFormat']));
        $this->assertInstanceOf(ReflectionMethod::class, ReflectionCallable::from([new DateTime(), 'format']));
        $this->assertInstanceOf(ReflectionMethod::class, ReflectionCallable::from(new class() {
            public function __invoke()
            {
            }
        }));
    }
}
