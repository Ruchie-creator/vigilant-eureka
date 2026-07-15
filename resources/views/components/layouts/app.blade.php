@props([
    'heading' => 'Dashboard',
    'title' => 'AI Growth & Conversion Agent',
])

@php
    $navigation = [
        ['label' => 'Overview', 'route' => 'dashboard', 'matches' => ['dashboard'], 'icon' => 'layout-dashboard'],
        ['label' => 'Workspaces', 'route' => 'websites.index', 'matches' => ['websites.*'], 'icon' => 'globe-2'],
        ['label' => 'AI Marketing Team', 'route' => 'agents.index', 'matches' => ['agents.*', 'agent-actions.*'], 'icon' => 'users-round'],
        ['label' => 'SEO Audits', 'route' => 'seo-audits.index', 'matches' => ['seo-audits.*'], 'icon' => 'scan-search'],
        ['label' => 'AI Insights', 'route' => 'ai-insights.index', 'matches' => ['ai-insights.*'], 'icon' => 'sparkles'],
        ['label' => 'Marketing Tasks', 'route' => 'marketing-tasks.index', 'matches' => ['marketing-tasks.*'], 'icon' => 'list-checks'],
        ['label' => 'Weekly Reports', 'route' => 'weekly-reports.index', 'matches' => ['weekly-reports.*'], 'icon' => 'chart-no-axes-combined'],
        ['label' => 'Settings', 'route' => 'settings', 'matches' => ['settings', 'google.search-console.*'], 'icon' => 'settings-2'],
    ];
    $userName = auth()->user()?->name ?: auth()->user()?->email ?: 'Administrator';
    $initials = collect(preg_split('/\s+/', trim($userName)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'AD';
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AI Growth & Conversion Agent' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#051237',
                        ink: '#07152f',
                        teal: '#016576',
                        aqua: '#0c9aa6',
                        mist: '#f4f7fb',
                    },
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    boxShadow: { soft: '0 1px 2px rgba(5,18,55,.04), 0 12px 32px rgba(5,18,55,.07)' },
                },
            },
        }
    </script>
    <style>
        :root {
            color-scheme: light;
            --navy: #051237;
            --navy-2: #071a3d;
            --teal: #016576;
            --teal-bright: #0c9aa6;
            --canvas: #f4f7fb;
            --surface: #ffffff;
            --muted: #64748b;
            --shadow: 0 1px 2px rgba(5,18,55,.04), 0 12px 32px rgba(5,18,55,.07);
        }
        html { background: var(--canvas); -webkit-font-smoothing: antialiased; }
        body { font-variant-numeric: tabular-nums; }
        h1, h2, h3 { text-wrap: balance; letter-spacing: 0; }
        p { text-wrap: pretty; }
        main .grid > * { min-width: 0; }
        button, a, input, select, textarea { -webkit-tap-highlight-color: transparent; }
        button, a[class] { transition-property: color, background-color, border-color, box-shadow, opacity, transform; transition-duration: 180ms; transition-timing-function: cubic-bezier(.2,0,0,1); }
        button:active, a[class]:active { transform: scale(.96); }
        button:disabled { cursor: not-allowed; opacity: .48; transform: none; }
        input, select, textarea { outline: none; }
        input:focus, select:focus, textarea:focus { border-color: rgba(1,101,118,.62) !important; box-shadow: 0 0 0 3px rgba(1,101,118,.12); }
        .app-sidebar { transform: translateX(-100%); transition: transform 240ms cubic-bezier(.2,0,0,1); }
        .drawer-backdrop { opacity: 0; pointer-events: none; transition: opacity 200ms cubic-bezier(.2,0,0,1); }
        body.drawer-open { overflow: hidden; }
        body.drawer-open .app-sidebar { transform: translateX(0); }
        body.drawer-open .drawer-backdrop { opacity: 1; pointer-events: auto; }
        .app-panel { background: #fff; border-radius: 8px; box-shadow: var(--shadow), 0 0 0 1px rgba(5,18,55,.045); }
        .app-table th { color: #64748b; font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .app-table tbody tr { transition-property: background-color; transition-duration: 160ms; }
        .app-table tbody tr:hover { background: #f8fafc; }
        .app-content table { font-variant-numeric: tabular-nums; }
        .app-content details > summary { cursor: pointer; }
        .app-content ::-webkit-scrollbar { width: 8px; height: 8px; }
        .app-content ::-webkit-scrollbar-thumb { border: 2px solid transparent; border-radius: 999px; background: #cbd5e1; background-clip: padding-box; }
        .app-content ::-webkit-scrollbar-track { background: transparent; }
        @media (min-width: 1024px) {
            .app-sidebar { transform: translateX(0); }
            body.drawer-open { overflow: auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body class="overflow-x-hidden bg-mist font-sans text-slate-900">
    <div id="drawer-backdrop" class="drawer-backdrop fixed inset-0 z-40 bg-navy/55 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <aside id="app-sidebar" class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-[268px] flex-col overflow-y-auto bg-navy text-white shadow-2xl lg:z-30" aria-label="Primary navigation">
        <div class="flex h-[76px] items-center justify-between px-5">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="AI Growth Agent home">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-teal-bright text-white shadow-[0_8px_22px_rgba(12,154,166,.25)]">
                    <i data-lucide="heart-pulse" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-[15px] font-bold">Growth Agent</span>
                    <span class="block truncate text-[11px] font-medium text-slate-400">Search & conversion</span>
                </span>
            </a>
            <button id="drawer-close" type="button" class="grid h-10 w-10 place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
        </div>

        <div class="px-4 pt-3">
            <p class="px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-500">Workspace</p>
        </div>
        <nav class="mt-3 grid gap-1 px-3" aria-label="Workspace">
            @foreach ($navigation as $item)
                @php($active = collect($item['matches'])->contains(fn ($match) => request()->routeIs($match)))
                <a href="{{ route($item['route']) }}" @class([
                    'group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold',
                    'bg-teal text-white shadow-[0_8px_20px_rgba(1,101,118,.28)]' => $active,
                    'text-slate-300 hover:bg-white/[.07] hover:text-white' => ! $active,
                ]) @if($active) aria-current="page" @endif>
                    <i data-lucide="{{ $item['icon'] }}" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-cyan-100' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-white/10 p-4">
            <div class="flex items-center gap-3 rounded-lg bg-white/[.055] p-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-cyan-400/15 text-xs font-bold text-cyan-200">{{ $initials }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-xs font-semibold text-white">{{ $userName }}</span>
                    <span class="block text-[11px] text-slate-400">Private admin</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid h-10 w-10 place-items-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white" title="Log out" aria-label="Log out">
                        <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-[268px]">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur-lg lg:z-20">
            <div class="flex min-h-[68px] items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button id="drawer-open" type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-navy text-white shadow-sm lg:hidden" aria-controls="app-sidebar" aria-expanded="false" aria-label="Open navigation">
                    <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-[17px] font-bold text-navy">{{ $heading ?? 'Dashboard' }}</h1>
                    <p class="hidden truncate text-xs text-slate-500 sm:block">AI Growth & Conversion Agent</p>
                </div>
                <div class="hidden items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 sm:flex">
                    <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                    No sensitive personal data
                </div>
            </div>
        </header>

        <main class="app-content min-w-0">
            <section class="mx-auto w-full max-w-[1680px] px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-5 flex items-start gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-[0_0_0_1px_rgba(5,150,105,.14)]" role="status"><i data-lucide="circle-check" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
                @endif
                @if (session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-[0_0_0_1px_rgba(225,29,72,.14)]" role="alert"><i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-[0_0_0_1px_rgba(225,29,72,.14)]" role="alert"><i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
                @endif
                {{ $slot }}
            </section>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script>
        (() => {
            const body = document.body;
            const openButton = document.getElementById('drawer-open');
            const closeButton = document.getElementById('drawer-close');
            const backdrop = document.getElementById('drawer-backdrop');
            const sidebar = document.getElementById('app-sidebar');

            const setDrawer = (open) => {
                body.classList.toggle('drawer-open', open);
                openButton?.setAttribute('aria-expanded', String(open));
                sidebar?.setAttribute('aria-hidden', window.innerWidth < 1024 ? String(!open) : 'false');
                if (open) closeButton?.focus();
            };

            openButton?.addEventListener('click', () => setDrawer(true));
            closeButton?.addEventListener('click', () => { setDrawer(false); openButton?.focus(); });
            backdrop?.addEventListener('click', () => setDrawer(false));
            sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
                if (window.innerWidth < 1024) setDrawer(false);
            }));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && body.classList.contains('drawer-open')) {
                    setDrawer(false);
                    openButton?.focus();
                }
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) setDrawer(false);
                else sidebar?.setAttribute('aria-hidden', String(!body.classList.contains('drawer-open')));
            });

            sidebar?.setAttribute('aria-hidden', window.innerWidth < 1024 ? 'true' : 'false');
            window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
        })();
    </script>
</body>
</html>
