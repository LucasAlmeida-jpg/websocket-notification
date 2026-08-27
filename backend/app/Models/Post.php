<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'parent_id', 'body', 'likes_count', 'replies_count', 'reposts_count'];

    protected $casts = ['likes_count' => 'integer', 'replies_count' => 'integer', 'reposts_count' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Post::class, 'parent_id')->latest();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function reposts()
    {
        return $this->hasMany(Repost::class);
    }

    public function isRepostedBy(User $user): bool
    {
        return $this->reposts()->where('user_id', $user->id)->exists();
    }
}
