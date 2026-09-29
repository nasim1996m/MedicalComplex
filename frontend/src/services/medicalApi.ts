import { Patient, PatientHistory, Visit, LabRequest, Prescription, InventoryItem, Medicine } from '@/types/medical';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

/**
 * Calls the Laravel API and throws an Error with a readable Arabic message.
 * Unlike fetchFromApi, it never falls back to mock data: if the database is not
 * reachable the user must see it instead of working on data that is not saved.
 */
export async function apiRequest<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  let res: Response;
  try {
    res = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers || {}),
      },
    });
  } catch {
    throw new Error(backendDownMessage());
  }

  const body = await res.json().catch(() => null);

  if (!res.ok || !body || body.status === 'error') {
    throw new Error(apiErrorMessage(res.status, body));
  }

  return body.data as T;
}

export function backendDownMessage(): string {
  return 'تعذر الاتصال بسيرفر Laravel. تأكد من تشغيل الأمر: php artisan serve (على المنفذ 8000).';
}

/** Builds a readable message from a Laravel error response (validation or exception). */
export function apiErrorMessage(status: number, body: { message?: string; errors?: Record<string, string[]> } | null): string {
  if (!body) {
    // Next.js proxy returns a non-JSON 500 when Laravel is not running
    return status >= 500 ? backendDownMessage() : `خطأ غير متوقع من السيرفر (${status})`;
  }
  const firstValidationError = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;
  return firstValidationError || body.message || `خطأ من السيرفر (${status})`;
}

function query(params: Record<string, string | number | undefined | null>): string {
  const qs = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && String(value).trim() !== '') qs.set(key, String(value));
  });
  const str = qs.toString();
  return str ? `?${str}` : '';
}

export function todayISO(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// ─── Shared patient records ──────────────────────────────────────────────────

export function searchPatients(params: { q?: string; from?: string; to?: string }) {
  return apiRequest<Patient[]>(`/patients${query(params)}`);
}

export function getPatientHistory(patientId: number) {
  return apiRequest<PatientHistory>(`/patients/${patientId}/history`);
}

export function createPatient(data: { name: string; age: number; gender: string; phone?: string; medical_history?: string }) {
  return apiRequest<Patient>('/patients', { method: 'POST', body: JSON.stringify(data) });
}

export function listVisits(params: { q?: string; from?: string; to?: string; doctor_id?: number }) {
  return apiRequest<{ total_visits: number; total_patients: number; visits: Visit[] }>(`/visits${query(params)}`);
}

// ─── Doctor ──────────────────────────────────────────────────────────────────

export interface ConsultationPayload {
  doctor_id: number;
  patient_id: number;
  diagnosis: string;
  notes?: string;
  lab_requests: { test_name: string }[];
  prescription_items: { medicine_name: string; dosage: string; duration: string }[];
}

export function saveConsultation(payload: ConsultationPayload) {
  return apiRequest<{ patient: Patient; visit_id: number; lab_request_ids: number[]; prescription_id: number | null }>(
    '/doctor/consultations',
    { method: 'POST', body: JSON.stringify(payload) }
  );
}

// ─── Lab ─────────────────────────────────────────────────────────────────────

export function getLabDashboard() {
  return apiRequest<{ lab_requests: LabRequest[]; consumables: InventoryItem[] }>('/lab/dashboard');
}

export function completeLabRequest(id: number, performedBy: number, resultSummary: string) {
  return apiRequest<null>(`/lab/requests/${id}/complete`, {
    method: 'POST',
    body: JSON.stringify({ performed_by: performedBy, result_summary: resultSummary }),
  });
}

// ─── Pharmacy ────────────────────────────────────────────────────────────────

export function getPharmacyDashboard() {
  return apiRequest<{ prescriptions: Prescription[]; medicines: Medicine[]; low_stock_alerts: Medicine[] }>('/pharmacy/dashboard');
}

export function dispensePrescription(id: number, pharmacistId: number) {
  return apiRequest<null>(`/pharmacy/prescriptions/${id}/dispense`, {
    method: 'POST',
    body: JSON.stringify({ pharmacist_id: pharmacistId }),
  });
}
