<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function formatArticle(Article $a): array
    {
        return [
            'id'            => $a->id,
            'title'         => $a->title,
            'slug'          => $a->slug,
            'thumbnail'     => $a->thumbnail,
            'thumbnail_url' => $a->thumbnail_url,
            'youtube_code'  => $a->youtube_code,
            'source_url'    => $a->source_url,
            'love'          => $a->love,
            'body'          => $a->body
                ? (preg_match('/<iframe[^>]+src=[^>]+>.*?<\/iframe>/is', $a->body, $m) ? $m[0] : null)
                : null,
            'published_at'  => $a->published_at?->format('j M Y'),
            'tags'          => $a->tags->map(fn (Tag $t) => [
                'name' => $t->name,
                'slug' => $t->slug,
            ]),
        ];
    }

    /** Top 40 tags by published article count. */
    private function popularTags(): array
    {
        return Tag::query()
            ->withCount(['articles' => fn ($q) => $q->where('published', true)])
            ->orderByDesc('articles_count')
            ->limit(40)
            ->get()
            ->map(fn (Tag $t) => [
                'name'  => $t->name,
                'slug'  => $t->slug,
                'count' => $t->articles_count,
            ])
            ->all();
    }

    /** Top 5 articles by love score. */
    private function topArticles(): array
    {
        return Article::query()
            ->published()
            ->orderByDesc('love')
            ->limit(5)
            ->get()
            ->map(fn (Article $a) => [
                'id'    => $a->id,
                'slug'  => $a->slug,
                'title' => $a->title,
            ])
            ->all();
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $tag    = $request->query('tag');
        $search = $request->query('search');

        $articles = Article::query()
            ->with('tags')
            ->published()
            ->when($tag,    fn ($q) => $q->withTag($tag))
            ->when($search, fn ($q) => $q->search($search))
            ->orderByDesc('published_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Article $a) => $this->formatArticle($a));

        return Inertia::render('Home', [
            'articles'    => $articles,
            'popularTags' => $this->popularTags(),
            'topArticles' => $this->topArticles(),
            'filters'     => [
                'tag'    => $tag,
                'search' => $search,
            ],
        ]);
    }

    public function show(Article $article): Response
    {
        $article->load('tags');

        // Prev: next older published article (stable tiebreak by id)
        $prev = Article::query()
            ->published()
            ->where(fn ($q) => $q
                ->where('published_at', '<', $article->published_at)
                ->orWhere(fn ($q2) => $q2
                    ->where('published_at', '=', $article->published_at)
                    ->where('id', '<', $article->id)
                )
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        // Next: next newer published article (stable tiebreak by id)
        $next = Article::query()
            ->published()
            ->where(fn ($q) => $q
                ->where('published_at', '>', $article->published_at)
                ->orWhere(fn ($q2) => $q2
                    ->where('published_at', '=', $article->published_at)
                    ->where('id', '>', $article->id)
                )
            )
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();

        // Related: same tags, excluding self, up to 6
        $tagIds  = $article->tags->pluck('id');
        $related = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return Inertia::render('ArticleView', [
            'article' => $this->formatArticle($article),

            'prevArticle' => $prev ? [
                'id'    => $prev->id,
                'slug'  => $prev->slug,
                'title' => $prev->title,
            ] : null,

            'nextArticle' => $next ? [
                'id'    => $next->id,
                'slug'  => $next->slug,
                'title' => $next->title,
            ] : null,

            'relatedArticles' => $related->map(fn (Article $a) => [
                'id'    => $a->id,
                'slug'  => $a->slug,
                'title' => $a->title,
            ])->all(),

            'popularTags' => $this->popularTags(),
            'topArticles' => $this->topArticles(),
        ]);
    }

    public function love(Request $request, Article $article)
    {
        $ip = $request->ip();

        if ($article->hasVoteFrom($ip)) {
            return response()->json(['love' => $article->love, 'already_voted' => true]);
        }

        $article->increment('love');
        $article->votes()->create([
            'ip_address' => $ip,
            'voted_at'   => now(),
        ]);

        return response()->json(['love' => $article->fresh()->love, 'already_voted' => false]);
    }
}
