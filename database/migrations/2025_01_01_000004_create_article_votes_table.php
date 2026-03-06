<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks which IPs have already voted on an article,
        // so one IP can't keep adding love to the same post.
        Schema::create('article_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            // IPv4 or IPv6 — varchar(45) covers both
            $table->string('ip_address', 45);
            $table->timestamp('voted_at')->useCurrent();

            // One vote per IP per article
            $table->unique(['article_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_votes');
    }
};
