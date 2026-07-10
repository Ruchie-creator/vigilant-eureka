<?php

namespace App\Http\Controllers;

use App\Services\EnvFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('settings', [
            'settings' => $this->settingsPayload(),
        ]);
    }

    public function update(Request $request, EnvFileService $env): RedirectResponse
    {
        $data = $request->validate([
            'app_url' => ['nullable', 'url', 'max:255'],
            'openai_api_key' => ['nullable', 'string', 'max:5000'],
            'openai_model' => ['nullable', 'string', 'max:100'],
            'openai_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'google_client_id' => ['nullable', 'string', 'max:1000'],
            'google_client_secret' => ['nullable', 'string', 'max:1000'],
            'google_redirect_uri' => ['nullable', 'url', 'max:255'],
            'mail_mailer' => ['nullable', Rule::in(['smtp', 'log'])],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_encryption' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $updates = [
            'APP_URL' => $data['app_url'] ?? null,
            'OPENAI_API_KEY' => $request->filled('openai_api_key') ? $data['openai_api_key'] : null,
            'OPENAI_MODEL' => $data['openai_model'] ?? null,
            'OPENAI_TIMEOUT' => $data['openai_timeout'] ?? null,
            'GOOGLE_CLIENT_ID' => $data['google_client_id'] ?? null,
            'GOOGLE_CLIENT_SECRET' => $request->filled('google_client_secret') ? $data['google_client_secret'] : null,
            'GOOGLE_REDIRECT_URI' => $data['google_redirect_uri'] ?? null,
            'MAIL_MAILER' => $data['mail_mailer'] ?? null,
            'MAIL_HOST' => $data['mail_host'] ?? null,
            'MAIL_PORT' => $data['mail_port'] ?? null,
            'MAIL_USERNAME' => $data['mail_username'] ?? null,
            'MAIL_PASSWORD' => $request->filled('mail_password') ? $data['mail_password'] : null,
            'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? null,
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? null,
            'MAIL_FROM_NAME' => $data['mail_from_name'] ?? null,
        ];

        try {
            $env->update($updates);
            Artisan::call('optimize:clear');

            return back()->with('success', 'Settings updated. The dashboard now reflects the latest .env configuration.');
        } catch (\Throwable) {
            return back()->with('error', 'Settings could not be saved. Check that the .env file is writable.');
        }
    }

    private function settingsPayload(): array
    {
        return [
            'app_url' => config('app.url'),
            'openai' => [
                'api_key_masked' => $this->mask(config('services.openai.key')),
                'has_api_key' => filled(config('services.openai.key')),
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'timeout' => config('services.openai.timeout', 20),
            ],
            'google' => [
                'client_id' => config('services.google.client_id'),
                'client_secret_masked' => $this->mask(config('services.google.client_secret')),
                'has_client_secret' => filled(config('services.google.client_secret')),
                'redirect_uri' => config('services.google.redirect_uri'),
                'scope' => config('services.google.search_console_scope'),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'has_password' => filled(config('mail.mailers.smtp.password')),
                'password_masked' => $this->mask(config('mail.mailers.smtp.password')),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ];
    }

    private function mask(?string $value): string
    {
        if (blank($value)) {
            return 'Not set';
        }

        return str_repeat('*', max(8, min(16, strlen($value) - 4))).substr($value, -4);
    }
}
