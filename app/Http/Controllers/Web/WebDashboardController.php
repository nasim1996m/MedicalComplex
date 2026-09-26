<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class WebDashboardController extends Controller
{
    /**
     * Show Main Page / Login / Active Dashboard
     */
    public function index(Request $request)
    {
        $user = Session::get('user');

        if (!$user) {
            $users = DB::table('users')->get();
            return view('login', compact('users'));
        }

        if ($user->role === 'pending' || $user->status === 'pending') {
            return view('request-role', compact('user'));
        }

        $activeRole = Session::get('active_role', $user->role);
        $notifications = DB::table('notifications')->orderBy('created_at', 'desc')->get();

        switch ($activeRole) {
            case 'admin':
                $totalPatients = DB::table('patients')->count();
                $totalDoctors = DB::table('users')->where('role', 'doctor')->where('status', 'approved')->count();
                $pendingRequests = DB::table('role_requests')
                    ->join('users', 'role_requests.user_id', '=', 'users.id')
                    ->select('role_requests.*', 'users.name as user_name', 'users.email as user_email', 'users.avatar as user_avatar')
                    ->where('role_requests.status', 'pending')
                    ->get();
                $doctors = DB::table('users')->where('role', 'doctor')->get();
                $totalIncome = DB::table('vouchers')->where('voucher_type', 'income')->sum('amount');
                $totalExpense = DB::table('vouchers')->where('voucher_type', 'expense')->sum('amount');
                return view('dashboards.admin', compact('user', 'activeRole', 'notifications', 'totalPatients', 'totalDoctors', 'pendingRequests', 'doctors', 'totalIncome', 'totalExpense'));

            case 'doctor':
                $patients = DB::table('patients')->get();
                $visits = DB::table('visits')
                    ->join('patients', 'visits.patient_id', '=', 'patients.id')
                    ->select('visits.*', 'patients.name as patient_name', 'patients.patient_code', 'patients.age')
                    ->get();
                $labTypes = DB::table('lab_test_types')->get();
                $medicines = DB::table('medicines')->get();
                return view('dashboards.doctor', compact('user', 'activeRole', 'notifications', 'patients', 'visits', 'labTypes', 'medicines'));

            case 'pharmacy':
                $prescriptions = DB::table('prescriptions')
                    ->join('patients', 'prescriptions.patient_id', '=', 'patients.id')
                    ->join('users as doctors', 'prescriptions.doctor_id', '=', 'doctors.id')
                    ->select('prescriptions.*', 'patients.name as patient_name', 'patients.patient_code', 'doctors.name as doctor_name')
                    ->get();
                foreach ($prescriptions as $p) {
                    $p->items = DB::table('prescription_items')
                        ->join('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
                        ->select('prescription_items.*', 'medicines.name as medicine_name')
                        ->where('prescription_id', $p->id)
                        ->get();
                }
                $medicines = DB::table('medicines')->get();
                return view('dashboards.pharmacy', compact('user', 'activeRole', 'notifications', 'prescriptions', 'medicines'));

            case 'lab_tech':
                $labRequests = DB::table('lab_requests')
                    ->join('patients', 'lab_requests.patient_id', '=', 'patients.id')
                    ->join('lab_test_types', 'lab_requests.test_type_id', '=', 'lab_test_types.id')
                    ->join('users as doctors', 'lab_requests.doctor_id', '=', 'doctors.id')
                    ->select('lab_requests.*', 'patients.name as patient_name', 'patients.patient_code', 'lab_test_types.name as test_name', 'lab_test_types.price as test_price', 'doctors.name as doctor_name')
                    ->get();
                $consumables = DB::table('inventory_items')->get();
                return view('dashboards.lab', compact('user', 'activeRole', 'notifications', 'labRequests', 'consumables'));

            case 'storekeeper':
                $items = DB::table('inventory_items')->get();
                $medicines = DB::table('medicines')->get();
                return view('dashboards.store', compact('user', 'activeRole', 'notifications', 'items', 'medicines'));

            case 'accountant':
                $vouchers = DB::table('vouchers')->orderBy('created_at', 'desc')->get();
                $accounts = DB::table('chart_of_accounts')->get();
                $journalEntries = DB::table('journal_entries')->orderBy('created_at', 'desc')->get();
                foreach ($journalEntries as $je) {
                    $je->items = DB::table('journal_entry_items')
                        ->join('chart_of_accounts', 'journal_entry_items.account_id', '=', 'chart_of_accounts.id')
                        ->select('journal_entry_items.*', 'chart_of_accounts.code as account_code', 'chart_of_accounts.name as account_name')
                        ->where('journal_entry_id', $je->id)
                        ->get();
                }
                return view('dashboards.accountant', compact('user', 'activeRole', 'notifications', 'vouchers', 'accounts', 'journalEntries'));

            case 'hr':
                $employees = DB::table('hr_employees')->get();
                $attendances = DB::table('hr_attendances')
                    ->join('hr_employees', 'hr_attendances.employee_id', '=', 'hr_employees.id')
                    ->select('hr_attendances.*', 'hr_employees.name as employee_name', 'hr_employees.job_title', 'hr_employees.fingerprint_id')
                    ->get();
                $rosters = DB::table('hr_rosters')
                    ->join('hr_employees', 'hr_rosters.employee_id', '=', 'hr_employees.id')
                    ->select('hr_rosters.*', 'hr_employees.name as employee_name', 'hr_employees.job_title')
                    ->get();
                return view('dashboards.hr', compact('user', 'activeRole', 'notifications', 'employees', 'attendances', 'rosters'));

            default:
                return redirect()->route('web.login');
        }
    }

    /**
     * Switch User Session
     */
    public function loginAs(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if ($user) {
            Session::put('user', $user);
            Session::put('active_role', $user->role);
        }
        return redirect()->route('web.index');
    }

    /**
     * Switch Active Role (Admin can switch to any dashboard)
     */
    public function switchRole(Request $request, $role)
    {
        Session::put('active_role', $role);
        return redirect()->route('web.index');
    }

    /**
     * Submit role request from Blade
     */
    public function submitRoleRequest(Request $request)
    {
        $user = Session::get('user');
        if (!$user) {
            return redirect()->route('web.login');
        }

        $request->validate([
            'requested_role'      => 'required|string',
            'requested_specialty' => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $existing = DB::table('role_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            DB::table('role_requests')->where('id', $existing->id)->update([
                'requested_role'      => $request->requested_role,
                'requested_specialty' => $request->requested_specialty,
                'notes'               => $request->notes,
                'updated_at'          => now(),
            ]);
        } else {
            DB::table('role_requests')->insert([
                'user_id'             => $user->id,
                'requested_role'      => $request->requested_role,
                'requested_specialty' => $request->requested_specialty,
                'notes'               => $request->notes,
                'status'              => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        DB::table('notifications')->insert([
            'user_id'     => null,
            'target_role' => 'admin',
            'title'       => 'طلب انضمام جديد',
            'message'     => 'قدم ' . $user->name . ' (' . $user->email . ') طلب فتح داشبورد ' . $request->requested_role,
            'type'        => 'role_request',
            'is_read'     => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('web.index')->with('success', 'تم تقديم طلبك بنجاح وهو قيد مراجعة الأدمن');
    }

    /**
     * Approve role request from Blade
     */
    public function approveRoleRequest(Request $request, $id)
    {
        $roleRequest = DB::table('role_requests')->where('id', $id)->first();
        if ($roleRequest) {
            DB::table('role_requests')->where('id', $id)->update([
                'status'     => 'approved',
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $roleRequest->user_id)->update([
                'role'       => $roleRequest->requested_role,
                'specialty'  => $roleRequest->requested_specialty,
                'status'     => 'approved',
                'updated_at' => now(),
            ]);

            DB::table('notifications')->insert([
                'user_id'    => $roleRequest->user_id,
                'title'      => 'تمت الموافقة على حسابك!',
                'message'    => 'لقد وافق الأدمن على طلبك. يمكنك الآن استخدام حسابك.',
                'type'       => 'role_approved',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('web.index')->with('success', 'تمت الموافقة على الحساب وتفعيله بنجاح');
    }

    /**
     * Reject role request from Blade
     */
    public function rejectRoleRequest(Request $request, $id)
    {
        $roleRequest = DB::table('role_requests')->where('id', $id)->first();
        if ($roleRequest) {
            DB::table('role_requests')->where('id', $id)->update([
                'status'     => 'rejected',
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $roleRequest->user_id)->update([
                'status'     => 'rejected',
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('web.index')->with('success', 'تم رفض الطلب');
    }

    /**
     * Logout Session
     */
    public function logout()
    {
        Session::forget('user');
        Session::forget('active_role');
        return redirect()->route('web.index');
    }
}
