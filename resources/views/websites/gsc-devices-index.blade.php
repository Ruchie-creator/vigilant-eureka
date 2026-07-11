<x-layouts.app :heading="$website->name.' - Devices'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('websites.show', $website) }}" class="text-sm font-semibold text-teal">Back to website</a>
        <p class="text-sm text-slate-500">{{ $devices->total() }} device rows</p>
    </div>

    @include('websites.partials.gsc-devices', ['devices' => $deviceMetrics])

    <div class="mt-5">{{ $devices->links() }}</div>
</x-layouts.app>
