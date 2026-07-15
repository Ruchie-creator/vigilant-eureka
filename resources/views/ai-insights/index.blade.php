<x-layouts.app :heading="$heading ?? 'AI Insights'">
    @isset($website)
        <div class="mb-4">
            <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        </div>
    @endisset

    <section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="grid gap-4 p-5">
            @forelse ($insights as $insight)
                @include('ai-insights.partials.card', ['insight' => $insight])
            @empty
                <div class="p-10 text-center text-sm text-slate-500">No insights yet. Open a website and generate one from Search Console data.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-6">{{ $insights->links() }}</div>
</x-layouts.app>
