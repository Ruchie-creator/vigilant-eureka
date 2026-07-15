<article class="rounded-lg border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                <span class="text-navy">{{ $handoff->fromAgent->name }}</span><i data-lucide="arrow-right" class="size-3.5"></i><span class="text-navy">{{ $handoff->toAgent->name }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 {{ $handoff->status === 'failed' ? 'text-rose-700' : 'text-slate-600' }}">{{ ucfirst($handoff->status) }}</span>
            </div>
            <p class="mt-3 text-sm font-semibold text-navy">{{ $handoff->reason }}</p>
            @if($handoff->expected_output)<p class="mt-1 text-sm text-slate-600">Expected: {{ $handoff->expected_output }}</p>@endif
            <p class="mt-2 text-xs text-slate-500">{{ $handoff->website->name }} &middot; Created {{ $handoff->created_at->format('M j, Y g:i A') }}</p>
        </div>
        @if(in_array($handoff->status, ['pending', 'accepted']))
            <div class="flex flex-wrap gap-2">
                @if($handoff->status === 'pending')<form method="POST" action="{{ route('agent-handoffs.update', $handoff) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="accepted"><button class="min-h-9 rounded-lg bg-teal px-3 text-xs font-semibold text-white">Accept</button></form>@endif
                <form method="POST" action="{{ route('agent-handoffs.update', $handoff) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="min-h-9 rounded-lg bg-navy px-3 text-xs font-semibold text-white">Complete</button></form>
                <form method="POST" action="{{ route('agent-handoffs.update', $handoff) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ignored"><button class="min-h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600">Ignore</button></form>
            </div>
        @endif
    </div>
</article>
