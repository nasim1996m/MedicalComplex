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
            'additional_quantity' => 'required|integer|min:1',
        ]);

        if ($request->type === 'medicine') {
            $item = DB::table('medicines')->where('id', $request->id)->first();
            if ($item) {
                DB::table('medicines')->where('id', $request->id)->update([
                    'quantity' => $item->quantity + $request->additional_quantity,
                    'updated_at' => now(),
                ]);
            }
        } else {
            $item = DB::table('inventory_items')->where('id', $request->id)->first();
            if ($item) {
                DB::table('inventory_items')->where('id', $request->id)->update([
                    'quantity' => $item->quantity + $request->additional_quantity,
                    'updated_at' => now(),
                ]);
            }
        }

        return $this->success(null, 'تم إضافة الكمية وتحديث المخزون بنجاح.');
    }
}
