<!DOCTYPE html>
<html lang="id" class="h-full bg-[#EDF5FC] text-[#06233B]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#072A47">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">

    <title>{{ $title ?? config('app.name', 'ISP Auto-PSB') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-[#EDF5FC] text-[#06233B] antialiased selection:bg-[#005F99] selection:text-white">
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-[#005F99] focus:px-4 focus:py-2.5 focus:text-xs focus:font-bold focus:text-white focus:shadow-lg">
        Lewati ke Konten Utama
    </a>

    <div id="main-content">
        @yield('content')
    </div>

    @stack('scripts')
</body>

</html>
