<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends ApiController
{
    /**
     * Get system overview metrics for Admin Dashboard
     */
    public function dashboardStats()
    {
        // ─── مزامنة تلقائية: أي مستخدم بحالة pending وليس لديه سجل في role_requests يتم إضافته فوراً للأدمن ───
        $pendingUsersWithoutRequest = DB::table('users')
            ->where(function ($q) {
                $q->where('role', 'pending')->orWhere('status', 'pending');
            })
            ->whereNotIn('id', function ($sub) {
                $sub->select('user_id')->from('role_requests');
            })
            ->get();

        foreach ($pendingUsersWithoutRequest as $pu) {
            DB::table('role_requests')->insert([
                'user_id'             => $pu->id,
                'requested_role'      => 'doctor',
                'requested_specialty' => $pu->specialty ?: 'طبيب عام (بانتظار تحديد التخصص)',
                'notes'               => 'تسجيل عبر Google وبانتظار موافقة وتفعيل الأدمن',
                'status'              => 'pending',
                'created_at'          => $pu->created_at ?: now(),
                'updated_at'          => now(),
            ]);
        }

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
     * Approve or reject a user role request
     */
    public function approveRoleRequest(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_id' => 'nullable|exists:users,id',
        ]);

        $adminId = $request->admin_id ?? DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        $roleRequest = DB::table('role_requests')->where('id', $id)->first();
        if (!$roleRequest) {
            // Also check by user_id
            $roleRequest = DB::table('role_requests')->where('user_id', $id)->first();
        }

        if (!$roleRequest) {
            return $this->error('طلب غير موجود', 404);
        }

        if ($request->action === 'approve') {
            DB::table('role_requests')->where('id', $roleRequest->id)->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $roleRequest->user_id)->update([
                'role' => $roleRequest->requested_role ?: 'doctor',
                'specialty' => $roleRequest->requested_specialty,
                'status' => 'approved',
                'updated_at' => now(),
            ]);

            // Notify user
            DB::table('notifications')->insert([
                'user_id' => $roleRequest->user_id,
                'title' => 'تمت الموافقة على حسابك!',
                'message' => 'لقد وافق الأدمن على طلبك. يمكنك الآن الوصول إلى لوحة تحكم ' . $roleRequest->requested_role,
                'type' => 'role_approved',
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->success(null, 'تمت الموافقة على الطلب وتفعيل الحساب بنجاح.');
        } else {
            DB::table('role_requests')->where('id', $id)->update([
                'status' => 'rejected',
                'reviewed_by' => $request->admin_id,
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $roleRequest->user_id)->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);

            return $this->success(null, 'تم رفض الطلب.');
        }
    }
}
