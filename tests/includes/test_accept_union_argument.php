<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Comment;
use Mpyw\EloquentHasByNonDependentSubquery\Tests\Models\Post;

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
        function (Post|BelongsTo $query) {
            $this->assertInstanceof(BelongsTo::class, $query);
            $query->onlyTrashed();
        }
    )->withTrashed()
);

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
        function (BelongsTo|Builder $query) {
            $this->assertInstanceof(BelongsTo::class, $query);
            $query->onlyTrashed();
        }
    )->withTrashed()
);

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
        function (Post|Builder $query) {
            $this->assertInstanceof(Builder::class, $query);
            $query->onlyTrashed();
        }
    )->withTrashed()
);
