<?php

namespace Mpyw\EloquentHasByNonDependentSubquery\Tests\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use Compoships, SoftDeletes;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sameAuthorPost()
    {
        return $this->belongsTo(Post::class, ['post_id', 'author_id'], ['id', 'author_id']);
    }
}
