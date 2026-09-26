@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold">قسم المحاسبة والمالية والقيد المزدوج</h2>
            <p class="text-xs text-indigo-200">دليل الحسابات، القيد المزدوج، ميزان المراجعة والمصاريف والخدمات</p>
        </div>
    </div>

    <!-- Double Entry Journal Entries -->
    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4 text-xs">
        <h3 class="font-bold text-slate-900 text-sm border-b pb-2">دفتر القيد اليومي المزدوج المسجل بالمنظومة (Debit / Credit)</h3>
        <div class="space-y-3">
            @foreach($journalEntries as $je)
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-2">
                <div class="flex justify-between items-center font-bold">
                    <span class="text-indigo-900">رقم القيد: {{ $je->entry_number }}</span>
                    <span class="text-slate-500 font-mono">{{ $je->entry_date }}</span>
                </div>
                <p class="font-bold text-slate-800">{{ $je->description }}</p>
                <table class="w-full text-right text-[11px] bg-white rounded border">
                    <thead class="bg-[#0f172a] text-white">
                        <tr>
                            <th class="p-2">رمز الحساب</th>
                            <th class="p-2">اسم الحساب</th>
                            <th class="p-2">مدين (Debit)</th>
                            <th class="p-2">دائن (Credit)</th>
                            <th class="p-2">الشرح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($je->items as $item)
                        <tr class="border-t">
                            <td class="p-2 font-mono font-bold text-indigo-700">{{ $item->account_code }}</td>
                            <td class="p-2 font-bold">{{ $item->account_name }}</td>
                            <td class="p-2 font-bold text-emerald-700">{{ $item->debit > 0 ? number_format($item->debit).' د.ع' : '-' }}</td>
                            <td class="p-2 font-bold text-rose-700">{{ $item->credit > 0 ? number_format($item->credit).' د.ع' : '-' }}</td>
                            <td class="p-2 text-slate-500">{{ $item->memo }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Chart of Accounts -->
    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4 text-xs">
        <h3 class="font-bold text-slate-900 text-sm border-b pb-2">دليل الحسابات المحاسبي الموحد (Chart of Accounts)</h3>
        <table class="w-full text-right">
            <thead class="bg-[#0f172a] text-white">
                <tr>
                    <th class="p-3">رمز الحساب</th>
                    <th class="p-3">اسم الحساب</th>
                    <th class="p-3">نوع الحساب</th>
                    <th class="p-3">الرصيد الحالي</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($accounts as $acc)
                <tr>
                    <td class="p-3 font-mono font-bold text-indigo-700">{{ $acc->code }}</td>
                    <td class="p-3 font-bold">{{ $acc->name }}</td>
                    <td class="p-3"><span class="bg-indigo-100 text-indigo-900 font-bold px-2 py-0.5 rounded">{{ $acc->type }}</span></td>
                    <td class="p-3 font-extrabold">{{ number_format($acc->balance) }} د.ع</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
