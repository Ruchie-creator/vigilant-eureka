<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Mobile vs Desktop</h2></div>
    <div class="divide-y divide-slate-100">
        @forelse($devices as $device)
            <div class="grid grid-cols-4 gap-3 px-5 py-4 text-sm">
                <div class="font-semibold capitalize">{{ $device->device }}</div>
                <div><span class="text-slate-500">Clicks</span><p class="font-semibold">{{ $device->clicks }}</p></div>
                <div><span class="text-slate-500">CTR</span><p class="font-semibold">{{ number_format($device->ctr, 2) }}%</p></div>
                <div><span class="text-slate-500">Pos.</span><p class="font-semibold">{{ number_format($device->position, 1) }}</p></div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">No device data yet.</div>
        @endforelse
    </div>
</section>
