'use client';

import React, { useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import { UserRole } from '@/types/medical';
import { Send, Clock, ShieldAlert, CheckCircle2 } from 'lucide-react';

export default function RequestRoleView() {
  const { user, requestRole } = useAuth();
  const [role, setRole] = useState<UserRole>('doctor');
  const [specialty, setSpecialty] = useState('أطباء أطفال');
  const [notes, setNotes] = useState('تقديم طلب لفتح داشبورد الطبيب ومباشرة العمل بالمجمع الطبي');
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    requestRole(role, specialty, notes);
    setSubmitted(true);
  };

  return (
    <div className="min-h-[85vh] flex items-center justify-center p-4">
      <div className="max-w-lg w-full bg-white rounded-3xl p-8 shadow-2xl border border-slate-200 text-right space-y-6">
        <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
          <div className="p-3 bg-amber-500/20 text-amber-600 rounded-2xl">
            <ShieldAlert className="w-7 h-7" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-slate-900">تقديم طلب فتح حساب وداشبورد للأدمن</h2>
            <p className="text-xs text-slate-500">تم تسجيل دخولك بـ Google ({user?.email}). حدد المهنة للارسال.</p>
          </div>
        </div>

        {/* Status banner */}
        <div className="bg-amber-50 border border-amber-200 text-amber-900 p-6 rounded-2xl text-center space-y-3">
          <Clock className="w-10 h-10 text-amber-600 mx-auto animate-pulse" />
          <h3 className="font-bold text-sm">
            {submitted ? 'تم إرسال الطلب بنجاح وهو الآن أمام الأدمن!' : 'طلبك مسجل ومعروض الآن أمام الأدمن للموافقة!'}
          </h3>
          <p className="text-xs text-amber-800 leading-relaxed">
            المهنة المطلوبة: <span className="font-bold text-indigo-900 bg-amber-200/60 px-2 py-0.5 rounded">{role}</span>
            {specialty && <span> - التخصص: <span className="font-bold text-slate-900">{specialty}</span></span>}
          </p>
          <p className="text-[11px] text-slate-500">
            عند قيام الأدمن بالضغط على "موافقة وتفعيل"، ستتمكن من الدخول المباشر إلى لوحة التحكم فوراً.
          </p>

          <div className="pt-2 flex items-center justify-center gap-2">
            <button
              onClick={() => window.location.reload()}
              className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer shadow-sm"
            >
              <CheckCircle2 className="w-4 h-4 text-emerald-300" />
              <span>فحص حالة الموافقة وتحديث</span>
            </button>
            <button
              type="button"
              onClick={() => setSubmitted(false)}
              className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs transition cursor-pointer"
            >
              تعديل بيانات الطلب أو المهنة
            </button>
          </div>
        </div>

        {/* Form to submit or modify requested role */}
        {!submitted && (
          <form onSubmit={handleSubmit} className="space-y-4 text-xs pt-2 border-t border-slate-100">
            <div>
              <label className="block font-bold text-slate-700 mb-1">المهنة والداشبورد المطلوب:</label>
              <select
                value={role}
                onChange={(e) => setRole(e.target.value as UserRole)}
                className="w-full border border-slate-300 rounded-xl p-3 bg-white font-bold text-slate-900 text-xs focus:ring-2 focus:ring-indigo-600 focus:outline-none"
              >
                <option value="doctor">طبيب (داشبورد التشخيص والتحاليل والوصفات)</option>
                <option value="pharmacist">صيدلاني (داشبورد الوصفات وصرف الدواء وتنبيهات المخزون)</option>
                <option value="lab_tech">فني مختبر وأشعة (داشبورد التحاليل ورفع التقارير)</option>
                <option value="storekeeper">أمين مخزن (داشبورد المخزون وتجهيز الأدوية)</option>
                <option value="accountant">محاسب (داشبورد القيود والخدمات والرواتب والميزانية)</option>
                <option value="hr">مسؤول موارد بشرية (داشبورد البصمة وجدول الحرس والأمن)</option>
              </select>
            </div>

            <div>
              <label className="block font-bold text-slate-700 mb-1">التخصص والفرع الطبي:</label>
              <input
                type="text"
                value={specialty}
                onChange={(e) => setSpecialty(e.target.value)}
                placeholder="مثال: أطباء أطفال، باطنية وغدد، نسائية، صيدلة سريرية..."
                className="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
              />
            </div>

            <div>
              <label className="block font-bold text-slate-700 mb-1">ملاحظات إضافية للأدمن:</label>
              <textarea
                rows={2}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="اكتب أي ملاحظات ترغب بإرسالها للمدير..."
                className="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
              />
            </div>

            <button
              type="submit"
              className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-3.5 rounded-xl transition shadow-xl flex items-center justify-center gap-2 text-xs cursor-pointer"
            >
              <Send className="w-4 h-4 text-sky-400" />
              <span>تحديث وحفظ الطلب للأدمن فوراً</span>
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
