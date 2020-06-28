<?php

namespace Mpyw\EloquentHasByNonDependentSubquery\Tests\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use Compoships, SoftDeletes;

    public function category()
    {
        return $this->hasMany(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function polymorphicComments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_references');
    }

    public function polymorphicTags()
    {
        return $this->morphToMany(Tag::class, 'taggable', 'tag_references');
    }
}
