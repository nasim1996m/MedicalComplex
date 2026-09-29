'use client';

import React, { useCallback, useEffect, useState } from 'react';
import { Patient } from '@/types/medical';
import { useAuth } from '@/context/AuthContext';
import { createPatient, saveConsultation, searchPatients, todayISO } from '@/services/medicalApi';
import PatientRecordsModal from '@/components/patients/PatientRecordsModal';
import {
  Stethoscope,
  Activity,
  Search,
  Plus,
  FlaskConical,
  Pill,
  CheckCircle2,
  User,
  UserPlus,
  CalendarRange,
  Loader2,
  AlertTriangle,
  X,
  Save,
} from 'lucide-react';

export default function DoctorDashboard() {
  const { user } = useAuth();
  const [patients, setPatients] = useState<Patient[]>([]);
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null);
  const [diagnosisInput, setDiagnosisInput] = useState('');
  const [loadingPatients, setLoadingPatients] = useState(true);
  const [saving, setSaving] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Add Patient Modal State
  const [showAddPatientModal, setShowAddPatientModal] = useState(false);
  const [newPatientName, setNewPatientName] = useState('');
  const [newPatientAge, setNewPatientAge] = useState<number>(30);
  const [newPatientGender, setNewPatientGender] = useState<'male' | 'female'>('male');
  const [newPatientPhone, setNewPatientPhone] = useState('');
  const [newPatientHistory, setNewPatientHistory] = useState('');
  const [newPatientDiagnosis, setNewPatientDiagnosis] = useState('');

  // Manual Text Inputs requested by Doctor
  const [manualTestName, setManualTestName] = useState('');
  const [labItems, setLabItems] = useState<{ test_name: string }[]>([]);
  const [manualMedicineName, setManualMedicineName] = useState('');
  const [dosage, setDosage] = useState('كبسولة كل 8 ساعات');
  const [duration, setDuration] = useState('7 أيام');
  const [prescriptionItems, setPrescriptionItems] = useState<{ medicine_name: string; dosage: string; duration: string }[]>([]);

  const [searchHistoryQuery, setSearchHistoryQuery] = useState('');
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [modalQuery, setModalQuery] = useState('');
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 4000);
  };

  const showError = (err: unknown) => {
    setErrorMsg(err instanceof Error ? err.message : 'حدث خطأ غير متوقع');
  };

  // قائمة مراجعي اليوم من قاعدة البيانات
  const applyTodayPatients = useCallback((list: Patient[]) => {
    setPatients((prev) => {
      // keep patients opened in this session that have no visit today yet
      const extra = prev.filter((p) => !list.some((l) => l.id === p.id));
      return [...extra, ...list];
    });
    setSelectedPatient((current) => current ?? list[0] ?? null);
  }, []);

  const loadTodayPatients = useCallback(
    () => searchPatients({ from: todayISO(), to: todayISO() }).then(applyTodayPatients),
    [applyTodayPatients]
  );

  useEffect(() => {
    let cancelled = false;
    searchPatients({ from: todayISO(), to: todayISO() })
      .then((list) => !cancelled && applyTodayPatients(list))
      .catch((err) => !cancelled && showError(err))
      .finally(() => !cancelled && setLoadingPatients(false));
    return () => {
      cancelled = true;
    };
  }, [applyTodayPatients]);

  const openPatient = (patient: Patient) => {
    setPatients((prev) => (prev.some((p) => p.id === patient.id) ? prev : [patient, ...prev]));
    setSelectedPatient(patient);
  };

  const handleCreatePatient = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPatientName.trim()) return;

    setSaving(true);
    try {
      const newPatient = await createPatient({
        name: newPatientName.trim(),
        age: newPatientAge,
        gender: newPatientGender,
        phone: newPatientPhone.trim() || undefined,
        medical_history: newPatientHistory.trim() || undefined,
      });

      openPatient(newPatient);
      setDiagnosisInput(newPatientDiagnosis);
      setLabItems([]);
      setPrescriptionItems([]);

      setNewPatientName('');
      setNewPatientPhone('');
      setNewPatientHistory('');
      setNewPatientDiagnosis('');
      setShowAddPatientModal(false);
      setErrorMsg(null);

      showNotification(`تم تسجيل المريض (${newPatient.name}) في قاعدة البيانات بكود ${newPatient.patient_code}. أكمل التشخيص ثم اضغط "حفظ الكشف".`);
    } catch (err) {
      showError(err);
    } finally {
      setSaving(false);
    }
  };

  const handleAddMedicineToRx = () => {
    if (!manualMedicineName.trim()) return;
    setPrescriptionItems((prev) => [...prev, { medicine_name: manualMedicineName.trim(), dosage, duration }]);
    setManualMedicineName('');
  };

  const handleAddLabTest = () => {
    if (!manualTestName.trim()) return;
    setLabItems((prev) => [...prev, { test_name: manualTestName.trim() }]);
    setManualTestName('');
  };

  // حفظ الكشف + التحاليل + الوصفة في قاعدة البيانات دفعة واحدة
  const handleSaveConsultation = async () => {
    if (!selectedPatient) return;
    if (!user) {
      setErrorMsg('يجب تسجيل الدخول أولاً');
      return;
    }
    if (!diagnosisInput.trim()) {
      setErrorMsg('اكتب التشخيص الطبي قبل حفظ الكشف.');
      return;
    }

    setSaving(true);
    try {
      const result = await saveConsultation({
        doctor_id: user.id,
        patient_id: selectedPatient.id,
        diagnosis: diagnosisInput.trim(),
        lab_requests: labItems,
        prescription_items: prescriptionItems,
      });

      const parts = ['تم حفظ الكشف في السجل الطبي'];
      if (result.lab_request_ids.length) parts.push(`وإرسال ${result.lab_request_ids.length} فحص للمختبر`);
      if (result.prescription_id) parts.push('وإرسال الوصفة للصيدلية');
      showNotification(`${parts.join(' ')} للمريض ${selectedPatient.name}.`);

      setDiagnosisInput('');
      setLabItems([]);
      setPrescriptionItems([]);
      setErrorMsg(null);
      await loadTodayPatients();
    } catch (err) {
      showError(err);
    } finally {
      setSaving(false);
    }
  };

  const openRecords = (query: string) => {
    setModalQuery(query);
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

      {errorMsg && (
        <div className="bg-rose-50 text-rose-800 p-4 rounded-2xl border border-rose-200 shadow flex items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
            <p className="text-xs font-bold">{errorMsg}</p>
          </div>
          <button onClick={() => setErrorMsg(null)} className="text-rose-400 hover:text-rose-700">
            <X className="w-4 h-4" />
          </button>
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
              onKeyDown={(e) => {
                if (e.key === 'Enter') openRecords(searchHistoryQuery);
              }}
              className="bg-indigo-950/80 border border-indigo-700/50 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-400 w-full md:w-64"
            />
            <button
              onClick={() => openRecords(searchHistoryQuery)}
              className="bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shrink-0"
            >
              <Search className="w-4 h-4" />
              <span>السجل المشترك</span>
            </button>
            <button
              onClick={() => openRecords('')}
              title="البحث عن المراجعين خلال فترة (من تاريخ - إلى تاريخ)"
              className="bg-indigo-700 hover:bg-indigo-600 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shrink-0"
            >
              <CalendarRange className="w-4 h-4" />
              <span>المراجعون حسب الفترة</span>
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
            {loadingPatients && (
              <div className="py-6 flex justify-center">
                <Loader2 className="w-5 h-5 animate-spin text-indigo-600" />
              </div>
            )}
            {!loadingPatients && patients.length === 0 && (
              <p className="text-xs text-slate-500 text-center py-6 leading-relaxed">
                لا يوجد مراجعون اليوم بعد.
                <br />
                أضف مريضاً جديداً أو ابحث في السجل المشترك لفتح مريض سابق.
              </p>
            )}
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
                    {patient.visit_count ? `${patient.visit_count} كشف اليوم` : 'جاهز للفحص'}
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
                <span>تشخيص حالة المريض: {selectedPatient?.name ?? 'اختر مريضاً من القائمة'}</span>
              </h3>
              <span className="text-xs bg-indigo-100 text-indigo-900 font-bold px-3 py-1 rounded-full">
                كود: {selectedPatient?.patient_code ?? '—'}
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
                    onClick={handleAddLabTest}
                    className="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2 text-xs shadow-md"
                  >
                    <Plus className="w-3.5 h-3.5 text-sky-400" />
                    <span>إضافة الفحص لطلب المختبر والأشعة</span>
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

              {/* Lab & Prescription Preview */}
              {(labItems.length > 0 || prescriptionItems.length > 0) && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {labItems.length > 0 && (
                    <div className="bg-sky-50 border border-sky-200 p-4 rounded-xl space-y-2">
                      <h4 className="font-bold text-sky-900 text-xs">الفحوصات المطلوبة ({labItems.length}):</h4>
                      <ul className="space-y-1">
                        {labItems.map((item, idx) => (
                          <li key={idx} className="flex justify-between items-center text-xs text-sky-950 bg-white p-2 rounded-lg border">
                            <span className="font-bold">{item.test_name}</span>
                            <button onClick={() => setLabItems((prev) => prev.filter((_, i) => i !== idx))} className="text-rose-500 hover:text-rose-700">
                              <X className="w-3.5 h-3.5" />
                            </button>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                  {prescriptionItems.length > 0 && (
                    <div className="bg-indigo-50 border border-indigo-200 p-4 rounded-xl space-y-2">
                      <h4 className="font-bold text-indigo-900 text-xs">أدوية الوصفة ({prescriptionItems.length}):</h4>
                      <ul className="space-y-1">
                        {prescriptionItems.map((item, idx) => (
                          <li key={idx} className="flex justify-between items-center gap-2 text-xs text-indigo-950 bg-white p-2 rounded-lg border">
                            <span className="font-bold">{item.medicine_name}</span>
                            <span className="text-slate-600 flex items-center gap-2">
                              {item.dosage} - {item.duration}
                              <button onClick={() => setPrescriptionItems((prev) => prev.filter((_, i) => i !== idx))} className="text-rose-500 hover:text-rose-700">
                                <X className="w-3.5 h-3.5" />
                              </button>
                            </span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              )}

              {/* Save everything to the database */}
              <button
                onClick={handleSaveConsultation}
                disabled={!selectedPatient || saving}
                className="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition text-sm flex items-center justify-center gap-2 shadow-md"
              >
                {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                <span>
                  حفظ الكشف في السجل
                  {labItems.length > 0 ? ' + إرسال الفحوصات للمختبر' : ''}
                  {prescriptionItems.length > 0 ? ' + إرسال الوصفة للصيدلية' : ''}
                </span>
              </button>
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
                <label className="block font-bold text-slate-700 mb-1">رقم الهاتف (يساعد في البحث لاحقاً):</label>
                <input
                  type="tel"
                  placeholder="مثال: 07701234567"
                  value={newPatientPhone}
                  onChange={(e) => setNewPatientPhone(e.target.value)}
                  className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-mono focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                />
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
                disabled={saving}
                className="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-xs mt-2"
              >
                {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle2 className="w-4 h-4" />}
                <span>حفظ وتأكيد تسجيل المريض جديد</span>
              </button>
            </form>
          </div>
        </div>
      )}

      {/* Shared Patient Records (search by name / date range + full history) */}
      <PatientRecordsModal
        open={showHistoryModal}
        onClose={() => setShowHistoryModal(false)}
        initialQuery={modalQuery}
        onSelectPatient={openPatient}
      />
    </div>
  );
}
