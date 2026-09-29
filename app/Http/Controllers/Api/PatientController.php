<?php

namespace App\Http\Controllers\Api;

use App\Support\ArabicText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * السجل الطبي المشترك للمرضى — متاح لكل الأطباء والمختبر والصيدلية.
 */
class PatientController extends ApiController
{
    /**
     * Search patients by name, code or phone, optionally only those who visited
     * between from/to (YYYY-MM-DD). Empty query returns the latest patients.
     */
    public function index(Request $request)
    {
        $request->validate([
            'q'    => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->query('from');
        $to   = $request->query('to');

        // عدد الزيارات وتاريخ آخر زيارة (ضمن الفترة إن وُجدت)
        $visitStats = DB::table('visits')
            ->select('patient_id', DB::raw('COUNT(*) as visit_count'), DB::raw('MAX(visit_date) as last_visit_date'))
            ->when($from, fn ($q) => $q->whereDate('visit_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('visit_date', '<=', $to))
            ->groupBy('patient_id');

        $query = DB::table('patients')
            ->select('patients.*', DB::raw('COALESCE(vs.visit_count, 0) as visit_count'), 'vs.last_visit_date');

        // عند تحديد فترة: فقط المرضى الذين راجعوا خلالها
        if ($from || $to) {
            $query->joinSub($visitStats, 'vs', 'vs.patient_id', '=', 'patients.id');
        } else {
            $query->leftJoinSub($visitStats, 'vs', 'vs.patient_id', '=', 'patients.id');
        }

        $this->applySearch($query, (string) $request->query('q', ''));

        $patients = $query
            ->orderByDesc(DB::raw('COALESCE(vs.last_visit_date, patients.updated_at)'))
            ->orderByDesc('patients.id')
            ->limit(200)
            ->get()
            ->map(function ($patient) {
                unset($patient->search_name);
                return $patient;
            });

        return $this->success($patients, 'نتائج البحث في سجل المرضى');
    }

    /**
     * Visits (المراجعين) between two dates with patient & doctor names
     */
    public function visits(Request $request)
    {
        $request->validate([
            'q'         => 'nullable|string|max:255',
            'from'      => 'nullable|date',
            'to'        => 'nullable|date|after_or_equal:from',
            'doctor_id' => 'nullable|integer',
        ]);

        $from = $request->query('from');
        $to   = $request->query('to');

        $query = DB::table('visits')
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->leftJoin('users', 'visits.doctor_id', '=', 'users.id')
            ->select(
                'visits.*',
                'patients.name as patient_name',
                'patients.patient_code',
                'patients.age',
                'patients.gender',
                'patients.phone',
                'users.name as doctor_name',
                'users.specialty as doctor_specialty'
            )
            ->when($from, fn ($q) => $q->whereDate('visits.visit_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('visits.visit_date', '<=', $to))
            ->when($request->query('doctor_id'), fn ($q, $doctorId) => $q->where('visits.doctor_id', $doctorId));

        $this->applySearch($query, (string) $request->query('q', ''));

        $visits = $query
            ->orderByDesc('visits.visit_date')
            ->orderByDesc('visits.id')
            ->limit(500)
            ->get();

        return $this->success([
            'from'           => $from,
            'to'             => $to,
            'total_visits'   => $visits->count(),
            'total_patients' => $visits->pluck('patient_id')->unique()->count(),
            'visits'         => $visits,
        ], 'قائمة المراجعين خلال الفترة');
    }

    /**
     * Every word of the query must match the (normalized) name, or the whole
     * query must match the patient code or phone.
     */
    private function applySearch($query, string $q): void
    {
        $q = trim($q);
        if ($q === '') {
            return;
        }

        $words = array_filter(explode(' ', ArabicText::normalize($q)));

        $query->where(function ($w) use ($q, $words) {
            $w->where(function ($nameQuery) use ($words) {
                foreach ($words as $word) {
                    $nameQuery->where('patients.search_name', 'like', '%' . $word . '%');
                }
            })
                ->orWhere('patients.patient_code', 'like', '%' . $q . '%')
                ->orWhere('patients.phone', 'like', '%' . $q . '%');
        });
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
            'search_name'     => ArabicText::normalize($data['name']),
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
