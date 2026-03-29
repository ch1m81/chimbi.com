<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminController extends Controller
{
    // ── Shared helpers ────────────────────────────────────────────────────

    private function sanitizeReturnTo(?string $candidate, Request $request): string
    {
        if (!$candidate) {
            return route('home');
        }

        $parts = parse_url($candidate);
        if ($parts === false) {
            return route('home');
        }

        $host = $parts['host'] ?? null;
        if ($host && $host !== $request->getHost()) {
            return route('home');
        }

        $path = $parts['path'] ?? '/';
        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        if (str_starts_with($path, '/chimbi')) {
            return route('home');
        }

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;
    }

    private function returnTo(Request $request): string
    {
        return $this->sanitizeReturnTo(
            $request->query('return_to') ?? $request->headers->get('referer'),
            $request,
        );
    }

    private function allTags(): array
    {
        return Tag::orderBy('name')->get()
            ->map(fn(Tag $t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])
            ->all();
    }

    private function supportsExcerptFields(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumns('articles', ['excerpt', 'trim_sentences']);
        }

        return $supports;
    }

    private function formArticle(Article $article): array
    {
        $article->load('tags');
        $supportsExcerptFields = $this->supportsExcerptFields();

        // Extract first image from body as fallback thumbnail
        $thumbnailUrl = $article->thumbnail_url;
        if (!$thumbnailUrl && $article->body) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $article->body, $m)) {
                $thumbnailUrl = $m[1];
            }
        }

        return [
            'id'            => $article->id,
            'title'         => $article->title,
            'slug'          => $article->slug,
            'body'          => $article->getRawOriginal('body') ?? $article->body,
            'body_trim'     => $article->body_trim,
            'excerpt'       => $supportsExcerptFields ? $article->excerpt : null,
            'trim_sentences' => $supportsExcerptFields ? $article->trim_sentences : null,
            'source_url'    => $article->source_url,
            'youtube_code'  => $article->youtube_code,
            'thumbnail'     => $article->thumbnail,
            'thumbnail_url' => $thumbnailUrl,
            'love'          => $article->love,
            'published'     => $article->published,
            'published_at'  => $article->published_at?->format('Y-m-d'),
            'tags'          => $article->tags->pluck('id')->all(),
        ];
    }

    private function normalizeExternalUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $host = Str::lower($parts['host'] ?? '');

        if (in_array($host, ['twitter.com', 'www.twitter.com', 'mobile.twitter.com'], true)) {
            $path = $parts['path'] ?? '';
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

            return 'https://x.com' . $path . $query . $fragment;
        }

        return $url;
    }

    private function collectArticleLinks(Article $article): array
    {
        $links = [];

        $push = function (string $url, string $source, ?string $context = null) use (&$links) {
            $normalized = $this->normalizeExternalUrl($url);

            if (!$normalized) {
                return;
            }

            if (!isset($links[$normalized])) {
                $links[$normalized] = [
                    'url' => $normalized,
                    'sources' => [],
                    'contexts' => [],
                ];
            }

            if (!in_array($source, $links[$normalized]['sources'], true)) {
                $links[$normalized]['sources'][] = $source;
            }

            $context = trim((string) $context);
            if ($context !== '' && !in_array($context, $links[$normalized]['contexts'], true)) {
                $links[$normalized]['contexts'][] = $context;
            }
        };

        $push($article->source_url ?? '', 'source_url', 'Source URL');
        $push($article->thumbnail_url ?? '', 'thumbnail_url', 'Thumbnail URL');

        if (!empty($article->youtube_code)) {
            $youtubeUrl = "https://www.youtube.com/watch?v={$article->youtube_code}";
            $push($youtubeUrl, 'youtube_code', 'YouTube video');
        }

        $body = $article->getRawOriginal('body') ?? $article->body ?? '';
        if ($body !== '') {
            preg_match_all('/(?:src|href)=["\']([^"\']+)["\']/i', $body, $attributeMatches);

            foreach ($attributeMatches[1] ?? [] as $attributeUrl) {
                $push($attributeUrl, 'body', $attributeUrl);
            }

            preg_match_all('/https?:\/\/[^\s<>"\'`]+/i', $body, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] ?? [] as $match) {
                [$url, $offset] = $match;
                $url = rtrim($url, ".,);:!?\"'<>]");

                $start = max(0, $offset - 60);
                $length = min(strlen($body) - $start, strlen($url) + 120);
                $context = trim(preg_replace('/\s+/u', ' ', substr($body, $start, $length)) ?? '');
                $push($url, 'body', $context);
            }
        }

        return array_values($links);
    }

    private function ignoredLinksPath(): string
    {
        return 'admin/tshoot-ignored-links.json';
    }

    private function ignoredLinksMap(): array
    {
        $disk = Storage::disk('local');
        $path = $this->ignoredLinksPath();

        if (!$disk->exists($path)) {
            return [];
        }

        $decoded = json_decode($disk->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function ignoredLinksForArticle(Article $article): array
    {
        $map = $this->ignoredLinksMap();
        $urls = $map[(string) $article->id] ?? [];

        return is_array($urls) ? array_values(array_filter($urls, 'is_string')) : [];
    }

    private function saveIgnoredLinksMap(array $map): void
    {
        Storage::disk('local')->put(
            $this->ignoredLinksPath(),
            json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function scanUrl(string $url, bool $force = false): array
    {
        $cacheKey = 'admin-link-scan:' . sha1(Str::lower($url));

        if (!$force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = [
            'url' => $url,
            'state' => 'broken',
            'label' => 'Broken',
            'status_code' => null,
            'checked_at' => now()->toIso8601String(),
            'reason' => 'Request failed.',
        ];

        $youtubeCode = $this->extractYoutubeCode($url);
        if ($youtubeCode) {
            $youtubeResult = $this->scanYoutubeUrl($url, $youtubeCode);
            Cache::put($cacheKey, $youtubeResult, now()->addHours(6));

            return $youtubeResult;
        }

        $request = fn(string $method) => Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Chimbi Admin Link Checker/1.0)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ])
            ->timeout(8)
            ->withOptions([
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
            ])->send($method, $url);

        try {
            $response = $request('HEAD');

            if (in_array($response->status(), [403, 405, 429, 500, 501, 502, 503], true)) {
                $response = $request('GET');
            }

            $status = $response->status();
            $result['status_code'] = $status;

            if ($status >= 200 && $status < 400) {
                $result['state'] = 'ok';
                $result['label'] = 'OK';
                $result['reason'] = 'Reachable.';
            } elseif (in_array($status, [401, 403, 429], true)) {
                $result['state'] = 'blocked';
                $result['label'] = 'Blocked';
                $result['reason'] = 'The site responded but restricted automated access.';
            } else {
                $result['state'] = 'broken';
                $result['label'] = 'Broken';
                $result['reason'] = 'The remote server returned an error response.';
            }
        } catch (\Throwable $e) {
            $message = trim($e->getMessage());
            $result['reason'] = $message !== '' ? $message : 'Connection failed.';
        }

        Cache::put($cacheKey, $result, now()->addHours(6));

        return $result;
    }

    private function extractYoutubeCode(string $url): ?string
    {
        $normalized = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/(?:youtube(?:-nocookie)?\.com\/watch\?[^"\']*v=|youtu\.be\/|youtube(?:-nocookie)?\.com\/embed\/)([a-zA-Z0-9_-]{11})/i', $normalized, $matches)) {
            return $matches[1];
        }

        $parts = parse_url($normalized);
        parse_str($parts['query'] ?? '', $query);

        if (!empty($query['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $query['v'])) {
            return $query['v'];
        }

        return null;
    }

    private function scanYoutubeUrl(string $url, string $youtubeCode): array
    {
        $oembedUrl = 'https://www.youtube.com/oembed?' . http_build_query([
            'url' => "https://www.youtube.com/watch?v={$youtubeCode}",
            'format' => 'json',
        ]);

        $result = [
            'url' => $url,
            'state' => 'broken',
            'label' => 'Broken',
            'status_code' => null,
            'checked_at' => now()->toIso8601String(),
            'reason' => 'YouTube video is unavailable.',
        ];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Chimbi Admin Link Checker/1.0)',
                'Accept' => 'application/json,text/html;q=0.9,*/*;q=0.8',
            ])->timeout(8)->get($oembedUrl);

            $status = $response->status();
            $result['status_code'] = $status;

            if ($response->successful()) {
                return [
                    ...$result,
                    'state' => 'ok',
                    'label' => 'OK',
                    'reason' => 'YouTube video is publicly available.',
                ];
            }

            if (in_array($status, [401, 403], true)) {
                return [
                    ...$result,
                    'state' => 'blocked',
                    'label' => 'Locked',
                    'reason' => 'YouTube video exists but is private, restricted, or otherwise locked.',
                ];
            }

            return [
                ...$result,
                'state' => 'broken',
                'label' => 'Broken',
                'reason' => 'YouTube video is missing, deleted, or unavailable.',
            ];
        } catch (\Throwable $e) {
            $message = trim($e->getMessage());

            return [
                ...$result,
                'reason' => $message !== '' ? $message : 'YouTube check failed.',
            ];
        }
    }

    private function articleHasLocalImageFallback(Article $article): bool
    {
        if (filled($article->thumbnail)) {
            return true;
        }

        $body = (string) ($article->getRawOriginal('body') ?? $article->body ?? '');
        if ($body === '') {
            return false;
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $body, $matches);

        foreach ($matches[1] ?? [] as $src) {
            $src = trim((string) $src);

            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }

            if ($this->normalizeExternalUrl($src) === null) {
                return true;
            }
        }

        return false;
    }

    private function linkCountsTowardArticleIssue(Article $article, array $link): bool
    {
        if ($link['ignored'] ?? false) {
            return false;
        }

        $sources = collect($link['sources'] ?? [])
            ->filter(fn($source) => is_string($source) && $source !== 'thumbnail_url')
            ->values();

        if (
            $sources->contains('source_url')
            && $this->articleHasLocalImageFallback($article)
        ) {
            $sources = $sources
                ->reject(fn($source) => $source === 'source_url')
                ->values();
        }

        return $sources->isNotEmpty();
    }

    private function tshootArticlePayload(Article $article, bool $includeStatuses = false, bool $forceScan = false): array
    {
        $body = $article->getRawOriginal('body') ?? $article->body ?? '';
        $ignoredLinks = $this->ignoredLinksForArticle($article);
        $links = collect($this->collectArticleLinks($article))
            ->map(function (array $link) use ($article, $includeStatuses, $forceScan, $ignoredLinks) {
                $link['ignored'] = in_array($link['url'], $ignoredLinks, true);
                $link['counts_toward_issue'] = $this->linkCountsTowardArticleIssue($article, $link);

                if ($includeStatuses) {
                    $link['scan'] = $this->scanUrl($link['url'], $forceScan);
                }

                return $link;
            })
            ->values();

        $primaryLink = $links->first(
            fn(array $link) => in_array('source_url', $link['sources'] ?? [], true)
        );

        $primaryIssue = $includeStatuses
            ? ($primaryLink['scan'] ?? null)
            : null;

        $issueCount = $includeStatuses
            ? $links->filter(
                fn(array $link) => ($link['counts_toward_issue'] ?? false)
                    && ($link['scan']['state'] ?? null) === 'broken'
            )->count()
            : 0;

        $blockedCount = $includeStatuses
            ? $links->filter(
                fn(array $link) => ($link['counts_toward_issue'] ?? false)
                    && ($link['scan']['state'] ?? null) === 'blocked'
            )->count()
            : 0;

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'published' => (bool) $article->published,
            'published_at' => $article->published_at?->format('Y-m-d'),
            'source_url' => $article->source_url,
            'body' => $body,
            'body_preview' => Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? ''), 280),
            'links' => $links->all(),
            'primary_link' => $primaryLink,
            'primary_issue' => $primaryIssue,
            'ignored_link_count' => $links->where('ignored', true)->count(),
            'link_count' => $links->count(),
            'issue_count' => $issueCount,
            'blocked_count' => $blockedCount,
            'has_issues' => $issueCount > 0,
            'edit_url' => route('admin.edit', ['article' => $article->id, 'return_to' => route('admin.tshoot')]),
            'view_url' => route('articles.show', ['article' => $article->id, 'slug' => $article->slug]),
        ];
    }

    private function bodyBlockRemovalCandidates(Article $article, string $url): array
    {
        $body = $article->getRawOriginal('body') ?? $article->body ?? '';
        if (trim($body) === '') {
            return [];
        }

        $wrapperId = 'tshoot-root';
        $html = '<div id="' . $wrapperId . '">' . $body . '</div>';

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        $candidate = null;
        foreach ($xpath->query('//*') as $node) {
            if (!$node instanceof \DOMElement || $node->getAttribute('id') === $wrapperId) {
                continue;
            }

            if ($this->bodyBlockNodeMatchesUrl($node, $url, $dom)) {
                $candidate = $node;
                break;
            }
        }

        if (!$candidate) {
            libxml_clear_errors();
            return [];
        }

        $candidates = [];
        $seen = [];
        $node = $candidate instanceof \DOMElement ? $candidate : $candidate->parentNode;

        while ($node instanceof \DOMElement) {
            if ($node->getAttribute('id') === $wrapperId) {
                break;
            }

            $tag = strtolower($node->nodeName);
            if (in_array($tag, ['a', 'p', 'div', 'li', 'figure', 'section', 'blockquote', 'span'], true)) {
                $path = $node->getNodePath();
                if (!isset($seen[$path])) {
                    $seen[$path] = true;
                    $candidates[] = [
                        'path' => $path,
                        'label' => $tag === 'a' ? 'Just the link tag' : "Whole <{$tag}> block",
                    ];
                }
            }

            $node = $node->parentNode;
        }

        if (!$candidates) {
            libxml_clear_errors();
            return [];
        }

        $results = [];

        foreach ($candidates as $index => $candidateMeta) {
            $candidateDom = new \DOMDocument();
            $candidateDom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $candidateXpath = new \DOMXPath($candidateDom);
            $targetNodes = $candidateXpath->query($candidateMeta['path']);
            $targetNode = $targetNodes?->item(0);

            if (!$targetNode instanceof \DOMElement) {
                continue;
            }

            $previewHtml = trim($candidateDom->saveHTML($targetNode));
            $targetNode->parentNode?->removeChild($targetNode);

            $root = $candidateDom->getElementById($wrapperId);
            $updatedBody = '';

            if ($root) {
                foreach ($root->childNodes as $child) {
                    $updatedBody .= $candidateDom->saveHTML($child);
                }
            }

            if ($previewHtml === '') {
                continue;
            }

            $results[] = [
                'key' => (string) $index,
                'label' => $candidateMeta['label'],
                'preview_html' => $previewHtml,
                'updated_body' => trim($updatedBody),
            ];
        }

        libxml_clear_errors();

        return $results;
    }

    private function bodyBlockNodeMatchesUrl(\DOMElement $node, string $url, \DOMDocument $dom): bool
    {
        $href = trim((string) $node->getAttribute('href'));
        $src = trim((string) $node->getAttribute('src'));

        if ($href === $url || $src === $url) {
            return true;
        }

        $text = html_entity_decode(trim((string) $node->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = html_entity_decode(trim((string) $dom->saveHTML($node)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        foreach ($this->bodyBlockUrlNeedles($url) as $needle) {
            if ($needle === '') {
                continue;
            }

            if (stripos($text, $needle) !== false || stripos($html, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function bodyBlockUrlNeedles(string $url): array
    {
        $decoded = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = parse_url($decoded);

        if ($parts === false) {
            return [$decoded];
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        $needles = collect([
            $decoded,
            preg_replace('#^https?://#i', '', $decoded),
            $host !== '' ? $host . $path . $query : null,
            $host !== '' ? preg_replace('/^www\./i', '', $host) . $path . $query : null,
            $host !== '' && !str_starts_with($host, 'www.') ? 'www.' . $host . $path . $query : null,
            $host,
            $host !== '' ? preg_replace('/^www\./i', '', $host) : null,
        ])
            ->filter(fn($needle) => is_string($needle) && trim($needle) !== '')
            ->map(fn($needle) => trim($needle, " \t\n\r\0\x0B/"))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $needles;
    }

    private function buildReplacementQuery(string $url, ?string $articleTitle = null): string
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $segments = collect(explode('/', $path))
            ->filter()
            ->map(fn(string $segment) => preg_replace('/[-_]+/', ' ', pathinfo($segment, PATHINFO_FILENAME)))
            ->filter()
            ->implode(' ');

        return trim(collect([
            $articleTitle,
            $host !== '' ? "\"{$host}\"" : null,
            $segments,
        ])->filter()->implode(' '));
    }

    private function duckDuckGoResults(string $query): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Chimbi Admin Search/1.0)',
            'Accept' => 'text/html,application/xhtml+xml',
        ])
            ->timeout(12)
            ->get('https://html.duckduckgo.com/html/', ['q' => $query]);

        if (!$response->successful()) {
            throw new \RuntimeException('Search provider returned an unexpected response.');
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($response->body());
        $xpath = new \DOMXPath($dom);

        $results = [];
        foreach ($xpath->query("//a[contains(@class, 'result__a')]") as $node) {
            $href = trim((string) $node->getAttribute('href'));
            $title = trim($node->textContent);

            if ($href === '' || $title === '') {
                continue;
            }

            $parsed = parse_url(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $target = $href;

            if (($parsed['host'] ?? '') === 'duckduckgo.com') {
                parse_str($parsed['query'] ?? '', $queryParams);
                $target = $queryParams['uddg'] ?? $href;
            }

            $target = html_entity_decode($target, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (!$this->normalizeExternalUrl($target)) {
                continue;
            }

            $results[] = [
                'title' => $title,
                'url' => $target,
                'host' => parse_url($target, PHP_URL_HOST) ?: '',
            ];

            if (count($results) >= 6) {
                break;
            }
        }

        libxml_clear_errors();

        return $results;
    }

    // ── Password check ────────────────────────────────────────────────────

    public function loginForm(): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response|InertiaResponse
    {
        if (session('admin_auth') === true) {
            return redirect()->route('admin.tshoot');
        }

        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $request->validate(['password' => 'required']);

        $throttleKey = Str::lower($request->ip() . '|chimbi-admin-login');
        $maxAttempts = 3;
        $decaySeconds = 600;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'password' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if ($request->password !== config('app.admin_password')) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        RateLimiter::clear($throttleKey);

        session(['admin_auth' => true]);
        return redirect()->route('admin.tshoot');
    }

    public function logout()
    {
        session()->forget('admin_auth');
        return redirect('/');
    }

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/ArticleForm', [
            'article'  => null,
            'allTags'  => $this->allTags(),
            'mode'     => 'create',
            'returnTo' => $this->returnTo($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:articles,slug',
            'body'         => 'nullable|string',
            'body_trim'    => 'nullable|integer|min:50',
            'excerpt'      => 'nullable|string',
            'trim_sentences' => 'nullable|integer|min:1|max:10',
            'source_url'   => 'nullable|url|max:500',
            'youtube_code' => 'nullable|string|max:50',
            'thumbnail_url' => 'nullable|string|max:500',
            'love'         => 'nullable|integer|min:0',
            'published'    => 'boolean',
            'published_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'integer|exists:tags,id',
        ]);

        if (!$this->supportsExcerptFields()) {
            unset($data['excerpt'], $data['trim_sentences']);
        }

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('articles', $filename, 'public');
            $data['thumbnail'] = $filename;
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $article = Article::create($data);
        $article->tags()->sync($tags);

        return redirect()->route('admin.edit', [
            'article' => $article,
            'return_to' => $this->returnTo($request),
        ])
            ->with('success', 'Article created.');
    }

    public function edit(Article $article, Request $request): Response
    {
        $prev = Article::where('id', '<', $article->id)->orderByDesc('id')->first();
        $next = Article::where('id', '>', $article->id)->orderBy('id')->first();

        return Inertia::render('Admin/ArticleForm', [
            'article'     => $this->formArticle($article),
            'allTags'     => $this->allTags(),
            'mode'        => 'edit',
            'returnTo'    => $this->returnTo($request),
            'prevArticle' => $prev ? ['id' => $prev->id, 'title' => $prev->title] : null,
            'nextArticle' => $next ? ['id' => $next->id, 'title' => $next->title] : null,
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:articles,slug,' . $article->id,
            'body'         => 'nullable|string',
            'body_trim'    => 'nullable|integer|min:50',
            'excerpt'      => 'nullable|string',
            'trim_sentences' => 'nullable|integer|min:1|max:10',
            'source_url'   => 'nullable|url|max:500',
            'youtube_code' => 'nullable|string|max:50',
            'thumbnail_url' => 'nullable|string|max:500',
            'love'         => 'nullable|integer|min:0',
            'published'    => 'boolean',
            'published_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'integer|exists:tags,id',
        ]);

        if (!$this->supportsExcerptFields()) {
            unset($data['excerpt'], $data['trim_sentences']);
        }

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('articles', $filename, 'public');
            $data['thumbnail'] = $filename;
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $article->update($data);
        $article->tags()->sync($tags);

        return back()->with('success', 'Article updated.');
    }

    public function destroy(Article $article, Request $request)
    {
        $deletedTitle = $article->title;
        $next = Article::where('id', '>', $article->id)->orderBy('id')->first();
        $prev = Article::where('id', '<', $article->id)->orderByDesc('id')->first();
        $returnTo = $this->returnTo($request);

        $article->tags()->detach();
        $article->delete();

        if ($next) {
            return redirect()->route('admin.edit', [
                'article' => $next,
                'return_to' => $returnTo,
            ])
                ->with('success', 'Article deleted.')
                ->with('deleted_article_title', $deletedTitle);
        }

        if ($prev) {
            return redirect()->route('admin.edit', [
                'article' => $prev,
                'return_to' => $returnTo,
            ])
                ->with('success', 'Article deleted.')
                ->with('deleted_article_title', $deletedTitle);
        }

        return redirect()->route('admin.create', [
            'return_to' => $returnTo,
        ])
            ->with('success', 'Article deleted.')
            ->with('deleted_article_title', $deletedTitle);
    }

    public function deleteFromTshoot(Article $article)
    {
        $deletedTitle = $article->title;
        $article->tags()->detach();
        $article->delete();

        return response()->json([
            'ok' => true,
            'article_id' => $article->id,
            'title' => $deletedTitle,
        ]);
    }

    public function tshoot(): Response
    {
        $articles = Article::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(Article $article) => $this->tshootArticlePayload($article))
            ->all();

        return Inertia::render('Admin/Tshoot', [
            'articles' => $articles,
        ]);
    }

    public function tshootScan(Request $request)
    {
        $data = $request->validate([
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'integer|exists:articles,id',
            'force' => 'nullable|boolean',
        ]);

        $force = (bool) ($data['force'] ?? false);

        $articles = Article::query()
            ->when(
                !empty($data['article_ids']),
                fn($query) => $query->whereIn('id', $data['article_ids'])
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(Article $article) => $this->tshootArticlePayload($article, true, $force))
            ->values();

        return response()->json([
            'articles' => $articles,
            'summary' => [
                'article_count' => $articles->count(),
                'issue_count' => $articles->sum('issue_count'),
                'articles_with_issues' => $articles->where('has_issues', true)->count(),
                'blocked_count' => $articles->sum('blocked_count'),
            ],
        ]);
    }

    public function searchReplacement(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|url',
            'article_title' => 'nullable|string|max:255',
        ]);

        $query = $this->buildReplacementQuery($data['url'], $data['article_title'] ?? null);

        try {
            $results = $this->duckDuckGoResults($query);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Replacement search failed.',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function toggleIgnoredLink(Request $request)
    {
        $data = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
            'url' => 'required|url',
            'ignored' => 'required|boolean',
        ]);

        $map = $this->ignoredLinksMap();
        $articleKey = (string) $data['article_id'];
        $urls = $map[$articleKey] ?? [];

        if (!is_array($urls)) {
            $urls = [];
        }

        if ($data['ignored']) {
            if (!in_array($data['url'], $urls, true)) {
                $urls[] = $data['url'];
            }
        } else {
            $urls = array_values(array_filter($urls, fn(string $url) => $url !== $data['url']));
        }

        if ($urls) {
            $map[$articleKey] = array_values(array_unique($urls));
        } else {
            unset($map[$articleKey]);
        }

        $this->saveIgnoredLinksMap($map);

        return response()->json([
            'ok' => true,
            'ignored' => (bool) $data['ignored'],
        ]);
    }

    // ── Tag suggestions ────────────────────────────────────────────────────

    public function suggestTags(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string',
            'body'  => 'nullable|string',
        ]);

        $allTags = Tag::orderBy('name')->pluck('name')->join(', ');

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 200,
            'system'     => "You suggest tags for articles. Available tags: {$allTags}. Return ONLY a JSON array of tag names from the available list. Maximum 8 tags. No explanation.",
            'messages'   => [[
                'role'    => 'user',
                'content' => "Title: " . ($request->title ?? '') . "\n\nBody: " . substr($request->body ?? '', 0, 500),
            ]],
        ]);

        if (!$response->successful()) {
            $message = $response->json('error.message', 'Claude API error.');
            return response()->json(['error' => $message], 422);
        }

        $text  = $response->json('content.0.text', '[]');
        $names = json_decode(preg_replace('/```json|```/', '', trim($text)), true) ?? [];

        $tags = Tag::whereIn('name', $names)
            ->get()
            ->map(fn(Tag $t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])
            ->all();

        return response()->json($tags);
    }

    // ── URL meta fetch ─────────────────────────────────────────────────────

    private function metadataForUrl(string $url): array
    {
        // Detect YouTube
        $youtubeCode = null;
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            $youtubeCode = $m[1];
        }

        $isGenericYoutubeDescription = static function (?string $text): bool {
            if (!$text) {
                return false;
            }

            $normalized = trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)));

            return in_array($normalized, [
                'auf youtube findest du die angesagtesten videos und tracks. außerdem kannst du eigene inhalte hochladen und mit freunden oder gleich der ganzen welt teilen.',
                'enjoy the videos and music you love, upload original content, and share it all with friends, family, and the world on youtube.',
            ], true);
        };

        $html = '';
        try {
            $ctx = stream_context_create(['http' => [
                'timeout'         => 5,
                'follow_location' => true,
                'user_agent'      => 'Mozilla/5.0 (compatible; Chimbi/1.0)',
            ]]);
            $html = @file_get_contents($url, false, $ctx);
            if ($html === false) {
                $html = '';
            }
        } catch (\Throwable) {
            $html = '';
        }

        $title = null;
        $description = null;
        $thumbnail = null;

        if ($html) {
            if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/', $html, $m)) {
                $title = html_entity_decode($m[1], ENT_QUOTES);
            } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
                $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES);
            }

            if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/', $html, $m)) {
                $description = html_entity_decode($m[1], ENT_QUOTES);
            } elseif (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/', $html, $m)) {
                $description = html_entity_decode($m[1], ENT_QUOTES);
            }

            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/', $html, $m)) {
                $thumbnail = $m[1];
            }
        }

        if ($youtubeCode && $isGenericYoutubeDescription($description)) {
            $description = null;
        }

        if ($youtubeCode && !$thumbnail) {
            $thumbnail = "https://img.youtube.com/vi/{$youtubeCode}/hqdefault.jpg";
        }

        return [
            'title' => $title,
            'description' => $description,
            'thumbnail_url' => $thumbnail,
            'youtube_code' => $youtubeCode,
        ];
    }

    public function fetchMeta(Request $request)
    {
        $request->validate(['url' => 'required|url']);
        return response()->json($this->metadataForUrl($request->url));
    }

    public function thumbnailSuggestion(Request $request)
    {
        $data = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
        ]);

        $article = Article::findOrFail($data['article_id']);

        if (!$article->source_url) {
            return response()->json([
                'error' => 'This article has no source URL to fetch a thumbnail from.',
            ], 422);
        }

        $meta = $this->metadataForUrl($article->source_url);
        $suggestedUrl = $meta['thumbnail_url'] ?? null;

        if (!$suggestedUrl) {
            return response()->json([
                'error' => 'No thumbnail candidate was found from the source URL.',
            ], 422);
        }

        return response()->json([
            'current_thumbnail_url' => $article->thumbnail_url,
            'suggested_thumbnail_url' => $suggestedUrl,
            'source_url' => $article->source_url,
        ]);
    }

    public function applyThumbnailSuggestion(Request $request)
    {
        $data = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
            'thumbnail_url' => 'required|url|max:500',
        ]);

        $article = Article::findOrFail($data['article_id']);
        $article->update([
            'thumbnail_url' => $data['thumbnail_url'],
        ]);

        return response()->json([
            'ok' => true,
            'thumbnail_url' => $article->thumbnail_url,
        ]);
    }

    public function deleteBlockPreview(Request $request)
    {
        $data = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
            'url' => 'required|url',
        ]);

        $article = Article::findOrFail($data['article_id']);
        $candidates = $this->bodyBlockRemovalCandidates($article, $data['url']);

        if (!$candidates) {
            return response()->json([
                'error' => 'Could not find a removable body block for that broken or blocked link.',
            ], 422);
        }

        return response()->json([
            'candidates' => array_map(
                fn(array $candidate) => [
                    'key' => $candidate['key'],
                    'label' => $candidate['label'],
                    'preview_html' => $candidate['preview_html'],
                ],
                $candidates
            ),
        ]);
    }

    public function deleteBlock(Request $request)
    {
        $data = $request->validate([
            'article_id' => 'required|integer|exists:articles,id',
            'url' => 'required|url',
            'candidate_key' => 'required|string',
        ]);

        $article = Article::findOrFail($data['article_id']);
        $candidates = collect($this->bodyBlockRemovalCandidates($article, $data['url']));
        $selected = $candidates->firstWhere('key', $data['candidate_key']);

        if (!$selected) {
            return response()->json([
                'error' => 'Could not find a removable body block for that broken or blocked link.',
            ], 422);
        }

        $article->update([
            'body' => $selected['updated_body'],
        ]);

        return response()->json([
            'ok' => true,
        ]);
    }
}
