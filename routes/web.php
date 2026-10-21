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


//Route::view('/','pages.auth.login2')->name('login2');

// Routes protégées
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', DashboardStats::class)->name('home');
    // Alias utilisé par Fortify (config fortify.home) et les vues du starter kit
    Route::redirect('/dashboard', '/')->name('dashboard');

    // ── Patients ───────────────────────────────────────────
    Route::prefix('patients')->name('patients.')->middleware('permission:view patients')->group(function () {
        Route::get('/', PatientIndex::class)->name('index');
        Route::get('/create', PatientForm::class)->name('create')->middleware('permission:create patients');
        Route::get('/{patient}/edit', PatientForm::class)->name('edit')->middleware('permission:edit patients');
        Route::get('/{patient}', PatientShow::class)->name('show');
    });

    // ── Rendez-vous ────────────────────────────────────────
    Route::prefix('appointments')->name('appointments.')->middleware('permission:view appointments')->group(function () {
        Route::get('/', AppointmentIndex::class)->name('index');
        Route::get('/calendar', AppointmentCalendar::class)->name('calendar');
        Route::get('/waiting-room', WaitingRoom::class)->name('waiting-room');
        Route::get('/create', AppointmentIndex::class)->name('create')->middleware('permission:create appointments');
    });

    // ── Consultations ──────────────────────────────────────
    Route::prefix('consultations')->name('consultations.')->middleware('permission:view consultations')->group(function () {
        Route::get('/', ConsultationIndex::class)->name('index');
        Route::get('/create', ConsultationForm::class)->name('create')->middleware('permission:create consultations');
        Route::get('/{consultation}', ConsultationShow::class)->name('show');
        Route::get('/{consultation}/edit', ConsultationForm::class)->name('edit')->middleware('permission:edit consultations');
    });

    // ── Ordonnances ────────────────────────────────────────
    Route::prefix('prescriptions')->name('prescriptions.')->middleware('permission:view prescriptions')->group(function () {
        Route::get('/', \App\Livewire\Prescription\PrescriptionIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Prescription\PrescriptionForm::class)->name('create')->middleware('permission:create prescriptions');
        Route::get('/{prescription}/edit', \App\Livewire\Prescription\PrescriptionForm::class)->name('edit')->middleware('permission:create prescriptions');
        Route::get('/{prescription}/pdf', [\App\Http\Controllers\PrescriptionController::class, 'pdf'])->name('pdf')->middleware('permission:print prescriptions');
        Route::get('/{prescription}', \App\Livewire\Prescription\PrescriptionShow::class)->name('show');
    });

    // ── Analyses ───────────────────────────────────────────
    Route::prefix('lab-tests')->name('lab-tests.')->middleware('permission:view lab-tests')->group(function () {
        Route::get('/', \App\Livewire\LabTest\LabTestIndex::class)->name('index');
        Route::get('/create', \App\Livewire\LabTest\LabTestForm::class)->name('create')->middleware('permission:create lab-tests');
        Route::get('/{labTest}/edit', \App\Livewire\LabTest\LabTestForm::class)->name('edit')->middleware('permission:edit lab-tests');
        Route::get('/{labTest}/pdf', [\App\Http\Controllers\LabTestController::class, 'pdf'])->name('pdf');
        Route::get('/{labTest}', \App\Livewire\LabTest\LabTestShow::class)->name('show');
    });

    // ── Imagerie ───────────────────────────────────────────
    Route::prefix('medical-images')->name('medical-images.')->middleware('permission:view medical-images')->group(function () {
        Route::get('/', \App\Livewire\MedicalImage\MedicalImageIndex::class)->name('index');
        Route::get('/create', \App\Livewire\MedicalImage\MedicalImageForm::class)->name('create')->middleware('permission:create medical-images');
        Route::get('/{medicalImage}/edit', \App\Livewire\MedicalImage\MedicalImageForm::class)->name('edit')->middleware('permission:edit medical-images');
        Route::get('/{medicalImage}', \App\Livewire\MedicalImage\MedicalImageShow::class)->name('show');
    });

    // ── Documents ──────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->middleware('permission:view documents')->group(function () {
        Route::get('/', \App\Livewire\Document\DocumentIndex::class)->name('index');
        Route::get('/create', \App\Livewire\Document\DocumentForm::class)->name('create')->middleware('permission:upload documents');
        Route::get('/{document}/edit', \App\Livewire\Document\DocumentForm::class)->name('edit')->middleware('permission:upload documents');
    });

    // ── Rapports médicaux ──────────────────────────────────
    Route::prefix('medical-reports')->name('medical-reports.')->middleware('permission:view medical-reports')->group(function () {
        Route::get('/', \App\Livewire\MedicalReport\MedicalReportIndex::class)->name('index');
        Route::get('/create', \App\Livewire\MedicalReport\MedicalReportForm::class)->name('create')->middleware('permission:create medical-reports');
        Route::get('/{medicalReport}/edit', \App\Livewire\MedicalReport\MedicalReportForm::class)->name('edit')->middleware('permission:edit medical-reports');
        Route::get('/{medicalReport}/pdf', [\App\Http\Controllers\MedicalReportController::class, 'pdf'])->name('pdf')->middleware('permission:print medical-reports');
        Route::get('/{medicalReport}', \App\Livewire\MedicalReport\MedicalReportShow::class)->name('show');
    });

    // ── Paiements ──────────────────────────────────────────
    Route::prefix('payments')->name('payments.')->middleware('permission:view payments')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
        Route::get('/{payment}/pdf', [\App\Http\Controllers\PaymentController::class, 'pdf'])->name('pdf');
    });

    // ── Rapports & statistiques ────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('permission:view reports')->group(function () {
        Route::get('/financial', \App\Livewire\Reports\FinancialReport::class)->name('financial');
        Route::get('/stats', \App\Livewire\Reports\CabinetStats::class)->name('stats');
    });

    // ── Notifications ──────────────────────────────────────
    Route::get('/notifications', \App\Livewire\Notification\NotificationIndex::class)->name('notifications.index');

    // ── Actes Médicaux ─────────────────────────────────────
    Route::get('/medical-acts', \App\Livewire\Admin\MedicalActIndex::class)->name('medical-acts.index')->middleware('permission:manage medications');

    // ── Médicaments ────────────────────────────────────────
    Route::get('/medications', \App\Livewire\Admin\MedicationIndex::class)->name('medications.index')->middleware('permission:manage medications');

    // ── Administration ─────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('permission:manage users')->group(function () {
        Route::get('/users', \App\Livewire\Admin\UserIndex::class)->name('users.index');
        Route::get('/roles', \App\Livewire\Admin\RoleIndex::class)->name('roles.index')->middleware('permission:manage roles');
        Route::get('/activity-log', \App\Livewire\Admin\ActivityLog::class)->name('activity-log')->middleware('permission:view activity-log');
        Route::get('/settings', \App\Livewire\Admin\CabinetSettings::class)->name('settings');
    });

    // ── API interne ────────────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/appointments/calendar', [\App\Http\Controllers\Api\AppointmentApiController::class, 'calendar'])
            ->name('appointments.calendar')->middleware('permission:view appointments');
        Route::get('/medications/search', [\App\Http\Controllers\Api\MedicationApiController::class, 'search'])
            ->name('medications.search')->middleware('permission:create prescriptions|manage medications');
    });
});

require __DIR__ . '/settings.php';
