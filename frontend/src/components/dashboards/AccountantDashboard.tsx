'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { Voucher, ChartOfAccount, JournalEntry } from '@/types/medical';
import {
  Calculator,
  Plus,
  CheckCircle2,
  TrendingUp,
  DollarSign,
  FileText,
  BookOpen,
  Scale,
  Building2,
  AlertCircle,
  BarChart3,
} from 'lucide-react';

export default function AccountantDashboard() {
  const [activeAccTab, setActiveAccTab] = useState<'chart' | 'journal' | 'trial' | 'vouchers'>('journal');

  const [vouchers, setVouchers] = useState<Voucher[]>(mockData.vouchers);
  const [accounts, setAccounts] = useState<ChartOfAccount[]>(mockData.chartOfAccounts);
  const [journalEntries, setJournalEntries] = useState<JournalEntry[]>(mockData.journalEntries);

  // New Journal Entry Form (Double-Entry Bookkeeping)
  const [entryDescription, setEntryDescription] = useState('');
  const [debitAccId, setDebitAccId] = useState<number>(1); // Cash by default
  const [creditAccId, setCreditAccId] = useState<number>(7); // Revenue by default
  const [entryAmount, setEntryAmount] = useState<number>(150000);

  // Simple Voucher Form
  const [voucherType, setVoucherType] = useState<'income' | 'expense'>('expense');
  const [category, setCategory] = useState('electricity');
  const [amount, setAmount] = useState<number>(50000);
  const [description, setDescription] = useState('');

  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  // Create Double Entry (Qayd Muzdawaj: Debit = Credit)
  const handleAddJournalEntry = () => {
    if (!entryDescription || entryAmount <= 0) return;

    const debitAcc = accounts.find((a) => a.id === Number(debitAccId));
    const creditAcc = accounts.find((a) => a.id === Number(creditAccId));

    if (!debitAcc || !creditAcc) return;

    const newEntry: JournalEntry = {
      id: Date.now(),
      entry_number: `JV-2026-00${journalEntries.length + 1}`,
      entry_date: new Date().toISOString().split('T')[0],
      description: entryDescription,
      total_debit: entryAmount,
      total_credit: entryAmount,
      creator_name: 'مصطفى كامل (المحاسب)',
      items: [
        { account_id: debitAcc.id, account_code: debitAcc.code, account_name: debitAcc.name, debit: entryAmount, credit: 0, memo: 'طرف مدين' },
        { account_id: creditAcc.id, account_code: creditAcc.code, account_name: creditAcc.name, debit: 0, credit: entryAmount, memo: 'طرف دائن' },
      ],
    };

    // Update account balances
    setAccounts((prev) =>
      prev.map((acc) => {
        if (acc.id === debitAcc.id) return { ...acc, balance: acc.balance + entryAmount };
        if (acc.id === creditAcc.id) return { ...acc, balance: acc.balance + entryAmount };
        return acc;
      })
    );

    setJournalEntries((prev) => [newEntry, ...prev]);
    setEntryDescription('');
    showNotification('تم تسجيل وتثبيت القيد المحاسبي المزدوج (مدين / دائن) بنجاح بالميزانية!');
  };

  // Add Voucher
  const handleAddVoucher = () => {
    if (!description || amount <= 0) return;

    const newVoucher: Voucher = {
      id: Date.now(),
      voucher_type: voucherType,
      category,
      amount,
      description,
      creator_name: 'مصطفى كامل (المحاسب)',
      created_at: new Date().toLocaleTimeString('ar-SA'),
    };

    setVouchers((prev) => [newVoucher, ...prev]);
    setDescription('');
    showNotification('تم تسجيل سند الصرف/القبض بنجاح!');
  };

  const totalIncome = vouchers.filter((v) => v.voucher_type === 'income').reduce((a, b) => a + b.amount, 0);
  const totalExpense = vouchers.filter((v) => v.voucher_type === 'expense').reduce((a, b) => a + b.amount, 0);

  const totalDebitSum = journalEntries.reduce((a, b) => a + b.total_debit, 0);
  const totalCreditSum = journalEntries.reduce((a, b) => a + b.total_credit, 0);

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
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
          <button
            onClick={() => setActiveAccTab('journal')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${activeAccTab === 'journal' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:text-white'
              }`}
          >
            دفتر القيد المزدوج
          </button>
          <button
            onClick={() => setActiveAccTab('chart')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${activeAccTab === 'chart' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:text-white'
              }`}
          >
            دليل الحسابات
          </button>
          <button
            onClick={() => setActiveAccTab('trial')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${activeAccTab === 'trial' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:text-white'
              }`}
          >
            ميزان المراجعة
          </button>
          <button
            onClick={() => setActiveAccTab('vouchers')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition ${activeAccTab === 'vouchers' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-200 hover:text-white'
              }`}
          >
            السندات والمصاريف
          </button>
        </div>
      </div>

      {/* Financial Overview Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-emerald-950/80 text-white p-4.5 rounded-2xl border border-emerald-800/40 shadow-lg">
          <p className="text-xs font-medium text-emerald-300">إجمالي المقبوضات (الإيرادات)</p>
          <h3 className="text-xl font-extrabold text-emerald-400 mt-1">{totalIncome.toLocaleString()} د.ع</h3>
        </div>
        <div className="bg-rose-950/80 text-white p-4.5 rounded-2xl border border-rose-800/40 shadow-lg">
          <p className="text-xs font-medium text-rose-300">إجمالي المدفوعات (المصاريف)</p>
          <h3 className="text-xl font-extrabold text-rose-400 mt-1">{totalExpense.toLocaleString()} د.ع</h3>
        </div>
        <div className="bg-[#1e1b4b] text-white p-4.5 rounded-2xl border border-indigo-700/50 shadow-lg">
          <p className="text-xs font-medium text-sky-300">صافي الأرباح التشغيلية</p>
          <h3 className="text-xl font-extrabold text-white mt-1">{(totalIncome - totalExpense).toLocaleString()} د.ع</h3>
        </div>
        <div className="bg-slate-900 text-white p-4.5 rounded-2xl border border-slate-700/50 shadow-lg">
          <p className="text-xs font-medium text-indigo-300">توازن القيد المزدوج (Debit / Credit)</p>
          <h3 className="text-xl font-extrabold text-emerald-400 mt-1">متوازن 100%</h3>
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
                <label className="block font-bold text-slate-700 mb-1">الطرف المدين (Debit - الإخَذ/المصروف/الصندوق):</label>
                <select
                  value={debitAccId}
                  onChange={(e) => setDebitAccId(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  {accounts.map((acc) => (
                    <option key={acc.id} value={acc.id}>
                      [{acc.code}] {acc.name} ({acc.type})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">الطرف الدائن (Credit - المَعطي/الإيراد/المصرف):</label>
                <select
                  value={creditAccId}
                  onChange={(e) => setCreditAccId(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  {accounts.map((acc) => (
                    <option key={acc.id} value={acc.id}>
                      [{acc.code}] {acc.name} ({acc.type})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">المبلغ (د.ع):</label>
                <input
                  type="number"
                  value={entryAmount}
                  onChange={(e) => setEntryAmount(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 font-bold text-indigo-950 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
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
                className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                <CheckCircle2 className="w-4 h-4 text-sky-400" />
                <span>حفظ وتثبيت القيد المزدوج المتوازن</span>
              </button>
            </div>
          </div>

          {/* Journal Entries List */}
          <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <FileText className="w-4 h-4 text-emerald-600" />
              <span>دفتر القيد اليومي المزدوج المسجل بالمنظومة</span>
            </h3>

            <div className="space-y-4">
              {journalEntries.map((je) => (
                <div key={je.id} className="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2">
                  <div className="flex justify-between items-center text-xs">
                    <span className="font-extrabold text-indigo-900 bg-indigo-100 px-2.5 py-1 rounded-lg">
                      رقم القيد: {je.entry_number}
                    </span>
                    <span className="text-slate-500 font-mono">{je.entry_date}</span>
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
                            <td className="p-2 font-bold text-emerald-700">
                              {item.debit > 0 ? `${item.debit.toLocaleString()} د.ع` : '-'}
                            </td>
                            <td className="p-2 font-bold text-rose-700">
                              {item.credit > 0 ? `${item.credit.toLocaleString()} د.ع` : '-'}
                            </td>
                            <td className="p-2 text-slate-500">{item.memo}</td>
                          </tr>
                        ))}
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
            <span>دليل الحسابات المحاسبي الموحد للمجمع الطبي (Chart of Accounts)</span>
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">رمز الحساب</th>
                  <th className="p-3">اسم الحساب المحاسبي</th>
                  <th className="p-3">تصنيف الحساب</th>
                  <th className="p-3">الرصيد الحرفي الحالي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {accounts.map((acc) => (
                  <tr key={acc.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-mono font-bold text-indigo-800">{acc.code}</td>
                    <td className="p-3 font-bold text-slate-900">{acc.name}</td>
                    <td className="p-3">
                      <span className={`font-bold px-2 py-0.5 rounded-md ${acc.type === 'asset' ? 'bg-sky-100 text-sky-800' :
                          acc.type === 'liability' ? 'bg-amber-100 text-amber-800' :
                            acc.type === 'revenue' ? 'bg-emerald-100 text-emerald-800' :
                              'bg-rose-100 text-rose-800'
                        }`}>
                        {acc.type === 'asset' ? 'أصول (Assets)' :
                          acc.type === 'liability' ? 'التزامات (Liabilities)' :
                            acc.type === 'equity' ? 'حقوق ملكية (Equity)' :
                              acc.type === 'revenue' ? 'إيرادات (Revenues)' : 'مصروفات (Expenses)'}
                      </span>
                    </td>
                    <td className="p-3 font-extrabold text-slate-900">{acc.balance.toLocaleString()} د.ع</td>
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
              <span>ميزان المراجعة العام (Trial Balance)</span>
            </span>
            <span className="text-xs bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-full">
              متوازن 100%
            </span>
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">رمز الحساب</th>
                  <th className="p-3">اسم الحساب</th>
                  <th className="p-3">إجمالي الأرصدة المدينة (Debit)</th>
                  <th className="p-3">إجمالي الأرصدة الدائنة (Credit)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {accounts.map((acc) => (
                  <tr key={acc.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-mono font-bold text-indigo-700">{acc.code}</td>
                    <td className="p-3 font-bold text-slate-800">{acc.name}</td>
                    <td className="p-3 font-bold text-emerald-700">
                      {['asset', 'expense'].includes(acc.type) ? `${acc.balance.toLocaleString()} د.ع` : '-'}
                    </td>
                    <td className="p-3 font-bold text-rose-700">
                      {['liability', 'equity', 'revenue'].includes(acc.type) ? `${acc.balance.toLocaleString()} د.ع` : '-'}
                    </td>
                  </tr>
                ))}
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
                    onClick={() => setVoucherType('expense')}
                    className={`p-2 rounded-xl font-bold border transition ${voucherType === 'expense'
                        ? 'bg-rose-900 text-white border-rose-600'
                        : 'bg-slate-100 text-slate-700 border-slate-200'
                      }`}
                  >
                    سند صرف (مصاريف)
                  </button>
                  <button
                    type="button"
                    onClick={() => setVoucherType('income')}
                    className={`p-2 rounded-xl font-bold border transition ${voucherType === 'income'
                        ? 'bg-emerald-900 text-white border-emerald-600'
                        : 'bg-slate-100 text-slate-700 border-slate-200'
                      }`}
                  >
                    سند قبض (إيراد)
                  </button>
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">البند المحاسبي الخدمي:</label>
                <select
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-800"
                >
                  <option value="electricity">كهرباء ومولدات</option>
                  <option value="water">ماء وخدمات صحية</option>
                  <option value="telecom">اتصالات وإنترنت</option>
                  <option value="hospitality">ضيافة ومشروبات</option>
                  <option value="cleaning">منظفات ومعقمات</option>
                  <option value="stationary">قرطاسية ومطبوعات</option>
                  <option value="contracts">عقود وصيانة</option>
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">المبلغ (د.ع):</label>
                <input
                  type="number"
                  value={amount}
                  onChange={(e) => setAmount(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 font-bold text-indigo-950 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
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
                className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                <CheckCircle2 className="w-4 h-4 text-sky-400" />
                <span>حفظ السند المالي</span>
              </button>
            </div>
          </div>

          <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
              <FileText className="w-4 h-4 text-emerald-600" />
              <span>سجل السندات المالية والمصاريف</span>
            </h3>

            <div className="overflow-x-auto">
              <table className="w-full text-right text-xs">
                <thead className="bg-[#0f172a] text-white rounded-xl">
                  <tr>
                    <th className="p-3">نوع السند</th>
                    <th className="p-3">الفئة</th>
                    <th className="p-3">المبلغ</th>
                    <th className="p-3">التفاصيل</th>
                    <th className="p-3">المحاسب</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {vouchers.map((v) => (
                    <tr key={v.id} className="hover:bg-slate-50 transition">
                      <td className="p-3">
                        <span className={`font-bold px-2 py-0.5 rounded-md ${v.voucher_type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                          }`}>
                          {v.voucher_type === 'income' ? 'إيراد (قبض)' : 'مصروف (صرف)'}
                        </span>
                      </td>
                      <td className="p-3 font-bold text-slate-800">{v.category}</td>
                      <td className="p-3 font-extrabold text-slate-900">{v.amount.toLocaleString()} د.ع</td>
                      <td className="p-3 text-slate-600">{v.description}</td>
                      <td className="p-3 text-slate-500">{v.creator_name}</td>
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
