@if($action)
    @php
        $meta = $action->metadata ?? [];
    @endphp
    <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-teal/10 px-2.5 py-1 text-xs font-semibold text-teal">{{ $action->run->agent->name }}</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ in_array($action->priority, ['critical','high']) ? 'bg-rose-50 text-rose-700' : ($action->priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($action->priority) }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($action->status) }}</span>
                </div>
                <h3 class="mt-3 text-lg font-bold text-navy">{{ $action->title }}</h3>
                <p class="mt-1 text-xs text-slate-400">{{ $action->created_at->diffForHumans() }}</p>
            </div>
            @if($action->createdTask)<a href="{{ route('marketing-tasks.index', ['source' => $action->createdTask->title]) }}" class="inline-flex min-h-9 items-center gap-2 rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700"><i data-lucide="check-check" class="size-4"></i>Task linked</a>@endif
        </div>

        <dl class="mt-5 grid gap-4 md:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">What I found</dt><dd class="mt-1 text-sm leading-6 text-slate-700">{{ $meta['what_i_found'] ?? $action->description }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Why it matters</dt><dd class="mt-1 text-sm leading-6 text-slate-700">{{ $meta['why_it_matters'] ?? 'This action supports the configured workspace conversion goal.' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Recommended action</dt><dd class="mt-1 text-sm font-medium leading-6 text-navy">{{ $meta['recommended_action'] ?? $action->description }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Expected result</dt><dd class="mt-1 text-sm leading-6 text-slate-700">{{ $action->expected_result ?: 'Progress toward the configured primary conversion.' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Affected page/query/audience</dt><dd class="mt-1 break-all text-sm text-slate-700">{{ $action->related_page_url ?: ($action->related_query ?: ($meta['affected_audience'] ?? 'Configured target audience')) }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Suggested task</dt><dd class="mt-1 text-sm text-slate-700">{{ $meta['suggested_task'] ?? $action->title }}</dd></div>
        </dl>

        <div class="mt-4 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold {{ ($meta['approval_required'] ?? true) ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-700' }}">
            <i data-lucide="{{ ($meta['approval_required'] ?? true) ? 'shield-alert' : 'shield-check' }}" class="size-4"></i>
            Approval required: {{ ($meta['approval_required'] ?? true) ? 'Yes. No external action has been executed.' : 'No for this internal analysis step.' }}
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('agent-actions.tasks.store', $action) }}">@csrf<button class="min-h-10 rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Create Task</button></form>
            @if(!in_array($action->status, ['approved','completed','ignored']))<form method="POST" action="{{ route('agent-actions.update', $action) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="min-h-10 rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">Approve</button></form>@endif
            <form method="POST" action="{{ route('agent-actions.update', $action) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ignored"><button class="min-h-10 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)]">Ignore</button></form>
            <form method="POST" action="{{ route('agent-actions.update', $action) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="min-h-10 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)]">Mark Completed</button></form>
        </div>
    </article>
@endif
