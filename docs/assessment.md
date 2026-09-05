# SIMAJU Application Assessment

## Architecture Summary
The application follows a standard but degraded MVC structure in Laravel 6. **Routes** are defined in `routes/web.php`, which heavily relies on route closures for both redirects and API endpoints. **Controllers** (e.g., `OrderController`, `LaporanController`) are severely bloated, taking on responsibilities like manual validation, raw database transactions, PDF generation, and email dispatching. **Models** exist (e.g., `Order`, `Product`), but the application frequently bypasses them in favor of raw `DB::table()` queries or `DB::select()`. **Views** are standard Blade templates but often rely on manually pre-calculated variables passed down from the monolithic controllers.

## 5 Riskiest Hotspots
- **`app/Http/Controllers/OrderController.php:80`**: The `store` method is over 500 lines long, handling everything from manual data validation and DB inserts to PDF generation and SMTP email dispatching.
- **`app/Http/Controllers/OrderController.php:21`**: Hardcoded SMTP credentials (`$smtp_host`, `$smtp_user`, `$smtp_pass`) and an API key are stored directly as class properties, posing a severe security and deployment risk.
- **`app/Http/Controllers/LaporanController.php:50`**: Pervasive use of raw SQL string queries via `DB::select()` (e.g. `SELECT COUNT(*) as jml_order...`) bypassing Eloquent and creating a high risk of SQL injection and fragility.
- **`routes/web.php:211`**: Unprotected API routes (`/api/v1/produk`) are defined as closures directly in web routes, bypassing API middleware and mixing API access with standard web routing.
- **`app/Http/Controllers/OrderController.php:382`**: Manual database transaction handling and rollback are mixed directly with PDF writing (`storage_path`) and email sending, meaning a filesystem or SMTP error could cause an inconsistent application state.

## Duplicated Business Logic / Calculations
The business logic to calculate an order's subtotal, discount, PPN (tax), and total is implemented multiple times across the codebase:
- In `app/Http/Controllers/OrderController.php:328` during order creation (`$diskon = $subtotal * $diskon_persen / 100; $ppn = $subtotal * 0.1;`)
- In `app/Http/Controllers/LaporanController.php:97` for reporting (`$dk = $sub * $dp / 100; $pp = $dpp * 0.1;`)
- Similarly duplicated inline inside the PDF and Email generation HTML strings within `OrderController`.
