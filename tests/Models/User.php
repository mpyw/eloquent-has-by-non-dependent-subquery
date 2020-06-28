<?php

namespace Mpyw\EloquentHasByNonDependentSubquery\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;

class User extends Model
{
    public function receivedComments()
    {
        return $this->hasManyThrough(Comment::class, Post::class, 'user_id', 'post_id');
    }

    public function pinnedPost()
    {
        return $this->hasOne(Post::class)->where('posts.pinned', 1);
    }

    public function pinnedPostInGeneralCategory()
    {
        return $this->pinnedPost()
            ->join('categories', function (JoinClause $join) {
                $join->on('posts.category_id', '=', 'categories.id');
                $join->where('categories.slug', '=', 'general');
            })
            ->select('posts.*');
    }
}
