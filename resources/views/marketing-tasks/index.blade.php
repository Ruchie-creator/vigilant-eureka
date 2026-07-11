<x-layouts.app heading="Marketing Tasks">
    @php
        $statuses = [
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'ignored' => 'Ignored',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $tasks->count() }} task{{ $tasks->count() === 1 ? '' : 's' }} shown</p>
        </div>
        <a href="{{ route('marketing-tasks.create') }}" class="inline-flex min-h-10 items-center rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Create Task</a>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] lg:grid-cols-6">
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Website<select name="website_id" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All websites</option>@foreach($websites as $website)<option value="{{ $website->id }}" @selected((string) $filters['website_id'] === (string) $website->id)>{{ $website->name }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Priority<select name="priority" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Any priority</option>@foreach(['high','medium','low'] as $priority)<option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Category<select name="category" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Any category</option>@foreach(['acquisition_growth','service_page_growth','conversion_improvement','reputation_conversion','branded_visibility','technical','content','conversion'] as $category)<option value="{{ $category }}" @selected($filters['category'] === $category)>{{ str_replace('_', ' ', ucfirst($category)) }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Status<select name="status" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Any status</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Origin<select name="origin" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Any origin</option><option value="opportunity" @selected($filters['origin'] === 'opportunity')>Opportunity</option><option value="ai_insight" @selected($filters['origin'] === 'ai_insight')>AI insight</option><option value="manual" @selected($filters['origin'] === 'manual')>Manual</option></select></label>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Source<input name="source" value="{{ $filters['source'] }}" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy" placeholder="Page or query"></label>
        <div class="flex items-end gap-2 lg:col-span-6"><button class="min-h-10 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Apply filters</button><a href="{{ route('marketing-tasks.index') }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.12)]">Reset</a></div>
    </form>

    <section class="grid gap-4 xl:grid-cols-4">
        @foreach($statuses as $status => $label)
            <div class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h2 class="font-semibold text-navy">{{ $label }}</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-600">{{ ($tasksByStatus[$status] ?? collect())->count() }}</span>
                </div>
                <div class="grid gap-3 p-3">
                    @forelse(($tasksByStatus[$status] ?? collect()) as $task)
                        @php
                            $origin = $task->growth_opportunity_id ? 'Opportunity' : ($task->ai_insight_id ? 'AI insight' : 'Manual');
                            $source = $task->source_value ?: $task->related_page_url;
                            $category = $task->growthOpportunity?->opportunity_category ?: $task->aiInsight?->category;
                        @endphp
                        <article class="rounded-lg bg-slate-50 p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-balance text-sm font-semibold leading-6 text-navy">{{ $task->title }}</h3>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $task->priority === 'high' ? 'bg-rose-50 text-rose-700' : ($task->priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($task->priority) }}</span>
                            </div>
                            <p class="mt-2 text-sm font-medium text-slate-700">{{ $task->website?->name ?? 'No website' }}</p>
                            <p class="mt-1 break-all text-xs text-slate-500">Source: {{ $origin }}@if($source) - {{ $source }}@endif</p>
                            @if($category)<p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ str_replace('_', ' ', $category) }}</p>@endif
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $task->expected_result ?: $task->description ?: 'Define the expected conversion result before implementation.' }}</p>
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Due: <span class="normal-case tracking-normal text-slate-700">{{ $task->due_date?->format('M j, Y') ?: 'No due date' }}</span></p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($statuses as $nextStatus => $nextLabel)
                                    @if($nextStatus !== $task->status)
                                        <form method="POST" action="{{ route('marketing-tasks.status.update', $task) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                                            <button class="min-h-10 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">{{ $nextLabel }}</button>
                                        </form>
                                    @endif
                                @endforeach
                                <a href="{{ route('marketing-tasks.edit', $task) }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)]">Edit</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No {{ strtolower($label) }} tasks.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>
</x-layouts.app>
