<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    // ── Shared helpers ────────────────────────────────────────────────────

    private function allTags(): array
    {
        return Tag::orderBy('name')->get()
            ->map(fn (Tag $t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])
            ->all();
    }

    private function formArticle(Article $article): array
    {
        $article->load('tags');

        // Extract first image from body as fallback thumbnail
        $thumbnailUrl = $article->thumbnail_url;
        if (!$thumbnailUrl && $article->body) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $article->body, $m)) {
                $thumbnailUrl = $m[1];
            }
        }

        return [
            'id'           => $article->id,
            'title'        => $article->title,
            'slug'         => $article->slug,
            'body'         => $article->getRawOriginal('body') ?? $article->body,
            'body_trim'    => $article->body_trim,
            'source_url'   => $article->source_url,
            'youtube_code' => $article->youtube_code,
            'thumbnail'    => $article->thumbnail,
            'thumbnail_url'=> $article->thumbnail_url,
            'love'         => $article->love,
            'published'    => $article->published,
            'published_at' => $article->published_at?->format('Y-m-d'),
            'tags'         => $article->tags->pluck('id')->all(),
            'thumbnail_url' => $thumbnailUrl,

        ];
    }

    // ── Password check ────────────────────────────────────────────────────

    public function loginForm(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $request->validate(['password' => 'required']);

        if ($request->password !== config('app.admin_password')) {
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        session(['admin_auth' => true]);
        return redirect()->route('admin.create');
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
            'referrer' => $request->headers->get('referer', '/'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:articles,slug',
            'body'         => 'nullable|string',
            'body_trim'    => 'nullable|integer|min:50',
            'source_url'   => 'nullable|url|max:500',
            'youtube_code' => 'nullable|string|max:50',
            'thumbnail_url'=> 'nullable|string|max:500',
            'love'         => 'nullable|integer|min:0',
            'published'    => 'boolean',
            'published_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'integer|exists:tags,id',
        ]);

        // Handle thumbnail upload
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

        return redirect()->route('admin.edit', $article)
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
            'referrer'    => $request->headers->get('referer', '/'),
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
            'source_url'   => 'nullable|url|max:500',
            'youtube_code' => 'nullable|string|max:50',
            'thumbnail_url'=> 'nullable|string|max:500',
            'love'         => 'nullable|integer|min:0',
            'published'    => 'boolean',
            'published_at' => 'nullable|date',
            'tags'         => 'array',
            'tags.*'       => 'integer|exists:tags,id',
        ]);

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

    public function destroy(Article $article)
    {
        $article->tags()->detach();
        $article->delete();
        return redirect()->route('admin.create')
            ->with('success', 'Article deleted.');
    }

    // ── URL meta fetch ─────────────────────────────────────────────────────


    public function suggestTags(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string',
            'body'  => 'nullable|string',
        ]);

        $allTags = Tag::orderBy('name')->pluck('name')->join(', ');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
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
            ->map(fn (Tag $t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])
            ->all();

        return response()->json($tags);
    }

public function fetchMeta(Request $request)
{
    $request->validate(['url' => 'required|url']);
    $url = $request->url;

    // Detect YouTube
    $youtubeCode = null;
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $youtubeCode = $m[1];
    }

    // Reddit — use JSON API
    if (preg_match('/reddit\.com\/r\/[^\/]+\/comments\//', $url)) {
        $jsonUrl = rtrim(preg_replace('/[?#].*$/', '', $url), '/') . '/.json';
        $redditError = null;
        $redditStatus = null;

        try {
            $response = \Illuminate\Support\Facades\Http::withUserAgent('Mozilla/5.0 (compatible; Chimbi/1.0)')
                ->timeout(5)
                ->get($jsonUrl);

            $redditStatus = $response->status();

            if ($response->successful()) {
                $data = $response->json();
                $post = $data[0]['data']['children'][0]['data'] ?? [];
                $title = $post['title'] ?? null;
                $thumb = null;

                $previews = $post['preview']['images'][0]['resolutions'] ?? [];
                if ($previews) {
                    $thumb = html_entity_decode(end($previews)['url']);
                } else {
                    $dest = $post['url_overridden_by_dest'] ?? null;
                    if ($dest && preg_match('/\.(jpg|jpeg|png|gif|webp)/i', $dest)) {
                        $thumb = $dest;
                    }
                }

                return response()->json([
                    'matched'      => 'reddit',
                    'title'        => $title,
                    'description'  => $post['selftext'] ?? null,
                    'thumbnail_url'=> $thumb,
                    'youtube_code' => null,
                ]);
            } else {
                $redditError = 'HTTP ' . $redditStatus . ': ' . substr($response->body(), 0, 200);
            }
        } catch (\Throwable $e) {
            $redditError = $e->getMessage();
        }

        // Reddit failed — return debug info
        return response()->json([
            'matched'       => 'reddit_failed',
            'jsonUrl'       => $jsonUrl,
            'status'        => $redditStatus,
            'error'         => $redditError,
            'title'         => null,
            'description'   => null,
            'thumbnail_url' => null,
            'youtube_code'  => null,
        ]);
    }

    // Standard HTML fetch
    $html = '';
    $fetchError = null;
    try {
        $ctx = stream_context_create(['http' => [
            'timeout'          => 5,
            'follow_location'  => true,
            'user_agent'       => 'Mozilla/5.0 (compatible; Chimbi/1.0)',
        ]]);
        $html = @file_get_contents($url, false, $ctx);
        if ($html === false) $html = '';
    } catch (\Throwable $e) {
        $fetchError = $e->getMessage();
        $html = '';
    }

    $title       = null;
    $description = null;
    $thumbnail   = null;

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

    if ($youtubeCode && !$thumbnail) {
        $thumbnail = "https://img.youtube.com/vi/{$youtubeCode}/hqdefault.jpg";
    }

    $matched = 'standard_html';
    if ($youtubeCode) $matched = 'youtube';

    return response()->json([
        'matched'      => $matched,
        'fetch_error'  => $fetchError,
        'title'        => $title,
        'description'  => $description,
        'thumbnail_url'=> $thumbnail,
        'youtube_code' => $youtubeCode,
    ]);
}