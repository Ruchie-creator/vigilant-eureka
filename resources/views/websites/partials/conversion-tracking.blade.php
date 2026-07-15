<section class="mt-6 rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="grid size-8 place-items-center rounded-lg bg-teal/10 text-teal"><i data-lucide="mouse-pointer-click" class="size-4"></i></span>
                <h2 class="font-semibold text-navy">Anonymous Conversion Tracking</h2>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $goalProfile['primary_action_label'] }} and supporting actions are attributed to matching opportunities without visitor identity, cookies, form contents, or sensitive personal data.</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Privacy-safe events</span>
    </div>

    <div class="grid gap-px bg-slate-100 sm:grid-cols-3">
        <div class="bg-white px-5 py-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last 30 days</p><p class="mt-1 text-2xl font-bold tabular-nums text-navy">{{ number_format($summary['total']) }}</p><p class="text-xs text-slate-500">Tracked conversion actions</p></div>
        <div class="bg-white px-5 py-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Attributed</p><p class="mt-1 text-2xl font-bold tabular-nums text-navy">{{ number_format($summary['attributed']) }}</p><p class="text-xs text-slate-500">Connected to opportunities</p></div>
        <div class="bg-white px-5 py-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last action</p><p class="mt-1 text-sm font-bold text-navy">{{ $summary['last_event_at']?->diffForHumans() ?? 'Waiting for first action' }}</p><p class="text-xs text-slate-500">Server-recorded timestamp</p></div>
    </div>

    @if($summary['install_tag'])
        <details class="border-t border-slate-100">
            <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-navy">Tracker installation</summary>
            <div class="grid gap-4 border-t border-slate-100 px-5 py-4 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,.65fr)]">
                <div>
                    @if($summary['is_local'])
                        <div class="mb-3 flex gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800"><i data-lucide="triangle-alert" class="mt-0.5 size-4 shrink-0"></i><p>This installation URL is for local testing. Production tracking requires a publicly reachable application URL.</p></div>
                    @endif
                    <p class="text-sm font-medium text-slate-700">Add before the closing body tag. Use <code class="text-teal">data-conversion-event</code> with one of this workspace's configured conversion keys.</p>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-navy p-4 text-xs leading-6 text-cyan-100"><code>{{ $summary['install_tag'] }}</code></pre>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exact attribution</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">Page matching is automatic. Add both attributes when an action should map to a specific opportunity.</p>
                    <code class="mt-3 block overflow-x-auto rounded-lg bg-white p-3 text-xs text-teal shadow-[0_0_0_1px_rgba(5,18,55,0.08)]">data-conversion-event="{{ $goalProfile['primary_action'] }}" data-growth-opportunity="{{ $summary['example_opportunity_id'] ?? 'OPPORTUNITY_ID' }}"</code>
                    <div class="mt-4 flex flex-wrap gap-2">@foreach($goalProfile['conversion_labels'] as $key => $label)<span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 shadow-[0_0_0_1px_rgba(5,18,55,0.08)]" title="{{ $key }}">{{ $label }}</span>@endforeach</div>
                </div>
            </div>
        </details>
    @endif
</section>
