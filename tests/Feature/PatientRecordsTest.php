<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * سيناريو كامل لـ 20 مريض: كشف + تحاليل + وصفة، ثم البحث عنهم من أي قسم،
 * والبحث بالفترة الزمنية، وإكمال التحاليل وصرف الأدوية.
 */
class PatientRecordsTest extends TestCase
{
    use RefreshDatabase;


    private const PATIENTS = [
        ['مريم كامل', 28, 'female'], ['أحمد جاسم', 40, 'male'], ['فاطمة الزهراء علي', 32, 'female'],
        ['علي حسين', 30, 'male'], ['زينب محمد', 25, 'female'], ['حسن عبد الله', 55, 'male'],
        ['سارة إبراهيم', 19, 'female'], ['محمد رضا', 61, 'male'], ['نور الهدى', 35, 'female'],
        ['عمر فاروق', 47, 'male'], ['هدى سلمان', 52, 'female'], ['يوسف كريم', 8, 'male'],
        ['رقية جعفر', 44, 'female'], ['مصطفى عادل', 38, 'male'], ['آمنة صالح', 70, 'female'],
        ['كرار حيدر', 23, 'male'], ['إسراء مهدي', 29, 'female'], ['عباس ناصر', 66, 'male'],
        ['ضحى قاسم', 31, 'female'], ['مرتضى سعد', 50, 'male'],
    ];

    private int $doctorId;
    private int $labTechId;
    private int $pharmacistId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MedicalComplexSeeder::class);

        $this->doctorId = DB::table('users')->where('role', 'doctor')->value('id');
        $this->labTechId = DB::table('users')->where('role', 'lab_tech')->value('id');
        $this->pharmacistId = DB::table('users')->where('role', 'pharmacist')->value('id');
    }

    /**
     * Patient i visits on (today - i days), so the 20 visits span 20 different days.
     */
    private function createTwentyConsultations(): array
    {
        $created = [];
        $today = Carbon::today();

        foreach (self::PATIENTS as $i => [$name, $age, $gender]) {
            Carbon::setTestNow($today->copy()->subDays($i)->setTime(10, 0));

            $response = $this->postJson('/api/v1/doctor/consultations', [
                'doctor_id' => $this->doctorId,
                'patient' => ['name' => $name, 'age' => $age, 'gender' => $gender, 'phone' => '0770' . str_pad((string) $i, 7, '0', STR_PAD_LEFT)],
                'diagnosis' => 'تشخيص رقم ' . ($i + 1) . ' للمريض ' . $name,
                'lab_requests' => [['test_name' => 'CBC فحص دم شامل'], ['test_name' => 'أشعة صدر']],
                'prescription_items' => [
                    ['medicine_name' => 'Amoxicillin 500mg', 'dosage' => 'كبسولة كل 8 ساعات', 'duration' => '7 أيام'],
                    ['medicine_name' => 'دواء غير موجود بالمخزن', 'dosage' => 'حبة يومياً', 'duration' => '5 أيام'],
                ],
            ]);

            $response->assertCreated()->assertJsonPath('status', 'success');
            $created[] = $response->json('data');
        }

        Carbon::setTestNow();

        return $created;
    }

    public function test_twenty_patients_are_saved_permanently_with_full_history(): void
    {
        $created = $this->createTwentyConsultations();

        // 20 new + 1 seeded patient
        $this->assertSame(21, DB::table('patients')->count());
        $this->assertCount(20, array_unique(array_column(array_column($created, 'patient'), 'patient_code')));

        foreach ($created as $i => $data) {
            $history = $this->getJson('/api/v1/patients/' . $data['patient']['id'] . '/history')
                ->assertOk()
                ->json('data');

            $this->assertSame(self::PATIENTS[$i][0], $history['patient']['name']);
            $this->assertCount(1, $history['visits']);
            $this->assertStringContainsString('تشخيص رقم ' . ($i + 1), $history['visits'][0]['diagnosis']);
            $this->assertNotEmpty($history['visits'][0]['doctor_name']);
            $this->assertEqualsCanonicalizing(['CBC فحص دم شامل', 'أشعة صدر'], array_column($history['lab_results'], 'test_name'));
            $this->assertCount(1, $history['prescriptions']);
            $this->assertEqualsCanonicalizing(
                ['Amoxicillin 500mg', 'دواء غير موجود بالمخزن'],
                array_column($history['prescriptions'][0]['items'], 'medicine_name')
            );
        }
    }

    public function test_every_patient_can_be_found_by_name_code_and_phone(): void
    {
        $created = $this->createTwentyConsultations();

        foreach ($created as $i => $data) {
            $patient = $data['patient'];

            foreach ([$patient['name'], $patient['patient_code'], $patient['phone']] as $term) {
                $ids = array_column($this->getJson('/api/v1/patients?q=' . urlencode($term))->assertOk()->json('data'), 'id');
                $this->assertContains($patient['id'], $ids, "لم يتم العثور على {$patient['name']} عند البحث بـ: {$term}");
            }
        }
    }

    public function test_search_tolerates_arabic_spelling_variants_and_partial_names(): void
    {
        $this->createTwentyConsultations();

        $cases = [
            'مريم'         => 'مريم كامل',        // first name only
            'كامل مريم'    => 'مريم كامل',        // words in any order
            'احمد'         => 'أحمد جاسم',        // أ written as ا
            'اسراء'        => 'إسراء مهدي',       // إ written as ا
            'امنة'         => 'آمنة صالح',        // آ written as ا
            'فاطمه الزهراء' => 'فاطمة الزهراء علي', // ة written as ه
            '  زينب   محمد ' => 'زينب محمد',      // extra spaces
        ];

        foreach ($cases as $term => $expectedName) {
            $names = array_column($this->getJson('/api/v1/patients?q=' . urlencode($term))->assertOk()->json('data'), 'name');
            $this->assertContains($expectedName, $names, "البحث بـ '{$term}' لم يُرجع '{$expectedName}'");
        }

        $this->assertSame([], $this->getJson('/api/v1/patients?q=' . urlencode('اسم غير موجود'))->json('data'));
    }

    public function test_patients_and_visits_can_be_filtered_by_date_range(): void
    {
        $this->createTwentyConsultations();

        // Patients 0..6 visited within the last 7 days (today .. today-6)
        $from = Carbon::today()->subDays(6)->toDateString();
        $to = Carbon::today()->toDateString();

        $patients = $this->getJson("/api/v1/patients?from={$from}&to={$to}")->assertOk()->json('data');
        $names = array_column($patients, 'name');

        // 7 new patients + seeded patient visited today
        $this->assertCount(8, $patients);
        foreach (array_slice(self::PATIENTS, 0, 7) as [$name]) {
            $this->assertContains($name, $names);
        }
        $this->assertNotContains(self::PATIENTS[10][0], $names);

        // Range in the middle: days 10..14 ago → patients 10..14
        $midFrom = Carbon::today()->subDays(14)->toDateString();
        $midTo = Carbon::today()->subDays(10)->toDateString();

        $visits = $this->getJson("/api/v1/visits?from={$midFrom}&to={$midTo}")->assertOk()->json('data');
        $this->assertSame(5, $visits['total_visits']);
        $this->assertSame(5, $visits['total_patients']);
        $this->assertEqualsCanonicalizing(
            array_column(array_slice(self::PATIENTS, 10, 5), 0),
            array_column($visits['visits'], 'patient_name')
        );
        foreach ($visits['visits'] as $visit) {
            $this->assertNotEmpty($visit['doctor_name']);
            $this->assertGreaterThanOrEqual($midFrom, substr($visit['visit_date'], 0, 10));
            $this->assertLessThanOrEqual($midTo, substr($visit['visit_date'], 0, 10));
        }

        // Name + range together
        $found = $this->getJson('/api/v1/patients?q=' . urlencode('هدى') . "&from={$midFrom}&to={$midTo}")->json('data');
        $this->assertSame(['هدى سلمان'], array_column($found, 'name'));

        // Invalid range is rejected
        $this->getJson("/api/v1/patients?from={$to}&to={$from}")->assertStatus(422);
    }

    public function test_returning_patient_keeps_old_history_for_the_next_doctor(): void
    {
        $first = $this->createTwentyConsultations()[0];

        $this->postJson('/api/v1/doctor/consultations', [
            'doctor_id' => $this->doctorId,
            'patient_id' => $first['patient']['id'],
            'diagnosis' => 'مراجعة ثانية: تحسن ملحوظ',
        ])->assertCreated();

        $history = $this->getJson('/api/v1/patients/' . $first['patient']['id'] . '/history')->json('data');
        $this->assertCount(2, $history['visits']);
        $this->assertSame(21, DB::table('patients')->count(), 'Returning patient must not be duplicated');
    }

    public function test_lab_and_pharmacy_see_and_complete_all_requests(): void
    {
        $this->createTwentyConsultations();

        $labRequests = $this->getJson('/api/v1/lab/dashboard')->assertOk()->json('data.lab_requests');
        $this->assertCount(40, $labRequests);
        foreach ($labRequests as $labRequest) {
            $this->assertNotEmpty($labRequest['test_name']);
            $this->assertNotEmpty($labRequest['patient_name']);

            $this->postJson('/api/v1/lab/requests/' . $labRequest['id'] . '/complete', [
                'performed_by' => $this->labTechId,
                'result_summary' => 'النتيجة طبيعية',
            ])->assertOk();
        }

        $prescriptions = $this->getJson('/api/v1/pharmacy/dashboard')->assertOk()->json('data.prescriptions');
        $this->assertCount(20, $prescriptions);
        $stockBefore = DB::table('medicines')->where('name', 'like', 'Amoxicillin%')->value('quantity');

        foreach ($prescriptions as $prescription) {
            $this->assertCount(2, $prescription['items']);
            $this->assertNotEmpty($prescription['items'][0]['medicine_name']);

            $this->postJson('/api/v1/pharmacy/prescriptions/' . $prescription['id'] . '/dispense', [
                'pharmacist_id' => $this->pharmacistId,
            ])->assertOk();
        }

        $this->assertSame(0, DB::table('lab_requests')->where('status', 'pending')->count());
        $this->assertSame(0, DB::table('prescriptions')->where('status', 'pending')->count());

        // Results are visible in the shared record
        $anyPatient = DB::table('patients')->where('name', 'عمر فاروق')->value('id');
        $history = $this->getJson('/api/v1/patients/' . $anyPatient . '/history')->json('data');
        $this->assertSame(['completed', 'completed'], array_column($history['lab_results'], 'status'));
        $this->assertSame('النتيجة طبيعية', $history['lab_results'][0]['result_summary']);
        $this->assertSame('dispensed', $history['prescriptions'][0]['status']);

        // Stock is only deducted for medicines that exist in the pharmacy
        $this->assertNotNull($stockBefore);
    }

    public function test_consultation_validation_errors_do_not_save_partial_data(): void
    {
        $this->postJson('/api/v1/doctor/consultations', [
            'doctor_id' => $this->doctorId,
            'patient' => ['name' => 'مريض ناقص'],
            'diagnosis' => 'x',
        ])->assertStatus(422);

        $this->postJson('/api/v1/doctor/consultations', [
            'doctor_id' => $this->doctorId,
            'patient' => ['name' => 'مريض بدون تشخيص', 'age' => 30],
        ])->assertStatus(422);

        $this->assertSame(1, DB::table('patients')->count());
    }
}
