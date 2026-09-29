import {
  User,
  RoleRequest,
  Patient,
  Visit,
  LabRequest,
  LabTestType,
  Medicine,
  Prescription,
  InventoryItem,
  Voucher,
  ChartOfAccount,
  JournalEntry,
  HrEmployee,
  HrAttendance,
  HrRoster,
  SystemNotification,
} from '@/types/medical';

const API_BASE_URL = '/api/v1';

export const mockData = {
  users: [
    {
      id: 1,
      name: 'مدير النظام (الادمن)',
      email: 'admin@medical.com',
      google_id: 'google_admin_101',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200',
      role: 'admin' as const,
      specialty: 'إدارة مجمع طبي',
      status: 'approved' as const,
      phone: '07700000001',
    },
    {
      id: 2,
      name: 'د. أحمد علي السامرائي',
      email: 'doctor.ahmed@medical.com',
      google_id: 'google_doc_102',
      avatar: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&q=80&w=200',
      role: 'doctor' as const,
      specialty: 'أطباء باطنية و غدد',
      status: 'approved' as const,
      phone: '07700000002',
    },
    {
      id: 3,
      name: 'د. سارة خالد (صيدلانية)',
      email: 'pharmacy@medical.com',
      google_id: 'google_pharm_104',
      avatar: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&q=80&w=200',
      role: 'pharmacist' as const,
      specialty: 'صيدلة سريرية',
      status: 'approved' as const,
      phone: '07700000004',
    },
    {
      id: 4,
      name: 'أحمد العبيدي (فني مختبر وأشعة)',
      email: 'lab@medical.com',
      google_id: 'google_lab_105',
      avatar: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=200',
      role: 'lab_tech' as const,
      specialty: 'تحاليل وأشعة وتخطيط',
      status: 'approved' as const,
      phone: '07700000005',
    },
    {
      id: 5,
      name: 'عمر الفاروق (أمين المخزن)',
      email: 'store@medical.com',
      google_id: 'google_store_106',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200',
      role: 'storekeeper' as const,
      specialty: 'إدارة المخزون والمعدات',
      status: 'approved' as const,
      phone: '07700000006',
    },
    {
      id: 6,
      name: 'مصطفى كامل (المحاسب)',
      email: 'accountant@medical.com',
      google_id: 'google_acc_107',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=200',
      role: 'accountant' as const,
      specialty: 'الحسابات والمالية',
      status: 'approved' as const,
      phone: '07700000007',
    },
    {
      id: 7,
      name: 'زينب الفضلي (مسؤولة HR)',
      email: 'hr@medical.com',
      google_id: 'google_hr_108',
      avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=200',
      role: 'hr' as const,
      specialty: 'إدارة الموارد البشرية والحرس',
      status: 'approved' as const,
      phone: '07700000008',
    },
  ] as User[],

  roleRequests: [
    {
      id: 1,
      user_id: 99,
      user_name: 'د. خالد الجبوري',
      user_email: 'khalid.new@gmail.com',
      user_avatar: 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&q=80&w=200',
      requested_role: 'doctor' as const,
      requested_specialty: 'طبيب أطفال',
      notes: 'طلب فتح حساب طبيب أطفال ومباشرة العمل بالمجمع',
      status: 'pending' as const,
      created_at: new Date().toISOString().split('T')[0],
    },
  ] as RoleRequest[],

  patients: [
    { id: 1, patient_code: 'PAT-1001', name: 'حيدر عبد الرضا', gender: 'male' as const, age: 45, phone: '07801112233', medical_history: 'ضغط دم مرتفع، حساسية من البنسلين' },
    { id: 2, patient_code: 'PAT-1002', name: 'فاطمة الزهراء علي', gender: 'female' as const, age: 32, phone: '07804445566', medical_history: 'فقر دم خفيف، عمليات قيصرية سابقة' },
  ] as Patient[],

  visits: [
    {
      id: 1,
      patient_id: 1,
      patient_name: 'حيدر عبد الرضا',
      patient_code: 'PAT-1001',
      age: 45,
      gender: 'male',
      doctor_id: 2,
      doctor_name: 'د. أحمد علي السامرائي',
      doctor_specialty: 'أطباء باطنية و غدد',
      visit_date: new Date().toISOString().split('T')[0],
      diagnosis: 'ارتفاع بالضغط الشرياني واضطراب معدل ضربات القلب',
      notes: 'يحتاج إجراء تخطيط قلب وفحص دم شامل',
      fee: 25000,
      status: 'in_consultation' as const,
      created_at: '2026-09-19 10:30',
    },
  ] as Visit[],

  labTestTypes: [
    { id: 1, name: 'فحص الدم الشامل (CBC)', category: 'blood' as const, price: 15000 },
    { id: 2, name: 'أشعة سينية للصدر (X-Ray)', category: 'xray' as const, price: 20000 },
    { id: 3, name: 'فحص السونار والإيكو (Echo)', category: 'echo' as const, price: 35000 },
    { id: 4, name: 'تخطيط القلب (ECG)', category: 'ecg' as const, price: 15000 },
  ] as LabTestType[],

  labRequests: [
    {
      id: 1,
      visit_id: 1,
      patient_id: 1,
      patient_name: 'حيدر عبد الرضا',
      patient_code: 'PAT-1001',
      age: 45,
      doctor_id: 2,
      doctor_name: 'د. أحمد علي السامرائي',
      test_type_id: 4,
      test_name: 'تخطيط القلب (ECG)',
      test_category: 'ecg',
      test_price: 15000,
      status: 'completed' as const,
      result_summary: 'تخطيط القلب يظهر تسارع جيبي خفيف، لا توجد علامات نقص تروية حادة',
      report_file_url: '/reports/ecg_pat1001.pdf',
      created_at: '2026-09-19 11:00',
    },
  ] as LabRequest[],

  medicines: [
    { id: 1, name: 'Amoxicillin 500mg (أمكسيسيلين)', barcode: '62911001', category: 'مضاد حيوي', unit_price: 5000, quantity: 120, min_threshold: 15, expiry_date: '2027-06-30', batch_number: 'BATCH-8821' },
    { id: 2, name: 'Paracetamol 500mg (باراسيتامول)', barcode: '62911002', category: 'مسكن آلام', unit_price: 2000, quantity: 10, min_threshold: 15, expiry_date: '2026-11-15', batch_number: 'BATCH-4412' },
  ] as Medicine[],

  prescriptions: [
    {
      id: 1,
      visit_id: 1,
      doctor_id: 2,
      doctor_name: 'د. أحمد علي السامرائي',
      patient_id: 1,
      patient_name: 'حيدر عبد الرضا',
      patient_code: 'PAT-1001',
      status: 'pending' as const,
      items: [
        { medicine_id: 1, medicine_name: 'Amoxicillin 500mg (أمكسيسيلين)', unit_price: 5000, stock_qty: 120, dosage: 'كبسولة كل 8 ساعات', duration: '7 أيام', notes: 'تؤخذ بعد الأكل مباشرة' },
      ],
      created_at: '2026-09-19 11:30',
    },
  ] as Prescription[],

  inventoryItems: [
    { id: 1, name: 'حقن وإبر معقمة 5 مل', category: 'lab_supplies' as const, quantity: 500, unit: 'باكيت', min_threshold: 15, expiry_date: '2028-01-01' },
    { id: 2, name: 'قطن طبي معقم', category: 'lab_supplies' as const, quantity: 8, unit: 'لفة', min_threshold: 15, expiry_date: '2027-01-01' },
    { id: 3, name: 'كحول طبي مطهر 70%', category: 'lab_supplies' as const, quantity: 40, unit: 'عبوة 1 لتر', min_threshold: 15, expiry_date: '2027-05-01' },
  ] as InventoryItem[],

  // Standard Chart of Accounts (دليل الحسابات المحاسبي الموحد للمجمع الطبي)
  chartOfAccounts: [
    { id: 1, code: '101', name: 'الصندوق الرئيسي (الخزينة)', type: 'asset' as const, balance: 4500000 },
    { id: 2, code: '102', name: 'حساب البنك (المصرف)', type: 'asset' as const, balance: 12000000 },
    { id: 3, code: '103', name: 'مخزون الصيدلية (الأدوية)', type: 'asset' as const, balance: 3500000 },
    { id: 4, code: '104', name: 'مخزون المستلزمات والمختبر', type: 'asset' as const, balance: 1800000 },
    { id: 5, code: '201', name: 'موردو الأدوية والأجهزة (ذمم دائنة)', type: 'liability' as const, balance: 1200000 },
    { id: 6, code: '301', name: 'رأس المال والمستثمرين', type: 'equity' as const, balance: 20000000 },
    { id: 7, code: '401', name: 'إيرادات كشوفات الأطباء', type: 'revenue' as const, balance: 250000 },
    { id: 8, code: '402', name: 'إيرادات مبيعات الصيدلية', type: 'revenue' as const, balance: 180000 },
    { id: 9, code: '403', name: 'إيرادات التحاليل والأشعة', type: 'revenue' as const, balance: 150000 },
    { id: 10, code: '501', name: 'مصاريف الكهرباء والمولدات', type: 'expense' as const, balance: 120000 },
    { id: 11, code: '502', name: 'رواتب وأجور الموظفين والحرس', type: 'expense' as const, balance: 3100000 },
    { id: 12, code: '503', name: 'مصاريف الضيافة والنظافة', type: 'expense' as const, balance: 35000 },
  ] as ChartOfAccount[],

  // Double-Entry Journal Entries (دفتر القيود المحاسبية المزدوجة المتوازنة)
  journalEntries: [
    {
      id: 1,
      entry_number: 'JV-2026-001',
      entry_date: new Date().toISOString().split('T')[0],
      description: 'إثبات تحصيل كشوفات الأطباء وتصفية فاتورة الكهرباء والمولد اليومية',
      total_debit: 250000,
      total_credit: 250000,
      creator_name: 'مصطفى كامل (المحاسب)',
      items: [
        { account_id: 1, account_code: '101', account_name: 'الصندوق الرئيسي (الخزينة)', debit: 250000, credit: 0, memo: 'قبض كشوفات الأطباء نقداً بالصندوق' },
        { account_id: 7, account_code: '401', account_name: 'إيرادات كشوفات الأطباء', debit: 0, credit: 250000, memo: 'إثبات إيراد الكشوفات بالدائن' },
      ],
    },
  ] as JournalEntry[],

  vouchers: [
    { id: 1, voucher_type: 'income' as const, category: 'doctor_income', amount: 250000, description: 'إيراد كشوفات د. أحمد علي السامرائي اليومية', creator_name: 'مصطفى كامل (المحاسب)', created_at: '2026-09-19 09:00' },
    { id: 2, voucher_type: 'expense' as const, category: 'electricity', amount: 120000, description: 'تسديد فاتورة الكهرباء والمولد الخاص بالمجمع', creator_name: 'مصطفى كامل (المحاسب)', created_at: '2026-09-19 10:00' },
    { id: 3, voucher_type: 'expense' as const, category: 'hospitality', amount: 35000, description: 'ضيافة مراجعين ومشروبات العيادات', creator_name: 'مصطفى كامل (المحاسب)', created_at: '2026-09-19 11:00' },
  ] as Voucher[],

  employees: [
    { id: 1, user_id: 2, name: 'د. أحمد علي السامرائي', job_title: 'طبيب باطنية وغدد', department: 'قسم العيادات', salary: 2500000, hire_date: '2024-01-15', fingerprint_id: 'FP-101', avatar: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&q=80&w=200' },
    { id: 2, name: 'جاسم محمد الخفاجي (حارس أمني)', job_title: 'حارس أمني ومسؤول البوابة', department: 'الحرس والأمن', salary: 600000, hire_date: '2024-03-01', fingerprint_id: 'FP-202', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200' },
  ] as HrEmployee[],

  attendances: [
    { id: 1, employee_id: 1, employee_name: 'د. أحمد علي السامرائي', job_title: 'طبيب باطنية وغدد', department: 'قسم العيادات', fingerprint_id: 'FP-101', check_in: '08:00:00', check_out: '16:00:00', date: new Date().toISOString().split('T')[0], status: 'present' as const },
    { id: 2, employee_id: 2, employee_name: 'جاسم محمد الخفاجي (حارس أمني)', job_title: 'حارس أمني ومسؤول البوابة', department: 'الحرس والأمن', fingerprint_id: 'FP-202', check_in: '07:45:00', check_out: '19:45:00', date: new Date().toISOString().split('T')[0], status: 'present' as const },
  ] as HrAttendance[],

  rosters: [
    { id: 1, employee_id: 2, employee_name: 'جاسم محمد الخفاجي (حارس أمني)', job_title: 'حارس أمني ومسؤول البوابة', shift: 'night' as const, date: new Date().toISOString().split('T')[0], location: 'بوابة المجمع الرئيسية والعيادات الخارجية', notes: 'وجبة حراسة ليلية مع تفقّد المولدات والمخازن' },
  ] as HrRoster[],

  notifications: [
    { id: 1, target_role: 'admin', title: 'طلب حساب جديد', message: 'قدم د. خالد الجبوري طلب فتح حساب لداشبورد أطباء الأطفال.', type: 'role_request', is_read: false, created_at: '10:00 AM' },
    { id: 2, target_role: 'pharmacist', title: 'وصفة طبية جديدة', message: 'تم كتابة وصفة جديدة للمريض حيدر عبد الرضا من قبل د. أحمد علي.', type: 'prescription', is_read: false, created_at: '11:30 AM' },
    { id: 3, target_role: 'admin', title: 'تنبيه نقص دواء (أقل من 15%)', message: 'انخفضت كمية Paracetamol 500mg إلى 10 قطب (أقل من 15%). يرجى التزويد.', type: 'stock_alert', is_read: false, created_at: '11:45 AM' },
  ] as SystemNotification[],
};

export async function fetchFromApi(endpoint: string, options?: RequestInit) {
  try {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      ...options,
    });
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    return await res.json();
  } catch (err) {
    console.warn(`API Connection to ${endpoint} failed, utilizing localized state fallback`, err);
    return null;
  }
}
