# Month-wise finance audit and isolated test data

## Audit findings

The application is Laravel 12 with Blade, Vite/Sass, Laravel session authentication, MySQL for local development, and SQLite in-memory databases for automated tests.

The ledger already used `fee_invoices.billing_month` and a unique `(student_id, billing_month)` database constraint. The incorrect accumulation was caused by query scope rather than a missing second ledger:

- Family Fees loaded every unpaid invoice, regardless of the month being viewed.
- A family payment always allocated against every outstanding invoice, so opening October did not mean the payment was limited to October.
- Family profile summaries were fixed to today's month and mixed cash receipt dates with invoice billing periods.
- Reports compared invoices selected by billing month with payments selected by payment date under one “Collected” label.
- `firstOrCreate` compared a date-cast midnight timestamp to a plain date under SQLite, so a repeated generation attempt reached the unique constraint instead of returning the existing invoice.

The fix keeps the existing accounting architecture. Every monthly charge remains one student invoice, every payment remains linked to its original invoice, and family payments continue to create internal invoice-level allocations.

## Accounting definitions

- **Current month fees:** invoice totals whose `billing_month` is the selected month.
- **Applied to selected month:** completed payments linked to those invoices, independent of receipt date.
- **Selected month outstanding:** selected-month invoice totals minus completed linked payments.
- **Previous outstanding:** remaining balances on invoices before the selected month.
- **Cash received:** completed transactions whose `payment_date` is inside the selected date range.
- **Total through selected month:** selected-month outstanding plus previous outstanding. Future invoices are excluded.

## Safe isolated fixture database (PowerShell)

Do not point these commands at the normal `.env` database. They create and reset only `storage/testing/school-fixtures.sqlite`.

```powershell
$fixtureRoot = Join-Path (Resolve-Path '.').Path 'storage\testing'
New-Item -ItemType Directory -Force -Path $fixtureRoot | Out-Null
$fixtureDb = Join-Path $fixtureRoot 'school-fixtures.sqlite'
if (-not $fixtureDb.StartsWith($fixtureRoot, [System.StringComparison]::OrdinalIgnoreCase)) { throw 'Unsafe fixture path' }
New-Item -ItemType File -Force -Path $fixtureDb | Out-Null

$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $fixtureDb
php artisan migrate:fresh --seed --seeder="Database\Seeders\ComprehensiveTestDataSeeder"
```

The seeder refuses production. In a local environment it also requires `ALLOW_TEST_FIXTURES=true`; the dedicated `testing` environment is allowed automatically.

The dataset is deterministic and idempotent and includes 100 fictional students, 20 families, 11 classes with two sections, 12 teachers, 6 non-teaching staff, guardians, four billing months, full/partial/unpaid/cross-month payments, five combined family payments, attendance, exams and marks, timetables, and three payroll months.

To reset, run the same `migrate:fresh` command only after verifying `$fixtureDb` still resolves inside `storage\testing`. The normal MySQL database is never selected by this procedure.

## Automated verification

```powershell
php artisan test
npm run build
```

The finance suite covers month switching, monthly isolation, previous dues, partial payments, selected-month family payments, oldest-first all-outstanding payments, cross-month receipt reporting, duplicate invoice prevention, full outstanding reconciliation, and fixture idempotency.
