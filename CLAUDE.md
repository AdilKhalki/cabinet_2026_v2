# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Cabinet Médical WA 2026 v2 — a medical practice management system built with Laravel 12, Livewire 4, and Flux UI. The UI is in French.

## Common Commands

```bash
# Full dev environment (Laravel server + queue + Vite)
composer dev

# Run tests (clears config, checks lint, runs PHPUnit)
composer test

# Lint (Laravel Pint)
composer lint          # fix
composer lint:check    # check only

# Assets
npm run dev            # Vite dev server
npm run build          # Production build

# Database
php artisan migrate
php artisan db:seed
```

## Architecture

**Stack:** Laravel 12 · Livewire 4 · Flux UI · Tailwind CSS 4 · Vite 7 · SQLite (dev) / MySQL (prod)

**Auth:** Laravel Fortify with email verification, optional 2FA. Roles via Spatie Permission: `admin`, `medecin`, `secretaire`. Admin routes use `role:admin` middleware.

**Key packages:** Spatie Activity Log (audit trail), Barryvdh DomPDF (PDF generation), Maatwebsite Excel (exports).

### Livewire Components (`app/Livewire/`)

The app is almost entirely Livewire-driven. Each module follows a consistent pattern:
- **Index** component — list with search, filters, pagination (`WithPagination`), inline create/edit/delete
- **Form** component — create/edit with validation, file uploads (`WithFileUploads`)
- **Show** component — detail view

Modules: Patient, Appointment (+ Calendar + WaitingRoom), Consultation, Prescription, LabTest, MedicalImage, MedicalReport, Document, Payment, Notification, Reports (Financial + CabinetStats). Admin modules: UserIndex, RoleIndex, MedicalActIndex, MedicationIndex, ActivityLog, CabinetSettings.

Views live in `resources/views/livewire/{module}/` mirroring the component namespace.

### Routing (`routes/web.php`)

All app routes are behind `auth` + `verified` middleware. Admin routes additionally require `role:admin`. Two internal JSON API endpoints exist under `/api/` (calendar data, medication search) — these are in `routes/web.php`, not a separate API file.

### Models (`app/Models/`)

- `Patient` — auto-generates `dossier_number` (format: `DOS-YYYY-NNNNN`), has soft deletes
- `User` — has `doctors()`, `active()` scopes and role-check helpers (`isAdmin()`, `isMedecin()`, `isSecretaire()`)
- `Appointment` — status enum (planned/confirmed/waiting/in_progress/completed/cancelled/no_show), scopes: `today()`, `forCalendar()`, `forDoctor()`
- Most models use `LogsActivity` trait for audit logging

### PDF Templates

Located in `resources/views/pdf/` — used by controllers in `app/Http/Controllers/` for prescriptions, medical reports, and payment invoices via DomPDF.

### Layouts

- `layouts/cabinet.blade.php` — main authenticated layout
- `layouts/auth/` — authentication pages layout
- `partials/` — navbar, sidebar, footer shared components

### Validation

Validation is done inline in the Livewire components (`$this->validate()` with rules + French attribute names). There are no Form Request classes. French validation messages live in `lang/fr/`.

Authorization: every Livewire action calls `$this->authorize()` against the policies in `app/Policies` (route middleware alone is not enough — Livewire actions can be called directly). Doctors only access their own records.

Medical files (documents, medical images, lab PDFs) are stored on the private `local` disk; never use `Storage::url()` for them. Use the models' `file_url` (signed URL) or an authorized download action. `php artisan cabinet:secure-files` moves legacy files from the public disk.

Numbering (`DOS-`, `ORD-`, `BIO-`, `RPT-`, `FAC-YYYY-NNNNN`) comes from the `GeneratesSequentialNumber` trait (max existing + 1, retries on a duplicate).

## Test Accounts (from seeders)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@cabinet.ma | Admin@2026! |
| Doctor | medecin@cabinet.ma | Medecin@2026! |
| Secretary | secretaire@cabinet.ma | Secret@2026! |
