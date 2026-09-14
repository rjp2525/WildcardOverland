@php
    // Rendered here rather than through Inertia: crawlers and link unfurlers
    // read this first response and do not run JavaScript.
    $seo = $page['props']['seo'] ?? \App\Support\Seo::make('Wildcard Overland');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    @unless ($seo['index'])
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endunless

    <meta property="og:site_name" content="{{ $seo['site'] }}">
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if ($seo['image'])
        <meta property="og:image" content="{{ $seo['image']['url'] }}">
        <meta property="og:image:width" content="{{ $seo['image']['width'] }}">
        <meta property="og:image:height" content="{{ $seo['image']['height'] }}">
        <meta property="og:image:alt" content="{{ $seo['image']['alt'] }}">
    @endif

    <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    @if ($seo['image'])
        <meta name="twitter:image" content="{{ $seo['image']['url'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['image']['alt'] }}">
    @endif

    <meta name="theme-color" content="#e85a2f">

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

    @if (! empty($seo['schema']))
        <script type="application/ld+json">{!! json_encode(\App\Support\StructuredData::graph($seo['schema']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    {{--
        Set the theme before anything paints. Doing it from a Vue component
        meant a flash of the wrong one on every load, and it never ran at all
        in the admin, which has no navbar.
    --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var dark = saved === 'dark' || (saved === null
                    && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            } catch (e) {
                // Private browsing can refuse localStorage. Light is the default.
            }
        })();
    </script>

    @routes
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-white dark:bg-black">
    @inertia
</body>
</html>
