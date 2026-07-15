@php
    $teamSlots = [
        'marketing-director' => ['Marketing Director priority', 'crown'],
        'acquisition-growth' => ['Acquisition Growth action', 'trending-up'],
        'content-strategy' => ['Content Strategy action', 'file-text'],
        'conversion' => ['Conversion action', 'mouse-pointer-click'],
        'analytics-reporting' => ['Analytics summary', 'chart-no-axes-combined'],
        'task-manager' => ['Task Manager action', 'list-checks'],
    ];
@endphp
<section class="mt-6 rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div><p class="text-xs font-bold uppercase tracking-[.16em] text-teal">Agentic workspace</p><h2 class="mt-1 text-xl font-bold text-navy">AI Marketing Team</h2><p class="mt-1 text-sm text-slate-500">Agents analyze connected evidence and suggest approval-gated work.</p></div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('websites.agents.run-full-team', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white"><i data-lucide="play" class="size-4"></i>Run Full Marketing Team</button></form>
            <a href="{{ route('websites.agents.index', $website) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.12)]">Open command center<i data-lucide="arrow-right" class="size-4"></i></a>
        </div>
    </div>
    <div class="grid gap-px bg-slate-100 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($teamSlots as $slug => [$label, $icon])
            @php
                $action = $latestActions->get($slug);
            @endphp
            <div class="min-w-0 bg-white p-5">
                <div class="flex items-center gap-2 text-slate-500"><i data-lucide="{{ $icon }}" class="size-4 text-teal"></i><p class="text-xs font-bold uppercase tracking-wide">{{ $label }}</p></div>
                @if($action)
                    <p class="mt-3 line-clamp-2 font-semibold text-navy">{{ $action->title }}</p>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $action->metadata['what_i_found'] ?? $action->description }}</p>
                    <p class="mt-3 text-xs font-semibold text-slate-400">{{ ucfirst($action->priority) }} · {{ $action->created_at->diffForHumans() }}</p>
                @else
                    <p class="mt-3 text-sm text-slate-400">No action generated yet.</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
