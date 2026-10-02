<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends ApiController
{
    /**
     * Get inventory dashboard data
     */
    public function dashboard()
    {
        $items = DB::table('inventory_items')->orderBy('quantity', 'asc')->get();
        $medicines = DB::table('medicines')->orderBy('quantity', 'asc')->get();

        $alerts = DB::table('inventory_items')
            ->whereRaw('quantity <= min_threshold')
            ->get();

        return $this->success([
            'inventory_items' => $items,
            'medicines_stock' => $medicines,
            'low_stock_alerts' => $alerts,
        ]);
    }

    /**
     * Restock inventory or medicine item
     */
    public function restockItem(Request $request)
    {
        $request->validate([
            'type' => 'required|in:medicine,inventory',
            'id' => 'required|integer',
            'additional_quantity' => 'required|integer|min:1|max:100000',
        ]);

        $table = $request->type === 'medicine' ? 'medicines' : 'inventory_items';
        // Atomic increment so concurrent restocks never overwrite each other.
        $updated = DB::table($table)->where('id', $request->id)->increment('quantity', (int) $request->additional_quantity, ['updated_at' => now()]);
        if (!$updated) {
            return $this->error('الصنف غير موجود', 404);
        }

        return $this->success(null, 'تم إضافة الكمية وتحديث المخزون بنجاح.');
    }
}
