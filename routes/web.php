<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index'])->name('home');

Route::get('/view/{article}/{slug}', [ArticleController::class, 'show'])
    ->name('articles.show');

// Love / vote — POST so it doesn't get crawled
Route::post('/articles/{article}/love', [ArticleController::class, 'love'])
    ->name('articles.love');
