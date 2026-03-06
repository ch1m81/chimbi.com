<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 150)->unique();

            // Full HTML body content
            $table->longText('body')->nullable();

            // Source URL (where the content was found)
            $table->text('source_url')->nullable();

            // Local thumbnail filename, e.g. "simpsons-001.jpg"
            // Served from /rucno/slike/slike_post/ or a new storage path
            $table->string('thumbnail', 512)->nullable();

            // YouTube video ID, e.g. "dQw4w9WgXcQ"
            $table->string('youtube_code', 20)->nullable();

            // Star / like count
            $table->unsignedInteger('love')->default(0);

            // Visible on the site
            $table->boolean('published')->default(false);

            // The real publish date (was stored as a string in the old DB)
            $table->date('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
