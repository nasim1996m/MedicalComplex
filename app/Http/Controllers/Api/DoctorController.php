<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends ApiController
{
    /**
     * Get doctor dashboard data (patients queue, recent visits, active requests)
     */
    public function dashboard(Request $request)
    {
        $doctorId = $request->query('doctor_id');

        $todayVisits = DB::table('visits')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->select('visits.*', 'patients.name as patient_name', 'patients.patient_code', 'patients.age', 'patients.gender', 'patients.phone', 'patients.medical_history')
            ->when($doctorId, function ($q) use ($doctorId) {
                return $q->where('visits.doctor_id', $doctorId);
            })
            ->orderBy('visits.created_at', 'desc')
            ->get();

        $allPatients = DB::table('patients')->get();

        $labTypes = DB::table('lab_test_types')->get();
        $medicines = DB::table('medicines')->where('quantity', '>', 0)->get();

        return $this->success([
            'today_visits' => $todayVisits,
            'all_patients' => $allPatients,
            'lab_types' => $labTypes,
            'medicines' => $medicines,
        ]);
    }

    /**
     * Create new visit/consultation & diagnosis
     */
    public function createVisit(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:patients,id',
            'diagnosis' => 'required|string',
            'notes' => 'nullable|string',
            'fee' => 'nullable|numeric',
        ]);

        $visitId = DB::table('visits')->insertGetId([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'visit_date' => today(),
            'diagnosis' => $request->diagnosis,
            'notes' => $request->notes,
            'fee' => $request->fee ?? 25000,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Record income voucher for Accountant
        $doctor = DB::table('users')->where('id', $request->doctor_id)->first();
        DB::table('vouchers')->insert([
            'voucher_type' => 'income',
            'category' => 'doctor_income',
            'amount' => $request->fee ?? 25000,
            'description' => 'إيراد كشفية مريض من الطبيب: ' . ($doctor->name ?? 'طبيب'),
            'created_by' => $request->doctor_id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['visit_id' => $visitId], 'تم تسجيل الكشفية والتشخيص بنجاح.');
    }

    /**
     * Order lab test or scan (Blood, X-Ray, Echo, ECG)
     */
    public function requestLabTest(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'test_type_id' => 'required|exists:lab_test_types,id',
        ]);

        $requestId = DB::table('lab_requests')->insertGetId([
            'visit_id' => $request->visit_id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'test_type_id' => $request->test_type_id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send alert notification to Lab Techs
        $test = DB::table('lab_test_types')->where('id', $request->test_type_id)->first();
        DB::table('notifications')->insert([
            'target_role' => 'lab_tech',
            'title' => 'طلب فحص جديد',
            'message' => 'تم طلب فحص ' . ($test->name ?? 'فحص') . ' لمريض من قبل الطبيب.',
            'type' => 'lab_request',
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['lab_request_id' => $requestId], 'تم إرسال طلب الفحص إلى قسم المختبر والأشعة بنجاح.');
    }

    /**
     * Send prescription to Pharmacy
     */
    public function sendPrescription(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'doctor_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:patients,id',
            'items' => 'required|array',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.dosage' => 'required|string',
            'items.*.duration' => 'required|string',
        ]);

        $prescriptionId = DB::table('prescriptions')->insertGetId([
            'visit_id' => $request->visit_id,
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table('prescription_items')->insert([
                'prescription_id' => $prescriptionId,
                'medicine_id' => $item['medicine_id'],
                'dosage' => $item['dosage'],
                'duration' => $item['duration'],
                'notes' => $item['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Alert notification to Pharmacy
        DB::table('notifications')->insert([
            'target_role' => 'pharmacist',
            'title' => 'وصفة طبية جديدة واردة',
            'message' => 'تم إرسال وصفة طبية جديدة للصيدلية لتجهيز العلاج للمريض.',
            'type' => 'prescription',
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['prescription_id' => $prescriptionId], 'تم إرسال الوصفة الطبية إلى الصيدلية بنجاح.');
    }

    /**
     * Search shared patient history across all doctors
     */
    public function patientHistory($patientId)
    {
        $patient = DB::table('patients')->where('id', $patientId)->first();
        if (!$patient) {
            return $this->error('المريض غير موجود', 404);
        }

        $visits = DB::table('visits')
            ->join('users', 'visits.doctor_id', '=', 'users.id')
            ->select('visits.*', 'users.name as doctor_name', 'users.specialty as doctor_specialty')
            ->where('visits.patient_id', $patientId)
            ->orderBy('visits.created_at', 'desc')
            ->get();

        $labResults = DB::table('lab_requests')
            ->join('lab_test_types', 'lab_requests.test_type_id', '=', 'lab_test_types.id')
            ->leftJoin('users as docs', 'lab_requests.doctor_id', '=', 'docs.id')
            ->select('lab_requests.*', 'lab_test_types.name as test_name', 'lab_test_types.category as test_category', 'docs.name as doctor_name')
            ->where('lab_requests.patient_id', $patientId)
            ->orderBy('lab_requests.created_at', 'desc')
            ->get();

        $prescriptions = DB::table('prescriptions')
            ->leftJoin('users as docs', 'prescriptions.doctor_id', '=', 'docs.id')
            ->select('prescriptions.*', 'docs.name as doctor_name')
            ->where('prescriptions.patient_id', $patientId)
            ->orderBy('prescriptions.created_at', 'desc')
            ->get();

        foreach ($prescriptions as $p) {
            $p->items = DB::table('prescription_items')
                ->join('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
                ->select('prescription_items.*', 'medicines.name as medicine_name')
                ->where('prescription_id', $p->id)
                ->get();
        }

        return $this->success([
            'patient' => $patient,
            'visits' => $visits,
            'lab_results' => $labResults,
            'prescriptions' => $prescriptions,
        ]);
    }
}
