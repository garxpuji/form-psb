<!DOCTYPE html>
<html lang="id" class="h-full bg-[#EDF5FC] text-[#06233B]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#072A47">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">

    <title>{{ $title ?? config('app.name', 'ISP Auto-PSB') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #005F99 !important;
            outline-offset: 2px !important;
        }

        /* Keep focused mobile inputs above the on-screen virtual keyboard. */
        input,
        select,
        textarea {
            scroll-margin-bottom: 6rem;
        }

        .ts-wrapper.single .ts-control {
            background: #FFFFFF !important;
            border: 1.5px solid #5B82A6 !important;
            border-radius: 0.5rem !important;
            padding: 0.625rem 0.875rem !important;
            min-height: 46px !important;
            color: #06233B !important;
            font-size: 0.9375rem !important;
            display: flex;
            align-items: center;
            box-shadow: none !important;
        }

        .ts-wrapper.single.focus .ts-control {
            border-color: #005F99 !important;
            outline: 2px solid #005F99 !important;
            outline-offset: 1px !important;
        }

        .ts-control input {
            color: #06233B !important;
            font-size: 0.9375rem !important;
        }

        .ts-control input::placeholder {
            color: #2C5270 !important;
        }

        .ts-dropdown {
            background: #FFFFFF !important;
            border: 1.5px solid #005F99 !important;
            border-radius: 0.5rem !important;
            color: #06233B !important;
            margin-top: 4px !important;
            z-index: 60 !important;
            box-shadow: 0 10px 25px -5px rgba(7, 42, 71, 0.18) !important;
        }

        .ts-dropdown .option {
            padding: 0.65rem 0.875rem !important;
            min-height: 44px !important;
            color: #06233B !important;
            border-bottom: 1px solid #E2EEF8;
        }

        .ts-dropdown .option.active,
        .ts-dropdown .option:hover {
            background: #E0F2FE !important;
            color: #06233B !important;
        }
    </style>
</head>

<body class="min-h-full bg-[#EDF5FC] text-[#06233B] antialiased selection:bg-[#005F99] selection:text-white">
    @yield('content')
    @stack('scripts')
</body>

</html>