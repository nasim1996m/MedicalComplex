<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountantController extends ApiController
{
    /**
     * Get accounting dashboard data & journal vouchers
     */
    public function dashboard()
    {
        $vouchers = DB::table('vouchers')
            ->join('users', 'vouchers.created_by', '=', 'users.id')
            ->select('vouchers.*', 'users.name as creator_name', 'users.role as creator_role')
            ->orderBy('vouchers.created_at', 'desc')
            ->get();

        $totalIncome = DB::table('vouchers')->where('voucher_type', 'income')->sum('amount');
        $totalExpense = DB::table('vouchers')->where('voucher_type', 'expense')->sum('amount');

        $incomeByDoctor = DB::table('vouchers')->where('category', 'doctor_income')->sum('amount');
        $incomeByPharmacy = DB::table('vouchers')->where('category', 'pharmacy_income')->sum('amount');
        $incomeByLab = DB::table('vouchers')->where('category', 'lab_income')->sum('amount');

        return $this->success([
            'vouchers' => $vouchers,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $totalIncome - $totalExpense,
                'doctor_income' => $incomeByDoctor,
                'pharmacy_income' => $incomeByPharmacy,
                'lab_income' => $incomeByLab,
            ],
        ]);
    }

    /**
     * Add new financial voucher/expense entry (قيد محاسبي)
     */
    public function addVoucher(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:income,expense',
            'category' => 'required|string|max:50', // water, electricity, telecom, cleaning, hospitality, salary, inventory_purchase, stationary, contracts
            'amount' => 'required|numeric|min:0.01|max:1000000000',
            'description' => 'required|string|max:1000',
        ]);

        $voucherId = DB::table('vouchers')->insertGetId([
            'voucher_type' => $request->voucher_type,
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description,
            'created_by' => $request->user()->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['voucher_id' => $voucherId], 'تم تسجيل القيد المحاسبي بنجاح.');
    }

    

}
