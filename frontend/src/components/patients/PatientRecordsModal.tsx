'use client';

import React, { useCallback, useEffect, useState } from 'react';
import { Patient, PatientHistory } from '@/types/medical';
import { searchPatients, getPatientHistory, todayISO } from '@/services/medicalApi';
import {
  FileText,
  Search,
  CalendarRange,
  Loader2,
  ArrowRight,
  Stethoscope,
  FlaskConical,
  Pill,
  AlertTriangle,
  UserCheck,
} from 'lucide-react';

interface Props {
  open: boolean;
  onClose: () => void;
  initialQuery?: string;
  /** When provided, a button lets the doctor open the patient for a new consultation */
  onSelectPatient?: (patient: Patient) => void;
}

function daysAgoISO(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatDate(value?: string | null): string {
  return value ? String(value).slice(0, 10) : '—';
}

/**
 * السجل الطبي المشترك: بحث بالاسم/الكود/الهاتف وبالفترة الزمنية، وعرض السجل الكامل للمريض
 * (الكشوفات، التحاليل ونتائجها، الوصفات) من قاعدة البيانات.
 */
export default function PatientRecordsModal({ open, ...rest }: Props) {
  return open ? <RecordsDialog {...rest} /> : null;
}

function RecordsDialog({ onClose, initialQuery = '', onSelectPatient }: Omit<Props, 'open'>) {
  const [q, setQ] = useState(initialQuery);
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [results, setResults] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searched, setSearched] = useState(false);

  const [history, setHistory] = useState<PatientHistory | null>(null);
  const [historyLoading, setHistoryLoading] = useState(false);

  const runSearch = useCallback(async (params: { q: string; from: string; to: string }) => {
    setLoading(true);
    setError(null);
    setHistory(null);
    try {
      setResults(await searchPatients(params));
      setSearched(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'فشل البحث');
      setResults([]);
    } finally {
      setLoading(false);
    }
  }, []);

  // Search automatically with the text typed in the header when the dialog opens
  useEffect(() => {
    let cancelled = false;
    searchPatients({ q: initialQuery })
      .then((list) => {
        if (cancelled) return;
        setResults(list);
        setSearched(true);
      })
      .catch((err) => !cancelled && setError(err instanceof Error ? err.message : 'فشل البحث'))
      .finally(() => !cancelled && setLoading(false));
    return () => {
      cancelled = true;
    };
  }, [initialQuery]);

  const applyRange = (rangeFrom: string, rangeTo: string) => {
    setFrom(rangeFrom);
    setTo(rangeTo);
    runSearch({ q, from: rangeFrom, to: rangeTo });
  };

  const openHistory = async (patient: Patient) => {
    setHistoryLoading(true);
    setError(null);
    try {
      setHistory(await getPatientHistory(patient.id));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'فشل تحميل السجل');
    } finally {
      setHistoryLoading(false);
    }
  };

  const rangeActive = Boolean(from || to);

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-6 space-y-4 shadow-2xl border border-indigo-900/40 text-right">
        <div className="flex justify-between items-center border-b pb-3">
          <h3 className="font-bold text-slate-900 text-base flex items-center gap-2">
            <FileText className="w-5 h-5 text-indigo-600" />
            <span>السجل الطبي التشاركي للمرضى</span>
          </h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 text-sm font-bold">
            ✕ إغلاق
          </button>
        </div>

        {error && (
          <div className="p-3 text-xs text-rose-800 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {history ? (
          <HistoryView history={history} onBack={() => setHistory(null)} onSelectPatient={onSelectPatient} onClose={onClose} />
        ) : (
          <>
            {/* Filters */}
            <form
              onSubmit={(e) => {
                e.preventDefault();
                runSearch({ q, from, to });
              }}
              className="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3 text-xs"
            >
              <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div className="md:col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">اسم المريض أو الكود أو رقم الهاتف:</label>
                  <input
                    type="text"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder="مثال: مريم كامل، PAT-1003، 0770..."
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">من تاريخ:</label>
                  <input
                    type="date"
                    value={from}
                    max={to || undefined}
                    onChange={(e) => setFrom(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">إلى تاريخ:</label>
                  <input
                    type="date"
                    value={to}
                    min={from || undefined}
                    onChange={(e) => setTo(e.target.value)}
                    className="w-full border border-slate-300 rounded-xl p-2.5 bg-white focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                  />
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-2">
                <button
                  type="submit"
                  className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5"
                >
                  <Search className="w-4 h-4" />
                  <span>بحث</span>
                </button>
                <span className="text-slate-400 mx-1">|</span>
                <CalendarRange className="w-4 h-4 text-slate-500" />
                <button type="button" onClick={() => applyRange(todayISO(), todayISO())} className="bg-white border border-slate-300 hover:bg-slate-100 px-3 py-1.5 rounded-lg font-bold text-slate-700">
                  مراجعو اليوم
                </button>
                <button type="button" onClick={() => applyRange(daysAgoISO(6), todayISO())} className="bg-white border border-slate-300 hover:bg-slate-100 px-3 py-1.5 rounded-lg font-bold text-slate-700">
                  آخر 7 أيام
                </button>
                <button type="button" onClick={() => applyRange(daysAgoISO(29), todayISO())} className="bg-white border border-slate-300 hover:bg-slate-100 px-3 py-1.5 rounded-lg font-bold text-slate-700">
                  آخر 30 يوم
                </button>
                {rangeActive && (
                  <button type="button" onClick={() => applyRange('', '')} className="text-rose-600 hover:text-rose-800 font-bold px-2">
                    إلغاء الفترة
                  </button>
                )}
              </div>
            </form>

            {/* Results */}
            {loading || historyLoading ? (
              <div className="py-8 flex justify-center">
                <Loader2 className="w-6 h-6 animate-spin text-indigo-600" />
              </div>
            ) : results.length > 0 ? (
              <div className="space-y-2">
                <p className="text-xs text-slate-500 font-bold">
                  {rangeActive
                    ? `${results.length} مريض راجعوا ${from ? `من ${from}` : ''} ${to ? `إلى ${to}` : ''}`
                    : `${results.length} مريض`}
                </p>
                <div className="overflow-x-auto">
                  <table className="w-full text-right text-xs">
                    <thead className="bg-[#0f172a] text-white">
                      <tr>
                        <th className="p-2.5">المريض</th>
                        <th className="p-2.5">الكود</th>
                        <th className="p-2.5">العمر</th>
                        <th className="p-2.5">الهاتف</th>
                        <th className="p-2.5">{rangeActive ? 'زيارات بالفترة' : 'عدد الزيارات'}</th>
                        <th className="p-2.5">آخر زيارة</th>
                        <th className="p-2.5"></th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {results.map((p) => (
                        <tr key={p.id} className="hover:bg-slate-50">
                          <td className="p-2.5 font-bold text-slate-900">{p.name}</td>
                          <td className="p-2.5 text-indigo-800 font-bold">{p.patient_code}</td>
                          <td className="p-2.5">{p.age}</td>
                          <td className="p-2.5 font-mono">{p.phone || '—'}</td>
                          <td className="p-2.5">{p.visit_count ?? 0}</td>
                          <td className="p-2.5 font-mono">{formatDate(p.last_visit_date)}</td>
                          <td className="p-2.5">
                            <button
                              onClick={() => openHistory(p)}
                              className="bg-sky-600 hover:bg-sky-500 text-white font-bold px-3 py-1.5 rounded-lg transition"
                            >
                              عرض السجل
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            ) : (
              searched && !error && (
                <p className="text-xs text-rose-600 py-6 text-center">لم يتم العثور على مريض مطابق في السجل المشترك.</p>
              )
            )}
          </>
        )}
      </div>
    </div>
  );
}

function HistoryView({
  history,
  onBack,
  onSelectPatient,
  onClose,
}: {
  history: PatientHistory;
  onBack: () => void;
  onSelectPatient?: (patient: Patient) => void;
  onClose: () => void;
}) {
  const { patient, visits, lab_results, prescriptions } = history;

  return (
    <div className="space-y-4 text-xs">
      <div className="flex items-center justify-between gap-2">
        <button onClick={onBack} className="text-indigo-700 hover:text-indigo-900 font-bold flex items-center gap-1">
          <ArrowRight className="w-4 h-4" />
          <span>رجوع لنتائج البحث</span>
        </button>
        {onSelectPatient && (
          <button
            onClick={() => {
              onSelectPatient(patient);
              onClose();
            }}
            className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2 rounded-xl flex items-center gap-1.5"
          >
            <UserCheck className="w-4 h-4" />
            <span>فتح المريض لكشف جديد</span>
          </button>
        )}
      </div>

      <div className="bg-indigo-50 p-3 rounded-xl">
        <p className="font-bold text-indigo-900 text-sm">
          {patient.name} ({patient.patient_code})
        </p>
        <p className="text-slate-600">
          العمر: {patient.age} | الجنس: {patient.gender === 'female' ? 'أنثى' : 'ذكر'} {patient.phone ? `| الهاتف: ${patient.phone}` : ''}
        </p>
        {patient.medical_history && (
          <p className="text-rose-700 font-semibold mt-1">الأمراض المزمنة والتاريخ المرضي: {patient.medical_history}</p>
        )}
      </div>

      <section className="bg-slate-50 p-3 rounded-xl space-y-2">
        <h4 className="font-bold text-slate-800 flex items-center gap-1.5">
          <Stethoscope className="w-4 h-4 text-indigo-600" />
          <span>الكشوفات والتشخيصات ({visits.length})</span>
        </h4>
        {visits.length === 0 && <p className="text-slate-500">لا توجد كشوفات مسجلة.</p>}
        {visits.map((v) => (
          <div key={v.id} className="bg-white p-2.5 rounded-lg border space-y-1">
            <div className="flex justify-between text-slate-500">
              <span>
                {v.doctor_name} {v.doctor_specialty ? `— ${v.doctor_specialty}` : ''}
              </span>
              <span className="font-mono">{formatDate(v.visit_date)}</span>
            </div>
            <p className="text-slate-900 font-semibold">{v.diagnosis}</p>
            {v.notes && <p className="text-slate-600">ملاحظات: {v.notes}</p>}
          </div>
        ))}
      </section>

      <section className="bg-slate-50 p-3 rounded-xl space-y-2">
        <h4 className="font-bold text-slate-800 flex items-center gap-1.5">
          <FlaskConical className="w-4 h-4 text-sky-600" />
          <span>التحاليل والأشعة ({lab_results.length})</span>
        </h4>
        {lab_results.length === 0 && <p className="text-slate-500">لا توجد فحوصات مسجلة.</p>}
        {lab_results.map((l) => (
          <div key={l.id} className="bg-white p-2.5 rounded-lg border flex justify-between items-start gap-3">
            <div>
              <p className="font-bold text-slate-900">{l.test_name}</p>
              <p className="text-slate-500">
                طلب: {l.doctor_name} — {formatDate(l.created_at)}
              </p>
              {l.result_summary && <p className="text-emerald-800 mt-1">النتيجة: {l.result_summary}</p>}
            </div>
            <span
              className={`shrink-0 font-bold px-2 py-0.5 rounded-md ${
                l.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
              }`}
            >
              {l.status === 'completed' ? 'مكتمل' : 'قيد الانتظار'}
            </span>
          </div>
        ))}
      </section>

      <section className="bg-slate-50 p-3 rounded-xl space-y-2">
        <h4 className="font-bold text-slate-800 flex items-center gap-1.5">
          <Pill className="w-4 h-4 text-emerald-600" />
          <span>الوصفات الطبية ({prescriptions.length})</span>
        </h4>
        {prescriptions.length === 0 && <p className="text-slate-500">لا توجد وصفات مسجلة.</p>}
        {prescriptions.map((rx) => (
          <div key={rx.id} className="bg-white p-2.5 rounded-lg border space-y-1">
            <div className="flex justify-between text-slate-500">
              <span>{rx.doctor_name}</span>
              <span className="flex items-center gap-2">
                <span className="font-mono">{formatDate(rx.created_at)}</span>
                <span
                  className={`font-bold px-2 py-0.5 rounded-md ${
                    rx.status === 'dispensed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                  }`}
                >
                  {rx.status === 'dispensed' ? 'تم الصرف' : 'بانتظار الصيدلية'}
                </span>
              </span>
            </div>
            <ul className="space-y-0.5">
              {rx.items.map((item, idx) => (
                <li key={item.id ?? idx} className="text-slate-900">
                  <span className="font-bold">{item.medicine_name}</span> — {item.dosage} ({item.duration})
                </li>
              ))}
            </ul>
          </div>
        ))}
      </section>
    </div>
  );
}
