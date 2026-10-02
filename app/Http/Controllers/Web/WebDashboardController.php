<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\Accounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WebDashboardController extends Controller
{
    /**
     * Show Main Page / Login / Active Dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isApproved()) {
            return view('request-role', compact('user'));
        }

        // Only the admin may view other departments' dashboards.
        $activeRole = $user->isAdmin() ? $request->session()->get('active_role', 'admin') : $user->dashboardKey();
        $notifications = DB::table('notifications')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('target_role', $user->role);
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        switch ($activeRole) {
            case 'admin':
                $totalPatients = DB::table('patients')->count();
                $totalDoctors = DB::table('users')->where('role', 'doctor')->where('status', 'approved')->count();
                $pendingRequests = DB::table('role_requests')
                    ->join('users', 'role_requests.user_id', '=', 'users.id')
                    ->select('role_requests.*', 'users.name as user_name', 'users.email as user_email', 'users.avatar as user_avatar')
                    ->where('role_requests.status', 'pending')
                    ->get();
                $doctors = DB::table('users')->where('role', 'doctor')->select('id', 'name', 'email', 'avatar', 'specialty', 'status')->get();
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
                abort(403);
        }
    }

    /**
     * Switch the dashboard the admin is viewing.
     */
    public function switchRole(Request $request, $role)
    {
        if (!in_array($role, User::DASHBOARDS, true)) {
            abort(404);
        }
        $request->session()->put('active_role', $role);

        return redirect()->route('web.index');
    }

    /**
     * Submit role request from Blade
     */
    public function submitRoleRequest(Request $request)
    {
        $user = $request->user();
        if ($user->isApproved()) {
            return redirect()->route('web.index');
        }

        $data = $request->validate([
            'requested_role'      => ['required', Rule::in(User::REQUESTABLE_ROLES)],
            'requested_specialty' => 'nullable|string|max:150',
            'notes'               => 'nullable|string|max:1000',
        ]);

        Accounts::submitRoleRequest($user, $data['requested_role'], $data['requested_specialty'] ?? null, $data['notes'] ?? null);

        return redirect()->route('web.index')->with('success', 'تم تقديم طلبك بنجاح وهو قيد مراجعة الأدمن');
    }

    public function approveRoleRequest(Request $request, $id)
    {
        $ok = Accounts::review((int) $id, $request->user(), true);

        return redirect()->route('web.index')->with('success', $ok ? 'تمت الموافقة على الحساب وتفعيله بنجاح' : 'الطلب غير موجود أو تمت مراجعته مسبقاً');
    }

    public function rejectRoleRequest(Request $request, $id)
    {
        $ok = Accounts::review((int) $id, $request->user(), false);

        return redirect()->route('web.index')->with('success', $ok ? 'تم رفض الطلب' : 'الطلب غير موجود أو تمت مراجعته مسبقاً');
    }
}
