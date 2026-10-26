# School Management System — Repository Audit and Upgrade Roadmap

Audit date: 2026-10-24

## 1. Existing application

- Laravel 12.70.1 on PHP 8.5 with Laravel UI session authentication.
- Bootstrap 5/Sass and Vite 7 frontend pipeline.
- MySQL is used by the local installation; the test suite uses SQLite in memory.
- Dompdf and Laravel Excel packages are installed.
- The authenticated portal currently exposes four areas: Dashboard, Students, Fees, and Analytics.
- The database currently has application records for users, students, and payments only.
- Students support basic identity/contact data, campus, status, class text, and monthly fee.
- Payments support itemized charges, discount, paid/remaining amounts, receipt numbers, and printable receipts.
- Analytics supports a selected historical date, daily activity, six-month trends, class/campus distribution, and monthly fee position.
- The current automated baseline is green: 8 tests / 38 assertions. The production Vite build also passes.

## 2. Important data-preservation note

The database already contains the 30 demo students and generated fee receipts requested during the earlier review cycle. They are identifiable by demo mobile/receipt prefixes. The commercial upgrade will preserve them because this phase is non-destructive. Demo cleanup must be a separate, explicit operation before production launch.

## 3. Gap analysis

| Area | Current state | Required upgrade |
| --- | --- | --- |
| Authentication | Single authenticated user type | Roles, active/inactive users, authorization boundaries |
| Dashboard | Student/payment cards and recent activity | Overdue alert, upcoming exams, attendance, enrollment and financial visualizations |
| Students | Basic CRUD and free-text class | Admission/roll data, DOB/gender/photo, guardian, session/class/section enrollment, history and promotion |
| Academics | No structured academic data | Sessions, classes, sections, subjects, teacher assignment |
| Fees | Itemized payment receipts | Categories, monthly invoices, due dates, paid/partial/unpaid/overdue states, invoice generation and exports |
| Attendance | Missing | Daily class register, update workflow, history and summaries |
| Exams | Missing | Exams, exam subjects, date sheets, marks, results, roll slips and result cards |
| Reports | Analytics page only | Filtered fee, attendance, exam, student and salary reports with print/PDF/Excel paths |
| Timetable | Missing | Class/section schedules, rooms, teacher assignment and conflict detection |
| Staff & Salary | Missing | Staff directory, roles, salary ledger and payslips |
| Settings | Missing | School profile, academic sessions, grading, fees, users and operational settings |
| Auditability | Application logs only | Database audit trail for material create/update/delete actions |
| Test coverage | Core smoke/analytics/payment tests | Feature tests for every new workflow and authorization rule |

## 4. Delivery plan

### Phase 1 — Platform foundation

1. Add the normalized academic, guardian, staff, settings and audit schema.
2. Extend students and users without removing legacy columns or records.
3. Add models/relationships, reusable validation, audit logging and full module navigation.
4. Add initial school profile and academic-session settings.

### Phase 2 — Priority operations

1. Upgrade student admissions and guardian/enrollment history.
2. Add classes, sections and subjects.
3. Add fee invoices/categories, monthly generation, partial payments and status calculation.
4. Rebuild the dashboard around live operational data.

### Phase 3 — Attendance and examinations

1. Daily attendance register and history.
2. Exam setup, date sheet, marks entry, result calculation, roll slips and result cards.

### Phase 4 — Reports, timetable and payroll

1. Cross-module filtered reports and export/print views.
2. Timetable scheduling with overlap checks.
3. Staff directory, salary records and payslips.

### Phase 5 — Hardening and launch readiness

1. Complete authorization checks and audit coverage.
2. Add empty/error/loading states and accessibility/responsive QA.
3. Add end-to-end feature tests, production build verification and migration rollback checks.
4. Review and explicitly remove demo data before real deployment, if requested.

## 5. Constraints and decisions

- Existing Laravel, Blade, Bootstrap, MySQL, authentication, students, payments, receipts and analytics are retained.
- All changes are additive migrations; no production records are dropped.
- Screens are database-backed. No static fake module pages will be introduced.
- WhatsApp automation/integration is excluded from the product.
- The provided screenshots are treated as visual/product references, not as instructions to copy unrelated branding or functionality.

## 6. Implementation status — 2026-10-24

The commercial MVP described by this roadmap is now implemented:

- Phase 1: normalized schema, roles, active-user checks, audit log, school settings and additive migration completed.
- Phase 2: structured admissions/guardians/enrollments, class promotion, classes/sections/subjects, monthly invoices, partial payments and operational dashboard completed.
- Phase 3: daily attendance, exams, date sheets, marks, roll slips and result cards completed.
- Phase 4: fee/attendance reporting, PDF and CSV exports, timetable conflict detection, staff directory, payroll and payslips completed.
- Phase 5: responsive desktop/mobile QA, authorization boundaries, merge-only JSON backup/restore, migration backfill, production asset build and feature tests completed.

Verification at handoff:

- 17 automated tests passing with 74 assertions.
- Blade templates compile successfully.
- Production Vite build succeeds.
- Authenticated browser regression passes on all ten modules at 1440×1000 and representative pages at 390×844, with no horizontal overflow or rendered error alerts.
