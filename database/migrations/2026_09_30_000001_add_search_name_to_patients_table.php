<?php

use App\Support\ArabicText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نسخة موحّدة من اسم المريض للبحث (أ/إ/آ = ا، ة = ه، ى = ي، بدون تشكيل).
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('search_name')->nullable()->index()->after('name');
        });

        DB::table('patients')->orderBy('id')->each(function ($patient) {
            DB::table('patients')->where('id', $patient->id)->update([
                'search_name' => ArabicText::normalize($patient->name),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('search_name');
        });
    }
};
