<?php

namespace Mpyw\EloquentHasByNonDependentSubquery\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable', 'tag_references');
    }
}
