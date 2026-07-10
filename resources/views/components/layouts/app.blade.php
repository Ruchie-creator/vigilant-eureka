@props([
    'heading' => 'Dashboard',
    'title' => 'AI Marketing Agents',
])

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AI Marketing Agents' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { navy: '#051237', teal: '#016576', mist: '#F3F8FA' }, boxShadow: { soft: '0 18px 45px rgba(5, 18, 55, 0.08)' } } } }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
<div class="min-h-screen lg:flex">
    <aside class="bg-navy text-white lg:fixed lg:inset-y-0 lg:w-72">
        <div class="px-6 py-5">
            <div class="text-xs uppercase tracking-[0.25em] text-teal-200">clint-rono.dev</div>
            <div class="mt-1 text-xl font-semibold">Marketing Agents</div>
        </div>
        <nav class="grid gap-1 px-4 pb-6 text-sm">
            @foreach ([['Dashboard','dashboard'],['Websites','websites.index'],['SEO Audits','seo-audits.index'],['AI Insights','ai-insights.index'],['Marketing Tasks','marketing-tasks.index'],['Weekly Reports','weekly-reports.index'],['Settings','settings']] as [$label, $route])
                <a href="{{ route($route) }}" class="rounded-lg px-4 py-3 font-medium transition {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'bg-white text-navy' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <main class="flex-1 lg:ml-72">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex items-center justify-between gap-4 px-5 py-4 lg:px-8">
                <div>
                    <h1 class="text-xl font-semibold text-navy">{{ $heading ?? 'Dashboard' }}</h1>
                    <p class="text-sm text-slate-500">AI Growth & Conversion Agent for healthcare websites. No patient medical data is collected.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-teal hover:text-teal">Logout</button></form>
            </div>
        </header>
        <section class="px-5 py-6 lg:px-8">
            @if (session('success'))<div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            {{ $slot }}
        </section>
    </main>
</div>
</body>
</html>
