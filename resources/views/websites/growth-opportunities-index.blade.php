<x-layouts.app :heading="$website->name.' - Growth Opportunities'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        <p class="text-sm text-slate-500">{{ $opportunities->total() }} open opportunities</p>
    </div>

    @include('websites.partials.growth-opportunities', ['opportunities' => $opportunities->getCollection(), 'title' => 'All Growth Opportunities'])

    <div class="mt-5">{{ $opportunities->links() }}</div>
</x-layouts.app>
