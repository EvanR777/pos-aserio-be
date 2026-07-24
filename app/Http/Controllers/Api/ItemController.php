<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $r)
    {
        $q = DB::table('item');
        if ($s = $r->get('q')) {
            $q->where(function ($x) use ($s) {
                $x->where('item', 'like', "%{$s}%")
                  ->orWhere('legacy_id', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%");
            });
        }
        return response()->json($q->orderBy('legacy_id')->paginate(25));
    }

    public function show($id)
    {
        return response()->json(DB::table('item')->where('legacy_id', $id)->first());
    }
}
