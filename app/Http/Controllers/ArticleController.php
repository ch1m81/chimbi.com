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

    /** Upgrade http embeds to https and extract first iframe for list view */
    private function upgradeEmbeds(string $html): string
    {
        // First fix protocol-relative URLs (//), then fix http://
        $html = preg_replace('#(src=["\'])\/\/(www\.youtube\.com|player\.vimeo\.com|coub\.com)#', '$1https://$2', $html);
        $html = preg_replace('#(src=["\'])http://(www\.youtube\.com|player\.vimeo\.com|coub\.com)#', '$1https://$2', $html);
        return $html;
    }

    private function excerptToHtml(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $paragraphs = preg_split("/\R{2,}/", $text) ?: [];

        return collect($paragraphs)
            ->map(fn(string $paragraph) => '<p>' . nl2br(e(trim($paragraph))) . '</p>')
            ->implode("\n");
    }

    private function textContent(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function trimTextToSentences(string $text, int $sentenceLimit): string
    {
        $text = trim($text);
        if ($text === '' || $sentenceLimit < 1) {
            return '';
        }

        preg_match_all('/[^.!?]+[.!?]+(?:\s+|$)|[^.!?]+$/u', $text, $matches);
        $sentences = array_values(array_filter(array_map('trim', $matches[0] ?? [])));

        if (!$sentences) {
            return $text;
        }

        return implode(' ', array_slice($sentences, 0, $sentenceLimit));
    }

    private function summarizeBody(Article $a, string $upgradedBody): ?string
    {
        if ($a->excerpt) {
            return $this->excerptToHtml($a->excerpt);
        }

        if (str_contains($upgradedBody, '<!--more-->')) {
            return trim(explode('<!--more-->', $upgradedBody, 2)[0]);
        }

        if ($a->trim_sentences) {
            $trimmed = $this->trimTextToSentences(
                $this->textContent($upgradedBody),
                (int) $a->trim_sentences,
            );

            return $trimmed !== '' ? $this->excerptToHtml($trimmed) : null;
        }

        if (preg_match('/<(iframe|video)\b[^>]*>.*?<\/\1>/is', $upgradedBody, $m)) {
            return $m[0];
        }

        if ($a->body_trim) {
            $text = $this->textContent($upgradedBody);
            if ($text === '') {
                return null;
            }

            $trimmed = mb_substr($text, 0, (int) $a->body_trim);
            if (mb_strlen($text) > (int) $a->body_trim) {
                $trimmed = rtrim($trimmed) . '...';
            }

            return $this->excerptToHtml($trimmed);
        }

        return trim($upgradedBody) !== '' ? $upgradedBody : null;
    }

    private function formatArticle(Article $a, bool $fullBody = false): array
    {
        // List view uses excerpt/marker/sentence summary; single view gets full body.
        $body = null;
        if ($a->body) {
            $upgraded = $this->upgradeEmbeds($a->body);
            if ($fullBody) {
                $body = $upgraded;
            } else {
                $body = $this->summarizeBody($a, $upgraded);
            }
        }

        return [
            'id'            => $a->id,
            'title'         => $a->title,
            'slug'          => $a->slug,
            'thumbnail'     => $a->thumbnail,
            'thumbnail_url' => $a->thumbnail_url,
            'youtube_code'  => $a->youtube_code,
            'source_url'    => $a->source_url,
            'love'          => $a->love,
            'body'          => $body,
            'published_at'  => $a->published_at?->format('j M Y'),
            'published'     => $a->published,
            'tags'          => $a->tags->map(fn(Tag $t) => [
                'name' => $t->name,
                'slug' => $t->slug,
            ]),
        ];
    }

    private function popularTags(): array
    {
        return Tag::query()
            ->withCount(['articles' => fn($q) => $q->where('published', true)])
            ->orderByDesc('articles_count')
            ->limit(40)
            ->get()
            ->map(fn(Tag $t) => [
                'name'  => $t->name,
                'slug'  => $t->slug,
                'count' => $t->articles_count,
            ])
            ->all();
    }

    private function topArticles(): array
    {
        return Article::query()
            ->published()
            ->orderByDesc('love')
            ->limit(5)
            ->get()
            ->map(fn(Article $a) => [
                'id'    => $a->id,
                'slug'  => $a->slug,
                'title' => $a->title,
            ])
            ->all();
    }

    private function buildList(Request $request, string $sort): Response
    {
        $tag    = $request->query('tag');
        $search = $request->query('search');

        $query = Article::query()
            ->with('tags')
            ->when(!session('admin_auth'), fn($q) => $q->published())  // skip published filter for admin
            ->when($tag,    fn($q) => $q->withTag($tag))
            ->when($search, fn($q) => $q->search($search));

        $query = match ($sort) {
            'popular' => $query->orderByDesc('love')->orderByDesc('published_at'),
            default   => $query->orderByDesc('published_at'),
        };

        $articles = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn(Article $a) => $this->formatArticle($a));

        return Inertia::render('Home', [
            'articles'    => $articles,
            'popularTags' => $this->popularTags(),
            'topArticles' => $this->topArticles(),
            'sort'        => $sort,
            'filters'     => ['tag' => $tag, 'search' => $search],
        ]);
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        return $this->buildList($request, 'newest');
    }

    public function popular(Request $request): Response
    {
        return $this->buildList($request, 'popular');
    }

    public function tagged(): Response
    {
        $tags = Tag::query()
            ->withCount(['articles' => fn($q) => $q->where('published', true)])
            ->having('articles_count', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(fn(Tag $t) => [
                'name'  => $t->name,
                'slug'  => $t->slug,
                'count' => $t->articles_count,
            ])
            ->all();

        return Inertia::render('Tagged', [
            'tags'        => $tags,
            'popularTags' => $this->popularTags(),
            'topArticles' => $this->topArticles(),
            'sort'        => 'tagged',
        ]);
    }

    public function show(Article $article): Response
    {
        $article->load('tags');
        $isAdmin = (bool) session('admin_auth');

        $formatted = $this->formatArticle($article);

        // Clean the full body the same way as list - strip wrapper tags around iframes/imgs
        $fullBody = preg_replace('/<(?:p|div)[^>]*>\s*(<iframe[^>]*>.*?<\/iframe>)\s*<\/(?:p|div)>/is', '$1', $article->body ?? '');
        $fullBody = $this->upgradeEmbeds($fullBody);
        $formatted['body'] = $fullBody;

        $prev = Article::query()
            ->when(!$isAdmin, fn($q) => $q->published())
            ->where(
                fn($q) => $q
                    ->where('published_at', '<', $article->published_at)
                    ->orWhere(
                        fn($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '<', $article->id)
                    )
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $next = Article::query()
            ->when(!$isAdmin, fn($q) => $q->published())
            ->where(
                fn($q) => $q
                    ->where('published_at', '>', $article->published_at)
                    ->orWhere(
                        fn($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '>', $article->id)
                    )
            )
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();

        $tagIds  = $article->tags->pluck('id');
        $related = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds))
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return Inertia::render('ArticleView', [
            'article' => $formatted,

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

            'relatedArticles' => $related->map(fn(Article $a) => [
                'id'    => $a->id,
                'slug'  => $a->slug,
                'title' => $a->title,
            ])->all(),

            'popularTags' => $this->popularTags(),
            'topArticles' => $this->topArticles(),
        ]);
    }

    public function archive(Request $request): Response
    {
        $selectedYear  = $request->query('year')  ? (int) $request->query('year')  : null;
        $selectedMonth = $request->query('month') ? (int) $request->query('month') : null;

        $archive = Article::query()
            ->published()
            ->selectRaw('YEAR(published_at) as year, MONTH(published_at) as month, COUNT(*) as count')
            ->groupByRaw('YEAR(published_at), MONTH(published_at)')
            ->orderByRaw('YEAR(published_at) DESC, MONTH(published_at) ASC')
            ->get()
            ->groupBy('year')
            ->map(fn($months, $year) => [
                'year'   => (int) $year,
                'months' => $months->map(fn($m) => [
                    'month' => (int) $m->month,
                    'count' => (int) $m->count,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $articles = null;
        if ($selectedYear) {
            $articles = Article::query()
                ->with('tags')
                ->published()
                ->whereYear('published_at', $selectedYear)
                ->when($selectedMonth, fn($q) => $q->whereMonth('published_at', $selectedMonth))
                ->orderByDesc('published_at')
                ->paginate(20)
                ->withQueryString()
                ->through(fn(Article $a) => $this->formatArticle($a));
        }

        return Inertia::render('Archive', [
            'archive'       => $archive,
            'articles'      => $articles ?? ['data' => [], 'prev_page_url' => null, 'next_page_url' => null],
            'selectedYear'  => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'popularTags'   => $this->popularTags(),
            'topArticles'   => $this->topArticles(),
            'sort'          => 'archive',
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
