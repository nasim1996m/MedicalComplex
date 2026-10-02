<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabController extends ApiController
{
    /**
     * Get Lab & Radiology dashboard data
     */
    public function dashboard()
    {
        $requests = DB::table('lab_requests')
            ->join('patients', 'lab_requests.patient_id', '=', 'patients.id')
            ->join('lab_test_types', 'lab_requests.test_type_id', '=', 'lab_test_types.id')
            ->join('users as doctors', 'lab_requests.doctor_id', '=', 'doctors.id')
            ->select('lab_requests.*', 'patients.name as patient_name', 'patients.patient_code', 'patients.age', 'lab_test_types.name as test_name', 'lab_test_types.category as test_category', 'lab_test_types.price as test_price', 'doctors.name as doctor_name')
            ->orderBy('lab_requests.created_at', 'desc')
            ->get();

        $consumables = DB::table('inventory_items')
            ->where('category', 'lab_supplies')
            ->get();

        return $this->success([
            'lab_requests' => $requests,
            'consumables' => $consumables,
        ]);
    }

    /**
     * Complete lab test request & upload results
     */
    public function completeTest(Request $request, $id)
    {
        $request->validate([
            'result_summary' => 'required|string|max:10000',
            // Only real web links: a "javascript:" URL here would run in whoever opens the report.
            'report_file_url' => 'nullable|url:https,http|max:2048',
        ]);
        $performedBy = $request->user()->id;

        $labReq = DB::table('lab_requests')->where('id', $id)->first();
        if (!$labReq) {
            return $this->error('طلب الفحص غير موجود', 404);
        }

        $testType = DB::table('lab_test_types')->where('id', $labReq->test_type_id)->first();

        return DB::transaction(function () use ($request, $id, $testType, $performedBy) {
        // Only a pending request can be completed, so the income is recorded once.
        $updated = DB::table('lab_requests')->where('id', $id)->where('status', 'pending')->update([
            'status' => 'completed',
            'result_summary' => $request->result_summary,
            'report_file_url' => $request->report_file_url,
            'performed_by' => $performedBy,
            'updated_at' => now(),
        ]);
        if (!$updated) {
            return $this->error('تم إنجاز هذا الفحص مسبقاً', 409);
        }

        // Record income voucher
        DB::table('vouchers')->insert([
            'voucher_type' => 'income',
            'category' => 'lab_income',
            'amount' => $testType->price ?? 15000,
            'description' => 'إيراد فحص مختبر/أشعة (' . ($testType->name ?? 'فحص') . ')',
            'created_by' => $performedBy,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(null, 'تم حفظ نتيجة الفحص بنجاح وإتاحتها للطبيب والصيدلية.');
        });
    }
}
