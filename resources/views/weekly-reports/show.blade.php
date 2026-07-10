<x-layouts.app :heading="$weeklyReport->title">
    <article class="max-w-4xl rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
        @foreach(['summary' => 'Summary', 'wins' => 'Wins', 'issues' => 'Issues', 'recommendations' => 'Recommendations', 'next_actions' => 'Next Actions'] as $field => $label)
            <section class="border-b border-slate-100 py-5 last:border-b-0">
                <h2 class="font-semibold text-navy">{{ $label }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $weeklyReport->{$field} ?: 'Nothing recorded.' }}</p>
            </section>
        @endforeach
    </article>
</x-layouts.app>
