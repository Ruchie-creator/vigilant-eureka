<x-layouts.app heading="Weekly Reports">
    <div class="mb-5 flex justify-end"><form method="POST" action="{{ route('weekly-reports.store') }}">@csrf<button class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white">Generate Weekly Report</button></form></div>
    <div class="grid gap-5 md:grid-cols-2">
        @forelse ($reports as $report)
            <a href="{{ route('weekly-reports.show', $report) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft hover:border-teal">
                <h2 class="font-semibold text-navy">{{ $report->title }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $report->week_start->format('M j') }} - {{ $report->week_end->format('M j, Y') }}</p>
                <span class="mt-4 inline-flex rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">{{ ucfirst($report->status) }}</span>
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2">No weekly reports generated yet.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $reports->links() }}</div>
</x-layouts.app>
