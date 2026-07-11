<x-layouts.app :heading="$website->name.' - Search Pages'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        <p class="text-sm text-slate-500">{{ $pages->total() }} pages</p>
    </div>

    @include('websites.partials.gsc-pages', ['pageRecommendations' => $pageRecommendations])

    <div class="mt-5">{{ $pages->links() }}</div>
</x-layouts.app>
