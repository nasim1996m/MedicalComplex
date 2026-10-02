<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebDashboardController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:10,1')->name('web.login.submit');
    Route::post('/auth/google', [WebAuthController::class, 'google'])->middleware('throttle:10,1')->name('web.login.google');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [WebDashboardController::class, 'index'])->name('web.index');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');
    Route::post('/request-role', [WebDashboardController::class, 'submitRoleRequest'])->name('web.submit_role_request');

    Route::middleware('role:admin')->group(function () {
        Route::get('/switch-role/{role}', [WebDashboardController::class, 'switchRole'])->name('web.switch');
        Route::post('/role-requests/{id}/approve', [WebDashboardController::class, 'approveRoleRequest'])->whereNumber('id')->name('web.role_requests.approve');
        Route::post('/role-requests/{id}/reject', [WebDashboardController::class, 'rejectRoleRequest'])->whereNumber('id')->name('web.role_requests.reject');
    });
});
