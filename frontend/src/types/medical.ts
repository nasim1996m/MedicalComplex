export type UserRole = 'admin' | 'doctor' | 'pharmacist' | 'lab_tech' | 'storekeeper' | 'accountant' | 'hr' | 'pending';

export interface User {
  id: number;
  name: string;
  email: string;
  google_id: string;
  avatar?: string;
  role: UserRole;
  specialty?: string;
  status: 'pending' | 'approved' | 'rejected';
  phone?: string;
}

export interface RoleRequest {
  id: number;
  user_id: number;
  user_name?: string;
  user_email?: string;
  user_avatar?: string;
  requested_role: UserRole;
  requested_specialty?: string;
  notes?: string;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
}

export interface Patient {
  id: number;
  patient_code: string;
  name: string;
  gender: 'male' | 'female';
  age: number;
  phone?: string;
  medical_history?: string;
  created_at?: string;
  visit_count?: number;
  last_visit_date?: string | null;
}

export interface Visit {
  id: number;
  patient_id: number;
  patient_name?: string;
  patient_code?: string;
  age?: number;
  gender?: string;
  doctor_id: number;
  doctor_name?: string;
  doctor_specialty?: string;
  visit_date: string;
  diagnosis: string;
  notes?: string;
  fee: number;
  status: 'waiting' | 'in_consultation' | 'completed';
  created_at: string;
}

export interface LabTestType {
  id: number;
  name: string;
  category: 'blood' | 'xray' | 'echo' | 'ecg' | 'other';
  price: number;
}

export interface LabRequest {
  id: number;
  visit_id: number;
  patient_id: number;
  patient_name?: string;
  patient_code?: string;
  age?: number;
  doctor_id: number;
  doctor_name?: string;
  test_type_id: number | null;
  test_name?: string;
  test_category?: string;
  test_price?: number;
  status: 'pending' | 'completed';
  result_summary?: string;
  report_file_url?: string;
  created_at: string;
}

export interface Medicine {
  id: number;
  name: string;
  barcode?: string;
  category: string;
  unit_price: number;
  quantity: number;
  min_threshold: number;
  expiry_date?: string;
  batch_number?: string;
}

export interface PrescriptionItem {
  id?: number;
  prescription_id?: number;
  medicine_id: number | null;
  medicine_name?: string;
  unit_price?: number;
  stock_qty?: number;
  dosage: string;
  duration: string;
  notes?: string;
}

export interface Prescription {
  id: number;
  visit_id: number;
  doctor_id: number;
  doctor_name?: string;
  patient_id: number;
  patient_name?: string;
  patient_code?: string;
  status: 'pending' | 'dispensed';
  dispensed_at?: string;
  items: PrescriptionItem[];
  created_at: string;
}

export interface InventoryItem {
  id: number;
  name: string;
  category: 'lab_supplies' | 'medical_supplies' | 'office_supplies';
  quantity: number;
  unit: string;
  min_threshold: number;
  expiry_date?: string;
}

// Chart of Accounts Entity (دليل الحسابات المحاسبي الموحد)
export interface ChartOfAccount {
  id: number;
  code: string;
  name: string;
  type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
  balance: number;
  opening_balance?: number;
  period_debit?: number;
  period_credit?: number;
  trial_debit?: number;
  trial_credit?: number;
}

// Double Entry Journal Entry Entity (سند القيد المحاسبي المزدوج)
export interface JournalEntryItem {
  account_id: number;
  account_code?: string;
  account_name?: string;
  debit: number;
  credit: number;
  memo?: string;
}

export interface JournalEntry {
  id: number;
  entry_number: string;
  entry_date: string;
  description: string;
  total_debit: number;
  total_credit: number;
  creator_name?: string;
  items: JournalEntryItem[];
}

export interface Voucher {
  id: number;
  voucher_type: 'income' | 'expense';
  category: string;
  amount: number;
  description: string;
  creator_name?: string;
  creator_role?: string;
  created_at: string;
  entry_number?: string | null;
  voucher_date?: string;
}

export interface HrEmployee {
  id: number;
  user_id?: number;
  name: string;
  job_title: string;
  department: string;
  salary: number;
  hire_date?: string;
  fingerprint_id: string;
  avatar?: string;
}

export interface HrAttendance {
  id: number;
  employee_id: number;
  employee_name?: string;
  job_title?: string;
  department?: string;
  fingerprint_id?: string;
  check_in?: string;
  check_out?: string;
  date: string;
  status: 'present' | 'absent' | 'late' | 'leave';
}

export interface HrRoster {
  id: number;
  employee_id: number;
  employee_name?: string;
  job_title?: string;
  shift: 'morning' | 'evening' | 'night';
  date: string;
  location: string;
  notes?: string;
}

export interface SystemNotification {
  id: number;
  user_id?: number;
  target_role?: string;
  title: string;
  message: string;
  type: string;
  is_read: boolean;
  created_at: string;
}

// السجل الطبي المشترك كما يرجعه الباك إند: GET /patients/{id}/history
export interface PatientHistory {
  patient: Patient;
  visits: Visit[];
  lab_results: LabRequest[];
  prescriptions: Prescription[];
}

// لوحة المحاسبة كما يرجعها الباك إند: GET /accountant/dashboard
export interface AccountingDashboardData {
  from: string | null;
  to: string | null;
  vouchers: Voucher[];
  journal_entries: JournalEntry[];
  accounts: ChartOfAccount[];
  trial_balance: { total_debit: number; total_credit: number; difference: number; is_balanced: boolean };
  summary: {
    total_income: number;
    total_expense: number;
    net_profit: number;
    doctor_income: number;
    pharmacy_income: number;
    lab_income: number;
  };
}
