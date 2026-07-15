<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>html{-webkit-font-smoothing:antialiased}body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}button,a{transition-property:color,background-color,box-shadow,transform;transition-duration:180ms;transition-timing-function:cubic-bezier(.2,0,0,1)}button:active{transform:scale(.96)}input:focus{border-color:#016576!important;box-shadow:0 0 0 3px rgba(1,101,118,.12);outline:none}</style>
</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-900">
    <main class="grid min-h-screen lg:grid-cols-[minmax(360px,.82fr)_minmax(560px,1.18fr)]">
        <section class="relative hidden overflow-hidden bg-[#051237] p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ route('login') }}" class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-lg bg-[#0c9aa6] shadow-[0_10px_28px_rgba(12,154,166,.25)]"><span class="text-lg font-bold">GA</span></span><span><span class="block text-base font-bold">Growth Agent</span><span class="text-xs text-slate-400">Search & conversion</span></span></a>
            <div class="max-w-xl"><p class="text-xs font-bold uppercase tracking-[.16em] text-cyan-200">Private command center</p><h1 class="mt-4 text-4xl font-bold leading-tight">Turn connected evidence into focused growth work.</h1><p class="mt-4 max-w-lg text-sm leading-7 text-slate-300">Agents use the connected data sources and conversion goals configured for each workspace.</p></div>
            <p class="text-xs text-slate-500">clint-rono.dev · Private administrator access</p>
        </section>
        <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-[0_1px_2px_rgba(5,18,55,.04),0_20px_60px_rgba(5,18,55,.1)] sm:p-8">
                <div class="mb-7 lg:hidden"><span class="grid h-10 w-10 place-items-center rounded-lg bg-[#051237] text-xs font-bold text-white">GA</span></div>
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#016576]">Administrator access</p>
                <h1 class="mt-2 text-2xl font-bold text-[#051237]">Welcome back</h1>
                <p class="mt-2 text-sm text-slate-500">Sign in to the AI Growth & Conversion Agent.</p>
                @if ($errors->any())<div class="mt-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-[inset_0_0_0_1px_rgba(225,29,72,.12)]" role="alert">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login.store') }}" class="mt-7 grid gap-4">
                    @csrf
                    <label class="grid gap-2 text-sm font-semibold text-[#051237]">Email<input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="min-h-11 rounded-lg border border-slate-200 px-4 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold text-[#051237]">Password<input name="password" type="password" required autocomplete="current-password" class="min-h-11 rounded-lg border border-slate-200 px-4 font-normal"></label>
                    <label class="flex min-h-10 items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#016576]">Remember this device</label>
                    <button class="min-h-11 rounded-lg bg-[#016576] px-4 py-3 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(1,101,118,.2)] hover:bg-[#087687]">Sign in</button>
                </form>
                <p class="mt-6 flex items-center gap-2 text-xs text-slate-400"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>No sensitive personal or medical data is collected.</p>
            </div>
        </section>
    </main>
</body>
</html>
