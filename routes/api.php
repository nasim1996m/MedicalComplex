<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\AccountantController;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Web\WebDashboardController;

/*
|--------------------------------------------------------------------------
| Medical Complex API Routes v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('/auth/google', [AuthController::class, 'googleLogin']);
    Route::post('/auth/request-role', [AuthController::class, 'requestRole']);

    // Admin Routes
    Route::get('/admin/stats', [AdminController::class, 'dashboardStats']);
    Route::post('/admin/role-requests/{id}', [AdminController::class, 'approveRoleRequest']);

    // Shared Patient Records (all doctors, lab & pharmacy)
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{id}/history', [PatientController::class, 'history']);
    Route::get('/visits', [PatientController::class, 'visits']);

    // Doctor Routes
    Route::get('/doctor/dashboard', [DoctorController::class, 'dashboard']);
    Route::post('/doctor/visits', [DoctorController::class, 'createVisit']);
    Route::post('/doctor/consultations', [DoctorController::class, 'saveConsultation']);
    Route::post('/doctor/lab-requests', [DoctorController::class, 'requestLabTest']);
    Route::post('/doctor/prescriptions', [DoctorController::class, 'sendPrescription']);
    Route::get('/doctor/patients/{id}/history', [DoctorController::class, 'patientHistory']);

    // Pharmacy Routes
    Route::get('/pharmacy/dashboard', [PharmacyController::class, 'dashboard']);
    Route::post('/pharmacy/prescriptions/{id}/dispense', [PharmacyController::class, 'dispense']);

    // Lab & Radiology Routes
    Route::get('/lab/dashboard', [LabController::class, 'dashboard']);
    Route::post('/lab/requests/{id}/complete', [LabController::class, 'completeTest']);

    // Inventory Routes
    Route::get('/inventory/dashboard', [InventoryController::class, 'dashboard']);
    Route::post('/inventory/restock', [InventoryController::class, 'restockItem']);

    // Accountant Routes
    Route::get('/accountant/dashboard', [AccountantController::class, 'dashboard']);
    Route::post('/accountant/vouchers', [AccountantController::class, 'addVoucher']);

    // HR Routes
    Route::get('/hr/dashboard', [HrController::class, 'dashboard']);
    Route::post('/hr/fingerprint', [HrController::class, 'recordFingerprint']);
    Route::post('/hr/rosters', [HrController::class, 'addRoster']);


    // مسارات الأدمن (حسب نظام الحماية عندك)
Route::get('/role-requests', [AuthController::class, 'listRoleRequests']);
Route::post('/role-requests/{id}/approve', [AuthController::class, 'approveRoleRequest']);
Route::post('/role-requests/{id}/reject', [AuthController::class, 'rejectRoleRequest']);


Route::get('/request-role', [WebDashboardController::class, 'index'])->name('web.request_role');
});
