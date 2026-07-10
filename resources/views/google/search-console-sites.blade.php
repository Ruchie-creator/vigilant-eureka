<x-layouts.app heading="Search Console Properties">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-navy">Connected Google Account</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $account->email ?: 'Google account connected' }}</p>
            </div>
            <form method="POST" action="{{ route('google.search-console.disconnect') }}">@csrf<button class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">Disconnect</button></form>
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Verified Properties</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Property</th><th class="px-5 py-3">Permission</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($account->sites as $site)
                    <tr><td class="break-all px-5 py-4 font-semibold">{{ $site->site_url }}</td><td class="px-5 py-4">{{ $site->permission_level ?: 'Unknown' }}</td></tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-10 text-center text-slate-500">No Search Console properties found for this account.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
