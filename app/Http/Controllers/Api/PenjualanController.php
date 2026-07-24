<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function index(Request $r)
    {
        $q = DB::table('nota_penjualan')->where('status', '<>', 'VOID');
        if ($d = $r->get('tanggal')) {
            $q->whereDate('trans_date', $d);
        }
        $rows = $q->orderByDesc('trans_date')->paginate(25)->through(function ($n) {
            $n->details = DB::table('nota_penjualan_detail')
                ->where('trans_id', $n->legacy_trans_id ?? $n->trans_id)
                ->get();
            return $n;
        });
        return response()->json($rows);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'trans_date'      => ['required', 'date'],
            'pelanggan_id'    => ['required'],
            'details'         => ['required', 'array', 'min:1'],
            'details.*.item_id' => ['required'],
            'details.*.qty'     => ['required', 'numeric', 'min:0.1'],
            'details.*.harga'   => ['required', 'numeric', 'min:0'],
            'bayar'           => ['required', 'numeric', 'min:0'],
        ]);

        [$nota, $total] = DB::transaction(function () use ($d) {
            $usr = request()->user();
            $tgl = $d['trans_date'];
            $no  = (int) DB::table('nota_penjualan')->whereDate('trans_date', $tgl)->count() + 1;
            $tid = 'JUAL-' . date('Ymd', strtotime($tgl)) . '-' . str_pad($no, 4, '0', STR_PAD_LEFT);

            $grand = 0; $qtyTot = 0;
            foreach ($d['details'] as $det) {
                $grand  += $det['qty'] * $det['harga'];
                $qtyTot += $det['qty'];
            }

            $notaId = DB::table('nota_penjualan')->insertGetId([
                'trans_id' => $tid, 'trans_no' => $no, 'trans_date' => $tgl,
                'status' => 'POSTED', 'pelanggan_id' => $d['pelanggan_id'],
                'total_qty' => $qtyTot, 'sub_total' => $grand, 'grand_total' => $grand,
                'bayar' => $d['bayar'], 'kurang_bayar' => max(0, $grand - $d['bayar']),
                'tipe_pembayaran' => 'CASH', 'input_date' => now(), 'user_id' => $usr->name,
                'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now(),
            ]);

            foreach ($d['details'] as $i => $det) {
                DB::table('nota_penjualan_detail')->insert([
                    'trans_id' => $tid, 'item_index' => $i + 1,
                    'item_id' => $det['item_id'], 'quan_out' => $det['qty'],
                    'harga_jual' => $det['harga'], 'total_per_item' => $det['qty'] * $det['harga'],
                    'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now(),
                ]);

                // stok out di item_inventory
                DB::table('item_inventory')->insert([
                    'item_id' => $det['item_id'], 'item_index' => $i + 1,
                    'trans_id' => $tid, 'trans_date' => $tgl,
                    'quan_out' => $det['qty'], 'quan_saldo' => -$det['qty'],
                    'source' => 'PENJUALAN', 'remarks' => 'Penjualan ' . $tid,
                    'status' => 'POSTED', 'input_date' => now(),
                    'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now(),
                ]);
            }

            // posting ganda: jurnal Debet Kas, Kredit Penjualan
            $coaKas = DB::table('chart_of_account')->where('description', 'like', '%KAS%')->value('account_no');
            $coaPju = DB::table('chart_of_account')->where('description', 'like', '%PENJUALAN%')->value('account_no');
            DB::table('jurnal')->insert([
                ['trans_id' => $tid, 'trans_date' => $tgl, 'no_coa' => $coaKas, 'debet' => $grand, 'kredit' => 0, 'status' => 'POSTED', 'remarks' => 'Penjualan ' . $tid, 'user_id' => $usr->name, 'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now()],
                ['trans_id' => $tid, 'trans_date' => $tgl, 'no_coa' => $coaPju, 'debet' => 0, 'kredit' => $grand, 'status' => 'POSTED', 'remarks' => 'Penjualan ' . $tid, 'user_id' => $usr->name, 'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now()],
            ]);

            // posting kas harian
            DB::table('kas_harian')->insert([
                'trans_id' => $tid, 'trans_date' => $tgl, 'source' => 'PENJUALAN',
                'keterangan' => 'Penjualan ' . $tid, 'debet' => $grand, 'kredit' => 0,
                'saldo' => $grand, 'user_id' => $usr->name,
                'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now(),
            ]);

            return [$tid, $grand];
        });

        return response()->json(['trans_id' => $nota, 'grand_total' => $total], 201);
    }
}
