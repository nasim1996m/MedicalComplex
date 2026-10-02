<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Support\Accounts;
use Illuminate\Support\Facades\DB;

class AdminController extends ApiController
{
    /**
     * Get system overview metrics for Admin Dashboard
     */
    public function dashboardStats()
    {
        $totalPatients = DB::table('patients')->count();
        $totalDoctors = DB::table('users')->where('role', 'doctor')->where('status', 'approved')->count();
        $pendingRequestsCount = DB::table('role_requests')->where('status', 'pending')->count();
        $todayVisitsCount = DB::table('visits')->whereDate('visit_date', today())->count();
        $totalIncome = DB::table('vouchers')->where('voucher_type', 'income')->sum('amount');
        $totalExpenses = DB::table('vouchers')->where('voucher_type', 'expense')->sum('amount');
        $lowStockMedicines = DB::table('medicines')->whereRaw('quantity <= min_threshold')->count();
        $lowStockInventory = DB::table('inventory_items')->whereRaw('quantity <= min_threshold')->count();

        $doctorsList = DB::table('users')
            ->where('role', 'doctor')
            ->where('status', 'approved')
            ->select('id', 'name', 'email', 'avatar', 'specialty', 'phone')
            ->get();

        $pendingRoleRequests = DB::table('role_requests')
            ->join('users', 'role_requests.user_id', '=', 'users.id')
            ->select('role_requests.*', 'users.name as user_name', 'users.email as user_email', 'users.avatar as user_avatar')
            ->where('role_requests.status', 'pending')
            ->orderBy('role_requests.created_at', 'desc')
            ->get();

        return $this->success([
            'stats' => [
                'total_patients' => $totalPatients,
                'total_doctors' => $totalDoctors,
                'pending_requests' => $pendingRequestsCount,
                'today_visits' => $todayVisitsCount,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_profit' => $totalIncome - $totalExpenses,
                'low_stock_alerts' => $lowStockMedicines + $lowStockInventory,
            ],
            'doctors' => $doctorsList,
            'pending_role_requests' => $pendingRoleRequests,
        ]);
    }

    /**
     * Approve or reject a pending user role request.
     */
    public function approveRoleRequest(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        if (!Accounts::review((int) $id, $request->user(), $request->action === 'approve')) {
            return $this->error('الطلب غير موجود أو تمت مراجعته مسبقاً', 404);
        }

        return $this->success(null, $request->action === 'approve' ? 'تمت الموافقة على الطلب وتفعيل الحساب بنجاح.' : 'تم رفض الطلب.');
    }
}
