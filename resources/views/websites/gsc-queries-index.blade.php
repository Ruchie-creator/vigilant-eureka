<x-layouts.app :heading="$website->name.' - Search Queries'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        <p class="text-sm text-slate-500">{{ $queries->total() }} queries</p>
    </div>

    @include('websites.partials.gsc-queries', ['queries' => $queries->getCollection(), 'queryIntents' => $queryIntents])

    <div class="mt-5">{{ $queries->links() }}</div>
</x-layouts.app>
