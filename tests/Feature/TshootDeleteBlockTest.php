<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TshootDeleteBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_block_preview_matches_plain_domain_text_for_normalized_url(): void
    {
        $article = Article::create([
            'title' => 'Broken body link test',
            'slug' => 'broken-body-link-test',
            'body' => '<p>others.com<br> The track "Tchavolo Swing" is on iTunes: http://bit.ly/xqHW39</p><p>Joseph Herscher takes a si</p>',
            'source_url' => 'https://example.com/source',
            'published' => true,
        ]);

        $preview = $this
            ->withSession(['admin_auth' => true])
            ->postJson(route('admin.tshoot.delete-block-preview'), [
                'article_id' => $article->id,
                'url' => 'https://others.com',
            ]);

        $preview
            ->assertOk()
            ->assertJsonPath('candidates.0.preview_html', '<p>others.com<br> The track "Tchavolo Swing" is on iTunes: http://bit.ly/xqHW39</p>');

        $candidateKey = $preview->json('candidates.0.key');

        $delete = $this
            ->withSession(['admin_auth' => true])
            ->postJson(route('admin.tshoot.delete-block'), [
                'article_id' => $article->id,
                'url' => 'https://others.com',
                'candidate_key' => $candidateKey,
            ]);

        $delete->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('<p>Joseph Herscher takes a si</p>', $article->fresh()->body);
    }
}
