'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { Patient, Visit, LabRequest, Prescription } from '@/types/medical';
import {
  Stethoscope,
  Activity,
  FileText,
  Search,
  Plus,
  Send,
  FlaskConical,
  Pill,
  CheckCircle2,
  Clock,
  User,
  UserPlus,
  HeartPulse,
} from 'lucide-react';

export default function DoctorDashboard() {
  const [patients, setPatients] = useState<Patient[]>(mockData.patients);
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(mockData.patients[0]);
  const [diagnosisInput, setDiagnosisInput] = useState('');

  // Add Patient Modal State
  const [showAddPatientModal, setShowAddPatientModal] = useState(false);
  const [newPatientName, setNewPatientName] = useState('');
  const [newPatientAge, setNewPatientAge] = useState<number>(30);
  const [newPatientGender, setNewPatientGender] = useState<'male' | 'female'>('male');
  const [newPatientHistory, setNewPatientHistory] = useState('');
  const [newPatientDiagnosis, setNewPatientDiagnosis] = useState('');

  // Manual Text Inputs requested by Doctor
  const [manualTestName, setManualTestName] = useState('');
  const [manualMedicineName, setManualMedicineName] = useState('');
  const [dosage, setDosage] = useState('كبسولة كل 8 ساعات');
  const [duration, setDuration] = useState('7 أيام');
  const [prescriptionItems, setPrescriptionItems] = useState<{ medicine_name: string; dosage: string; duration: string }[]>([]);

  const [searchHistoryQuery, setSearchHistoryQuery] = useState('');
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [historyResult, setHistoryResult] = useState<Patient | null>(null);
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const handleCreatePatient = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPatientName.trim()) return;

    const newPatient: Patient = {
      id: Date.now(),
      patient_code: `PAT-100${patients.length + 1}`,
      name: newPatientName,
      gender: newPatientGender,
      age: newPatientAge,
      medical_history: newPatientHistory,
    };

    setPatients((prev) => [newPatient, ...prev]);
    setSelectedPatient(newPatient);
    setDiagnosisInput(newPatientDiagnosis);

    setNewPatientName('');
    setNewPatientHistory('');
    setNewPatientDiagnosis('');
    setShowAddPatientModal(false);

    showNotification(`تم تسديد وتسجيل المريض الجديد (${newPatient.name}) بكود ${newPatient.patient_code} بنجاح!`);
  };

  const handleAddMedicineToRx = () => {
    if (!manualMedicineName.trim()) return;
    setPrescriptionItems((prev) => [
      ...prev,
      { medicine_name: manualMedicineName, dosage, duration },
    ]);
    setManualMedicineName('');
  };

  const handleSendPrescription = () => {
    if (!selectedPatient || prescriptionItems.length === 0) return;

    const newRx: Prescription = {
      id: Date.now(),
      visit_id: 1,
      doctor_id: 2,
      doctor_name: 'د. أحمد علي السامرائي',
      patient_id: selectedPatient.id,
      patient_name: selectedPatient.name,
      patient_code: selectedPatient.patient_code,
      status: 'pending',
      items: prescriptionItems.map((item, idx) => ({
        medicine_id: idx + 1,
        medicine_name: item.medicine_name,
        dosage: item.dosage,
        duration: item.duration,
      })),
      created_at: new Date().toLocaleTimeString('ar-SA'),
    };

    mockData.prescriptions.unshift(newRx);
    setPrescriptionItems([]);
    showNotification('تم إرسال الوصفة الطبية المكتوبة يدوياً بفرز فوري إلى الصيدلية بنجاح!');
  };

  const handleOrderLabTest = () => {
    if (!selectedPatient || !manualTestName.trim()) return;

    const newReq: LabRequest = {
      id: Date.now(),
      visit_id: 1,
      patient_id: selectedPatient.id,
      patient_name: selectedPatient.name,
      patient_code: selectedPatient.patient_code,
      age: selectedPatient.age,
      doctor_id: 2,
      doctor_name: 'د. أحمد علي السامرائي',
      test_type_id: 1,
      test_name: manualTestName,
      test_category: 'blood',
      test_price: 15000,
      status: 'pending',
      created_at: new Date().toLocaleTimeString('ar-SA'),
    };

    mockData.labRequests.unshift(newReq);
    showNotification(`تم إرسال طلب فحص (${manualTestName}) المكتوب يدوياً إلى قسم المختبر والأشعة والتخطيط بنجاح!`);
    setManualTestName('');
  };

  const handleSearchPatientHistory = () => {
    const found = patients.find(
      (p) => p.name.includes(searchHistoryQuery) || p.patient_code.includes(searchHistoryQuery)
    );
    setHistoryResult(found || null);
    setShowHistoryModal(true);
  };

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {/* Doctor Header Control */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-4 border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-indigo-500/20 rounded-2xl border border-indigo-400/30">
            <Stethoscope className="w-8 h-8 text-sky-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">عيادة التشخيص الطبي التخصصية</h2>
            <p className="text-xs text-indigo-200">فحص المرضى، كتابة طلبات الفحوصات والوصفات يدوياً، والسجل المشترك</p>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-3 flex-wrap w-full md:w-auto justify-end">
          <button
            onClick={() => setShowAddPatientModal(true)}
            className="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-lg shrink-0"
          >
            <UserPlus className="w-4 h-4" />
            <span>+ إضافة مريض جديد</span>
          </button>

          <div className="flex items-center gap-2 w-full md:w-auto">
            <input
              type="text"
              placeholder="بحث في سجل مريض سابق (الاسم أو الكود)..."
              value={searchHistoryQuery}
              onChange={(e) => setSearchHistoryQuery(e.target.value)}
              className="bg-indigo-950/80 border border-indigo-700/50 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-400 w-full md:w-64"
            />
            <button
              onClick={handleSearchPatientHistory}
              className="bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shrink-0"
            >
              <Search className="w-4 h-4" />
              <span>السجل المشترك</span>
            </button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Patient Selection Queue */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-5">
          <h3 className="font-bold text-slate-900 text-sm mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
            <span className="flex items-center gap-2">
              <User className="w-4 h-4 text-indigo-600" />
              <span>قائمة المرضى والمراجعين اليوم</span>
            </span>
            <span className="text-xs bg-indigo-100 text-indigo-900 font-bold px-2.5 py-0.5 rounded-full">
              {patients.length} مريض
            </span>
          </h3>
          <div className="space-y-3">
            {patients.map((patient) => (
              <div
                key={patient.id}
                onClick={() => setSelectedPatient(patient)}
                className={`p-3.5 rounded-xl border cursor-pointer transition ${
                  selectedPatient?.id === patient.id
                    ? 'bg-indigo-950 text-white border-indigo-600 shadow-md'
                    : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-800'
                }`}
              >
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-bold text-xs">{patient.name}</h4>
                    <p className={`text-[11px] ${selectedPatient?.id === patient.id ? 'text-indigo-200' : 'text-slate-500'}`}>
                      الكود: {patient.patient_code} | العمر: {patient.age} سنة
                    </p>
                  </div>
                  <span className="text-[10px] bg-sky-500/20 text-sky-400 font-bold px-2 py-0.5 rounded-md">
                    جاهز للفحص
                  </span>
                </div>
                {patient.medical_history && (
                  <p className={`text-[10px] mt-2 pt-1 border-t ${selectedPatient?.id === patient.id ? 'border-indigo-800 text-indigo-300' : 'border-slate-200 text-slate-500'}`}>
                    سابق: {patient.medical_history}
                  </p>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Diagnosis & Manual Consultation Form */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
              <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                <Activity className="w-4 h-4 text-emerald-600" />
                <span>تشخيص حالة المريض: {selectedPatient?.name}</span>
              </h3>
              <span className="text-xs bg-indigo-100 text-indigo-900 font-bold px-3 py-1 rounded-full">
                كود: {selectedPatient?.patient_code}
              </span>
            </div>

            <div className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">التشخيص الطبي والتوصية:</label>
                <textarea
                  rows={3}
                  value={diagnosisInput}
                  onChange={(e) => setDiagnosisInput(e.target.value)}
                  placeholder="أدخل التقييم والتشخيص الطبي التفصيلي للمريض..."
                  className="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              {/* Action Buttons: Manual Order Tests / Prescriptions */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                {/* Manual Lab & Scan Order Box */}
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                  <h4 className="font-bold text-indigo-950 flex items-center gap-1.5">
                    <FlaskConical className="w-4 h-4 text-indigo-600" />
                    <span>كتابة طلب الفحوصات يدوياً (دم / أشعة / إيكو / تخطيط)</span>
                  </h4>
                  <input
                    type="text"
                    placeholder="أدخل اسم الفحص المطلوب يدوياً (مثال: فحص دم شامل CBC، أشعة صدر X-Ray...)"
                    value={manualTestName}
                    onChange={(e) => setManualTestName(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                  <button
                    onClick={handleOrderLabTest}
                    className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2 text-xs shadow-md"
                  >
                    <Send className="w-3.5 h-3.5 text-sky-400" />
                    <span>إرسال الطلب للمختبر والأشعة</span>
                  </button>
                </div>

                {/* Manual Prescription Box */}
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                  <h4 className="font-bold text-emerald-950 flex items-center gap-1.5">
                    <Pill className="w-4 h-4 text-emerald-600" />
                    <span>كتابة وصفة الدواء يدوياً للصيدلية</span>
                  </h4>
                  <input
                    type="text"
                    placeholder="أدخل اسم الدواء يدوياً (مثال: Amoxicillin 500mg)..."
                    value={manualMedicineName}
                    onChange={(e) => setManualMedicineName(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                  />
                  <div className="grid grid-cols-2 gap-2">
                    <input
                      type="text"
                      placeholder="الجرعة"
                      value={dosage}
                      onChange={(e) => setDosage(e.target.value)}
                      className="border border-slate-300 rounded-lg p-2 bg-white text-xs"
                    />
                    <input
                      type="text"
                      placeholder="المدة"
                      value={duration}
                      onChange={(e) => setDuration(e.target.value)}
                      className="border border-slate-300 rounded-lg p-2 bg-white text-xs"
                    />
                  </div>
                  <button
                    onClick={handleAddMedicineToRx}
                    className="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 rounded-lg transition text-xs flex items-center justify-center gap-1"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>إضافة دواء مكتوب يدوياً للوصفة</span>
                  </button>
                </div>
              </div>

              {/* Prescription Items Preview & Send */}
              {prescriptionItems.length > 0 && (
                <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-xl space-y-2">
                  <h4 className="font-bold text-indigo-900 text-xs">قائمة الأدوية المكتوبة يدوياً بالوصفة الحالية:</h4>
                  <ul className="space-y-1">
                    {prescriptionItems.map((item, idx) => (
                      <li key={idx} className="flex justify-between items-center text-xs text-indigo-950 bg-white p-2 rounded-lg border">
                        <span className="font-bold">{item.medicine_name}</span>
                        <span className="text-slate-600">{item.dosage} - {item.duration}</span>
                      </li>
                    ))}
                  </ul>
                  <button
                    onClick={handleSendPrescription}
                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl transition text-xs flex items-center justify-center gap-2 shadow-md mt-2"
                  >
                    <Send className="w-4 h-4" />
                    <span>إرسال الوصفة للصيدلية فوراً</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Add Patient Modal */}
      {showAddPatientModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-indigo-900/40 text-right">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-base flex items-center gap-2">
                <UserPlus className="w-5 h-5 text-emerald-600" />
                <span>تسجيل وإضافة مريض جديد بالعيادة</span>
              </h3>
              <button onClick={() => setShowAddPatientModal(false)} className="text-slate-400 hover:text-slate-600 text-xs font-bold">
                ✕ إغلاق
              </button>
            </div>

            <form onSubmit={handleCreatePatient} className="space-y-3 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">اسم المريض الثلاثي:</label>
                <input
                  type="text"
                  required
                  placeholder="أدخل اسم المريض الكامل..."
                  value={newPatientName}
                  onChange={(e) => setNewPatientName(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">العمر:</label>
                  <input
                    type="number"
                    required
                    placeholder="العمر بالسنين"
                    value={newPatientAge}
                    onChange={(e) => setNewPatientAge(Number(e.target.value))}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">الجندر:</label>
                  <select
                    value={newPatientGender}
                    onChange={(e) => setNewPatientGender(e.target.value as any)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  >
                    <option value="male">ذكر</option>
                    <option value="female">أنثى</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">تاريخ المرض والأمراض المزمنة السابقة (إن وجد):</label>
                <input
                  type="text"
                  placeholder="مثال: ضغط دم، سكري، حساسية مسبقة..."
                  value={newPatientHistory}
                  onChange={(e) => setNewPatientHistory(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">التشخيص الحالي للحالة:</label>
                <textarea
                  rows={3}
                  placeholder="أدخل تشخيصك الطبي الأولي وحالة المريض..."
                  value={newPatientDiagnosis}
                  onChange={(e) => setNewPatientDiagnosis(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
              </div>

              <button
                type="submit"
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-xs mt-2"
              >
                <CheckCircle2 className="w-4 h-4" />
                <span>حفظ وتأكيد تسجيل المريض جديد</span>
              </button>
            </form>
          </div>
        </div>
      )}

      {/* History Shared Modal */}
      {showHistoryModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-2xl border border-indigo-900/40 text-right">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-base flex items-center gap-2">
                <FileText className="w-5 h-5 text-indigo-600" />
                <span>السجل الطبي التشاركي للمريض</span>
              </h3>
              <button onClick={() => setShowHistoryModal(false)} className="text-slate-400 hover:text-slate-600 text-sm font-bold">
                ✕ إغلاق
              </button>
            </div>

            {historyResult ? (
              <div className="space-y-3 text-xs">
                <div className="bg-indigo-50 p-3 rounded-xl">
                  <p className="font-bold text-indigo-900">{historyResult.name} ({historyResult.patient_code})</p>
                  <p className="text-slate-600">العمر: {historyResult.age} | الجندر: {historyResult.gender}</p>
                  <p className="text-rose-700 font-semibold mt-1">تاريخ المرض والأمراض المزمنة: {historyResult.medical_history}</p>
                </div>
                <div className="bg-slate-50 p-3 rounded-xl space-y-2">
                  <h4 className="font-bold text-slate-800">الكشوفات السابقة بالمنظومة:</h4>
                  <p className="text-slate-600 bg-white p-2 rounded-lg border">
                    تشخيص د. أحمد علي: ارتفاع بالضغط الشرياني واضطراب معدل ضربات القلب
                  </p>
                </div>
              </div>
            ) : (
              <p className="text-xs text-rose-600 py-6 text-center">لم يتم العثور على مريض مطابق في السجل المشترك.</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
