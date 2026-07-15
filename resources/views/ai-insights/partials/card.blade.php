@php
    $display = fn (mixed $value): string => \App\Support\DisplayValue::make($value);
    $priority = $display($insight->priority) ?: 'low';
    $category = $display($insight->category) ?: 'general';
@endphp
<article class="rounded-lg bg-slate-50 p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 max-w-4xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $priority === 'high' ? 'bg-rose-50 text-rose-700' : ($priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($priority) }}</span>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-[0_0_0_1px_rgba(5,18,55,0.08)]">{{ str_replace('_', ' ', ucfirst($category)) }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $insight->source === 'ai' ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $insight->source === 'ai' ? 'AI' : 'Rule-based fallback' }}</span>
            </div>
            <div class="mt-3 text-balance text-lg font-semibold text-navy"><x-structured-value :value="$insight->title" empty="Untitled insight" /></div>
            <p class="mt-2 text-sm text-slate-500">{{ $display($insight->website?->name) }}@if($insight->data_period) · {{ $display($insight->data_period) }}@endif</p>
            @if($insight->property_url)<p class="mt-1 break-all text-xs text-slate-500">Property: {{ $display($insight->property_url) }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('ai-insights.update', $insight) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="reviewed"><button class="min-h-10 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Mark Reviewed</button></form>
            <form method="POST" action="{{ route('ai-insights.tasks.store', $insight) }}">@csrf<button class="min-h-10 rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Convert to Task</button></form>
        </div>
    </div>

    @if($insight->affected_source_value)
        <div class="mt-3 break-all text-sm text-slate-500">Affected {{ str_replace('_', ' ', $display($insight->affected_source_type) ?: 'source') }}: <div class="mt-1 font-medium text-slate-700"><x-structured-value :value="$insight->affected_source_value" /></div></div>
    @endif

    <div class="mt-4 grid gap-3 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">What is happening</p><div class="mt-2 text-sm leading-6 text-slate-700"><x-structured-value :value="$insight->summary" empty="No summary provided." /></div></div>
        <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Why it matters</p><div class="mt-2 text-sm leading-6 text-slate-700"><x-structured-value :value="$insight->why_it_matters" empty="This is tied to real search visibility and conversion potential." /></div></div>
        <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Best conversion action</p><div class="mt-2 text-sm font-medium leading-6 text-navy"><x-structured-value :value="$insight->recommendation" empty="Review this insight and define the next conversion action." /></div></div>
    </div>

    <div class="mt-3 grid gap-3 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expected result</p><div class="mt-2 text-sm leading-6 text-slate-700"><x-structured-value :value="$insight->expected_result" empty="Clearer search visibility and a more focused conversion path." /></div></div>
        <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suggested task</p><div class="mt-2 text-sm leading-6 text-slate-700"><x-structured-value :value="$insight->suggested_task" empty="Create a focused task from this insight." /></div></div>
    </div>

    @if($insight->data_used)
        <details class="mt-3 rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-navy">Data used</summary>
            @if(is_array($insight->data_used))
                <dl class="grid gap-3 border-t border-slate-100 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($insight->data_used as $label => $value)
                        @if(! is_null($value) && $value !== '')
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', $display($label)) }}</dt><dd class="mt-1 break-words text-slate-700"><x-structured-value :value="$value" /></dd></div>
                        @endif
                    @endforeach
                </dl>
            @else
                <div class="border-t border-slate-100 p-4 text-sm text-slate-700"><x-structured-value :value="$insight->data_used" /></div>
            @endif
        </details>
    @endif
</article>
