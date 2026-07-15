<article class="rounded-lg border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-teal/10 px-2.5 py-1 text-xs font-semibold text-teal">{{ str_replace('_', ' ', ucfirst($memory->memory_type)) }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $memory->is_expired ? 'bg-slate-100 text-slate-500' : 'bg-emerald-50 text-emerald-700' }}">{{ $memory->is_expired ? 'Expired' : 'Active' }}</span>
            </div>
            <p class="mt-3 text-sm font-semibold text-navy">{{ $memory->memory_value }}</p>
            <dl class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                <div><dt class="font-semibold text-slate-400">Workspace</dt><dd class="mt-0.5">{{ $memory->website?->name ?? 'Global' }}</dd></div>
                <div><dt class="font-semibold text-slate-400">Agent</dt><dd class="mt-0.5">{{ $memory->agent?->name }}</dd></div>
                <div><dt class="font-semibold text-slate-400">Confidence</dt><dd class="mt-0.5">{{ $memory->confidence !== null ? number_format($memory->confidence * 100).'%' : 'Not set' }}</dd></div>
                <div><dt class="font-semibold text-slate-400">Source</dt><dd class="mt-0.5">{{ $memory->source_type ? str_replace('_', ' ', ucfirst($memory->source_type)).($memory->source_id ? ' #'.$memory->source_id : '') : 'Manual context' }}</dd></div>
                <div><dt class="font-semibold text-slate-400">Created</dt><dd class="mt-0.5">{{ $memory->created_at->format('M j, Y g:i A') }}</dd></div>
                <div><dt class="font-semibold text-slate-400">Expires</dt><dd class="mt-0.5">{{ $memory->expires_at?->format('M j, Y g:i A') ?? 'No expiry' }}</dd></div>
            </dl>
        </div>
        <div class="flex gap-2">
            @unless($memory->is_expired)<form method="POST" action="{{ route('agent-memories.expire', $memory) }}">@csrf @method('PATCH')<button title="Expire memory" class="grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy"><i data-lucide="clock" class="size-4"></i></button></form>@endunless
            <form method="POST" action="{{ route('agent-memories.destroy', $memory) }}">@csrf @method('DELETE')<button title="Forget memory" class="grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-700"><i data-lucide="trash-2" class="size-4"></i></button></form>
        </div>
    </div>
</article>
