<?php

use App\Livewire\Appointment\AppointmentCalendar;
use App\Livewire\Appointment\AppointmentIndex;
use App\Livewire\Appointment\WaitingRoom;
use App\Livewire\Consultation\ConsultationForm;
use App\Livewire\Consultation\ConsultationIndex;
use App\Livewire\Consultation\ConsultationShow;
use App\Livewire\Dashboard\DashboardStats;
use App\Livewire\Patient\PatientForm;
use App\Livewire\Patient\PatientIndex;
use App\Livewire\Patient\PatientShow;
use App\Livewire\Payment\PaymentIndex;
use Illuminate\Support\Facades\Route;

// Page d'accueil
Route::view('/', 'welcome')->name('home');

// Routes protégées
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardStats::class)->name('dashboard');

    // ── Patients ──────────────────────────────────────────────
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', PatientIndex::class)->name('index');
        Route::get('/create', PatientForm::class)->name('create');
        Route::get('/{patient}/edit', PatientForm::class)->name('edit');
        Route::get('/{patient}', PatientShow::class)->name('show');
    });

    // ── Rendez-vous ────────────────────────────────────────────
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', AppointmentIndex::class)->name('index');
        Route::get('/calendar', AppointmentCalendar::class)->name('calendar');
        Route::get('/waiting-room', WaitingRoom::class)->name('waiting-room');
        Route::get('/create', AppointmentIndex::class)->name('create');
    });

    // ── Consultations ──────────────────────────────────────────
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/', ConsultationIndex::class)->name('index');
        Route::get('/create', ConsultationForm::class)->name('create');
        Route::get('/{consultation}', ConsultationShow::class)->name('show');
        Route::get('/{consultation}/edit', ConsultationForm::class)->name('edit');
    });

    // ── Ordonnances ────────────────────────────────────────────
    Route::prefix('prescriptions')->name('prescriptions.')->group(function () {
        Route::get('/', \App\Livewire\Prescription\PrescriptionIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Prescription\PrescriptionForm::class)->name('create');
        Route::get('/{prescription}/edit', \App\Livewire\Prescription\PrescriptionForm::class)->name('edit');
        Route::get('/{prescription}/pdf', [\App\Http\Controllers\PrescriptionController::class, 'pdf'])->name('pdf');
        Route::get('/{prescription}', \App\Livewire\Prescription\PrescriptionShow::class)->name('show');
    });

    // ── Analyses ───────────────────────────────────────────────
    Route::prefix('lab-tests')->name('lab-tests.')->group(function () {
        Route::get('/', \App\Livewire\LabTest\LabTestIndex::class)->name('index');
        Route::get('/create', \App\Livewire\LabTest\LabTestForm::class)->name('create');
        Route::get('/{labTest}/edit', \App\Livewire\LabTest\LabTestForm::class)->name('edit');
        Route::get('/{labTest}', \App\Livewire\LabTest\LabTestShow::class)->name('show');
    });

    // ── Imagerie ───────────────────────────────────────────────
    Route::prefix('medical-images')->name('medical-images.')->group(function () {
        Route::get('/', \App\Livewire\MedicalImage\MedicalImageIndex::class)->name('index');
        Route::get('/create', \App\Livewire\MedicalImage\MedicalImageForm::class)->name('create');
        Route::get('/{medicalImage}/edit', \App\Livewire\MedicalImage\MedicalImageForm::class)->name('edit');
        Route::get('/{medicalImage}', \App\Livewire\MedicalImage\MedicalImageShow::class)->name('show');
    });

    // ── Documents ──────────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', \App\Livewire\Document\DocumentIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Document\DocumentForm::class)->name('create');
        Route::get('/{document}/edit', \App\Livewire\Document\DocumentForm::class)->name('edit');
    });

    // ── Rapports médicaux ──────────────────────────────────────
    Route::prefix('medical-reports')->name('medical-reports.')->group(function () {
        Route::get('/', \App\Livewire\MedicalReport\MedicalReportIndex::class)->name('index');
        Route::get('/create', \App\Livewire\MedicalReport\MedicalReportForm::class)->name('create');
        Route::get('/{medicalReport}/edit', \App\Livewire\MedicalReport\MedicalReportForm::class)->name('edit');
        Route::get('/{medicalReport}/pdf', [\App\Http\Controllers\MedicalReportController::class, 'pdf'])->name('pdf');
        Route::get('/{medicalReport}', \App\Livewire\MedicalReport\MedicalReportShow::class)->name('show');
    });

    // ── Paiements ──────────────────────────────────────────────
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
        Route::get('/{payment}/pdf', [\App\Http\Controllers\PaymentController::class, 'pdf'])->name('pdf');
    });

    // ── Rapports & statistiques ────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/financial', \App\Livewire\Reports\FinancialReport::class)->name('financial');
        Route::get('/stats', \App\Livewire\Reports\CabinetStats::class)->name('stats');
    });

    // ── Notifications ──────────────────────────────────────────
    Route::get('/notifications', \App\Livewire\Notification\NotificationIndex::class)->name('notifications.index');

    // ── Administration (admin uniquement) ──────────────────────
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', \App\Livewire\Admin\UserIndex::class)->name('users.index');
        Route::get('/roles', \App\Livewire\Admin\RoleIndex::class)->name('roles.index');
        Route::get('/medical-acts', \App\Livewire\Admin\MedicalActIndex::class)->name('medical-acts.index');
        Route::get('/medications', \App\Livewire\Admin\MedicationIndex::class)->name('medications.index');
        Route::get('/activity-log', \App\Livewire\Admin\ActivityLog::class)->name('activity-log');
        Route::get('/settings', \App\Livewire\Admin\CabinetSettings::class)->name('settings');
    });

    // ── API interne ────────────────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/appointments/calendar', [\App\Http\Controllers\Api\AppointmentApiController::class, 'calendar'])
            ->name('appointments.calendar');
        Route::get('/medications/search', [\App\Http\Controllers\Api\MedicationApiController::class, 'search'])
            ->name('medications.search');
    });

});

require __DIR__.'/settings.php';
