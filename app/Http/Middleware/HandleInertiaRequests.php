<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'isAdmin' => session('admin_auth', false),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'deletedArticleTitle' => fn() => $request->session()->get('deleted_article_title'),
            ],
        ];
    }
}
