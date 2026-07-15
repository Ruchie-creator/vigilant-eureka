<x-layouts.app heading="Marketing Team Command Center">
    <section class="rounded-lg bg-navy p-6 text-white shadow-[0_18px_50px_rgba(5,18,55,0.22)]">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div><a href="{{ route('websites.show', $website) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-teal-200"><i data-lucide="arrow-left" class="size-4"></i>{{ $website->name }}</a><h2 class="mt-4 text-2xl font-bold">AI Marketing Team</h2><p class="mt-2 text-sm text-slate-300">Primary goal: <span class="font-semibold text-white">{{ $goalProfile['label'] }}</span> · Primary action: <span class="font-semibold text-white">{{ $goalProfile['primary_action_label'] }}</span></p></div>
            <form method="POST" action="{{ route('websites.agents.run-full-team', $website) }}">@csrf<button class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-teal px-5 text-sm font-semibold text-white"><i data-lucide="play" class="size-4"></i>Run Full Marketing Team</button></form>
        </div>
        <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-300">Agents analyze data and create pending recommendations. Campaigns, messages, and website changes require approval and are never executed by this workflow.</p>
    </section>

    @php
        $runButtons = [
            'acquisition-growth' => ['Run Acquisition Agent', 'trending-up'],
            'content-strategy' => ['Run Content Agent', 'file-text'],
            'conversion' => ['Run Conversion Agent', 'mouse-pointer-click'],
            'analytics-reporting' => ['Run Analytics Agent', 'chart-no-axes-combined'],
        ];
    @endphp
    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($runButtons as $slug => [$label, $icon])
            @php
                $agent = $agents->firstWhere('slug', $slug);
            @endphp
            @if($agent)<form method="POST" action="{{ route('agents.run', $agent) }}">@csrf<input type="hidden" name="website_id" value="{{ $website->id }}"><button class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-white px-3 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.08),0_10px_24px_rgba(5,18,55,0.06)] hover:text-teal"><i data-lucide="{{ $icon }}" class="size-4"></i>{{ $label }}</button></form>@endif
        @endforeach
    </section>

    <section class="mt-6"><div class="mb-4 flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-xl font-bold text-navy">Latest team actions</h2><p class="mt-1 text-sm text-slate-500">One latest action per specialist.</p></div></div><div class="grid gap-5 xl:grid-cols-2">@forelse($latestActions as $action)@include('agents.partials.action-card', ['action' => $action])@empty<div class="rounded-lg bg-white px-6 py-14 text-center text-sm text-slate-500 shadow-[0_0_0_1px_rgba(5,18,55,0.06)] xl:col-span-2">No agent actions yet. Run the full team or a specialist agent.</div>@endforelse</div></section>

    @if($actions->count() > $latestActions->count())<section class="mt-8"><h2 class="text-xl font-bold text-navy">Recent action history</h2><div class="mt-4 grid gap-5 xl:grid-cols-2">@foreach($actions->reject(fn ($action) => $latestActions->contains('id', $action->id))->take(10) as $action)@include('agents.partials.action-card', ['action' => $action])@endforeach</div></section>@endif
</x-layouts.app>
