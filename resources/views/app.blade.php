<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ ($appearance ?? 'system') === 'dark' ? '#0b0b0c' : '#f4f7fc' }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#0b0b0c');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f4f7fc;
            }

            html.dark {
                background-color: #0b0b0c;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        @fonts

        @if (str_starts_with($page['component'] ?? '', 'stores/'))
            <script src="https://telegram.org/js/telegram-web-app.js"></script>
        @else
            {{-- A mini app link opened on another page: go to the store it names, keeping Telegram's data. --}}
            <script>
                (function () {
                    try {
                        var data = new URLSearchParams(window.location.hash.slice(1)).get('tgWebAppData');
                        var start = data ? new URLSearchParams(data).get('start_param') : null;

                        if (start && /^[A-Za-z0-9_-]{1,64}$/.test(start)) {
                            window.location.replace('/m?startapp=' + encodeURIComponent(start) + window.location.hash);
                        }
                    } catch (error) {}
                })();
            </script>
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @php($meta = $page['props']['meta'] ?? null)
        {{-- Without server-side rendering this slot is the head; with it, the rendered page supplies the same tags. --}}
        <x-inertia::head>
            <title>{{ is_array($meta) ? $meta['title'].' - '.config('app.name', 'Vendly') : config('app.name', 'Vendly') }}</title>
            @if (is_array($meta))
                <meta name="description" content="{{ $meta['description'] }}" inertia="description">
                <link rel="canonical" href="{{ $meta['url'] }}" inertia="canonical">
                <meta property="og:title" content="{{ $meta['title'] }} - {{ config('app.name', 'Vendly') }}" inertia="og:title">
                <meta property="og:description" content="{{ $meta['description'] }}" inertia="og:description">
                <meta property="og:url" content="{{ $meta['url'] }}" inertia="og:url">
            @endif
        </x-inertia::head>
        @if (is_array($meta))
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="{{ config('app.name', 'Vendly') }}">
            <meta property="og:image" content="{{ asset('images/brand/vendly-logo.png') }}">
            <meta name="twitter:card" content="summary">
        @endif
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
