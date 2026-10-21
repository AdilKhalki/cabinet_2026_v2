<?php

use App\Http\Controllers\Api\AppointmentApiController;
use App\Http\Controllers\Api\MedicationApiController;
use App\Http\Controllers\LabTestController;
use App\Http\Controllers\MedicalReportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrescriptionController;
use App\Livewire\Admin\ActivityLog;
use App\Livewire\Admin\CabinetSettings;
use App\Livewire\Admin\MedicalActIndex;
use App\Livewire\Admin\MedicationIndex;
use App\Livewire\Admin\RoleIndex;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Appointment\AppointmentCalendar;
use App\Livewire\Appointment\AppointmentIndex;
use App\Livewire\Appointment\WaitingRoom;
use App\Livewire\Consultation\ConsultationForm;
use App\Livewire\Consultation\ConsultationIndex;
use App\Livewire\Consultation\ConsultationShow;
use App\Livewire\Dashboard\DashboardStats;
use App\Livewire\Document\DocumentForm;
use App\Livewire\Document\DocumentIndex;
use App\Livewire\LabTest\LabTestForm;
use App\Livewire\LabTest\LabTestIndex;
use App\Livewire\LabTest\LabTestShow;
use App\Livewire\MedicalImage\MedicalImageForm;
use App\Livewire\MedicalImage\MedicalImageIndex;
use App\Livewire\MedicalImage\MedicalImageShow;
use App\Livewire\MedicalReport\MedicalReportForm;
use App\Livewire\MedicalReport\MedicalReportIndex;
use App\Livewire\MedicalReport\MedicalReportShow;
use App\Livewire\Notification\NotificationIndex;
use App\Livewire\Patient\PatientForm;
use App\Livewire\Patient\PatientIndex;
use App\Livewire\Patient\PatientShow;
use App\Livewire\Payment\PaymentIndex;
use App\Livewire\Prescription\PrescriptionForm;
use App\Livewire\Prescription\PrescriptionIndex;
use App\Livewire\Prescription\PrescriptionShow;
use App\Livewire\Reports\CabinetStats;
use App\Livewire\Reports\FinancialReport;
use Illuminate\Support\Facades\Route;

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
        Route::get('/', PrescriptionIndex::class)->name('index');
        Route::get('/create', PrescriptionForm::class)->name('create')->middleware('permission:create prescriptions');
        Route::get('/{prescription}/edit', PrescriptionForm::class)->name('edit')->middleware('permission:create prescriptions');
        Route::get('/{prescription}/pdf', [PrescriptionController::class, 'pdf'])->name('pdf')->middleware('permission:print prescriptions');
        Route::get('/{prescription}', PrescriptionShow::class)->name('show');
    });

    // ── Analyses ───────────────────────────────────────────
    Route::prefix('lab-tests')->name('lab-tests.')->middleware('permission:view lab-tests')->group(function () {
        Route::get('/', LabTestIndex::class)->name('index');
        Route::get('/create', LabTestForm::class)->name('create')->middleware('permission:create lab-tests');
        Route::get('/{labTest}/edit', LabTestForm::class)->name('edit')->middleware('permission:edit lab-tests');
        Route::get('/{labTest}/pdf', [LabTestController::class, 'pdf'])->name('pdf');
        Route::get('/{labTest}', LabTestShow::class)->name('show');
    });

    // ── Imagerie ───────────────────────────────────────────
    Route::prefix('medical-images')->name('medical-images.')->middleware('permission:view medical-images')->group(function () {
        Route::get('/', MedicalImageIndex::class)->name('index');
        Route::get('/create', MedicalImageForm::class)->name('create')->middleware('permission:create medical-images');
        Route::get('/{medicalImage}/edit', MedicalImageForm::class)->name('edit')->middleware('permission:edit medical-images');
        Route::get('/{medicalImage}', MedicalImageShow::class)->name('show');
    });

    // ── Documents ──────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->middleware('permission:view documents')->group(function () {
        Route::get('/', DocumentIndex::class)->name('index');
        Route::get('/create', DocumentForm::class)->name('create')->middleware('permission:upload documents');
        Route::get('/{document}/edit', DocumentForm::class)->name('edit')->middleware('permission:upload documents');
    });

    // ── Rapports médicaux ──────────────────────────────────
    Route::prefix('medical-reports')->name('medical-reports.')->middleware('permission:view medical-reports')->group(function () {
        Route::get('/', MedicalReportIndex::class)->name('index');
        Route::get('/create', MedicalReportForm::class)->name('create')->middleware('permission:create medical-reports');
        Route::get('/{medicalReport}/edit', MedicalReportForm::class)->name('edit')->middleware('permission:edit medical-reports');
        Route::get('/{medicalReport}/pdf', [MedicalReportController::class, 'pdf'])->name('pdf')->middleware('permission:print medical-reports');
        Route::get('/{medicalReport}', MedicalReportShow::class)->name('show');
    });

    // ── Paiements ──────────────────────────────────────────
    Route::prefix('payments')->name('payments.')->middleware('permission:view payments')->group(function () {
        Route::get('/', PaymentIndex::class)->name('index');
        Route::get('/{payment}/pdf', [PaymentController::class, 'pdf'])->name('pdf');
    });

    // ── Rapports & statistiques ────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('permission:view reports')->group(function () {
        Route::get('/financial', FinancialReport::class)->name('financial');
        Route::get('/stats', CabinetStats::class)->name('stats');
    });

    // ── Notifications ──────────────────────────────────────
    Route::get('/notifications', NotificationIndex::class)->name('notifications.index');

    // ── Actes Médicaux ─────────────────────────────────────
    Route::get('/medical-acts', MedicalActIndex::class)->name('medical-acts.index')->middleware('permission:manage medications');

    // ── Médicaments ────────────────────────────────────────
    Route::get('/medications', MedicationIndex::class)->name('medications.index')->middleware('permission:manage medications');

    // ── Administration ─────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('permission:manage users')->group(function () {
        Route::get('/users', UserIndex::class)->name('users.index');
        Route::get('/roles', RoleIndex::class)->name('roles.index')->middleware('permission:manage roles');
        Route::get('/activity-log', ActivityLog::class)->name('activity-log')->middleware('permission:view activity-log');
        Route::get('/settings', CabinetSettings::class)->name('settings');
    });

    // ── API interne ────────────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/appointments/calendar', [AppointmentApiController::class, 'calendar'])
            ->name('appointments.calendar')->middleware('permission:view appointments');
        Route::get('/medications/search', [MedicationApiController::class, 'search'])
            ->name('medications.search')->middleware('permission:create prescriptions|manage medications');
    });
});

require __DIR__.'/settings.php';
