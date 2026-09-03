---
paths:
  - 'database/migrations/**'
---

# Migrations

## noerd/customer needs the published audits table
Customer and CustomerAddress implement owen-it/laravel-auditing's Auditable, but the package ships its migration only as a stub. Without publishing it, the customer detail screen's activity log dies with "SQLSTATE[HY000]: General error: 1 no such table: audits".

Publish it once with `php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag=migrations`; the app keeps it as database/migrations/*_create_audits_table.php so `demo:reset` (migrate:fresh --seed, every two hours) recreates the table.

Audits are not written from the console (`audit.console` defaults to false), so a seeder creates no audit records — tests that need one must set `config(['audit.console' => true])`.
