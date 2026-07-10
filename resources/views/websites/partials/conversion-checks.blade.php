<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Conversion Checks</h2></div>
    <div class="divide-y divide-slate-100">
        @forelse($checks as $check)
            <div class="px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="font-semibold">{{ $check->item }}</p>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $check->priority === 'high' ? 'bg-rose-50 text-rose-700' : 'bg-teal/10 text-teal' }}">{{ ucfirst($check->priority) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $check->recommendation }}</p>
                <p class="mt-1 text-xs font-semibold uppercase text-slate-400">{{ str_replace('_', ' ', $check->status) }}</p>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">Sync Search Console data to create conversion checks.</div>
        @endforelse
    </div>
</section>
