<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-navy">Top Growth Opportunities</h2>
        <a href="{{ route('ai-insights.index') }}" class="text-sm font-semibold text-teal">View all</a>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($opportunities as $opportunity)
            <article class="px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ str_replace('_', ' ', ucfirst($opportunity->opportunity_type)) }}</p>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ ucfirst($opportunity->source_type) }}: {{ $opportunity->source_value }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">Score {{ $opportunity->score }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $opportunity->priority === 'high' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700' }}">{{ ucfirst($opportunity->priority) }}</span>
                    </div>
                </div>
                @if($opportunity->related_page_url)<p class="mt-2 break-all text-sm text-slate-500">Related page: {{ $opportunity->related_page_url }}</p>@endif
                <p class="mt-3 text-sm text-slate-700">{{ $opportunity->problem }}</p>
                <p class="mt-2 text-sm font-medium text-navy">{{ $opportunity->recommendation }}</p>
                <p class="mt-2 text-sm text-slate-600">Expected result: {{ $opportunity->expected_result }}</p>
                @if($opportunity->conversion_action)<p class="mt-2 text-sm text-slate-600">Conversion impact: {{ $opportunity->conversion_action }}</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('growth-opportunities.tasks.store', $opportunity) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Create Task</button></form>
                    <form method="POST" action="{{ route('growth-opportunities.update', $opportunity) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ignored"><button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Ignore</button></form>
                    <form method="POST" action="{{ route('growth-opportunities.update', $opportunity) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Mark Completed</button></form>
                </div>
            </article>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">No growth opportunities yet. Sync Search Console data to generate them.</div>
        @endforelse
    </div>
</section>
