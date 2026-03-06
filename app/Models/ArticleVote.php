<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVote extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'ip_address', 'voted_at'];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
