<x-layouts.app :heading="$task->exists ? 'Edit Task' : 'Create Task'">
    <form method="POST" action="{{ $task->exists ? route('marketing-tasks.update', $task) : route('marketing-tasks.store') }}" class="max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
        @csrf
        @if ($task->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Workspace<select name="website_id" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach($websites as $website)<option value="{{ $website->id }}" @selected(old('website_id', $task->website_id) == $website->id)>{{ $website->name }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Title<input name="title" value="{{ old('title', $task->title) }}" required class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Priority<select name="priority" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['low','medium','high'] as $priority)<option value="{{ $priority }}" @selected(old('priority', $task->priority ?: 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold">Status<select name="status" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">@foreach(['pending','in_progress','completed','ignored'] as $status)<option value="{{ $status }}" @selected(old('status', $task->status ?: 'pending') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-semibold">Due Date<input name="due_date" type="date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold">Source Type<input name="source_type" value="{{ old('source_type', $task->source_type) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="manual, query, page"></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Source Page / Query<input name="source_value" value="{{ old('source_value', $task->source_value) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="Search query, page URL, or source note"></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Related Page URL<input name="related_page_url" value="{{ old('related_page_url', $task->related_page_url) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="https://example.com/service-page/"></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Expected Result<textarea name="expected_result" rows="3" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('expected_result', $task->expected_result) }}</textarea></label>
            <label class="grid gap-2 text-sm font-semibold md:col-span-2">Description<textarea name="description" rows="5" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">{{ old('description', $task->description) }}</textarea></label>
        </div>
        <div class="mt-6 flex gap-3"><button class="rounded-lg bg-teal px-4 py-2 font-semibold text-white">Save Task</button><a href="{{ route('marketing-tasks.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 font-semibold">Cancel</a></div>
    </form>
</x-layouts.app>
