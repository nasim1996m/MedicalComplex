<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Role Requests (طلبات الأدوار المعلقة)
        Schema::create('role_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('requested_role');
            $table->string('requested_specialty')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Patients (المرضى)
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_code')->unique();
            $table->string('name');
            $table->string('gender')->default('male');
            $table->integer('age');
            $table->string('phone')->nullable();
            $table->text('medical_history')->nullable();
            $table->timestamps();
        });

        // 3. Visits (الكشوفات والزيارات)
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->date('visit_date');
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('fee', 10, 2)->default(0);
            $table->enum('status', ['waiting', 'in_consultation', 'completed'])->default('waiting');
            $table->timestamps();
        });

        // 4. Lab Test Types (أنواع الفحوصات: دم، أشعة سينية، إيكو، تخطيط قلب)
        Schema::create('lab_test_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['blood', 'xray', 'echo', 'ecg', 'other'])->default('blood');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 5. Lab Requests (طلبات التحاليل والأشعة)
        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('test_type_id')->constrained('lab_test_types')->onDelete('cascade');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->text('result_summary')->nullable();
            $table->string('report_file_url')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 6. Medicines (الأدوية في الصيدلية والمخزن)
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->nullable();
            $table->string('category')->default('عام');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('min_threshold')->default(15);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });

        // 7. Prescriptions (الوصفات الطبية)
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->enum('status', ['pending', 'dispensed'])->default('pending');
            $table->timestamp('dispensed_at')->nullable();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 8. Prescription Items (عناصر الوصفة الطبية)
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('dosage');
            $table->string('duration');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Inventory Items (معدات المختبر والمواد والمستلزمات)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['lab_supplies', 'medical_supplies', 'office_supplies'])->default('lab_supplies');
            $table->integer('quantity')->default(0);
            $table->string('unit')->default('قطعة');
            $table->integer('min_threshold')->default(15);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // 10. Accounting Chart of Accounts (دليل الحسابات المحاسبي المعياري)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 101, 102, 201, 301, 401, 501
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        // 11. Journal Entries (دفتر القيود المحاسبية القيد المزدوج)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->decimal('total_debit', 12, 2)->default(0);
            $table->decimal('total_credit', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // 12. Vouchers (القيود والمصاريف والإيرادات اليومية)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->enum('voucher_type', ['income', 'expense'])->default('expense');
            $table->string('category');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('description');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved'])->default('approved');
            $table->timestamps();
        });

        // 13. HR Employees & Attendance & Rosters
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('job_title');
            $table->string('department');
            $table->decimal('salary', 10, 2)->default(0);
            $table->date('hire_date')->nullable();
            $table->string('fingerprint_id')->unique();
            $table->timestamps();
        });

        Schema::create('hr_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');
            $table->timestamps();
        });

        Schema::create('hr_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->enum('shift', ['morning', 'evening', 'night'])->default('morning');
            $table->date('date');
            $table->string('location')->default('المجمع الطبي');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 14. Notifications (التنبيهات والإشعارات الفورية)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('target_role')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('hr_rosters');
        Schema::dropIfExists('hr_attendances');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('lab_requests');
        Schema::dropIfExists('lab_test_types');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('role_requests');
    }
};
