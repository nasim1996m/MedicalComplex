<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrController extends ApiController
{
    /**
     * Get HR dashboard data (Employees, Attendance log, Rosters)
     */
    public function dashboard()
    {
        $employees = DB::table('hr_employees')
            ->leftJoin('users', 'hr_employees.user_id', '=', 'users.id')
            ->select('hr_employees.*', 'users.email', 'users.avatar')
            ->get();

        $todayAttendance = DB::table('hr_attendances')
            ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
            ->select('hr_attendances.*', 'hr_employees.name as employee_name', 'hr_employees.job_title', 'hr_employees.department', 'hr_employees.fingerprint_id')
            ->whereDate('hr_attendances.date', today())
            ->get();

        $rosters = DB::table('hr_rosters')
            ->join('hr_employees', 'hr_rosters.employee_id', '=', 'hr_employees.id')
            ->select('hr_rosters.*', 'hr_employees.name as employee_name', 'hr_employees.job_title')
            ->orderBy('hr_rosters.date', 'desc')
            ->get();

        return $this->success([
            'employees' => $employees,
            'today_attendance' => $todayAttendance,
            'rosters' => $rosters,
        ]);
    }

    /**
     * Simulate biometric fingerprint check-in/check-out
     */
    public function recordFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_id' => 'required|string|max:50',
            'action' => 'required|in:check_in,check_out',
        ]);

        $emp = DB::table('hr_employees')->where('fingerprint_id', $request->fingerprint_id)->first();
        if (!$emp) {
            return $this->error('رمز البصمة غير معرف بالنظام', 404);
        }

        $today = today();
        $att = DB::table('hr_attendances')
            ->where('employee_id', $emp->id)
            ->whereDate('date', $today)
            ->first();

        if ($request->action === 'check_in') {
            if ($att) {
                return $this->error('تم تسجيل الحضور سابقاً لهذا اليوم');
            }
            DB::table('hr_attendances')->insert([
                'employee_id' => $emp->id,
                'check_in' => now()->toTimeString(),
                'date' => $today,
                'status' => 'present',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $this->success(null, 'تم تسديد قراءة البصمة (تسجيل دخول) للموظف: ' . $emp->name);
        } else {
            if (!$att) {
                return $this->error('لم يتم تسجيل الدخول أولاً لتسجيل الخروج');
            }
            DB::table('hr_attendances')->where('id', $att->id)->update([
                'check_out' => now()->toTimeString(),
                'updated_at' => now(),
            ]);
            return $this->success(null, 'تم تسديد قراءة البصمة (تسجيل خروج) للموظف: ' . $emp->name);
        }
    }

    /**
     * Add Guard/Staff duty roster item (جدول الحرس واللزام)
     */
    public function addRoster(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'shift' => 'required|in:morning,evening,night',
            'date' => 'required|date',
            'location' => 'required|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rosterId = DB::table('hr_rosters')->insertGetId([
            'employee_id' => $request->employee_id,
            'shift' => $request->shift,
            'date' => $request->date,
            'location' => $request->location,
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['roster_id' => $rosterId], 'تم إضافة الموظف إلى جدول الحرس/الواجبات بنجاح.');
    }
}
