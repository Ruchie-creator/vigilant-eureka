<x-layouts.app heading="Settings">
    <form method="POST" action="{{ route('settings.update') }}" class="grid gap-6">
        @csrf

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-navy">Application</h2>
                    <p class="mt-1 text-sm text-slate-500">Used for local testing, production URLs, and OAuth callbacks.</p>
                </div>
                <span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">.env synced</span>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold md:col-span-2">
                    App URL
                    <input name="app_url" value="{{ old('app_url', $settings['app_url']) }}" placeholder="http://127.0.0.1:8000" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-navy">OpenAI API</h2>
                    <p class="mt-1 text-sm text-slate-500">Powers AI marketing insights. API keys are stored only in the local .env file.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $settings['openai']['has_api_key'] ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $settings['openai']['has_api_key'] ? 'Configured' : 'Not configured' }}</span>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold md:col-span-2">
                    API Key
                    <input name="openai_api_key" type="password" autocomplete="new-password" placeholder="{{ $settings['openai']['has_api_key'] ? 'Configured - leave blank to keep current key' : 'Enter API key' }}" class="min-h-10 rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Model
                    <input name="openai_model" value="{{ old('openai_model', $settings['openai']['model']) }}" placeholder="gpt-4o-mini" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Timeout seconds
                    <input name="openai_timeout" type="number" min="5" max="120" value="{{ old('openai_timeout', $settings['openai']['timeout']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-navy">Google Search Console</h2>
                    <p class="mt-1 text-sm text-slate-500">Readonly OAuth connection for public search performance sync.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $settings['google']['client_id'] && $settings['google']['has_client_secret'] ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $settings['google']['client_id'] && $settings['google']['has_client_secret'] ? 'Configured' : 'Not configured' }}</span>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold">
                    Client ID
                    <input name="google_client_id" value="{{ old('google_client_id', $settings['google']['client_id']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Client Secret
                    <input name="google_client_secret" type="password" autocomplete="new-password" placeholder="{{ $settings['google']['has_client_secret'] ? 'Configured - leave blank to keep current secret' : 'Enter client secret' }}" class="min-h-10 rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold md:col-span-2">
                    Redirect URI
                    <input name="google_redirect_uri" value="{{ old('google_redirect_uri', $settings['google']['redirect_uri']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600 md:col-span-2">
                    Scope: <span class="break-all font-semibold text-navy">{{ $settings['google']['scope'] }}</span>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('google.search-console.connect') }}" class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white">Connect Google</a>
                <a href="{{ route('google.search-console.sites') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">View Properties</a>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-navy">Email - Infomaniak</h2>
                    <p class="mt-1 text-sm text-slate-500">Add your Infomaniak mailbox SMTP settings when ready.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $settings['mail']['mailer'] === 'smtp' && $settings['mail']['host'] ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $settings['mail']['mailer'] === 'smtp' && $settings['mail']['host'] ? 'SMTP configured' : 'Log mode / incomplete' }}</span>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold">
                    Mailer
                    <select name="mail_mailer" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                        <option value="log" @selected(old('mail_mailer', $settings['mail']['mailer']) === 'log')>Log only</option>
                        <option value="smtp" @selected(old('mail_mailer', $settings['mail']['mailer']) === 'smtp')>SMTP</option>
                    </select>
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Host
                    <input name="mail_host" value="{{ old('mail_host', $settings['mail']['host']) }}" placeholder="mail.infomaniak.com" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Port
                    <input name="mail_port" type="number" value="{{ old('mail_port', $settings['mail']['port']) }}" placeholder="587" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Encryption
                    <select name="mail_encryption" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                        <option value="" @selected(old('mail_encryption', $settings['mail']['encryption']) === null || old('mail_encryption', $settings['mail']['encryption']) === '')>None</option>
                        <option value="tls" @selected(old('mail_encryption', $settings['mail']['encryption']) === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('mail_encryption', $settings['mail']['encryption']) === 'ssl')>SSL</option>
                    </select>
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Username
                    <input name="mail_username" value="{{ old('mail_username', $settings['mail']['username']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    Password
                    <input name="mail_password" type="password" autocomplete="new-password" placeholder="{{ $settings['mail']['has_password'] ? 'Configured - leave blank to keep current password' : 'Enter mail password' }}" class="min-h-10 rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    From Address
                    <input name="mail_from_address" type="email" value="{{ old('mail_from_address', $settings['mail']['from_address']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    From Name
                    <input name="mail_from_name" value="{{ old('mail_from_name', $settings['mail']['from_name']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-soft">
            <h2 class="font-semibold text-navy">Deployment Notes</h2>
            <div class="mt-4 grid gap-4 text-sm leading-6 text-slate-700">
                <p>Point the clint-rono.dev domain or subdomain document root to the Laravel <span class="font-semibold">public</span> directory.</p>
                <p>Keep production secrets in <span class="font-semibold">.env</span>. This page updates those values for the private admin only.</p>
                <p>Only read-only Google Search Console performance data is synced. Sensitive personal or medical data must not be collected or processed.</p>
            </div>
        </section>

        <div class="sticky bottom-0 rounded-lg border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
            <button class="rounded-lg bg-teal px-5 py-3 text-sm font-semibold text-white">Save Settings</button>
        </div>
    </form>
</x-layouts.app>
