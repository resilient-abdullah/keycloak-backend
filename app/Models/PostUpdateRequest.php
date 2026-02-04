<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostUpdateRequest extends Model
{
    protected $fillable = [
        'post_id',
        'title',
        'content',
        'requested_by',
        'status',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
