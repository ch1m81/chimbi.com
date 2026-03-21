<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/',         [ArticleController::class, 'index'])->name('home');
Route::get('/popular',  [ArticleController::class, 'popular'])->name('articles.popular');
Route::get('/newest',   [ArticleController::class, 'index'])->name('articles.newest');
Route::get('/tagged',   [ArticleController::class, 'tagged'])->name('articles.tagged');

Route::get('/view/{article}/{slug}', [ArticleController::class, 'show'])
    ->name('articles.show');

Route::get('/archive',    [ArticleController::class, 'archive'])->name('articles.archive');

Route::post('/articles/{article}/love', [ArticleController::class, 'love'])
    ->name('articles.love');

// ── Admin ─────────────────────────────────────────────────────────────────
use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminAuth;

Route::get('/chimbi/login',  [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/chimbi/login', [AdminController::class, 'login'])->name('admin.login.post');
Route::post('/chimbi/logout',[AdminController::class, 'logout'])->name('admin.logout');

Route::middleware(AdminAuth::class)->group(function () {
    Route::get('/chimbi/create',         [AdminController::class, 'create'])->name('admin.create');
    Route::post('/chimbi/create',        [AdminController::class, 'store'])->name('admin.store');
    Route::get('/chimbi/edit/{article}', [AdminController::class, 'edit'])->name('admin.edit');
    Route::put('/chimbi/edit/{article}', [AdminController::class, 'update'])->name('admin.update');
    Route::delete('/chimbi/delete/{article}', [AdminController::class, 'destroy'])->name('admin.destroy');
    Route::post('/chimbi/fetch-meta',    [AdminController::class, 'fetchMeta'])->name('admin.fetch-meta');
    Route::post('/chimbi/suggest-tags',  [AdminController::class, 'suggestTags'])->name('admin.suggest-tags');
});
    Route::post('/chimbi/tags', function (\Illuminate\Http\Request $request) {
        $request->validate(['name' => 'required|string|max:100']);
        $slug = \Illuminate\Support\Str::slug($request->name);
        $tag  = \App\Models\Tag::firstOrCreate(['slug' => $slug], ['name' => $request->name]);
        return response()->json(['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug]);
    })->middleware(AdminAuth::class)->name('admin.tags.create');