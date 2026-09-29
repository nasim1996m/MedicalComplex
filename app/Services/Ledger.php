<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * دفتر الأستاذ: كل عملية مالية (سند قبض/صرف، إيراد كشفية، صيدلية، مختبر، قيد يدوي)
 * تُسجَّل كقيد مزدوج متوازن، وأرصدة الحسابات وميزان المراجعة تُحسب من القيود.
 */
class Ledger
{
    /** Account that each voucher category posts to (the other side is cash/bank). */
    public const CATEGORY_ACCOUNTS = [
        // income
        'doctor_income'   => '401',
        'pharmacy_income' => '402',
        'lab_income'      => '403',
        'other_income'    => '404',
        // expenses
        'electricity'        => '501',
        'salary'             => '502',
        'hospitality'        => '503',
        'cleaning'           => '503',
        'water'              => '504',
        'telecom'            => '504',
        'stationary'         => '505',
        'contracts'          => '505',
        'inventory_purchase' => '505',
        'other_expense'      => '505',
    ];

    public const CASH_ACCOUNTS = ['101', '102'];

    /** Asset and expense accounts increase on the debit side; the rest on the credit side. */
    public static function isDebitNormal(string $type): bool
    {
        return in_array($type, ['asset', 'expense'], true);
    }

    /**
     * Post a balanced double-entry journal entry.
     *
     * @param  array<int, array{account_id:int, debit?:float|int|string, credit?:float|int|string, memo?:string|null}>  $lines
     */
    public static function postEntry(string $date, string $description, array $lines, int $createdBy): int
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $clean = [];

        foreach ($lines as $i => $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0 || ($debit > 0) === ($credit > 0)) {
                throw ValidationException::withMessages([
                    "lines.$i" => 'كل سطر في القيد يجب أن يكون مديناً أو دائناً بمبلغ موجب (وليس الاثنين معاً).',
                ]);
            }
            if (!DB::table('chart_of_accounts')->where('id', $line['account_id'])->exists()) {
                throw ValidationException::withMessages(["lines.$i" => 'الحساب المحدد غير موجود في دليل الحسابات.']);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
            $clean[] = [
                'account_id' => (int) $line['account_id'],
                'debit'      => $debit,
                'credit'     => $credit,
                'memo'       => $line['memo'] ?? null,
            ];
        }

        if (count($clean) < 2) {
            throw ValidationException::withMessages(['lines' => 'القيد المزدوج يحتاج طرفاً مديناً وطرفاً دائناً على الأقل.']);
        }
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw ValidationException::withMessages([
                'lines' => 'القيد غير متوازن: مجموع المدين (' . number_format($totalDebit) . ') لا يساوي مجموع الدائن (' . number_format($totalCredit) . ').',
            ]);
        }
        $debitAccounts = array_column(array_filter($clean, fn ($l) => $l['debit'] > 0), 'account_id');
        $creditAccounts = array_column(array_filter($clean, fn ($l) => $l['credit'] > 0), 'account_id');
        if (array_intersect($debitAccounts, $creditAccounts)) {
            throw ValidationException::withMessages(['lines' => 'لا يمكن أن يكون نفس الحساب مديناً ودائناً في نفس القيد.']);
        }

        return DB::transaction(function () use ($date, $description, $clean, $totalDebit, $totalCredit, $createdBy) {
            $entryId = DB::table('journal_entries')->insertGetId([
                'entry_number' => 'JV-TMP-' . uniqid('', true),
                'entry_date'   => $date,
                'description'  => $description,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by'   => $createdBy,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Unique, sequential number derived from the row id
            DB::table('journal_entries')->where('id', $entryId)->update([
                'entry_number' => sprintf('JV-%s-%06d', substr($date, 0, 4), $entryId),
            ]);

            foreach ($clean as $line) {
                DB::table('journal_entry_items')->insert($line + [
                    'journal_entry_id' => $entryId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            return $entryId;
        });
    }

    /**
     * Save a receipt/payment voucher and its journal entry together.
     * Income: Dr cash/bank, Cr revenue. Expense: Dr expense, Cr cash/bank.
     * Returns null (nothing recorded) when the amount is zero.
     */
    public static function recordVoucher(array $data): ?int
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            return null;
        }

        $type = $data['voucher_type'];
        $category = $data['category'];
        $categoryCode = self::CATEGORY_ACCOUNTS[$category] ?? ($type === 'income' ? '404' : '505');
        $cashCode = $data['cash_account_code'] ?? '101';

        $categoryAccount = self::accountIdByCode($categoryCode);
        $cashAccount = self::accountIdByCode($cashCode);

        return DB::transaction(function () use ($data, $amount, $type, $category, $categoryAccount, $cashAccount) {
            $date = $data['date'] ?? now()->toDateString();

            $lines = $type === 'income'
                ? [
                    ['account_id' => $cashAccount, 'debit' => $amount, 'memo' => 'قبض نقدي'],
                    ['account_id' => $categoryAccount, 'credit' => $amount, 'memo' => $data['description']],
                ]
                : [
                    ['account_id' => $categoryAccount, 'debit' => $amount, 'memo' => $data['description']],
                    ['account_id' => $cashAccount, 'credit' => $amount, 'memo' => 'صرف نقدي'],
                ];

            $entryId = self::postEntry($date, $data['description'], $lines, (int) $data['created_by']);

            return DB::table('vouchers')->insertGetId([
                'voucher_type'     => $type,
                'category'         => $category,
                'amount'           => $amount,
                'description'      => $data['description'],
                'created_by'       => $data['created_by'],
                'status'           => 'approved',
                'journal_entry_id' => $entryId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        });
    }

    /**
     * Balance of every account up to $to (inclusive), plus debit/credit movement
     * within [$from, $to]. Balance is on the account's normal side.
     */
    public static function accountBalances(?string $from = null, ?string $to = null)
    {
        $movement = function (?string $start, ?string $end) {
            return DB::table('journal_entry_items')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->when($start, fn ($q) => $q->whereDate('journal_entries.entry_date', '>=', $start))
                ->when($end, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $end))
                ->groupBy('journal_entry_items.account_id')
                ->select('journal_entry_items.account_id', DB::raw('SUM(debit) as debit'), DB::raw('SUM(credit) as credit'))
                ->get()
                ->keyBy('account_id');
        };

        $upToEnd = $movement(null, $to);
        $inPeriod = $movement($from, $to);

        return DB::table('chart_of_accounts')->orderBy('code')->get()->map(function ($account) use ($upToEnd, $inPeriod) {
            $debitNormal = self::isDebitNormal($account->type);
            $opening = (float) $account->opening_balance;
            $totalDebit = (float) ($upToEnd[$account->id]->debit ?? 0);
            $totalCredit = (float) ($upToEnd[$account->id]->credit ?? 0);

            $balance = $debitNormal
                ? $opening + $totalDebit - $totalCredit
                : $opening + $totalCredit - $totalDebit;

            // signed: positive = debit balance, negative = credit balance
            $signed = $debitNormal ? $balance : -$balance;

            return [
                'id'              => $account->id,
                'code'            => $account->code,
                'name'            => $account->name,
                'type'            => $account->type,
                'opening_balance' => round($opening, 2),
                'period_debit'    => round((float) ($inPeriod[$account->id]->debit ?? 0), 2),
                'period_credit'   => round((float) ($inPeriod[$account->id]->credit ?? 0), 2),
                'balance'         => round($balance, 2),
                'trial_debit'     => $signed > 0 ? round($signed, 2) : 0,
                'trial_credit'    => $signed < 0 ? round(-$signed, 2) : 0,
            ];
        })->values();
    }

    public static function accountIdByCode(string $code): int
    {
        $id = DB::table('chart_of_accounts')->where('code', $code)->value('id');
        if (!$id) {
            throw ValidationException::withMessages(['account' => "الحساب رقم {$code} غير موجود في دليل الحسابات."]);
        }

        return (int) $id;
    }
}
