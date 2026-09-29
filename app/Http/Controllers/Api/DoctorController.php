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
     * Order lab test or scan (Blood, X-Ray, Echo, ECG) — by type id or free-text name
     */
    public function requestLabTest(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'test_type_id' => 'nullable|required_without:test_name|exists:lab_test_types,id',
            'test_name' => 'nullable|required_without:test_type_id|string|max:255',
        ]);

        $requestId = $this->insertLabRequest(
            $request->visit_id,
            $request->patient_id,
            $request->doctor_id,
            $request->only(['test_type_id', 'test_name'])
        );

        return $this->success(['lab_request_id' => $requestId], 'تم إرسال طلب الفحص إلى قسم المختبر والأشعة بنجاح.');
    }

    /**
     * Send prescription to Pharmacy — items by medicine id or free-text name
     */
    public function sendPrescription(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'doctor_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:patients,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'nullable|required_without:items.*.medicine_name|exists:medicines,id',
            'items.*.medicine_name' => 'nullable|required_without:items.*.medicine_id|string|max:255',
            'items.*.dosage' => 'required|string',
            'items.*.duration' => 'required|string',
        ]);

        $prescriptionId = $this->insertPrescription(
            $request->visit_id,
            $request->doctor_id,
            $request->patient_id,
            $request->items
        );

        return $this->success(['prescription_id' => $prescriptionId], 'تم إرسال الوصفة الطبية إلى الصيدلية بنجاح.');
    }

    /**
     * Save a full consultation in one request: patient (existing or new), diagnosis,
     * lab requests and prescription. Everything is stored permanently so the patient
     * can be found later by any doctor, the lab or the pharmacy.
     */
    public function saveConsultation(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'patient_id' => 'nullable|required_without:patient|exists:patients,id',
            'patient' => 'nullable|required_without:patient_id|array',
            'patient.name' => 'required_with:patient|string|max:255',
            'patient.age' => 'required_with:patient|integer|min:0|max:150',
            'patient.gender' => 'nullable|string',
            'patient.phone' => 'nullable|string|max:50',
            'patient.medical_history' => 'nullable|string',
            'visit_id' => 'nullable|exists:visits,id',
            'diagnosis' => 'required|string',
            'notes' => 'nullable|string',
            'fee' => 'nullable|numeric',
            'lab_requests' => 'nullable|array',
            'lab_requests.*.test_type_id' => 'nullable|required_without:lab_requests.*.test_name|exists:lab_test_types,id',
            'lab_requests.*.test_name' => 'nullable|required_without:lab_requests.*.test_type_id|string|max:255',
            'prescription_items' => 'nullable|array',
            'prescription_items.*.medicine_id' => 'nullable|required_without:prescription_items.*.medicine_name|exists:medicines,id',
            'prescription_items.*.medicine_name' => 'nullable|required_without:prescription_items.*.medicine_id|string|max:255',
            'prescription_items.*.dosage' => 'required|string',
            'prescription_items.*.duration' => 'required|string',
        ]);

        $result = DB::transaction(function () use ($request) {
            $patient = $request->filled('patient_id')
                ? DB::table('patients')->where('id', $request->patient_id)->first()
                : PatientController::createPatient($request->input('patient'));

            // Reuse the queued visit (e.g. "in_consultation") if the frontend sends it
            $visit = $request->filled('visit_id')
                ? DB::table('visits')->where('id', $request->visit_id)->where('patient_id', $patient->id)->first()
                : null;

            if ($visit) {
                DB::table('visits')->where('id', $visit->id)->update([
                    'doctor_id' => $request->doctor_id,
                    'diagnosis' => $request->diagnosis,
                    'notes' => $request->notes,
                    'fee' => $request->fee ?? $visit->fee,
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
                $visitId = $visit->id;
            } else {
                $visitId = DB::table('visits')->insertGetId([
                    'patient_id' => $patient->id,
                    'doctor_id' => $request->doctor_id,
                    'visit_date' => today(),
                    'diagnosis' => $request->diagnosis,
                    'notes' => $request->notes,
                    'fee' => $request->fee ?? 25000,
                    'status' => 'completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

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
            }

            $labRequestIds = [];
            foreach ($request->input('lab_requests', []) as $lab) {
                $labRequestIds[] = $this->insertLabRequest($visitId, $patient->id, $request->doctor_id, $lab);
            }

            $prescriptionId = null;
            if (!empty($request->input('prescription_items'))) {
                $prescriptionId = $this->insertPrescription($visitId, $request->doctor_id, $patient->id, $request->input('prescription_items'));
            }

            DB::table('patients')->where('id', $patient->id)->update(['updated_at' => now()]);

            return [
                'patient' => DB::table('patients')->where('id', $patient->id)->first(),
                'visit_id' => $visitId,
                'lab_request_ids' => $labRequestIds,
                'prescription_id' => $prescriptionId,
            ];
        });

        return $this->success($result, 'تم حفظ الكشفية والتحاليل والوصفة في السجل الطبي للمريض بنجاح.', 201);
    }

    /**
     * Search shared patient history across all doctors
     */
    public function patientHistory($patientId)
    {
        return app(PatientController::class)->history($patientId);
    }

    private function insertLabRequest($visitId, $patientId, $doctorId, array $lab): int
    {
        $test = !empty($lab['test_type_id'])
            ? DB::table('lab_test_types')->where('id', $lab['test_type_id'])->first()
            : null;

        $requestId = DB::table('lab_requests')->insertGetId([
            'visit_id' => $visitId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'test_type_id' => $test->id ?? null,
            'test_name' => $test->name ?? $lab['test_name'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send alert notification to Lab Techs
        DB::table('notifications')->insert([
            'target_role' => 'lab_tech',
            'title' => 'طلب فحص جديد',
            'message' => 'تم طلب فحص ' . ($test->name ?? $lab['test_name'] ?? 'فحص') . ' لمريض من قبل الطبيب.',
            'type' => 'lab_request',
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $requestId;
    }

    private function insertPrescription($visitId, $doctorId, $patientId, array $items): int
    {
        $prescriptionId = DB::table('prescriptions')->insertGetId([
            'visit_id' => $visitId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            $medicine = !empty($item['medicine_id'])
                ? DB::table('medicines')->where('id', $item['medicine_id'])->first()
                : null;

            DB::table('prescription_items')->insert([
                'prescription_id' => $prescriptionId,
                'medicine_id' => $medicine->id ?? null,
                'medicine_name' => $medicine->name ?? $item['medicine_name'],
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

        return $prescriptionId;
    }
}
