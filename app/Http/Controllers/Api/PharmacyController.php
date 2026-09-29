<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends ApiController
{
    /**
     * Get pharmacy dashboard data
     */
    public function dashboard()
    {
        $prescriptions = DB::table('prescriptions')
            ->join('patients', 'prescriptions.patient_id', '=', 'patients.id')
            ->join('users as doctors', 'prescriptions.doctor_id', '=', 'doctors.id')
            ->select('prescriptions.*', 'patients.name as patient_name', 'patients.patient_code', 'doctors.name as doctor_name')
            ->orderBy('prescriptions.created_at', 'desc')
            ->get();

        foreach ($prescriptions as $p) {
            $p->items = DB::table('prescription_items')
                ->leftJoin('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
                ->select('prescription_items.*', DB::raw('COALESCE(medicines.name, prescription_items.medicine_name) as medicine_name'), 'medicines.unit_price', 'medicines.quantity as stock_qty')
                ->where('prescription_id', $p->id)
                ->get();
        }

        $medicines = DB::table('medicines')->orderBy('quantity', 'asc')->get();

        $lowStockAlerts = DB::table('medicines')
            ->whereRaw('quantity <= min_threshold')
            ->get();

        return $this->success([
            'prescriptions' => $prescriptions,
            'medicines' => $medicines,
            'low_stock_alerts' => $lowStockAlerts,
        ]);
    }

    /**
     * Dispense medicine & deduct stock
     */
    public function dispense(Request $request, $id)
    {
        $request->validate([
            'pharmacist_id' => 'required|exists:users,id',
        ]);

        $prescription = DB::table('prescriptions')->where('id', $id)->first();
        if (!$prescription) {
            return $this->error('الوصفة الطبية غير موجودة', 404);
        }

        $items = DB::table('prescription_items')->where('prescription_id', $id)->get();
        $totalPrice = 0;

        foreach ($items as $item) {
            $med = $item->medicine_id
                ? DB::table('medicines')->where('id', $item->medicine_id)->first()
                : DB::table('medicines')->where('name', $item->medicine_name)->first();
            if ($med) {
                $totalPrice += $med->unit_price;

                // Deduct quantity
                $newQty = max(0, $med->quantity - 1);
                DB::table('medicines')->where('id', $med->id)->update([
                    'quantity' => $newQty,
                    'updated_at' => now(),
                ]);

                // Check 15% threshold trigger -> notify admin
                if ($newQty <= $med->min_threshold) {
                    DB::table('notifications')->insert([
                        'target_role' => 'admin',
                        'title' => 'تنبيه نقص دواء في الصيدلية (<= 15%)',
                        'message' => 'وصل دواء (' . $med->name . ') إلى الكمية الحرجية: ' . $newQty . ' قطعة. يرجى إرسال طلب تزويد من المخزن.',
                        'type' => 'stock_alert',
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Mark as dispensed
        DB::table('prescriptions')->where('id', $id)->update([
            'status' => 'dispensed',
            'dispensed_at' => now(),
            'dispensed_by' => $request->pharmacist_id,
            'updated_at' => now(),
        ]);

        // Record income in vouchers for Accountant
        DB::table('vouchers')->insert([
            'voucher_type' => 'income',
            'category' => 'pharmacy_income',
            'amount' => $totalPrice,
            'description' => 'إيراد مبيعات صيدلية عن وصفة رقم #' . $id,
            'created_by' => $request->pharmacist_id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(null, 'تم صرف الوصفة الطبية وتحديث المخزون وإبلاغ المحاسبة بنجاح.');
    }
}
