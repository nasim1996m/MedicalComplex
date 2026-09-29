<?php

use App\Services\Ledger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الأرصدة صارت تُحسب من القيود: رصيد افتتاحي + حركة القيود.
     * - الحسابات الختامية (إيرادات/مصروفات) تبدأ من صفر، ورأس المال يوازن الأرصدة الافتتاحية.
     * - كل سند قديم (بما فيه إيرادات الكشفيات والصيدلية والمختبر) يُرحَّل كقيد مزدوج.
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->decimal('opening_balance', 14, 2)->default(0)->after('type');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained('journal_entries')->nullOnDelete();
        });

        $missing = [
            ['code' => '404', 'name' => 'إيرادات أخرى', 'type' => 'revenue'],
            ['code' => '504', 'name' => 'مصاريف الماء والاتصالات والإنترنت', 'type' => 'expense'],
            ['code' => '505', 'name' => 'مصاريف عامة (قرطاسية، عقود، صيانة، مشتريات)', 'type' => 'expense'],
        ];
        // Only when upgrading an existing chart (a fresh database gets them from the seeder)
        $hasChart = DB::table('chart_of_accounts')->exists();
        foreach ($missing as $account) {
            if ($hasChart && !DB::table('chart_of_accounts')->where('code', $account['code'])->exists()) {
                DB::table('chart_of_accounts')->insert($account + ['balance' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Opening balances: balance-sheet accounts keep their balance, P&L accounts start at zero
        DB::table('chart_of_accounts')->whereIn('type', ['asset', 'liability', 'equity'])->update(['opening_balance' => DB::raw('balance')]);
        DB::table('chart_of_accounts')->whereIn('type', ['revenue', 'expense'])->update(['opening_balance' => 0]);

        // Capital balances the opening trial balance (assets = liabilities + equity)
        $capitalId = DB::table('chart_of_accounts')->where('code', '301')->value('id');
        if ($capitalId) {
            $assets = (float) DB::table('chart_of_accounts')->where('type', 'asset')->sum('opening_balance');
            $liabilities = (float) DB::table('chart_of_accounts')->where('type', 'liability')->sum('opening_balance');
            $otherEquity = (float) DB::table('chart_of_accounts')->where('type', 'equity')->where('id', '!=', $capitalId)->sum('opening_balance');
            DB::table('chart_of_accounts')->where('id', $capitalId)->update(['opening_balance' => $assets - $liabilities - $otherEquity]);
        }

        // The demo entry from the seeder duplicates the demo doctor-income voucher
        $demoEntries = DB::table('journal_entries')
            ->where('description', 'قيد تحصيل كشوفات وتصفية فاتورة الكهرباء والمولد اليومية')
            ->pluck('id');
        DB::table('journal_entry_items')->whereIn('journal_entry_id', $demoEntries)->delete();
        DB::table('journal_entries')->whereIn('id', $demoEntries)->delete();

        // Post every existing voucher to the ledger
        DB::table('vouchers')->whereNull('journal_entry_id')->orderBy('id')->each(function ($voucher) {
            $code = Ledger::CATEGORY_ACCOUNTS[$voucher->category] ?? ($voucher->voucher_type === 'income' ? '404' : '505');
            $categoryAccount = Ledger::accountIdByCode($code);
            $cash = Ledger::accountIdByCode('101');
            $amount = (float) $voucher->amount;

            if ($amount <= 0) {
                return;
            }

            $lines = $voucher->voucher_type === 'income'
                ? [['account_id' => $cash, 'debit' => $amount], ['account_id' => $categoryAccount, 'credit' => $amount, 'memo' => $voucher->description]]
                : [['account_id' => $categoryAccount, 'debit' => $amount, 'memo' => $voucher->description], ['account_id' => $cash, 'credit' => $amount]];

            $entryId = Ledger::postEntry(substr((string) $voucher->created_at, 0, 10), $voucher->description, $lines, (int) $voucher->created_by);
            DB::table('vouchers')->where('id', $voucher->id)->update(['journal_entry_id' => $entryId]);
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
