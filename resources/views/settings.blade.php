<x-layouts.app heading="Settings">
    <section class="max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
        <h2 class="font-semibold text-navy">Deployment Notes</h2>
        <div class="mt-4 grid gap-4 text-sm leading-6 text-slate-700">
            <p>Point the clint-rono.dev domain or subdomain document root to the Laravel <span class="font-semibold">public</span> directory.</p>
            <p>Keep production secrets in <span class="font-semibold">.env</span>, including database credentials and the future OpenAI API key.</p>
            <p>Use MySQL or MariaDB, run migrations and seeders, then cache configuration, routes, and views for production.</p>
            <p>The scanner validates public HTTP URLs, rejects private or reserved hosts, and uses short request timeouts.</p>
        </div>
    </section>
</x-layouts.app>
