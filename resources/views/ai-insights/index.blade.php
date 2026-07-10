<x-layouts.app heading="AI Insights">
    <section class="rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="divide-y divide-slate-100">
            @forelse ($insights as $insight)
                <article class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="max-w-3xl">
                            <h2 class="font-semibold text-navy">{{ $insight->title }}</h2>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span>{{ $insight->website->name }} · {{ ucfirst($insight->priority) }} · {{ str_replace('_', ' ', $insight->category) }}</span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $insight->source === 'ai' ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $insight->source === 'ai' ? 'AI' : 'Rule-based fallback' }}</span>
                            </p>
                            <p class="mt-3 text-sm text-slate-700">{{ $insight->summary }}</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $insight->recommendation }}</p>
                            @if($insight->expected_result)<p class="mt-2 text-sm text-slate-500">Expected result: {{ $insight->expected_result }}</p>@endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('ai-insights.update', $insight) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="reviewed"><button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Mark Reviewed</button></form>
                            <form method="POST" action="{{ route('ai-insights.tasks.store', $insight) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Convert to Task</button></form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">No insights yet. Open a website and generate one from the latest audit.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-6">{{ $insights->links() }}</div>
</x-layouts.app>
