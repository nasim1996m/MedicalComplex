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
            'performed_by' => 'required|exists:users,id',
            'result_summary' => 'required|string',
            'report_file_url' => 'nullable|string',
        ]);

        $labReq = DB::table('lab_requests')->where('id', $id)->first();
        if (!$labReq) {
            return $this->error('طلب الفحص غير موجود', 404);
        }

        $testType = DB::table('lab_test_types')->where('id', $labReq->test_type_id)->first();

        DB::table('lab_requests')->where('id', $id)->update([
            'status' => 'completed',
            'result_summary' => $request->result_summary,
            'report_file_url' => $request->report_file_url ?? '/reports/sample_result.pdf',
            'performed_by' => $request->performed_by,
            'updated_at' => now(),
        ]);

        // Record income voucher
        DB::table('vouchers')->insert([
            'voucher_type' => 'income',
            'category' => 'lab_income',
            'amount' => $testType->price ?? 15000,
            'description' => 'إيراد فحص مختبر/أشعة (' . ($testType->name ?? 'فحص') . ')',
            'created_by' => $request->performed_by,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(null, 'تم حفظ نتيجة الفحص بنجاح وإتاحتها للطبيب والصيدلية.');
    }
}
