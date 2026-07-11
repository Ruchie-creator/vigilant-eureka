<x-layouts.app :heading="$website->exists ? 'Edit Website' : 'Add Website'">
    <form method="POST" action="{{ $website->exists ? route('websites.update', $website) : route('websites.store') }}" class="max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
        @csrf
        @if ($website->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-semibold">Name<input name="name" value="{{ old('name', $website->name) }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">URL<input name="url" value="{{ old('url', $website->url) }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Type<select name="type" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['osteopathy','auriculotherapy','sexology','other'] as $type)<option value="{{ $type }}" @selected(old('type', $website->type ?: 'other') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold">Language<input name="language" value="{{ old('language', $website->language ?: 'en') }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Target Location<input name="target_location" value="{{ old('target_location', $website->target_location) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Status<select name="status" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['active','paused','archived'] as $status)<option value="{{ $status }}" @selected(old('status', $website->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Primary Services<textarea name="primary_services" rows="5" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('primary_services', implode("\n", $website->primary_services ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Target Locations<textarea name="target_locations" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('target_locations', implode("\n", $website->target_locations ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Practitioner Names<textarea name="practitioner_names" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('practitioner_names', implode("\n", $website->practitioner_names ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Brand Terms<textarea name="brand_terms" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('brand_terms', implode("\n", $website->brand_terms ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold">Priority Pages<textarea name="priority_pages" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('priority_pages', implode("\n", $website->priority_pages ?? [])) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Notes<textarea name="notes" rows="5" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('notes', $website->notes) }}</textarea></label>
        </div>
        <div class="mt-6 flex gap-3"><button class="rounded-lg bg-teal px-4 py-2 font-semibold text-white">Save Website</button><a href="{{ route('websites.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 font-semibold">Cancel</a></div>
    </form>
</x-layouts.app>
