'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { HrEmployee, HrAttendance, HrRoster } from '@/types/medical';
import {
  Users,
  Clock,
  CheckCircle2,
  Fingerprint,
  Calendar,
  ShieldCheck,
  Plus,
  Building2,
  DollarSign,
} from 'lucide-react';

export default function HrDashboard() {
  const [employees] = useState<HrEmployee[]>(mockData.employees);
  const [attendances, setAttendances] = useState<HrAttendance[]>(mockData.attendances);
  const [rosters, setRosters] = useState<HrRoster[]>(mockData.rosters);

  const [selectedEmpId, setSelectedEmpId] = useState<number>(2); // Default guard
  const [shift, setShift] = useState<'morning' | 'evening' | 'night'>('night');
  const [rosterDate, setRosterDate] = useState(new Date().toISOString().split('T')[0]);
  const [location, setLocation] = useState('بوابة المجمع الرئيسية والعيادات الخارجية');
  const [notes, setNotes] = useState('وجبة حراسة ليلية مع تفقّد المولدات والمخازن');

  const [fingerprintInput, setFingerprintInput] = useState('FP-101');
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const handleSimulateFingerprint = () => {
    const emp = employees.find((e) => e.fingerprint_id === fingerprintInput);
    if (!emp) {
      showNotification('خطأ: رمز البصمة غير معرف في النظام!');
      return;
    }

    const newAtt: HrAttendance = {
      id: Date.now(),
      employee_id: emp.id,
      employee_name: emp.name,
      job_title: emp.job_title,
      department: emp.department,
      fingerprint_id: emp.fingerprint_id,
      check_in: new Date().toLocaleTimeString('ar-SA'),
      check_out: undefined,
      date: new Date().toISOString().split('T')[0],
      status: 'present',
    };

    setAttendances((prev) => [newAtt, ...prev]);
    showNotification(`تم تسجيل دخول البصمة للموظف: ${emp.name} بنجاح!`);
  };

  const handleAddRoster = () => {
    const emp = employees.find((e) => e.id === Number(selectedEmpId));
    if (!emp) return;

    const newRoster: HrRoster = {
      id: Date.now(),
      employee_id: emp.id,
      employee_name: emp.name,
      job_title: emp.job_title,
      shift,
      date: rosterDate,
      location,
      notes,
    };

    setRosters((prev) => [newRoster, ...prev]);
    showNotification(`تم إضافة الموظف (${emp.name}) إلى جدول الحرس/الواجبات بنجاح!`);
  };

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {/* HR Banner Header */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex items-center justify-between border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-indigo-500/20 rounded-2xl border border-indigo-400/30">
            <Users className="w-8 h-8 text-sky-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">إدارة الموارد البشرية والحرس والأمن</h2>
            <p className="text-xs text-indigo-200">نظام البصمة الإلكتروني اليومي، جدول الحرس واللزام والوجبات، والرواتب</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Biometric Fingerprint Simulator */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <Fingerprint className="w-5 h-5 text-indigo-600" />
            <span>محاكاة قارئ البصمة الإلكترونية للموظفين</span>
          </h3>

          <div className="space-y-3 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
              <label className="block font-bold text-slate-700 mb-1">معرّف البصمة (Fingerprint ID):</label>
              <select
                value={fingerprintInput}
                onChange={(e) => setFingerprintInput(e.target.value)}
                className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-900"
              >
                {employees.map((emp) => (
                  <option key={emp.id} value={emp.fingerprint_id}>
                    {emp.name} ({emp.fingerprint_id}) - {emp.job_title}
                  </option>
                ))}
              </select>
            </div>

            <button
              onClick={handleSimulateFingerprint}
              className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
            >
              <Fingerprint className="w-4 h-4 text-sky-400" />
              <span>تسجيل قراءة البصمة الفورية</span>
            </button>
          </div>

          <div className="pt-2">
            <h4 className="font-bold text-slate-900 text-xs mb-2">سجل الحضور اليومي للبصمة:</h4>
            <div className="space-y-2 max-h-48 overflow-y-auto">
              {attendances.map((att) => (
                <div key={att.id} className="bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl flex justify-between items-center text-xs">
                  <div>
                    <p className="font-bold text-emerald-950">{att.employee_name}</p>
                    <p className="text-[10px] text-emerald-700">{att.job_title} | البصمة: {att.fingerprint_id}</p>
                  </div>
                  <span className="bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
                    دخول: {att.check_in}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Guard Duty Roster (جدول الحرس واللزام) */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center justify-between border-b border-slate-100 pb-3">
            <span className="flex items-center gap-2">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              <span>جدول الحرس واللزام والوجبات الأمنية</span>
            </span>
          </h3>

          <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label className="block font-bold text-slate-700 mb-1">الموظف / الحارس:</label>
                <select
                  value={selectedEmpId}
                  onChange={(e) => setSelectedEmpId(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2 bg-white font-bold"
                >
                  {employees.map((emp) => (
                    <option key={emp.id} value={emp.id}>
                      {emp.name} ({emp.job_title})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">الوجبة (الخفر):</label>
                <select
                  value={shift}
                  onChange={(e) => setShift(e.target.value as any)}
                  className="w-full border border-slate-300 rounded-xl p-2 bg-white font-bold"
                >
                  <option value="morning">صباحية (8:00 ص - 4:00 م)</option>
                  <option value="evening">مسائية (4:00 م - 12:00 ل)</option>
                  <option value="night">ليلية (12:00 ل - 8:00 ص)</option>
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">التاريخ:</label>
                <input
                  type="date"
                  value={rosterDate}
                  onChange={(e) => setRosterDate(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2 bg-white font-bold"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input
                type="text"
                placeholder="موقع الحراسة والواجب"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                className="border border-slate-300 rounded-xl p-2 bg-white"
              />
              <input
                type="text"
                placeholder="ملاحظات الواجب واللزام"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="border border-slate-300 rounded-xl p-2 bg-white"
              />
            </div>

            <button
              onClick={handleAddRoster}
              className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm"
            >
              <Plus className="w-4 h-4" />
              <span>إضافة لجدول الحرس والواجبات</span>
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">اسم الموظف / الحارس</th>
                  <th className="p-3">العنوان الوظيفي</th>
                  <th className="p-3">الواجب (الشفت)</th>
                  <th className="p-3">الموقع</th>
                  <th className="p-3">التاريخ</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {rosters.map((r) => (
                  <tr key={r.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-bold text-slate-900">{r.employee_name}</td>
                    <td className="p-3 text-slate-600">{r.job_title}</td>
                    <td className="p-3">
                      <span className="bg-indigo-100 text-indigo-900 font-bold px-2.5 py-1 rounded-lg">
                        {r.shift === 'night' ? 'خفر ليلي' : r.shift === 'evening' ? 'مسائي' : 'صباحي'}
                      </span>
                    </td>
                    <td className="p-3 text-slate-700">{r.location}</td>
                    <td className="p-3 text-slate-500 font-mono text-[11px]">{r.date}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
