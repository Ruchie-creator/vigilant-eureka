<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="font-semibold text-navy">Devices</h2>
            <p class="text-sm text-slate-500">Conversion recommendations by device.</p>
        </div>
        @isset($website)
            <a href="{{ route('websites.gsc-devices.index', $website) }}" class="shrink-0 text-sm font-semibold text-teal">View all</a>
        @endisset
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($devices as $device)
            <div class="grid gap-3 px-5 py-4 text-sm md:grid-cols-5">
                <div class="font-semibold capitalize text-navy">{{ is_array($device) ? $device['device'] : $device->device }}</div>
                <div><span class="text-slate-500">Clicks</span><p class="font-semibold tabular-nums">{{ number_format(is_array($device) ? $device['clicks'] : $device->clicks) }}</p></div>
                <div><span class="text-slate-500">Impr.</span><p class="font-semibold tabular-nums">{{ number_format(is_array($device) ? $device['impressions'] : ($device->impressions ?? 0)) }}</p></div>
                <div><span class="text-slate-500">CTR / Pos.</span><p class="font-semibold tabular-nums">{{ number_format(is_array($device) ? $device['ctr'] : $device->ctr, 2) }}% · {{ number_format(is_array($device) ? $device['position'] : $device->position, 1) }}</p></div>
                <div class="text-slate-600">{{ is_array($device) ? $device['recommendation'] : 'Keep the conversion path clear and verify booking interactions are tracked.' }}</div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">No device data yet.</div>
        @endforelse
    </div>
</section>
