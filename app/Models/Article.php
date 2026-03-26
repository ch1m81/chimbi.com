<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'body_trim',
        'excerpt',
        'trim_sentences',
        'source_url',
        'thumbnail',
        'youtube_code',
        'thumbnail_url',
        'love',
        'published',
        'published_at',
    ];

    protected $casts = [
        'published'    => 'boolean',
        'published_at' => 'date',
        'love'         => 'integer',
        'body_trim'    => 'integer',
        'trim_sentences' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ArticleVote::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Only return published articles. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /** Filter by a single tag slug. */
    public function scopeWithTag(Builder $query, string $tagSlug): Builder
    {
        return $query->whereHas(
            'tags',
            fn(Builder $q) =>
            $q->where('slug', $tagSlug)
        );
    }

    /** Full-text-style title search (LIKE is fine for this size). */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('title', 'like', "%{$term}%");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true if the given IP has already voted on this article.
     */
    public function hasVoteFrom(string $ip): bool
    {
        return $this->votes()->where('ip_address', $ip)->exists();
    }

    /**
     * Whether this post embeds a YouTube video.
     */
    public function hasYoutube(): bool
    {
        return ! empty($this->youtube_code);
    }

    /**
     * Thumbnail URL, relative to public storage.
     * Adjust the path prefix to wherever you serve images from.
     */
    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        return '/storage/articles/' . $this->thumbnail;
    }
}
