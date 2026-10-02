<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MedicalComplexSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->exists()) {
            $this->command?->warn('Database already seeded; skipping.');
            return;
        }

        // 1. Users — the admin's credentials come from the environment, never from the code.
        $adminEmail = strtolower(trim((string) env('SEED_ADMIN_EMAIL')));
        $adminPassword = (string) env('SEED_ADMIN_PASSWORD');
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 12) {
            throw new \RuntimeException('Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD (12+ characters) before seeding.');
        }
        // Demo staff accounts are only created when SEED_DEMO_PASSWORD is set (local testing).
        $demoPassword = env('SEED_DEMO_PASSWORD') ? Hash::make(env('SEED_DEMO_PASSWORD')) : null;

        $adminId = DB::table('users')->insertGetId([
            'name' => 'مدير النظام (الادمن)',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
            'specialty' => 'إدارة مجمع طبي',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctor1Id = DB::table('users')->insertGetId([
            'name' => 'د. أحمد علي السامرائي',
            'email' => 'doctor.ahmed@medical.com',
            'avatar' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&q=80&w=200',
            'password' => $demoPassword,
            'role' => 'doctor',
            'specialty' => 'أطباء باطنية و غدد',
            'status' => 'approved',
            'phone' => '07700000002',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pharmacistId = DB::table('users')->insertGetId([
            'name' => 'د. سارة خالد (صيدلانية)',
            'email' => 'pharmacy@medical.com',
            'avatar' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&q=80&w=200',
            'password' => $demoPassword,
            'role' => 'pharmacist',
            'specialty' => 'صيدلة سريرية',
            'status' => 'approved',
            'phone' => '07700000004',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $labTechId = DB::table('users')->insertGetId([
            'name' => 'أحمد العبيدي (فني مختبر وأشعة)',
            'email' => 'lab@medical.com',
            'avatar' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=200',
            'password' => $demoPassword,
            'role' => 'lab_tech',
            'specialty' => 'تحاليل وأشعة وتخطيط',
            'status' => 'approved',
            'phone' => '07700000005',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountantId = DB::table('users')->insertGetId([
            'name' => 'مصطفى كامل (المحاسب)',
            'email' => 'accountant@medical.com',
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=200',
            'password' => $demoPassword,
            'role' => 'accountant',
            'specialty' => 'الحسابات والمالية',
            'status' => 'approved',
            'phone' => '07700000007',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Chart of Accounts (دليل الحسابات المحاسبي الموحد للمجمع الطبي)
        $accCash = DB::table('chart_of_accounts')->insertGetId(['code' => '101', 'name' => 'الصندوق الرئيسي (الخزينة)', 'type' => 'asset', 'balance' => 4500000, 'created_at' => now(), 'updated_at' => now()]);
        $accBank = DB::table('chart_of_accounts')->insertGetId(['code' => '102', 'name' => 'حساب البنك (المصرف)', 'type' => 'asset', 'balance' => 12000000, 'created_at' => now(), 'updated_at' => now()]);
        $accPharmacyInv = DB::table('chart_of_accounts')->insertGetId(['code' => '103', 'name' => 'مخزون الصيدلية (الأدوية)', 'type' => 'asset', 'balance' => 3500000, 'created_at' => now(), 'updated_at' => now()]);
        $accLabInv = DB::table('chart_of_accounts')->insertGetId(['code' => '104', 'name' => 'مخزون المستلزمات والمختبر', 'type' => 'asset', 'balance' => 1800000, 'created_at' => now(), 'updated_at' => now()]);
        
        $accPayable = DB::table('chart_of_accounts')->insertGetId(['code' => '201', 'name' => 'موردو الأدوية والأجهزة (ذمم دائنة)', 'type' => 'liability', 'balance' => 1200000, 'created_at' => now(), 'updated_at' => now()]);
        $accCapital = DB::table('chart_of_accounts')->insertGetId(['code' => '301', 'name' => 'رأس المال والمستثمرين', 'type' => 'equity', 'balance' => 20000000, 'created_at' => now(), 'updated_at' => now()]);
        
        $accRevDoctor = DB::table('chart_of_accounts')->insertGetId(['code' => '401', 'name' => 'إيرادات كشوفات الأطباء', 'type' => 'revenue', 'balance' => 250000, 'created_at' => now(), 'updated_at' => now()]);
        $accRevPharm = DB::table('chart_of_accounts')->insertGetId(['code' => '402', 'name' => 'إيرادات مبيعات الصيدلية', 'type' => 'revenue', 'balance' => 180000, 'created_at' => now(), 'updated_at' => now()]);
        $accRevLab = DB::table('chart_of_accounts')->insertGetId(['code' => '403', 'name' => 'إيرادات التحاليل والأشعة', 'type' => 'revenue', 'balance' => 150000, 'created_at' => now(), 'updated_at' => now()]);
        
        $accExpElec = DB::table('chart_of_accounts')->insertGetId(['code' => '501', 'name' => 'مصاريف الكهرباء والمولدات', 'type' => 'expense', 'balance' => 120000, 'created_at' => now(), 'updated_at' => now()]);
        $accExpSalaries = DB::table('chart_of_accounts')->insertGetId(['code' => '502', 'name' => 'رواتب وأجور الموظفين والحرس', 'type' => 'expense', 'balance' => 3100000, 'created_at' => now(), 'updated_at' => now()]);
        $accExpHosp = DB::table('chart_of_accounts')->insertGetId(['code' => '503', 'name' => 'مصاريف الضيافة والنظافة', 'type' => 'expense', 'balance' => 35000, 'created_at' => now(), 'updated_at' => now()]);

        // Sample Double Entry Journal Entry (قيد محاسبي مزدوج متوازن)
        $jEntryId = DB::table('journal_entries')->insertGetId([
            'entry_number' => 'JV-2026-001',
            'entry_date' => Carbon::today(),
            'description' => 'قيد تحصيل كشوفات وتصفية فاتورة الكهرباء والمولد اليومية',
            'total_debit' => 250000,
            'total_credit' => 250000,
            'created_by' => $accountantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_items')->insert([
            ['journal_entry_id' => $jEntryId, 'account_id' => $accCash, 'debit' => 250000, 'credit' => 0, 'memo' => 'قبض كشوفات الأطباء نقداً بالصندوق', 'created_at' => now(), 'updated_at' => now()],
            ['journal_entry_id' => $jEntryId, 'account_id' => $accRevDoctor, 'debit' => 0, 'credit' => 250000, 'memo' => 'إثبات إيراد الكشوفات', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Patients
        $p1 = DB::table('patients')->insertGetId(['patient_code' => 'PAT-1001', 'name' => 'حيدر عبد الرضا', 'gender' => 'male', 'age' => 45, 'phone' => '07801112233', 'medical_history' => 'ضغط دم مرتفع، حساسية من البنسلين', 'created_at' => now(), 'updated_at' => now()]);
        
        // Visits
        $v1 = DB::table('visits')->insertGetId(['patient_id' => $p1, 'doctor_id' => $doctor1Id, 'visit_date' => Carbon::today(), 'diagnosis' => 'ارتفاع بالضغط الشرياني واضطراب معدل ضربات القلب', 'notes' => 'يحتاج إجراء تخطيط قلب وفحص دم شامل', 'fee' => 25000, 'status' => 'in_consultation', 'created_at' => now(), 'updated_at' => now()]);

        // Lab Test Types & Medicines
        $t1 = DB::table('lab_test_types')->insertGetId(['name' => 'فحص الدم الشامل (CBC)', 'category' => 'blood', 'price' => 15000, 'created_at' => now(), 'updated_at' => now()]);
        $m1 = DB::table('medicines')->insertGetId(['name' => 'Amoxicillin 500mg (أمكسيسيلين)', 'barcode' => '62911001', 'category' => 'مضاد حيوي', 'unit_price' => 5000, 'quantity' => 120, 'min_threshold' => 15, 'expiry_date' => '2027-06-30', 'batch_number' => 'BATCH-8821', 'created_at' => now(), 'updated_at' => now()]);
        $m2 = DB::table('medicines')->insertGetId(['name' => 'Paracetamol 500mg (باراسيتامول)', 'barcode' => '62911002', 'category' => 'مسكن آلام', 'unit_price' => 2000, 'quantity' => 10, 'min_threshold' => 15, 'expiry_date' => '2026-11-15', 'batch_number' => 'BATCH-4412', 'created_at' => now(), 'updated_at' => now()]);

        // Vouchers
        DB::table('vouchers')->insert([
            ['voucher_type' => 'income', 'category' => 'doctor_income', 'amount' => 250000, 'description' => 'إيراد كشوفات د. أحمد علي السامرائي اليومية', 'created_by' => $accountantId, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
            ['voucher_type' => 'expense', 'category' => 'electricity', 'amount' => 120000, 'description' => 'تسديد فاتورة الكهرباء والمولد الخاص بالمجمع', 'created_by' => $accountantId, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
            ['voucher_type' => 'expense', 'category' => 'hospitality', 'amount' => 35000, 'description' => 'ضيافة مراجعين ومشروبات العيادات', 'created_by' => $accountantId, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
