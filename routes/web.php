<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebDashboardController;

Route::get('/', [WebDashboardController::class, 'index'])->name('web.index');
Route::get('/login', [WebDashboardController::class, 'index'])->name('web.login');
Route::get('/login-as/{id}', [WebDashboardController::class, 'loginAs'])->name('web.login_as');
Route::get('/switch-role/{role}', [WebDashboardController::class, 'switchRole'])->name('web.switch');
Route::get('/logout', [WebDashboardController::class, 'logout'])->name('web.logout');
Route::post('/request-role', [WebDashboardController::class, 'submitRoleRequest'])->name('web.submit_role_request');
Route::get('/role-requests/{id}/approve', [WebDashboardController::class, 'approveRoleRequest'])->name('web.role_requests.approve');
Route::get('/role-requests/{id}/reject', [WebDashboardController::class, 'rejectRoleRequest'])->name('web.role_requests.reject');
