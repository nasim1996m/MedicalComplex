<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\AccountantController;
use App\Http\Controllers\Api\HrController;

/*
|--------------------------------------------------------------------------
| Medical Complex API Routes v1
|--------------------------------------------------------------------------
| Every route except Google login requires a Sanctum bearer token, and each
| group is limited to the roles that own it (admins can access everything).
*/

Route::prefix('v1')->group(function () {
    // Public: exchange a verified Google token for an API token.
    Route::post('/auth/google', [AuthController::class, 'googleLogin'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        // Any signed-in user (including pending ones).
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/request-role', [AuthController::class, 'requestRole']);

        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/stats', [AdminController::class, 'dashboardStats']);
            Route::post('/admin/role-requests/{id}', [AdminController::class, 'approveRoleRequest'])->whereNumber('id');
            Route::get('/role-requests', [AuthController::class, 'listRoleRequests']);
            Route::post('/role-requests/{id}/approve', [AuthController::class, 'approveRoleRequest'])->whereNumber('id');
            Route::post('/role-requests/{id}/reject', [AuthController::class, 'rejectRoleRequest'])->whereNumber('id');
        });

        Route::middleware('role:doctor')->group(function () {
            Route::get('/doctor/dashboard', [DoctorController::class, 'dashboard']);
            Route::post('/doctor/visits', [DoctorController::class, 'createVisit']);
            Route::post('/doctor/lab-requests', [DoctorController::class, 'requestLabTest']);
            Route::post('/doctor/prescriptions', [DoctorController::class, 'sendPrescription']);
            Route::get('/doctor/patients/{id}/history', [DoctorController::class, 'patientHistory'])->whereNumber('id');
        });

        Route::middleware('role:pharmacist')->group(function () {
            Route::get('/pharmacy/dashboard', [PharmacyController::class, 'dashboard']);
            Route::post('/pharmacy/prescriptions/{id}/dispense', [PharmacyController::class, 'dispense'])->whereNumber('id');
        });

        Route::middleware('role:lab_tech')->group(function () {
            Route::get('/lab/dashboard', [LabController::class, 'dashboard']);
            Route::post('/lab/requests/{id}/complete', [LabController::class, 'completeTest'])->whereNumber('id');
        });

        Route::middleware('role:storekeeper')->group(function () {
            Route::get('/inventory/dashboard', [InventoryController::class, 'dashboard']);
            Route::post('/inventory/restock', [InventoryController::class, 'restockItem']);
        });

        Route::middleware('role:accountant')->group(function () {
            Route::get('/accountant/dashboard', [AccountantController::class, 'dashboard']);
            Route::post('/accountant/vouchers', [AccountantController::class, 'addVoucher']);
        });

        Route::middleware('role:hr')->group(function () {
            Route::get('/hr/dashboard', [HrController::class, 'dashboard']);
            Route::post('/hr/fingerprint', [HrController::class, 'recordFingerprint']);
            Route::post('/hr/rosters', [HrController::class, 'addRoster']);
        });
    });
});
