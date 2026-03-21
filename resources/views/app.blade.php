<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- ── Primary SEO ──────────────────────────────────────────────────── -->
    <meta name="description" content="chimbi h0mepage — chimbi-jevo igraliste, ovde se cika chimbi igra :) dobrodošli \o/" />
    <meta name="robots" content="index, follow" />

    <!-- ── Favicon ──────────────────────────────────────────────────────── -->
    <link rel="icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" href="/favicon.ico" />

    <!-- ── Fonts ────────────────────────────────────────────────────────── -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Reenie+Beanie&display=swap" rel="stylesheet" />

    <!-- ── Google Analytics 4 ───────────────────────────────────────────── -->
    @if(config('app.ga_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('app.ga_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('app.ga_id') }}');
    </script>
    @endif

    <!-- ── Vite + Inertia (per-page <Head> tags injected here) ─────────── -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>
<body>
    <div scroll-region style="height:100vh; overflow-y:auto; overflow-x:hidden; scroll-behavior:smooth;">
        @inertia
    </div>
</body>
</html>