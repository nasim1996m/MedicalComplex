'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { LabRequest, InventoryItem } from '@/types/medical';
import {
  FlaskConical,
  CheckCircle2,
  Upload,
  Boxes,
  FileText,
  AlertTriangle,
  Send,
  Clock,
  Activity,
} from 'lucide-react';

export default function LabDashboard() {
  const [labRequests, setLabRequests] = useState<LabRequest[]>(mockData.labRequests);
  const [consumables, setConsumables] = useState<InventoryItem[]>(mockData.inventoryItems);
  const [selectedReq, setSelectedReq] = useState<LabRequest | null>(null);
  const [resultInput, setResultInput] = useState('');
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const handleCompleteTest = () => {
    if (!selectedReq || !resultInput) return;

    setLabRequests((prev) =>
      prev.map((req) =>
        req.id === selectedReq.id
          ? {
              ...req,
              status: 'completed',
              result_summary: resultInput,
              report_file_url: '/reports/result_generated.pdf',
            }
          : req
      )
    );

    // Deduct consumable needle/cotton simulation
    setConsumables((prev) =>
      prev.map((c) => (c.id === 2 ? { ...c, quantity: Math.max(0, c.quantity - 1) } : c))
    );

    setSelectedReq(null);
    setResultInput('');
    showNotification('تم حفظ نتيجة الفحص ورفع التقرير وإتاحتها فوراً للطبيب والصيدلية!');
  };

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {/* Lab Banner Header */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex items-center justify-between border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-sky-500/20 rounded-2xl border border-sky-400/30">
            <FlaskConical className="w-8 h-8 text-sky-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">قسم المختبر والأشعة والإيكو والتخطيط</h2>
            <p className="text-xs text-indigo-200">استقبال طلبات الأطباء، إجراء الفحوصات، رفع التقرير الكامل ومراقبة المستلزمات</p>
          </div>
        </div>

        <div className="bg-indigo-950/80 px-4 py-2 rounded-xl border border-indigo-800/40 text-xs text-indigo-200">
          <span>الطلبات المعلقة: </span>
          <span className="font-bold text-amber-400">
            {labRequests.filter((l) => l.status === 'pending').length} فحص
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Test Requests List */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <Activity className="w-4 h-4 text-indigo-600" />
            <span>طلبات التحاليل والأشعة الواردة من الأطباء</span>
          </h3>

          <div className="space-y-3">
            {labRequests.map((req) => (
              <div key={req.id} className="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div className="space-y-1">
                  <div className="flex items-center gap-3">
                    <span className="font-bold text-slate-900 text-xs">{req.patient_name}</span>
                    <span className="text-[11px] bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-md">
                      {req.patient_code}
                    </span>
                    <span className="text-[11px] text-slate-500">الطبيب: {req.doctor_name}</span>
                  </div>
                  <p className="text-xs font-bold text-sky-700 pt-1">
                    نوع الفحص: {req.test_name} ({req.test_price?.toLocaleString()} د.ع)
                  </p>
                  {req.result_summary && (
                    <p className="text-[11px] text-emerald-800 bg-emerald-50 p-2 rounded-lg border border-emerald-200 font-medium">
                      النتيجة المسجلة: {req.result_summary}
                    </p>
                  )}
                </div>

                <div className="shrink-0">
                  {req.status === 'pending' ? (
                    <button
                      onClick={() => setSelectedReq(req)}
                      className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-md transition flex items-center gap-1.5"
                    >
                      <Upload className="w-4 h-4" />
                      <span>إدخال النتيجة ورفع التقرير</span>
                    </button>
                  ) : (
                    <a
                      href="#"
                      onClick={(e) => { e.preventDefault(); alert('تحميل ملف PDF النتيجة المرفع بالم المنظومة'); }}
                      className="bg-emerald-100 text-emerald-800 hover:bg-emerald-200 text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5"
                    >
                      <FileText className="w-4 h-4" />
                      <span>عرض تقرير الـ PDF</span>
                    </a>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Consumables Inventory (Needles, Cotton, Alcohol) */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <Boxes className="w-4 h-4 text-emerald-600" />
            <span>مستلزمات المختبر والقطن والإبر</span>
          </h3>

          <div className="space-y-3">
            {consumables.map((item) => (
              <div key={item.id} className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex justify-between items-center text-xs">
                <div>
                  <h4 className="font-bold text-slate-900">{item.name}</h4>
                  <p className="text-[11px] text-slate-500">الوحدة: {item.unit}</p>
                </div>
                <span className={`font-bold px-2.5 py-1 rounded-lg ${
                  item.quantity <= item.min_threshold
                    ? 'bg-rose-100 text-rose-800 animate-pulse'
                    : 'bg-emerald-100 text-emerald-800'
                }`}>
                  {item.quantity} {item.unit}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Result Upload Modal */}
      {selectedReq && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-indigo-900/40">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                <Upload className="w-4 h-4 text-indigo-600" />
                <span>إدخال نتيجة فحص: {selectedReq.test_name}</span>
              </h3>
              <button onClick={() => setSelectedReq(null)} className="text-slate-400 hover:text-slate-600 text-xs font-bold">
                ✕ إغلاق
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <p className="font-bold text-indigo-900">المريض: {selectedReq.patient_name} ({selectedReq.patient_code})</p>
              <div>
                <label className="block font-bold text-slate-700 mb-1">خلاصة النتيجة والتقرير الطبي:</label>
                <textarea
                  rows={4}
                  value={resultInput}
                  onChange={(e) => setResultInput(e.target.value)}
                  placeholder="أدخل قيم الفحص والنتيجة والملاحظات الطبية..."
                  className="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>
              <button
                onClick={handleCompleteTest}
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                <CheckCircle2 className="w-4 h-4" />
                <span>حفظ ورفع التقرير نهائياً</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
