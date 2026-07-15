<x-layouts.app :heading="$website->exists ? 'Edit Website' : 'Add Website'">
    @php
        $selectedGoalKey = old('primary_conversion_goal', $website->primary_conversion_goal ?: 'lead_generation');
        $selectedGoal = $goalProfiles[$selectedGoalKey] ?? $goalProfiles['custom'];
        $secondaryGoalValue = old('secondary_conversion_goals', implode("\n", $website->secondary_conversion_goals ?: $selectedGoal['secondary_conversion_goals']));
        $conversionLabelValue = old('conversion_labels', collect($website->conversion_labels ?: $selectedGoal['conversion_labels'])->map(fn ($label, $key) => $key.'='.$label)->implode("\n"));
    @endphp
    <form method="POST" action="{{ $website->exists ? route('websites.update', $website) : route('websites.store') }}" class="max-w-5xl rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
        @csrf
        @if ($website->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-semibold">Name<input name="name" value="{{ old('name', $website->name) }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">URL<input name="url" value="{{ old('url', $website->url) }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Business category<select name="type" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['professional_services' => 'Professional services','saas' => 'SaaS','ecommerce' => 'Ecommerce','osteopathy' => 'Osteopathy','auriculotherapy' => 'Auriculotherapy','sexology' => 'Sexology','other' => 'Other'] as $type => $label)<option value="{{ $type }}" @selected(old('type', $website->type ?: 'other') === $type)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold">Language<input name="language" value="{{ old('language', $website->language ?: 'en') }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Target Location<input name="target_location" value="{{ old('target_location', $website->target_location) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Status<select name="status" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['active','paused','archived'] as $status)<option value="{{ $status }}" @selected(old('status', $website->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <div class="md:col-span-2 mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6"><div><h2 class="font-semibold text-navy">Workspace conversion goal</h2><p class="mt-1 text-sm text-slate-500">Agents use the connected data sources and conversion goals configured for this workspace.</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Approval required for external actions</span></div>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Primary conversion goal<select name="primary_conversion_goal" data-goal-profile class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach($goalProfiles as $key => $profile)<option value="{{ $key }}" @selected($selectedGoalKey === $key)>{{ $profile['label'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold">Target audience<textarea name="target_audience" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="Who should complete the primary action?">{{ old('target_audience', $website->target_audience) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Business model<input name="business_model" value="{{ old('business_model', $website->business_model) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="SaaS subscription, lead generation, ecommerce..."></label>
            <label class="grid gap-2 text-sm font-semibold">Supporting conversion keys<textarea name="secondary_conversion_goals" data-secondary-goals rows="6" class="rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs font-normal" placeholder="trial_started&#10;onboarding_completed">{{ $secondaryGoalValue }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Conversion labels<textarea name="conversion_labels" data-conversion-labels rows="6" class="rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs font-normal" placeholder="business_signup=Business signup&#10;trial_started=14-day trial started">{{ $conversionLabelValue }}</textarea></label>

            <div class="md:col-span-2 mt-2 border-t border-slate-100 pt-6"><h2 class="font-semibold text-navy">Search and audience context</h2><p class="mt-1 text-sm text-slate-500">These terms help classify acquisition demand and map it to priority pages.</p></div>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Primary services, products, or offers<textarea name="primary_services" rows="5" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('primary_services', implode("\n", $website->primary_services ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Target Locations<textarea name="target_locations" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('target_locations', implode("\n", $website->target_locations ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Representative or practitioner names<textarea name="practitioner_names" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('practitioner_names', implode("\n", $website->practitioner_names ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Brand Terms<textarea name="brand_terms" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('brand_terms', implode("\n", $website->brand_terms ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Priority Pages<textarea name="priority_pages" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('priority_pages', implode("\n", $website->priority_pages ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Notes<textarea name="notes" rows="5" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('notes', $website->notes) }}</textarea></label>
        </div>
        <div class="mt-6 flex gap-3"><button class="rounded-lg bg-teal px-4 py-2 font-semibold text-white">Save workspace</button><a href="{{ route('websites.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 font-semibold">Cancel</a></div>
    </form>
    <script>
        (() => {
            const profiles = @json($goalProfiles);
            const select = document.querySelector('[data-goal-profile]');
            const secondary = document.querySelector('[data-secondary-goals]');
            const labels = document.querySelector('[data-conversion-labels]');
            select?.addEventListener('change', () => {
                const profile = profiles[select.value];
                if (!profile) return;
                secondary.value = profile.secondary_conversion_goals.join('\n');
                labels.value = Object.entries(profile.conversion_labels).map(([key, label]) => `${key}=${label}`).join('\n');
            });
        })();
    </script>
</x-layouts.app>
