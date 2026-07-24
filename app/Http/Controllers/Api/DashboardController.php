<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show()
    {
        $today = now()->toDateString();
        $lastMonth = now()->subMonth()->toDateString();

        $data = [
            'penjualan_hari_ini' => DB::table('nota_penjualan')
                ->whereDate('trans_date', $today)
                ->where('status', '<>', 'VOID')
                ->sum('grand_total'),
            'penjualan_bulan_ini' => DB::table('nota_penjualan')
                ->whereYear('trans_date', now()->year)
                ->whereMonth('trans_date', now()->month)
                ->where('status', '<>', 'VOID')
                ->sum('grand_total'),
            'pembelian_bulan_ini' => DB::table('nota_pembelian')
                ->whereYear('trans_date', now()->year)
                ->whereMonth('trans_date', now()->month)
                ->where('status', '<>', 'VOID')
                ->sum('grand_total'),
            'stok_menipis' => DB::table('item_inventory as inv')
                ->leftJoin('item as i', 'inv.item_id', '=', 'i.legacy_id')
                ->where('inv.quan_saldo', '<', 10)
                ->count(),
            'top_penjualan_bulan_ini' => DB::table('nota_penjualan_detail as d')
                ->join('nota_penjualan as n', 'd.trans_id', '=', 'n.trans_id')
                ->join('item as i', 'd.item_id', '=', 'i.legacy_id')
                ->whereYear('n.trans_date', now()->year)
                ->whereMonth('n.trans_date', now()->month)
                ->where('n.status', '<>', 'VOID')
                ->select('i.item', DB::raw('SUM(d.quan_out) as total_qty'), DB::raw('SUM(d.total_per_item) as total'))
                ->groupBy('i.item')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get(),
            'kas_saldo_terakhir' => DB::table('kas_harian')
                ->latest('trans_date')
                ->value('saldo') ?? 0,
            'total_pelanggan' => DB::table('pelanggan')->count(),
            'total_item' => DB::table('item')->count(),
        ];

        return response()->json($data);
    }
}