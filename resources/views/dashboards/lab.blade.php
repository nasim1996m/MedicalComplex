@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold">قسم المختبر والأشعة والإيكو والتخطيط</h2>
            <p class="text-xs text-indigo-200">استقبال فحوصات الأطباء، إدخال النتيجة ومتابعة المستلزمات</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-xs">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">طلبات التحاليل والأشعة الواردة</h3>
            @foreach($labRequests as $req)
            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-slate-900">{{ $req->patient_name }} ({{ $req->patient_code }})</h4>
                    <p class="text-sky-700 font-bold mt-1">الفحص: {{ $req->test_name }} ({{ number_format($req->test_price) }} د.ع)</p>
                    <p class="text-slate-500">الطبيب: {{ $req->doctor_name }}</p>
                    @if($req->result_summary)
                    <p class="text-emerald-800 bg-emerald-50 p-1.5 rounded border mt-1 font-bold">النتيجة: {{ $req->result_summary }}</p>
                    @endif
                </div>
                <a href="{{ route('web.index') }}" class="bg-indigo-600 text-white font-bold px-3 py-2 rounded-xl">إدخال النتيجة والتقرير</a>
            </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">مستلزمات المختبر والقطن والإبر</h3>
            @foreach($consumables as $item)
            <div class="bg-slate-50 p-3 rounded-xl border flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-slate-900">{{ $item->name }}</h4>
                    <p className="text-slate-500">الوحدة: {{ $item->unit }}</p>
                </div>
                <span class="font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">{{ $item->quantity }} {{ $item->unit }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
