<x-layouts.app heading="SEO Audits">
    <section class="rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Website</th><th class="px-5 py-3">Title</th><th class="px-5 py-3">HTTP</th><th class="px-5 py-3">Indexability</th><th class="px-5 py-3">Missing</th><th class="px-5 py-3">Date</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($audits as $audit)
                    <tr>
                        <td class="px-5 py-4 font-semibold"><a href="{{ route('websites.show', $audit->website) }}" class="text-teal">{{ $audit->website->name }}</a></td>
                        <td class="px-5 py-4">{{ $audit->page_title ?: 'Untitled' }}</td>
                        <td class="px-5 py-4">{{ $audit->http_status ?: 'n/a' }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $audit->is_indexable ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $audit->is_indexable ? 'Indexable' : 'Review' }}</span></td>
                        <td class="px-5 py-4">{{ $audit->missing_fields ? implode(', ', $audit->missing_fields) : 'None' }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $audit->created_at->format('M j, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No audits yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-6">{{ $audits->links() }}</div>
</x-layouts.app>
