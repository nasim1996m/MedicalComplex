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
        // 1. Users
        $adminId = DB::table('users')->insertGetId([
            'name' => 'مدير النظام (الادمن)',
            'email' => 'admin@medical.com',
            'google_id' => 'google_admin_101',
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'specialty' => 'إدارة مجمع طبي',
            'status' => 'approved',
            'phone' => '07700000001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctor1Id = DB::table('users')->insertGetId([
            'name' => 'د. أحمد علي السامرائي',
            'email' => 'doctor.ahmed@medical.com',
            'google_id' => 'google_doc_102',
            'avatar' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&q=80&w=200',
            'password' => Hash::make('password'),
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
            'google_id' => 'google_pharm_104',
            'avatar' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&q=80&w=200',
            'password' => Hash::make('password'),
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
            'google_id' => 'google_lab_105',
            'avatar' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=200',
            'password' => Hash::make('password'),
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
            'google_id' => 'google_acc_107',
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=200',
            'password' => Hash::make('password'),
            'role' => 'accountant',
            'specialty' => 'الحسابات والمالية',
            'status' => 'approved',
            'phone' => '07700000007',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Chart of Accounts (دليل الحسابات المحاسبي الموحد للمجمع الطبي)
        $accCash = DB::table('chart_of_accounts')->insertGetId(['code' => '101', 'name' => 'الصندوق الرئيسي (الخزينة)', 'type' => 'asset', 'balance' => 4500000, 'opening_balance' => 4500000, 'created_at' => now(), 'updated_at' => now()]);
        $accBank = DB::table('chart_of_accounts')->insertGetId(['code' => '102', 'name' => 'حساب البنك (المصرف)', 'type' => 'asset', 'balance' => 12000000, 'opening_balance' => 12000000, 'created_at' => now(), 'updated_at' => now()]);
        $accPharmacyInv = DB::table('chart_of_accounts')->insertGetId(['code' => '103', 'name' => 'مخزون الصيدلية (الأدوية)', 'type' => 'asset', 'balance' => 3500000, 'opening_balance' => 3500000, 'created_at' => now(), 'updated_at' => now()]);
        $accLabInv = DB::table('chart_of_accounts')->insertGetId(['code' => '104', 'name' => 'مخزون المستلزمات والمختبر', 'type' => 'asset', 'balance' => 1800000, 'opening_balance' => 1800000, 'created_at' => now(), 'updated_at' => now()]);
        
        $accPayable = DB::table('chart_of_accounts')->insertGetId(['code' => '201', 'name' => 'موردو الأدوية والأجهزة (ذمم دائنة)', 'type' => 'liability', 'balance' => 1200000, 'opening_balance' => 1200000, 'created_at' => now(), 'updated_at' => now()]);
        $accCapital = DB::table('chart_of_accounts')->insertGetId(['code' => '301', 'name' => 'رأس المال والمستثمرين', 'type' => 'equity', 'balance' => 20600000, 'opening_balance' => 20600000, 'created_at' => now(), 'updated_at' => now()]);
        
        $accRevDoctor = DB::table('chart_of_accounts')->insertGetId(['code' => '401', 'name' => 'إيرادات كشوفات الأطباء', 'type' => 'revenue', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $accRevPharm = DB::table('chart_of_accounts')->insertGetId(['code' => '402', 'name' => 'إيرادات مبيعات الصيدلية', 'type' => 'revenue', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $accRevLab = DB::table('chart_of_accounts')->insertGetId(['code' => '403', 'name' => 'إيرادات التحاليل والأشعة', 'type' => 'revenue', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        
        $accExpElec = DB::table('chart_of_accounts')->insertGetId(['code' => '501', 'name' => 'مصاريف الكهرباء والمولدات', 'type' => 'expense', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $accExpSalaries = DB::table('chart_of_accounts')->insertGetId(['code' => '502', 'name' => 'رواتب وأجور الموظفين والحرس', 'type' => 'expense', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $accExpHosp = DB::table('chart_of_accounts')->insertGetId(['code' => '503', 'name' => 'مصاريف الضيافة والنظافة', 'type' => 'expense', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('chart_of_accounts')->insert([
            ['code' => '404', 'name' => 'إيرادات أخرى', 'type' => 'revenue', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '504', 'name' => 'مصاريف الماء والاتصالات والإنترنت', 'type' => 'expense', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '505', 'name' => 'مصاريف عامة (قرطاسية، عقود، صيانة، مشتريات)', 'type' => 'expense', 'balance' => 0, 'opening_balance' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Patients
        $p1 = DB::table('patients')->insertGetId(['patient_code' => 'PAT-1001', 'name' => 'حيدر عبد الرضا', 'search_name' => \App\Support\ArabicText::normalize('حيدر عبد الرضا'), 'gender' => 'male', 'age' => 45, 'phone' => '07801112233', 'medical_history' => 'ضغط دم مرتفع، حساسية من البنسلين', 'created_at' => now(), 'updated_at' => now()]);
        
        // Visits
        $v1 = DB::table('visits')->insertGetId(['patient_id' => $p1, 'doctor_id' => $doctor1Id, 'visit_date' => Carbon::today(), 'diagnosis' => 'ارتفاع بالضغط الشرياني واضطراب معدل ضربات القلب', 'notes' => 'يحتاج إجراء تخطيط قلب وفحص دم شامل', 'fee' => 25000, 'status' => 'in_consultation', 'created_at' => now(), 'updated_at' => now()]);

        // Lab Test Types & Medicines
        $t1 = DB::table('lab_test_types')->insertGetId(['name' => 'فحص الدم الشامل (CBC)', 'category' => 'blood', 'price' => 15000, 'created_at' => now(), 'updated_at' => now()]);
        $m1 = DB::table('medicines')->insertGetId(['name' => 'Amoxicillin 500mg (أمكسيسيلين)', 'barcode' => '62911001', 'category' => 'مضاد حيوي', 'unit_price' => 5000, 'quantity' => 120, 'min_threshold' => 15, 'expiry_date' => '2027-06-30', 'batch_number' => 'BATCH-8821', 'created_at' => now(), 'updated_at' => now()]);
        $m2 = DB::table('medicines')->insertGetId(['name' => 'Paracetamol 500mg (باراسيتامول)', 'barcode' => '62911002', 'category' => 'مسكن آلام', 'unit_price' => 2000, 'quantity' => 10, 'min_threshold' => 15, 'expiry_date' => '2026-11-15', 'batch_number' => 'BATCH-4412', 'created_at' => now(), 'updated_at' => now()]);

        // Vouchers (each one is posted to the ledger as a balanced double entry)
        foreach ([
            ['voucher_type' => 'income', 'category' => 'doctor_income', 'amount' => 250000, 'description' => 'إيراد كشوفات د. أحمد علي السامرائي اليومية'],
            ['voucher_type' => 'expense', 'category' => 'electricity', 'amount' => 120000, 'description' => 'تسديد فاتورة الكهرباء والمولد الخاص بالمجمع'],
            ['voucher_type' => 'expense', 'category' => 'hospitality', 'amount' => 35000, 'description' => 'ضيافة مراجعين ومشروبات العيادات'],
        ] as $voucher) {
            \App\Services\Ledger::recordVoucher($voucher + ['created_by' => $accountantId]);
        }
    }
}
