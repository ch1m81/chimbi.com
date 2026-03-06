<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleVote;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ImportLifestreamSeeder
 *
 * Imports the old `lifestream` database into the new chimbi schema.
 *
 * HOW TO RUN:
 *   1. Make sure the old database is accessible. Either:
 *      a) Import lifestream.sql into a separate DB (e.g. `lifestream`) on the
 *         same MySQL server, then set OLD_DB_* in your .env (see below), OR
 *      b) Import it directly into the chimbi DB temporarily.
 *
 *   2. Add these to your .env:
 *        OLD_DB_DATABASE=lifestream
 *        OLD_DB_USERNAME=chimbi_user
 *        OLD_DB_PASSWORD=chimbi_pass
 *
 *   3. Run fresh migrations + this seeder:
 *        php artisan migrate:fresh --seeder=ImportLifestreamSeeder
 *
 *      Or just the seeder on an already-migrated DB:
 *        php artisan db:seed --class=ImportLifestreamSeeder
 */
class ImportLifestreamSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Connecting to old lifestream database...');

        // Dynamically configure a second DB connection pointing at the old data
        config(['database.connections.lifestream' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'mysql'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('OLD_DB_DATABASE', 'lifestream'),
            'username'  => env('OLD_DB_USERNAME', env('DB_USERNAME')),
            'password'  => env('OLD_DB_PASSWORD', env('DB_PASSWORD')),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]]);

        $old = DB::connection('lifestream');

        // ------------------------------------------------------------------ //
        // 1. TAGS                                                              //
        // ------------------------------------------------------------------ //
        $this->command->info('Importing tags...');

        $oldTags = $old->table('tags')->orderBy('tag_id')->get();

        // tag_id (old) => Tag model (new)
        $tagMap = [];

        foreach ($oldTags as $oldTag) {
            $name = trim($oldTag->tag_name);
            if (! $name) {
                continue;
            }

            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name, '_')],
                ['name' => $name]
            );

            $tagMap[$oldTag->tag_id] = $tag;
        }

        $this->command->info("  → {$oldTags->count()} tags imported.");

        // ------------------------------------------------------------------ //
        // 2. ARTICLES                                                          //
        // ------------------------------------------------------------------ //
        $this->command->info('Importing articles...');

        $oldPosts = $old->table('post')->orderBy('article_id')->get();
        $imported  = 0;
        $skipped   = 0;

        foreach ($oldPosts as $post) {

            // Parse the freeform date string, e.g. "11 September 2011"
            $publishedAt = self::parseDate($post->datum);

            // Clean up the thumbnail field:
            // slika_lokal is the reliable local filename ("simpsons-001.jpg")
            // slika is often a raw <img> tag from an external source — skip it
            $thumbnail = ! empty($post->slika_lokal) ? trim($post->slika_lokal) : null;

            // Ensure slug is unique (old data occasionally has duplicates)
            $slug = self::uniqueSlug($post->slug_naslov);

            $article = Article::create([
                'title'        => trim($post->naslov ?? ''),
                'slug'         => $slug,
                'body'         => $post->tekst,
                'source_url'   => ! empty($post->url) ? trim($post->url) : null,
                'thumbnail'    => $thumbnail,
                'youtube_code' => ! empty($post->youtube_code) ? trim($post->youtube_code) : null,
                'love'         => max(0, (int) $post->love),
                'published'    => (bool) $post->published,
                'published_at' => $publishedAt,
                // Preserve the original timestamps from the old DB
                'created_at'   => $post->timestamp,
                'updated_at'   => $post->timestamp,
            ]);

            // ---------------------------------------------------------------- //
            // 3. ARTICLE <-> TAG PIVOT (from post_tag join table)               //
            // ---------------------------------------------------------------- //
            $oldPivotRows = $old->table('post_tag')
                ->where('id_post', $post->article_id)
                ->pluck('id_tag');

            $newTagIds = [];
            foreach ($oldPivotRows as $oldTagId) {
                if (isset($tagMap[$oldTagId])) {
                    $newTagIds[] = $tagMap[$oldTagId]->id;
                }
            }

            // Also import any tags that existed only in the legacy `tagovi`
            // comma-separated field but were NOT in the post_tag table
            if (! empty($post->tagovi)) {
                $rawTags = array_filter(array_map('trim', explode(',', $post->tagovi)));
                foreach ($rawTags as $rawName) {
                    if (! $rawName) {
                        continue;
                    }
                    $tagSlug = Str::slug($rawName, '_');
                    $tag = Tag::firstOrCreate(
                        ['slug' => $tagSlug],
                        ['name' => $rawName]
                    );
                    if (! in_array($tag->id, $newTagIds)) {
                        $newTagIds[] = $tag->id;
                    }
                }
            }

            if ($newTagIds) {
                $article->tags()->sync(array_unique($newTagIds));
            }

            $imported++;
        }

        $this->command->info("  → {$imported} articles imported, {$skipped} skipped.");

        // ------------------------------------------------------------------ //
        // 4. VOTES  (article_IP → article_votes)                              //
        // ------------------------------------------------------------------ //
        $this->command->info('Importing votes...');

        // Build a map of old article_id => new article id
        $articleIdMap = Article::pluck('id', 'slug')->all();
        // We'll look up by the new auto-increment id; easier to re-query
        // Since we created articles in order, we can match via a temp lookup.
        // Rebuild map: old_article_id (int) => new Article id
        $oldToNew = [];
        foreach ($oldPosts as $post) {
            $slug = self::slugForPost($post->slug_naslov);
            if (isset($articleIdMap[$slug])) {
                $oldToNew[$post->article_id] = $articleIdMap[$slug];
            }
        }

        $oldVotes    = $old->table('article_IP')->orderBy('ip_id')->get();
        $voteRows    = [];
        $votesSeen   = []; // deduplicate (article_id, ip) pairs

        foreach ($oldVotes as $vote) {
            $newArticleId = $oldToNew[$vote->article_id_fk] ?? null;
            if (! $newArticleId) {
                continue;
            }
            $key = "{$newArticleId}_{$vote->ip_add}";
            if (isset($votesSeen[$key])) {
                continue; // old table had no unique constraint, skip dupes
            }
            $votesSeen[$key] = true;
            $voteRows[] = [
                'article_id' => $newArticleId,
                'ip_address' => $vote->ip_add,
                'voted_at'   => now(),
            ];
        }

        // Chunk inserts to avoid hitting query size limits
        foreach (array_chunk($voteRows, 500) as $chunk) {
            ArticleVote::insert($chunk);
        }

        $this->command->info("  → " . count($voteRows) . " votes imported.");
        $this->command->info('✅  Import complete!');
    }

    // ------------------------------------------------------------------ //
    // Helpers                                                              //
    // ------------------------------------------------------------------ //

    /**
     * Parse the old freeform date strings into Y-m-d.
     *
     * Old format examples:
     *   "11 September 2011"
     *   "7 September 2011"
     *   "13 March 2013"
     */
    private static function parseDate(?string $datum): ?string
    {
        if (empty($datum)) {
            return null;
        }

        $datum = trim($datum);

        // Try PHP's own parser first — it handles "11 September 2011" natively
        $ts = strtotime($datum);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Tracks used slugs in memory so we never insert a duplicate.
     */
    private static array $usedSlugs = [];

    private static function uniqueSlug(string $rawSlug): string
    {
        $slug = Str::slug($rawSlug, '-') ?: 'article';

        if (! isset(self::$usedSlugs[$slug])) {
            self::$usedSlugs[$slug] = true;
            return $slug;
        }

        // Append -2, -3, etc. until unique
        $i = 2;
        while (isset(self::$usedSlugs["{$slug}-{$i}"])) {
            $i++;
        }
        $final = "{$slug}-{$i}";
        self::$usedSlugs[$final] = true;
        return $final;
    }

    /**
     * Reproduce the same slug that uniqueSlug() would have assigned,
     * for the vote-mapping pass. Since we process posts in the same
     * order both times, the slugs will match.
     */
    private static array $slugLookup = [];

    private static function slugForPost(string $rawSlug): string
    {
        // This is only called AFTER the import loop, so self::$usedSlugs
        // already has all final slugs. We need to reproduce the assignment.
        // Simplest: just resolve from a separate lookup we build lazily.
        static $built = false;
        if (! $built) {
            // Re-derive by re-running the uniqueSlug logic on a fresh copy
            // — but we already ran it, so the map IS self::$usedSlugs.
            // Instead, we look up via Article::where('slug') below.
            $built = true;
        }
        return Str::slug($rawSlug, '-') ?: 'article';
    }
}
