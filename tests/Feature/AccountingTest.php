<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * فحص المحاسبة: القيد المزدوج، السندات، ربط الإيرادات التلقائية (كشفية/مختبر/صيدلية)،
 * أرصدة الحسابات، ميزان المراجعة، والتقارير حسب الفترة.
 */
class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private int $accountantId;
    private int $doctorId;
    private int $labTechId;
    private int $pharmacistId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MedicalComplexSeeder::class);

        $this->accountantId = DB::table('users')->where('role', 'accountant')->value('id');
        $this->doctorId = DB::table('users')->where('role', 'doctor')->value('id');
        $this->labTechId = DB::table('users')->where('role', 'lab_tech')->value('id');
        $this->pharmacistId = DB::table('users')->where('role', 'pharmacist')->value('id');
    }

    private function dashboard(array $params = []): array
    {
        return $this->getJson('/api/v1/accountant/dashboard?' . http_build_query($params))->assertOk()->json('data');
    }

    private function balance(array $dashboard, string $code): float
    {
        return (float) collect($dashboard['accounts'])->firstWhere('code', $code)['balance'];
    }

    private function accountId(string $code): int
    {
        return DB::table('chart_of_accounts')->where('code', $code)->value('id');
    }

    private function voucher(string $type, string $category, float $amount, array $extra = [])
    {
        return $this->postJson('/api/v1/accountant/vouchers', [
            'created_by' => $this->accountantId,
            'voucher_type' => $type,
            'category' => $category,
            'amount' => $amount,
            'description' => "سند {$category}",
        ] + $extra);
    }

    public function test_seeded_books_are_balanced(): void
    {
        $data = $this->dashboard();

        $this->assertTrue($data['trial_balance']['is_balanced']);
        $this->assertEquals(22050000, $data['trial_balance']['total_debit']);
        $this->assertEquals(4595000, $this->balance($data, '101')); // 4.5M + 250k - 120k - 35k
        $this->assertEquals(250000, $data['summary']['total_income']);
        $this->assertEquals(155000, $data['summary']['total_expense']);
        $this->assertEquals(95000, $data['summary']['net_profit']);

        // Every voucher has its journal entry
        $this->assertSame(0, DB::table('vouchers')->whereNull('journal_entry_id')->count());
    }

    public function test_expense_voucher_decreases_cash_and_increases_expense(): void
    {
        $before = $this->dashboard();

        $res = $this->voucher('expense', 'water', 40000)->assertCreated();
        $entryId = $res->json('data.journal_entry_id');

        $lines = DB::table('journal_entry_items')->where('journal_entry_id', $entryId)->get();
        $this->assertCount(2, $lines);
        $this->assertEquals(40000, $lines->firstWhere('account_id', $this->accountId('504'))->debit);
        $this->assertEquals(40000, $lines->firstWhere('account_id', $this->accountId('101'))->credit);

        $after = $this->dashboard();
        $this->assertEquals($this->balance($before, '101') - 40000, $this->balance($after, '101'));
        $this->assertEquals($this->balance($before, '504') + 40000, $this->balance($after, '504'));
        $this->assertTrue($after['trial_balance']['is_balanced']);
    }

    public function test_income_voucher_to_bank_increases_bank_and_revenue(): void
    {
        $before = $this->dashboard();
        $this->voucher('income', 'other_income', 300000, ['cash_account_code' => '102'])->assertCreated();
        $after = $this->dashboard();

        $this->assertEquals($this->balance($before, '102') + 300000, $this->balance($after, '102'));
        $this->assertEquals($this->balance($before, '101'), $this->balance($after, '101'));
        $this->assertEquals($this->balance($before, '404') + 300000, $this->balance($after, '404'));
        $this->assertEquals($before['summary']['total_income'] + 300000, $after['summary']['total_income']);
    }

    public function test_invalid_vouchers_are_rejected_and_nothing_is_saved(): void
    {
        $vouchers = DB::table('vouchers')->count();
        $entries = DB::table('journal_entries')->count();

        $this->voucher('expense', 'water', 0)->assertStatus(422);
        $this->voucher('expense', 'water', -500)->assertStatus(422);
        $this->voucher('expense', 'unknown_category', 1000)->assertStatus(422);
        $this->voucher('expense', 'doctor_income', 1000)->assertStatus(422); // income category on expense voucher
        $this->voucher('income', 'electricity', 1000)->assertStatus(422);
        $this->voucher('expense', 'water', 1000, ['cash_account_code' => '401'])->assertStatus(422);

        $this->assertSame($vouchers, DB::table('vouchers')->count());
        $this->assertSame($entries, DB::table('journal_entries')->count());
    }

    public function test_manual_journal_entry_must_be_balanced(): void
    {
        $bank = $this->accountId('102');
        $cash = $this->accountId('101');
        $payable = $this->accountId('201');

        // Unbalanced
        $this->postJson('/api/v1/accountant/journal-entries', [
            'created_by' => $this->accountantId,
            'description' => 'قيد غير متوازن',
            'lines' => [['account_id' => $cash, 'debit' => 1000], ['account_id' => $bank, 'credit' => 900]],
        ])->assertStatus(422);

        // Same account on both sides
        $this->postJson('/api/v1/accountant/journal-entries', [
            'created_by' => $this->accountantId,
            'description' => 'نفس الحساب',
            'lines' => [['account_id' => $cash, 'debit' => 1000], ['account_id' => $cash, 'credit' => 1000]],
        ])->assertStatus(422);

        // A line with both debit and credit
        $this->postJson('/api/v1/accountant/journal-entries', [
            'created_by' => $this->accountantId,
            'description' => 'سطر مزدوج',
            'lines' => [['account_id' => $cash, 'debit' => 1000, 'credit' => 1000], ['account_id' => $bank, 'credit' => 0]],
        ])->assertStatus(422);

        // Only one line
        $this->postJson('/api/v1/accountant/journal-entries', [
            'created_by' => $this->accountantId,
            'description' => 'سطر واحد',
            'lines' => [['account_id' => $cash, 'debit' => 1000]],
        ])->assertStatus(422);

        $this->assertSame(3, DB::table('journal_entries')->count()); // only the 3 seeded voucher entries

        // Balanced multi-line: pay supplier 500k from bank 300k + cash 200k
        $before = $this->dashboard();
        $res = $this->postJson('/api/v1/accountant/journal-entries', [
            'created_by' => $this->accountantId,
            'description' => 'تسديد جزء من ذمم موردي الأدوية',
            'lines' => [
                ['account_id' => $payable, 'debit' => 500000, 'memo' => 'تخفيض الذمم'],
                ['account_id' => $bank, 'credit' => 300000],
                ['account_id' => $cash, 'credit' => 200000],
            ],
        ])->assertCreated();
        $this->assertMatchesRegularExpression('/^JV-\d{4}-\d{6}$/', $res->json('data.entry_number'));

        $after = $this->dashboard();
        $this->assertEquals($this->balance($before, '201') - 500000, $this->balance($after, '201'));
        $this->assertEquals($this->balance($before, '102') - 300000, $this->balance($after, '102'));
        $this->assertEquals($this->balance($before, '101') - 200000, $this->balance($after, '101'));
        $this->assertTrue($after['trial_balance']['is_balanced']);
        // Balance-sheet only movement: profit unchanged
        $this->assertEquals($before['summary']['net_profit'], $after['summary']['net_profit']);
    }

    public function test_clinic_operations_post_revenue_automatically(): void
    {
        $before = $this->dashboard();

        // Visit (fee 30,000) + CBC from the lab catalogue (15,000) + free-text test (default 15,000)
        // + prescription with one stocked medicine (5,000) and one unknown free-text medicine (no price)
        $consultation = $this->postJson('/api/v1/doctor/consultations', [
            'doctor_id' => $this->doctorId,
            'patient' => ['name' => 'مراجع محاسبة', 'age' => 40],
            'diagnosis' => 'فحص',
            'fee' => 30000,
            'lab_requests' => [['test_type_id' => 1], ['test_name' => 'أشعة صدر']],
            'prescription_items' => [
                ['medicine_id' => 1, 'dosage' => 'x', 'duration' => 'y'],
                ['medicine_name' => 'دواء غير موجود', 'dosage' => 'x', 'duration' => 'y'],
            ],
        ])->assertCreated()->json('data');

        foreach ($consultation['lab_request_ids'] as $labId) {
            $this->postJson("/api/v1/lab/requests/{$labId}/complete", ['performed_by' => $this->labTechId, 'result_summary' => 'طبيعي'])->assertOk();
        }
        $this->postJson("/api/v1/pharmacy/prescriptions/{$consultation['prescription_id']}/dispense", ['pharmacist_id' => $this->pharmacistId])->assertOk();

        // Completing / dispensing twice must not double the income
        $this->postJson("/api/v1/lab/requests/{$consultation['lab_request_ids'][0]}/complete", ['performed_by' => $this->labTechId, 'result_summary' => 'مكرر'])->assertStatus(422);
        $this->postJson("/api/v1/pharmacy/prescriptions/{$consultation['prescription_id']}/dispense", ['pharmacist_id' => $this->pharmacistId])->assertStatus(422);

        $after = $this->dashboard();
        $this->assertEquals($before['summary']['doctor_income'] + 30000, $after['summary']['doctor_income']);
        $this->assertEquals($before['summary']['lab_income'] + 30000, $after['summary']['lab_income']);
        $this->assertEquals($before['summary']['pharmacy_income'] + 5000, $after['summary']['pharmacy_income']);
        $this->assertEquals($this->balance($before, '101') + 65000, $this->balance($after, '101'));
        $this->assertTrue($after['trial_balance']['is_balanced']);
        $this->assertSame(0, DB::table('vouchers')->where('amount', '<=', 0)->count(), 'No zero-amount vouchers');
        $this->assertSame(0, DB::table('vouchers')->whereNull('journal_entry_id')->count());
    }

    public function test_many_mixed_operations_keep_books_balanced_and_numbers_consistent(): void
    {
        $categories = [
            ['expense', 'electricity'], ['expense', 'salary'], ['expense', 'hospitality'], ['expense', 'cleaning'],
            ['expense', 'water'], ['expense', 'telecom'], ['expense', 'stationary'], ['expense', 'contracts'],
            ['income', 'other_income'], ['income', 'doctor_income'],
        ];

        $expectedIncome = 250000;   // seeded
        $expectedExpense = 155000;  // seeded
        for ($i = 0; $i < 30; $i++) {
            [$type, $category] = $categories[$i % count($categories)];
            $amount = 1000 * ($i + 1) + 0.5 * ($i % 2); // include fractional amounts
            $this->voucher($type, $category, $amount, ['cash_account_code' => $i % 3 ? '101' : '102'])->assertCreated();
            $type === 'income' ? $expectedIncome += $amount : $expectedExpense += $amount;
        }

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v1/doctor/consultations', [
                'doctor_id' => $this->doctorId,
                'patient' => ['name' => 'مريض ' . $i, 'age' => 30],
                'diagnosis' => 'تشخيص',
            ])->assertCreated();
            $expectedIncome += 25000;
        }

        $data = $this->dashboard();

        $this->assertTrue($data['trial_balance']['is_balanced'], 'Trial balance difference: ' . $data['trial_balance']['difference']);
        $this->assertEqualsWithDelta($expectedIncome, $data['summary']['total_income'], 0.001);
        $this->assertEqualsWithDelta($expectedExpense, $data['summary']['total_expense'], 0.001);
        $this->assertEqualsWithDelta($expectedIncome - $expectedExpense, $data['summary']['net_profit'], 0.001);

        // Accounting equation: assets = liabilities + equity + (revenue - expenses)
        $sum = fn (string $type) => collect($data['accounts'])->where('type', $type)->sum('balance');
        $this->assertEqualsWithDelta(
            $sum('asset'),
            $sum('liability') + $sum('equity') + $sum('revenue') - $sum('expense'),
            0.001
        );

        // Entry numbers are unique
        $numbers = DB::table('journal_entries')->pluck('entry_number');
        $this->assertSame($numbers->count(), $numbers->unique()->count());

        // Each journal entry is balanced on its own
        $unbalanced = DB::table('journal_entry_items')
            ->select('journal_entry_id')
            ->groupBy('journal_entry_id')
            ->havingRaw('ROUND(SUM(debit) - SUM(credit), 2) != 0')
            ->count();
        $this->assertSame(0, $unbalanced);
    }

    public function test_period_filter_limits_vouchers_entries_and_profit(): void
    {
        $today = Carbon::today();

        Carbon::setTestNow($today->copy()->subDays(10)->setTime(12, 0));
        $this->voucher('expense', 'electricity', 70000)->assertCreated();
        Carbon::setTestNow($today->copy()->subDays(3)->setTime(12, 0));
        $this->voucher('income', 'other_income', 90000)->assertCreated();
        Carbon::setTestNow();

        $lastWeek = $this->dashboard(['from' => $today->copy()->subDays(6)->toDateString(), 'to' => $today->toDateString()]);
        $this->assertEquals(250000 + 90000, $lastWeek['summary']['total_income']);
        $this->assertEquals(155000, $lastWeek['summary']['total_expense']);
        $this->assertNotContains('سند electricity', array_column($lastWeek['vouchers'], 'description'));
        $this->assertContains('سند other_income', array_column($lastWeek['vouchers'], 'description'));

        $older = $this->dashboard(['from' => $today->copy()->subDays(12)->toDateString(), 'to' => $today->copy()->subDays(8)->toDateString()]);
        $this->assertEquals(0, $older['summary']['total_income']);
        $this->assertEquals(70000, $older['summary']['total_expense']);
        $this->assertCount(1, $older['journal_entries']);
        // Balances "as of" the end of that older period exclude later movements
        $this->assertEquals(4500000 - 70000, $this->balance($older, '101'));
        $this->assertTrue($older['trial_balance']['is_balanced']);

        // A back-dated voucher is reported on its accounting date, not on the day it was typed in
        $backDate = $today->copy()->subDays(20)->toDateString();
        $this->voucher('expense', 'stationary', 12000, ['date' => $backDate])->assertCreated();
        $thatDay = $this->dashboard(['from' => $backDate, 'to' => $backDate]);
        $this->assertSame(['سند stationary'], array_column($thatDay['vouchers'], 'description'));
        $this->assertEquals(12000, $thatDay['summary']['total_expense']);
        $this->assertStringStartsWith($backDate, $thatDay['vouchers'][0]['voucher_date']);

        $this->getJson('/api/v1/accountant/dashboard?from=2026-09-10&to=2026-09-01')->assertStatus(422);
    }
}
