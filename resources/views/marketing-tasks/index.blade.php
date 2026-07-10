<x-layouts.app heading="Marketing Tasks">
    <div class="mb-5 flex justify-end"><a href="{{ route('marketing-tasks.create') }}" class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white">Create Task</a></div>
    <section class="rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Task</th><th class="px-5 py-3">Website</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Due</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    <tr><td class="px-5 py-4 font-semibold">{{ $task->title }}</td><td class="px-5 py-4">{{ $task->website->name }}</td><td class="px-5 py-4">{{ ucfirst($task->priority) }}</td><td class="px-5 py-4">{{ str_replace('_', ' ', ucfirst($task->status)) }}</td><td class="px-5 py-4">{{ $task->due_date?->format('M j, Y') ?: 'None' }}</td><td class="px-5 py-4 text-right"><a href="{{ route('marketing-tasks.edit', $task) }}" class="text-teal font-semibold">Edit</a></td></tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No marketing tasks yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-6">{{ $tasks->links() }}</div>
</x-layouts.app>
