<x-layouts.app :heading="$website->name.' - Countries'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        <p class="text-sm text-slate-500">{{ $countries->total() }} country rows</p>
    </div>

    @include('websites.partials.gsc-countries', ['countries' => $countryMetrics, 'targetCountry' => $targetCountry])

    <div class="mt-5">{{ $countries->links() }}</div>
</x-layouts.app>
