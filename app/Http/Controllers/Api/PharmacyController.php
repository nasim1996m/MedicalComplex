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
                ->join('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
                ->select('prescription_items.*', 'medicines.name as medicine_name', 'medicines.unit_price', 'medicines.quantity as stock_qty')
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
        $pharmacistId = $request->user()->id;

        if (!DB::table('prescriptions')->where('id', $id)->exists()) {
            return $this->error('الوصفة الطبية غير موجودة', 404);
        }

        return DB::transaction(function () use ($id, $pharmacistId) {
            // Claim the prescription first: a second (or concurrent) dispense finds nothing to claim.
            $claimed = DB::table('prescriptions')->where('id', $id)->where('status', 'pending')->update([
                'status' => 'dispensed',
                'dispensed_at' => now(),
                'dispensed_by' => $pharmacistId,
                'updated_at' => now(),
            ]);
            if (!$claimed) {
                return $this->error('تم صرف هذه الوصفة مسبقاً', 409);
            }

            $items = DB::table('prescription_items')->where('prescription_id', $id)->get();
            $totalPrice = 0;

            foreach ($items as $item) {
                $med = DB::table('medicines')->where('id', $item->medicine_id)->lockForUpdate()->first();
                if (!$med) {
                    continue;
                }
                if ($med->quantity < 1) {
                    // Roll back the whole dispense rather than selling stock that does not exist.
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(
                        $this->error('الدواء (' . $med->name . ') نفد من المخزون', 409)
                    );
                }
                $totalPrice += $med->unit_price;
                $newQty = $med->quantity - 1;
                DB::table('medicines')->where('id', $med->id)->update(['quantity' => $newQty, 'updated_at' => now()]);

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

            // Record income in vouchers for Accountant
            DB::table('vouchers')->insert([
                'voucher_type' => 'income',
                'category' => 'pharmacy_income',
                'amount' => $totalPrice,
                'description' => 'إيراد مبيعات صيدلية عن وصفة رقم #' . $id,
                'created_by' => $pharmacistId,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->success(null, 'تم صرف الوصفة الطبية وتحديث المخزون وإبلاغ المحاسبة بنجاح.');
        });
    }
}
