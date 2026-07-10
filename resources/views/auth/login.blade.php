<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#051237] text-white antialiased">
<main class="grid min-h-screen place-items-center px-5">
    <section class="w-full max-w-md rounded-xl bg-white p-8 text-slate-900 shadow-2xl">
        <div class="mb-8">
            <div class="text-xs font-semibold uppercase tracking-[0.25em] text-[#016576]">Private Dashboard</div>
            <h1 class="mt-2 text-2xl font-bold text-[#051237]">AI Marketing Agents</h1>
            <p class="mt-2 text-sm text-slate-500">Admin access for clint-rono.dev.</p>
        </div>
        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="grid gap-4">
            @csrf
            <label class="grid gap-2 text-sm font-semibold">
                Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus class="rounded-lg border border-slate-300 px-4 py-3 font-normal outline-none focus:border-[#016576] focus:ring-4 focus:ring-[#016576]/10">
            </label>
            <label class="grid gap-2 text-sm font-semibold">
                Password
                <input name="password" type="password" required class="rounded-lg border border-slate-300 px-4 py-3 font-normal outline-none focus:border-[#016576] focus:ring-4 focus:ring-[#016576]/10">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#016576]">
                Remember this device
            </label>
            <button class="rounded-lg bg-[#016576] px-4 py-3 font-semibold text-white hover:bg-[#014f5c]">Login</button>
        </form>
    </section>
</main>
</body>
</html>
