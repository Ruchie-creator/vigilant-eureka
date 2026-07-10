# AI Marketing Agents

Private Laravel 11 dashboard for monitoring healthcare-related websites for SEO, marketing performance, content opportunities, and technical website issues.

The MVP intentionally avoids collecting or processing patient medical data.

## Setup

1. Copy `.env.example` to `.env`.
2. Fill in MySQL or MariaDB credentials.
3. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD`.
4. Install dependencies with Composer.
5. Run the Laravel key generation, migrations, and seeder.

## Production

Point the Infomaniak domain or subdomain document root to the `public` directory.

Use the standard Laravel production commands:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Add the scheduler cron entry recommended by Laravel so weekly reports can be generated:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Keep `OPENAI_API_KEY` in `.env` only. The current AI insight button uses rule-based logic and does not call OpenAI yet.
