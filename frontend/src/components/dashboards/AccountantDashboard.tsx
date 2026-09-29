'use client';

import React, { useCallback, useEffect, useState } from 'react';
import { AccountingDashboardData, ChartOfAccount } from '@/types/medical';
import { useAuth } from '@/context/AuthContext';
import { addJournalEntry, addVoucher, getAccountingDashboard, todayISO } from '@/services/medicalApi';
import {
  Calculator,
  Plus,
  CheckCircle2,
  FileText,
  BookOpen,
  Scale,
  AlertTriangle,
  Loader2,
  CalendarRange,
} from 'lucide-react';

// البنود المحاسبية للسندات (يجب أن تطابق Ledger::CATEGORY_ACCOUNTS في الباك إند)
const EXPENSE_CATEGORIES: Record<string, string> = {
  electricity: 'كهرباء ومولدات',
  salary: 'رواتب وأجور',
  water: 'ماء وخدمات صحية',
  telecom: 'اتصالات وإنترنت',
  hospitality: 'ضيافة ومشروبات',
  cleaning: 'منظفات ومعقمات',
  stationary: 'قرطاسية ومطبوعات',
  contracts: 'عقود وصيانة',
  inventory_purchase: 'مشتريات مخزن',
  other_expense: 'مصاريف أخرى',
};

const INCOME_CATEGORIES: Record<string, string> = {
  other_income: 'إيرادات أخرى',
  doctor_income: 'إيراد كشوفات الأطباء',
  pharmacy_income: 'إيراد مبيعات الصيدلية',
  lab_income: 'إيراد التحاليل والأشعة',
};

const CATEGORY_LABELS: Record<string, string> = { ...EXPENSE_CATEGORIES, ...INCOME_CATEGORIES };

const TYPE_LABELS: Record<ChartOfAccount['type'], string> = {
  asset: 'أصول',
  liability: 'التزامات',
  equity: 'حقوق ملكية',
  revenue: 'إيرادات',
  expense: 'مصروفات',
};

const TYPE_STYLES: Record<ChartOfAccount['type'], string> = {
  asset: 'bg-sky-100 text-sky-800',
  liability: 'bg-amber-100 text-amber-800',
  equity: 'bg-violet-100 text-violet-800',
  revenue: 'bg-emerald-100 text-emerald-800',
  expense: 'bg-rose-100 text-rose-800',
};

/** Decimal columns may arrive as strings from MySQL */
const money = (value: number | string | null | undefined) =>
  `${Number(value ?? 0).toLocaleString('en-US', { maximumFractionDigits: 2 })} د.ع`;

function monthStartISO(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
}

export default function AccountantDashboard() {
  const { user } = useAuth();
  const [activeAccTab, setActiveAccTab] = useState<'chart' | 'journal' | 'trial' | 'vouchers'>('journal');

  const [data, setData] = useState<AccountingDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  // Period filter
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');

  // New Journal Entry Form (Double-Entry Bookkeeping)
  const [entryDescription, setEntryDescription] = useState('');
  const [entryDate, setEntryDate] = useState(todayISO());
  const [debitAccId, setDebitAccId] = useState<number>(0);
  const [creditAccId, setCreditAccId] = useState<number>(0);
  const [entryAmount, setEntryAmount] = useState<number>(150000);

  // Voucher Form
  const [voucherType, setVoucherType] = useState<'income' | 'expense'>('expense');
  const [category, setCategory] = useState('electricity');
  const [cashAccount, setCashAccount] = useState<'101' | '102'>('101');
  const [amount, setAmount] = useState<number>(50000);
  const [voucherDate, setVoucherDate] = useState(todayISO());
  const [description, setDescription] = useState('');

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const applyData = useCallback((result: AccountingDashboardData) => {
    setData(result);
    // Default journal accounts: cash (101) debit, doctor revenue (401) credit
    const byCode = (code: string) => result.accounts.find((a) => a.code === code)?.id ?? result.accounts[0]?.id ?? 0;
    setDebitAccId((current) => current || byCode('101'));
    setCreditAccId((current) => current || byCode('401'));
  }, []);

  const reload = useCallback(
    async (range: { from: string; to: string }) => {
      applyData(await getAccountingDashboard(range));
      setErrorMsg(null);
    },
    [applyData]
  );

  useEffect(() => {
    let cancelled = false;
    getAccountingDashboard({})
      .then((result) => !cancelled && applyData(result))
      .catch((err) => !cancelled && setErrorMsg(err instanceof Error ? err.message : 'فشل تحميل الحسابات'))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, [applyData]);

  const applyPeriod = async (newFrom: string, newTo: string) => {
    setFrom(newFrom);
    setTo(newTo);
    setLoading(true);
    try {
      await reload({ from: newFrom, to: newTo });
    } catch (err) {
      setErrorMsg(err instanceof Error ? err.message : 'فشل تحميل الحسابات');
    } finally {
      setLoading(false);
    }
  };

  // Create Double Entry (Qayd Muzdawaj: Debit = Credit)
  const handleAddJournalEntry = async () => {
    if (!user) return;
    if (!entryDescription.trim()) return setErrorMsg('اكتب البيان والشرح للقيد.');
    if (!(entryAmount > 0)) return setErrorMsg('المبلغ يجب أن يكون أكبر من صفر.');
    if (debitAccId === creditAccId) return setErrorMsg('لا يمكن أن يكون الطرف المدين والدائن نفس الحساب.');

    setSaving(true);
    try {
      const res = await addJournalEntry({
        created_by: user.id,
        entry_date: entryDate,
        description: entryDescription.trim(),
        lines: [
          { account_id: debitAccId, debit: entryAmount, memo: 'طرف مدين' },
          { account_id: creditAccId, credit: entryAmount, memo: 'طرف دائن' },
        ],
      });
      setEntryDescription('');
      await reload({ from, to });
      showNotification(`تم تسجيل القيد المزدوج ${res.entry_number} وترحيله إلى دفتر الأستاذ بنجاح!`);
    } catch (err) {
      setErrorMsg(err instanceof Error ? err.message : 'فشل تسجيل القيد');
    } finally {
      setSaving(false);
    }
  };

  // Add Voucher (posts its double entry automatically)
  const handleAddVoucher = async () => {
    if (!user) return;
    if (!description.trim()) return setErrorMsg('اكتب الشرح والبيان للسند.');
    if (!(amount > 0)) return setErrorMsg('المبلغ يجب أن يكون أكبر من صفر.');

    setSaving(true);
    try {
      await addVoucher({
        created_by: user.id,
        voucher_type: voucherType,
        category,
        amount,
        description: description.trim(),
        cash_account_code: cashAccount,
        date: voucherDate,
      });
      setDescription('');
      await reload({ from, to });
      showNotification('تم حفظ السند المالي وترحيل قيده المزدوج بنجاح!');
    } catch (err) {
      setErrorMsg(err instanceof Error ? err.message : 'فشل حفظ السند');
    } finally {
      setSaving(false);
    }
  };

  const switchVoucherType = (type: 'income' | 'expense') => {
    setVoucherType(type);
    setCategory(type === 'income' ? 'other_income' : 'electricity');
  };

  const accounts = data?.accounts ?? [];
  const summary = data?.summary;
  const trial = data?.trial_balance;
  const periodLabel = from || to ? `${from || 'البداية'} ← ${to || 'اليوم'}` : 'كل الفترات';

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {errorMsg && (
        <div className="bg-rose-50 text-rose-800 p-4 rounded-2xl border border-rose-200 flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
            <p className="text-xs font-bold">{errorMsg}</p>
          </div>
          <button onClick={() => setErrorMsg(null)} className="text-rose-400 hover:text-rose-700 text-xs font-bold">
            ✕
          </button>
        </div>
      )}

      {/* Accountant Banner Header */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-4 border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-emerald-500/20 rounded-2xl border border-emerald-400/30">
            <Calculator className="w-8 h-8 text-emerald-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">إدارة الحسابات والمالية والقيد المزدوج للمجمع</h2>
            <p className="text-xs text-indigo-200">دليل الحسابات المعياري، القيود المزدوجة المتوازنة، ميزان المراجعة، وسندات المصاريف</p>
          </div>
        </div>

        {/* Sub-Tab Switcher */}
        <div className="flex items-center gap-1.5 bg-indigo-950/90 p-1.5 rounded-xl border border-indigo-800/40">
          {(
            [
              ['journal', 'دفتر القيد المزدوج'],
              ['chart', 'دليل الحسابات'],
              ['trial', 'ميزان المراجعة'],
              ['vouchers', 'السندات والمصاريف'],
            ] as const
          ).map(([tab, label]) => (
            <button
              key={tab}
              onClick={() => setActiveAccTab(tab)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${
                activeAccTab === tab ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:text-white'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Period filter */}
      <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap items-end gap-3 text-xs">
        <CalendarRange className="w-5 h-5 text-indigo-600 mb-2" />
        <div>
          <label className="block font-bold text-slate-700 mb-1">من تاريخ:</label>
          <input type="date" value={from} max={to || undefined} onChange={(e) => setFrom(e.target.value)} className="border border-slate-300 rounded-xl p-2 bg-white" />
        </div>
        <div>
          <label className="block font-bold text-slate-700 mb-1">إلى تاريخ:</label>
          <input type="date" value={to} min={from || undefined} onChange={(e) => setTo(e.target.value)} className="border border-slate-300 rounded-xl p-2 bg-white" />
        </div>
        <button onClick={() => applyPeriod(from, to)} className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl">
          عرض الفترة
        </button>
        <button onClick={() => applyPeriod(todayISO(), todayISO())} className="bg-white border border-slate-300 hover:bg-slate-100 font-bold px-3 py-2 rounded-xl text-slate-700">
          اليوم
        </button>
        <button onClick={() => applyPeriod(monthStartISO(), todayISO())} className="bg-white border border-slate-300 hover:bg-slate-100 font-bold px-3 py-2 rounded-xl text-slate-700">
          هذا الشهر
        </button>
        <button onClick={() => applyPeriod('', '')} className="bg-white border border-slate-300 hover:bg-slate-100 font-bold px-3 py-2 rounded-xl text-slate-700">
          كل الفترات
        </button>
        <span className="text-slate-500 font-bold mr-auto">الفترة المعروضة: {periodLabel}</span>
        {loading && <Loader2 className="w-4 h-4 animate-spin text-indigo-600" />}
      </div>

      {/* Financial Overview Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-emerald-950/80 text-white p-4.5 rounded-2xl border border-emerald-800/40 shadow-lg">
          <p className="text-xs font-medium text-emerald-300">إجمالي الإيرادات للفترة</p>
          <h3 className="text-xl font-extrabold text-emerald-400 mt-1">{money(summary?.total_income)}</h3>
          <p className="text-[10px] text-emerald-200/80 mt-1">
            كشوفات {money(summary?.doctor_income)} · صيدلية {money(summary?.pharmacy_income)} · مختبر {money(summary?.lab_income)}
          </p>
        </div>
        <div className="bg-rose-950/80 text-white p-4.5 rounded-2xl border border-rose-800/40 shadow-lg">
          <p className="text-xs font-medium text-rose-300">إجمالي المصروفات للفترة</p>
          <h3 className="text-xl font-extrabold text-rose-400 mt-1">{money(summary?.total_expense)}</h3>
        </div>
        <div className="bg-[#1e1b4b] text-white p-4.5 rounded-2xl border border-indigo-700/50 shadow-lg">
          <p className="text-xs font-medium text-sky-300">صافي الربح للفترة</p>
          <h3 className={`text-xl font-extrabold mt-1 ${Number(summary?.net_profit ?? 0) < 0 ? 'text-rose-400' : 'text-white'}`}>
            {money(summary?.net_profit)}
          </h3>
        </div>
        <div className="bg-slate-900 text-white p-4.5 rounded-2xl border border-slate-700/50 shadow-lg">
          <p className="text-xs font-medium text-indigo-300">توازن ميزان المراجعة (مدين / دائن)</p>
          {trial ? (
            trial.is_balanced ? (
              <h3 className="text-xl font-extrabold text-emerald-400 mt-1">متوازن ✓</h3>
            ) : (
              <h3 className="text-base font-extrabold text-rose-400 mt-1">غير متوازن — الفرق {money(trial.difference)}</h3>
            )
          ) : (
            <h3 className="text-xl font-extrabold text-slate-500 mt-1">—</h3>
          )}
        </div>
      </div>

      {/* TAB 1: DOUBLE ENTRY JOURNAL (دفتر القيود المزدوجة) */}
      {activeAccTab === 'journal' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Create New Double Entry Form */}
          <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <BookOpen className="w-4 h-4 text-indigo-600" />
              <span>إنشاء سند قيد محاسبي مزدوج (Debit / Credit)</span>
            </h3>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">الطرف المدين (Debit):</label>
                <select
                  value={debitAccId}
                  onChange={(e) => setDebitAccId(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  {accounts.map((acc) => (
                    <option key={acc.id} value={acc.id}>
                      [{acc.code}] {acc.name} ({TYPE_LABELS[acc.type]})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">الطرف الدائن (Credit):</label>
                <select
                  value={creditAccId}
                  onChange={(e) => setCreditAccId(Number(e.target.value))}
                  className={`w-full border rounded-xl p-2.5 bg-white font-bold text-slate-800 ${
                    debitAccId === creditAccId ? 'border-rose-500' : 'border-slate-300'
                  }`}
                >
                  {accounts.map((acc) => (
                    <option key={acc.id} value={acc.id}>
                      [{acc.code}] {acc.name} ({TYPE_LABELS[acc.type]})
                    </option>
                  ))}
                </select>
                {debitAccId === creditAccId && (
                  <p className="text-rose-600 mt-1">لا يمكن اختيار نفس الحساب للطرفين.</p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">المبلغ (د.ع):</label>
                  <input
                    type="number"
                    min={0}
                    value={entryAmount}
                    onChange={(e) => setEntryAmount(Number(e.target.value))}
                    className="w-full border border-slate-300 rounded-xl p-2.5 font-bold text-indigo-950 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">تاريخ القيد:</label>
                  <input
                    type="date"
                    value={entryDate}
                    onChange={(e) => setEntryDate(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">البيان والشرح العام للقيد:</label>
                <textarea
                  rows={3}
                  value={entryDescription}
                  onChange={(e) => setEntryDescription(e.target.value)}
                  placeholder="أدخل الشرح التفصيلي للعملية المالية..."
                  className="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              <button
                onClick={handleAddJournalEntry}
                disabled={saving || debitAccId === creditAccId}
                className="w-full bg-[#1e1b4b] hover:bg-[#312e81] disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle2 className="w-4 h-4 text-sky-400" />}
                <span>حفظ وتثبيت القيد المزدوج المتوازن</span>
              </button>
            </div>
          </div>

          {/* Journal Entries List */}
          <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <FileText className="w-4 h-4 text-emerald-600" />
              <span>دفتر اليومية ({data?.journal_entries.length ?? 0} قيد)</span>
            </h3>

            <div className="space-y-4 max-h-[700px] overflow-y-auto">
              {!loading && data?.journal_entries.length === 0 && (
                <p className="text-xs text-slate-500 text-center py-6">لا توجد قيود في هذه الفترة.</p>
              )}
              {data?.journal_entries.map((je) => (
                <div key={je.id} className="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2">
                  <div className="flex justify-between items-center text-xs">
                    <span className="font-extrabold text-indigo-900 bg-indigo-100 px-2.5 py-1 rounded-lg">
                      رقم القيد: {je.entry_number}
                    </span>
                    <span className="text-slate-500 font-mono">
                      {String(je.entry_date).slice(0, 10)} {je.creator_name ? `· ${je.creator_name}` : ''}
                    </span>
                  </div>
                  <p className="text-xs font-bold text-slate-800">{je.description}</p>

                  <div className="overflow-x-auto pt-1">
                    <table className="w-full text-right text-[11px] bg-white rounded-xl border border-slate-200">
                      <thead className="bg-[#0f172a] text-white">
                        <tr>
                          <th className="p-2">رمز الحساب</th>
                          <th className="p-2">اسم الحساب</th>
                          <th className="p-2">مدين (Debit)</th>
                          <th className="p-2">دائن (Credit)</th>
                          <th className="p-2">البيان الفرعي</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {je.items.map((item, idx) => (
                          <tr key={idx} className="hover:bg-slate-50">
                            <td className="p-2 font-mono font-bold text-indigo-700">{item.account_code}</td>
                            <td className="p-2 font-bold text-slate-800">{item.account_name}</td>
                            <td className="p-2 font-bold text-emerald-700">{Number(item.debit) > 0 ? money(item.debit) : '-'}</td>
                            <td className="p-2 font-bold text-rose-700">{Number(item.credit) > 0 ? money(item.credit) : '-'}</td>
                            <td className="p-2 text-slate-500">{item.memo}</td>
                          </tr>
                        ))}
                        <tr className="bg-slate-100 font-extrabold">
                          <td className="p-2" colSpan={2}>
                            المجموع
                          </td>
                          <td className="p-2 text-emerald-800">{money(je.total_debit)}</td>
                          <td className="p-2 text-rose-800">{money(je.total_credit)}</td>
                          <td className="p-2"></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: CHART OF ACCOUNTS (دليل الحسابات) */}
      {activeAccTab === 'chart' && (
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <BookOpen className="w-4 h-4 text-indigo-600" />
            <span>دليل الحسابات وأرصدتها (الرصيد حتى نهاية الفترة، والحركة خلال الفترة)</span>
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">رمز الحساب</th>
                  <th className="p-3">اسم الحساب المحاسبي</th>
                  <th className="p-3">التصنيف</th>
                  <th className="p-3">الرصيد الافتتاحي</th>
                  <th className="p-3">حركة مدينة</th>
                  <th className="p-3">حركة دائنة</th>
                  <th className="p-3">الرصيد الحالي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {accounts.map((acc) => (
                  <tr key={acc.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-mono font-bold text-indigo-800">{acc.code}</td>
                    <td className="p-3 font-bold text-slate-900">{acc.name}</td>
                    <td className="p-3">
                      <span className={`font-bold px-2 py-0.5 rounded-md ${TYPE_STYLES[acc.type]}`}>{TYPE_LABELS[acc.type]}</span>
                    </td>
                    <td className="p-3 text-slate-600">{money(acc.opening_balance)}</td>
                    <td className="p-3 text-emerald-700">{money(acc.period_debit)}</td>
                    <td className="p-3 text-rose-700">{money(acc.period_credit)}</td>
                    <td className={`p-3 font-extrabold ${Number(acc.balance) < 0 ? 'text-rose-700' : 'text-slate-900'}`}>{money(acc.balance)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 3: TRIAL BALANCE (ميزان المراجعة) */}
      {activeAccTab === 'trial' && (
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center justify-between border-b border-slate-100 pb-3">
            <span className="flex items-center gap-2">
              <Scale className="w-4 h-4 text-emerald-600" />
              <span>ميزان المراجعة بالأرصدة (Trial Balance) {to ? `حتى ${to}` : ''}</span>
            </span>
            {trial && (
              <span
                className={`text-xs font-bold px-3 py-1 rounded-full ${
                  trial.is_balanced ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                }`}
              >
                {trial.is_balanced ? 'متوازن ✓' : `غير متوازن — الفرق ${money(trial.difference)}`}
              </span>
            )}
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">رمز الحساب</th>
                  <th className="p-3">اسم الحساب</th>
                  <th className="p-3">رصيد مدين (Debit)</th>
                  <th className="p-3">رصيد دائن (Credit)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {accounts.map((acc) => (
                  <tr key={acc.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-mono font-bold text-indigo-700">{acc.code}</td>
                    <td className="p-3 font-bold text-slate-800">{acc.name}</td>
                    <td className="p-3 font-bold text-emerald-700">{Number(acc.trial_debit) > 0 ? money(acc.trial_debit) : '-'}</td>
                    <td className="p-3 font-bold text-rose-700">{Number(acc.trial_credit) > 0 ? money(acc.trial_credit) : '-'}</td>
                  </tr>
                ))}
                {trial && (
                  <tr className="bg-slate-100 font-extrabold text-slate-900">
                    <td className="p-3" colSpan={2}>
                      الإجمالي
                    </td>
                    <td className="p-3 text-emerald-800">{money(trial.total_debit)}</td>
                    <td className="p-3 text-rose-800">{money(trial.total_credit)}</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 4: VOUCHERS & SIMPLE EXPENSES */}
      {activeAccTab === 'vouchers' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <Plus className="w-4 h-4 text-indigo-600" />
              <span>سند مصروفات وسندات قبض يومية</span>
            </h3>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">نوع السند:</label>
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => switchVoucherType('expense')}
                    className={`p-2 rounded-xl font-bold border transition ${
                      voucherType === 'expense' ? 'bg-rose-900 text-white border-rose-600' : 'bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    سند صرف (مصاريف)
                  </button>
                  <button
                    type="button"
                    onClick={() => switchVoucherType('income')}
                    className={`p-2 rounded-xl font-bold border transition ${
                      voucherType === 'income' ? 'bg-emerald-900 text-white border-emerald-600' : 'bg-slate-100 text-slate-700 border-slate-200'
                    }`}
                  >
                    سند قبض (إيراد)
                  </button>
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">البند المحاسبي:</label>
                <select
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  {Object.entries(voucherType === 'income' ? INCOME_CATEGORIES : EXPENSE_CATEGORIES).map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">{voucherType === 'income' ? 'يُقبض إلى:' : 'يُدفع من:'}</label>
                <select
                  value={cashAccount}
                  onChange={(e) => setCashAccount(e.target.value as '101' | '102')}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  <option value="101">[101] الصندوق الرئيسي (الخزينة)</option>
                  <option value="102">[102] حساب البنك (المصرف)</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">المبلغ (د.ع):</label>
                  <input
                    type="number"
                    min={0}
                    value={amount}
                    onChange={(e) => setAmount(Number(e.target.value))}
                    className="w-full border border-slate-300 rounded-xl p-2.5 font-bold text-indigo-950 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">التاريخ:</label>
                  <input
                    type="date"
                    value={voucherDate}
                    onChange={(e) => setVoucherDate(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">الشرح والبيان:</label>
                <textarea
                  rows={3}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="أدخل الشرح والجهة المستلمة..."
                  className="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              <button
                onClick={handleAddVoucher}
                disabled={saving}
                className="w-full bg-[#1e1b4b] hover:bg-[#312e81] disabled:opacity-50 text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle2 className="w-4 h-4 text-sky-400" />}
                <span>حفظ السند المالي</span>
              </button>
            </div>
          </div>

          <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <FileText className="w-4 h-4 text-emerald-600" />
              <span>سجل السندات المالية ({data?.vouchers.length ?? 0})</span>
            </h3>

            <div className="overflow-x-auto max-h-[700px] overflow-y-auto">
              <table className="w-full text-right text-xs">
                <thead className="bg-[#0f172a] text-white rounded-xl sticky top-0">
                  <tr>
                    <th className="p-3">نوع السند</th>
                    <th className="p-3">البند</th>
                    <th className="p-3">المبلغ</th>
                    <th className="p-3">التفاصيل</th>
                    <th className="p-3">أنشأه</th>
                    <th className="p-3">التاريخ</th>
                    <th className="p-3">رقم القيد</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {!loading && data?.vouchers.length === 0 && (
                    <tr>
                      <td colSpan={7} className="p-6 text-center text-slate-500">
                        لا توجد سندات في هذه الفترة.
                      </td>
                    </tr>
                  )}
                  {data?.vouchers.map((v) => (
                    <tr key={v.id} className="hover:bg-slate-50 transition">
                      <td className="p-3">
                        <span
                          className={`font-bold px-2 py-0.5 rounded-md ${
                            v.voucher_type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                          }`}
                        >
                          {v.voucher_type === 'income' ? 'قبض' : 'صرف'}
                        </span>
                      </td>
                      <td className="p-3 font-bold text-slate-800">{CATEGORY_LABELS[v.category] ?? v.category}</td>
                      <td className="p-3 font-extrabold text-slate-900 whitespace-nowrap">{money(v.amount)}</td>
                      <td className="p-3 text-slate-600">{v.description}</td>
                      <td className="p-3 text-slate-500">{v.creator_name}</td>
                      <td className="p-3 text-slate-500 font-mono whitespace-nowrap">{String(v.voucher_date ?? v.created_at).slice(0, 10)}</td>
                      <td className="p-3 text-indigo-700 font-mono whitespace-nowrap">{v.entry_number ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
