<?php

namespace App\Http\Controllers\Api;

use App\Services\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountantController extends ApiController
{
    /**
     * Accounting dashboard for a period (from/to, YYYY-MM-DD, both optional):
     * vouchers, journal entries, account balances, trial balance and P&L summary.
     */
    public function dashboard(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->query('from');
        $to = $request->query('to');

        $vouchers = DB::table('vouchers')
            ->leftJoin('users', 'vouchers.created_by', '=', 'users.id')
            ->leftJoin('journal_entries', 'vouchers.journal_entry_id', '=', 'journal_entries.id')
            ->select(
                'vouchers.*',
                'users.name as creator_name',
                'users.role as creator_role',
                'journal_entries.entry_number',
                // The voucher's accounting date is the date of its journal entry (may be back-dated)
                DB::raw('COALESCE(journal_entries.entry_date, vouchers.created_at) as voucher_date')
            )
            ->when($from, fn ($q) => $q->whereDate(DB::raw('COALESCE(journal_entries.entry_date, vouchers.created_at)'), '>=', $from))
            ->when($to, fn ($q) => $q->whereDate(DB::raw('COALESCE(journal_entries.entry_date, vouchers.created_at)'), '<=', $to))
            ->orderByDesc('voucher_date')
            ->orderByDesc('vouchers.id')
            ->limit(500)
            ->get();

        $entries = DB::table('journal_entries')
            ->leftJoin('users', 'journal_entries.created_by', '=', 'users.id')
            ->select('journal_entries.*', 'users.name as creator_name')
            ->when($from, fn ($q) => $q->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $to))
            ->orderByDesc('journal_entries.entry_date')
            ->orderByDesc('journal_entries.id')
            ->limit(200)
            ->get();

        $items = DB::table('journal_entry_items')
            ->join('chart_of_accounts', 'journal_entry_items.account_id', '=', 'chart_of_accounts.id')
            ->select('journal_entry_items.*', 'chart_of_accounts.code as account_code', 'chart_of_accounts.name as account_name')
            ->whereIn('journal_entry_id', $entries->pluck('id'))
            ->orderBy('journal_entry_items.id')
            ->get()
            ->groupBy('journal_entry_id');

        foreach ($entries as $entry) {
            $entry->items = $items->get($entry->id, collect())->values();
        }

        $accounts = Ledger::accountBalances($from, $to);

        // Profit & loss for the period comes from the ledger (vouchers + manual entries)
        $revenue = $accounts->where('type', 'revenue');
        $expense = $accounts->where('type', 'expense');
        $periodIncome = $revenue->sum(fn ($a) => $a['period_credit'] - $a['period_debit']);
        $periodExpense = $expense->sum(fn ($a) => $a['period_debit'] - $a['period_credit']);
        $byCode = fn (string $code) => (float) optional($revenue->firstWhere('code', $code), fn ($a) => $a['period_credit'] - $a['period_debit']);

        $trialDebit = round($accounts->sum('trial_debit'), 2);
        $trialCredit = round($accounts->sum('trial_credit'), 2);

        return $this->success([
            'from'     => $from,
            'to'       => $to,
            'vouchers' => $vouchers,
            'journal_entries' => $entries,
            'accounts' => $accounts,
            'trial_balance' => [
                'total_debit'  => $trialDebit,
                'total_credit' => $trialCredit,
                'difference'   => round($trialDebit - $trialCredit, 2),
                'is_balanced'  => abs($trialDebit - $trialCredit) < 0.01,
            ],
            'summary' => [
                'total_income'    => round($periodIncome, 2),
                'total_expense'   => round($periodExpense, 2),
                'net_profit'      => round($periodIncome - $periodExpense, 2),
                'doctor_income'   => $byCode('401'),
                'pharmacy_income' => $byCode('402'),
                'lab_income'      => $byCode('403'),
            ],
        ]);
    }

    /**
     * Add a receipt/payment voucher (سند قبض/صرف) and post its journal entry
     */
    public function addVoucher(Request $request)
    {
        $request->validate([
            'created_by'        => 'required|exists:users,id',
            'voucher_type'      => 'required|in:income,expense',
            'category'          => 'required|string|in:' . implode(',', array_keys(Ledger::CATEGORY_ACCOUNTS)),
            'amount'            => 'required|numeric|min:0.01|max:9999999999',
            'description'       => 'required|string|max:1000',
            'cash_account_code' => 'nullable|in:' . implode(',', Ledger::CASH_ACCOUNTS),
            'date'              => 'nullable|date',
        ]);

        $isIncomeCategory = str_ends_with($request->category, '_income');
        if ($isIncomeCategory !== ($request->voucher_type === 'income')) {
            return $this->error('البند المحاسبي لا يطابق نوع السند (قبض/صرف).', 422);
        }

        $voucherId = Ledger::recordVoucher($request->only(['voucher_type', 'category', 'amount', 'description', 'created_by', 'cash_account_code', 'date']));

        return $this->success([
            'voucher_id'       => $voucherId,
            'journal_entry_id' => DB::table('vouchers')->where('id', $voucherId)->value('journal_entry_id'),
        ], 'تم تسجيل السند وترحيل القيد المزدوج بنجاح.', 201);
    }

    /**
     * Manual double-entry journal entry (قيد يومية)
     */
    public function addJournalEntry(Request $request)
    {
        $request->validate([
            'created_by'         => 'required|exists:users,id',
            'entry_date'         => 'nullable|date',
            'description'        => 'required|string|max:1000',
            'lines'              => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.debit'      => 'nullable|numeric|min:0',
            'lines.*.credit'     => 'nullable|numeric|min:0',
            'lines.*.memo'       => 'nullable|string|max:255',
        ]);

        $entryId = Ledger::postEntry(
            $request->entry_date ?? now()->toDateString(),
            $request->description,
            $request->lines,
            (int) $request->created_by
        );

        return $this->success([
            'journal_entry_id' => $entryId,
            'entry_number'     => DB::table('journal_entries')->where('id', $entryId)->value('entry_number'),
        ], 'تم تسجيل القيد المزدوج المتوازن بنجاح.', 201);
    }
}
