# Known Deviations in the Safety Net

The safety net test suite locks in the current behavior of the application, including bugs, to prevent regressions during refactoring.

The following known deviations (bugs) are currently asserted in the test suite:

1. **Timezone Bug in Report Aggregation**
   - **Location:** `app/Http/Controllers/LaporanController.php:55`
   - **Details:** The "Per Hari" summary in `LaporanController` uses raw SQL: `DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR))`. If an order is created at 18:00 WIB, adding 7 hours pushes it to 25:00, causing it to be grouped in the next day's bucket. The test `test_report_timezone_bug` explicitly creates an order at 18:00 and asserts it appears on the next day's report.

## FIXED
- **Inconsistent PPN Calculation in `LaporanController`**: Unified under `App\Domain\OrderCalculator`, calculating PPN uniformly on `subtotal` instead of `DPP`, reducing "Selisih" discrepancies to 0.
