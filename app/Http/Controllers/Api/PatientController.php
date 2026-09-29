<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * السجل الطبي المشترك للمرضى — متاح لكل الأطباء والمختبر والصيدلية.
 */
class PatientController extends ApiController
{
    /**
     * Search patients by name, code or phone (empty query returns latest patients)
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $patients = DB::table('patients')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                        ->orWhere('patient_code', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return $this->success($patients, 'نتائج البحث في سجل المرضى');
    }

    /**
     * Register a new patient permanently in the database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'age'             => 'required|integer|min:0|max:150',
            'gender'          => 'nullable|string',
            'phone'           => 'nullable|string|max:50',
            'medical_history' => 'nullable|string',
        ]);

        $patient = self::createPatient($request->only(['name', 'age', 'gender', 'phone', 'medical_history']));

        return $this->success($patient, 'تم تسجيل المريض في قاعدة البيانات بنجاح.', 201);
    }

    /**
     * Full shared history of a patient (visits, lab results, prescriptions)
     */
    public function history($patientId)
    {
        $patient = DB::table('patients')->where('id', $patientId)->first();
        if (!$patient) {
            return $this->error('المريض غير موجود', 404);
        }

        $visits = DB::table('visits')
            ->leftJoin('users', 'visits.doctor_id', '=', 'users.id')
            ->select('visits.*', 'users.name as doctor_name', 'users.specialty as doctor_specialty')
            ->where('visits.patient_id', $patientId)
            ->orderBy('visits.created_at', 'desc')
            ->get();

        $labResults = DB::table('lab_requests')
            ->leftJoin('lab_test_types', 'lab_requests.test_type_id', '=', 'lab_test_types.id')
            ->leftJoin('users as docs', 'lab_requests.doctor_id', '=', 'docs.id')
            ->select(
                'lab_requests.*',
                DB::raw('COALESCE(lab_test_types.name, lab_requests.test_name) as test_name'),
                'lab_test_types.category as test_category',
                'docs.name as doctor_name'
            )
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
                ->leftJoin('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
                ->select('prescription_items.*', DB::raw('COALESCE(medicines.name, prescription_items.medicine_name) as medicine_name'))
                ->where('prescription_id', $p->id)
                ->get();
        }

        return $this->success([
            'patient'       => $patient,
            'visits'        => $visits,
            'lab_results'   => $labResults,
            'prescriptions' => $prescriptions,
        ]);
    }

    /**
     * Insert a patient with an auto-generated code (PAT-1001, PAT-1002, ...)
     */
    public static function createPatient(array $data): object
    {
        $lastId = (int) DB::table('patients')->max('id');
        $code = 'PAT-' . (1001 + $lastId);
        while (DB::table('patients')->where('patient_code', $code)->exists()) {
            $code = 'PAT-' . (++$lastId + 1001);
        }

        $id = DB::table('patients')->insertGetId([
            'patient_code'    => $code,
            'name'            => $data['name'],
            'age'             => $data['age'],
            'gender'          => $data['gender'] ?? 'male',
            'phone'           => $data['phone'] ?? null,
            'medical_history' => $data['medical_history'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return DB::table('patients')->where('id', $id)->first();
    }
}
