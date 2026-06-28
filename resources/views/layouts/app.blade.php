<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $mcaPermTitle ?? mca_perm('app.title'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ \Mca\Permission\Support\McaPermissionView::uiCssUrl() }}">
    <link rel="stylesheet" href="{{ \Mca\Permission\Support\McaPermissionView::cssUrl() }}">
    @stack('mca-perm-head')
</head>
<body class="mca-ui-root mca-perm-root">
    @include('mca-permission::partials.header')

    <main class="mca-ui-main mca-perm-main">
        @include('mca-permission::partials.flash')
        @yield('content')
    </main>

    @php
        $mcaUiI18n = [
            'ok' => mca_perm('modal.ok'),
            'confirm' => mca_perm('modal.confirm'),
            'cancel' => mca_perm('modal.cancel'),
            'close' => mca_perm('modal.close'),
            'alert_title' => mca_perm('modal.alert_title'),
            'confirm_title' => mca_perm('modal.confirm_title'),
            'delete_title' => mca_perm('modal.delete_title'),
        ];
    @endphp
    <script>
        window.McaUiI18n = @json($mcaUiI18n);
    </script>
    <script src="{{ \Mca\Permission\Support\McaPermissionView::uiJsUrl() }}" defer></script>
    <script src="{{ \Mca\Permission\Support\McaPermissionView::jsUrl() }}" defer></script>
    @stack('mca-perm-scripts')
</body>
</html>
