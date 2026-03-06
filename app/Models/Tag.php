<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }

    /**
     * How many published articles use this tag.
     */
    public function publishedCount(): int
    {
        return $this->articles()->where('published', true)->count();
    }
}
