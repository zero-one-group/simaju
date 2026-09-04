# Upgrade Log (Laravel 6 → Laravel 8 / PHP 7.3 → PHP 8.0)

## Docker Changes
- **Dockerfile**: Updated base image from `php:7.3-fpm` to `php:8.0-fpm`.
- **Dockerfile**: Changed `--with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/` to `--with-freetype --with-jpeg` (PHP 8.0 GD configure API change).
- **Dockerfile**: Removed `default-mysql-client` (Debian Bullseye MariaDB packages 404'd from security mirror). App doesn't need a mysql CLI client at runtime.
- **Dockerfile**: Added `--fix-missing` to `apt-get update` to tolerate stale mirrors.
- **docker-compose.yml**: Added explicit DNS (`8.8.8.8`, `8.8.4.4`) to the `app` service to resolve packagist connectivity issues inside the container.

## Composer Dependency Changes
| Package | Old Version | New Version | Notes |
|---|---|---|---|
| `php` | `^7.3` | `^7.3\|^8.0` | |
| `laravel/framework` | `^6.20.26` | `^8.0` | Installed `v8.83.29` |
| `laravel/ui` | `^1.3` | `^3.0` | Required for L8 |
| `barryvdh/laravel-dompdf` | `^0.8.7` | `^0.9.0` | PHP 8 compat; dompdf upgraded to v1.2.2 |
| `facade/ignition` | `^1.16.15` | `^2.5` | L8-compatible error page |
| `nunomaduro/collision` | `^3.0` | `^5.0` | L8 CLI error handler |
| `mockery/mockery` | `^1.0` | `^1.4.4` | PHP 8 compat |
| `phpunit/phpunit` | `^8.5.8\|^9.3.3` | `^9.3.3` | Dropped PHPUnit 8 |

### Automatically Upgraded Transitive Dependencies
- `symfony/*` packages upgraded from v4 to v5/v6 (console, http-kernel, routing, etc.)
- `vlucas/phpdotenv` v3 → v5
- `ramsey/uuid` v3 → v4
- `league/commonmark` v1 → v2
- `dragonmantank/cron-expression` v2 → v3
- New: `laravel/serializable-closure`, `psr/event-dispatcher`, `symfony/string`
- Removed: `symfony/polyfill-php72`, `symfony/debug`, `paragonie/random_compat`

### Abandoned Packages (kept for now)
- `laravelcollective/html` → abandoned, recommends `spatie/laravel-html`. Kept because views depend on `{!! Form:: !!}` helpers extensively.
- `swiftmailer/swiftmailer` → abandoned, recommends `symfony/mailer`. Kept because L8 still ships with SwiftMailer; migration to symfony/mailer is an L9 concern.

## Breaking Code Changes Fixed

1. **`App\Exceptions\Handler`** — `report(Exception)` and `render($request, Exception)` signatures changed to `Throwable` in L8. Updated both method signatures.

2. **`App\Http\Middleware\CheckForMaintenanceMode`** — Removed in L8. Created `App\Http\Middleware\PreventRequestsDuringMaintenance` extending `Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance`. Updated `App\Http\Kernel` to reference the new middleware.

3. **`App\Providers\AppServiceProvider`** — Added `\Illuminate\Pagination\Paginator::useBootstrap()` in `boot()`. Laravel 8 defaults to Tailwind CSS pagination views; the app uses Bootstrap.

4. **Seeder/Factory Autoloading** — `composer.json` still has `"classmap": ["database/seeds", "database/factories"]` which works in L8 (namespace-based seeders are optional in L8, mandatory in L9+). No change needed for this rung.
