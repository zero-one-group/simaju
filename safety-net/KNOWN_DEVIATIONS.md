# Known Deviations in the Safety Net

The safety net test suite locks in the current behavior of the application, including bugs, to prevent regressions during refactoring.

The following known deviations (bugs) are currently asserted in the test suite:

1. **Inconsistent PPN Calculation in `LaporanController`**
   - **Location:** `app/Http/Controllers/LaporanController.php:97` vs `app/Http/Controllers/OrderController.php:341`
   - **Details:** When an order is created, `OrderController` calculates PPN based on the `subtotal` (e.g. `ppn = subtotal * 0.1`). However, `LaporanController` calculates PPN based on `DPP` (subtotal minus discount). This results in mismatched total values and a non-zero "Selisih" column in the report. The test `test_report_summary_and_bug` asserts this incorrect discrepancy.

2. **Timezone Bug in Report Aggregation**
   - **Location:** `app/Http/Controllers/LaporanController.php:55`
   - **Details:** The "Per Hari" summary in `LaporanController` uses raw SQL: `DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR))`. If an order is created at 18:00 WIB, adding 7 hours pushes it to 25:00, causing it to be grouped in the next day's bucket. The test `test_report_timezone_bug` explicitly creates an order at 18:00 and asserts it appears on the next day's report.
