<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', __('notification-preferences::notification-preferences.unsubscribe'))</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f4f4f5; margin: 0; display: grid; min-height: 100vh; place-items: center; padding: 1.5rem; }
        .card { background: #fff; max-width: 28rem; width: 100%; border-radius: 0.875rem; box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06); padding: 2rem; text-align: center; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; color: #18181b; }
        p { color: #52525b; line-height: 1.5; margin: 0 0 1.5rem; }
        p:last-child { margin-bottom: 0; }
        button { background: #18181b; color: #fff; border: 0; border-radius: 0.5rem; padding: .7rem 1.25rem; font-size: .95rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #27272a; }
        @media (prefers-color-scheme: dark) { body { background: #09090b; } .card { background: #18181b; } h1 { color: #fafafa; } p { color: #a1a1aa; } button { background: #fafafa; color: #18181b; } }
    </style>
</head>
<body>
    <div class="card">
        @yield('content')
    </div>
</body>
</html>
