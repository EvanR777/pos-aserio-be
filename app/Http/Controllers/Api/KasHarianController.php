<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasHarianController extends Controller
{
    public function index(Request $r)
    {
        $q = DB::table('kas_harian');
        if ($d = $r->get('tanggal')) {
            $q->whereDate('trans_date', $d);
        }
        $rows = $q->orderByDesc('trans_date')->paginate(25);
        // saldo berjalan per tanggal
        $rows->getCollection()->transform(function ($k) {
            $k->saldo_berjalan = DB::table('kas_harian')
                ->whereDate('trans_date', '<=', $k->trans_date)
                ->sum(DB::raw('debet - kredit'));
            return $k;
        });
        return response()->json($rows);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'trans_date'  => ['required', 'date'],
            'keterangan'  => ['required', 'string', 'max:150'],
            'tipe'        => ['required', 'in:MASUK,KELUAR'],
            'nominal'     => ['required', 'numeric', 'min:0'],
        ]);

        $usr = request()->user();
        $tgl = $d['trans_date'];
        $no  = (int) DB::table('kas_harian')->whereDate('trans_date', $tgl)->count() + 1;
        $tid = 'KAS-' . date('dmY', strtotime($tgl)) . '-' . str_pad($no, 3, '0', STR_PAD_LEFT);

        $debet = $d['tipe'] === 'MASUK' ? $d['nominal'] : 0;
        $kredit = $d['tipe'] === 'KELUAR' ? $d['nominal'] : 0;

        DB::table('kas_harian')->insert([
            'trans_id' => $tid, 'trans_date' => $tgl, 'source' => 'MANUAL',
            'keterangan' => $d['keterangan'], 'debet' => $debet, 'kredit' => $kredit,
            'saldo' => 0, 'user_id' => $usr->name,
            'holding_id' => 1, 'company_id' => 1, 'outlet_id' => 1, 'created_at' => now(),
        ]);

        return response()->json(['trans_id' => $tid], 201);
    }
}
