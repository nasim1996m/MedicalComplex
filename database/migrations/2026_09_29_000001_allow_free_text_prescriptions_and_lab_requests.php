<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * يسمح للطبيب بكتابة اسم الدواء واسم الفحص يدوياً بدل الاختيار من القائمة فقط.
     */
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->unsignedBigInteger('medicine_id')->nullable()->change();
            $table->string('medicine_name')->nullable()->after('medicine_id');
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('test_type_id')->nullable()->change();
            $table->string('test_name')->nullable()->after('test_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropColumn('medicine_name');
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropColumn('test_name');
        });
    }
};
