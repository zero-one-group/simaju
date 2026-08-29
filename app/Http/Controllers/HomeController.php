<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use App\Order;
use App\Product;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $data = array();
        $data['total_order'] = DB::table('tbl_orders')->where('status', '!=', 'deleted')->count();
        $data['order_bulan_ini'] = DB::table('tbl_orders')
            ->whereRaw("MONTH(tgl_order) = " . date('m') . " AND YEAR(tgl_order) = " . date('Y'))
            ->where('status', '!=', 'deleted')
            ->count();
        $data['omzet_bulan_ini'] = DB::table('tbl_orders')
            ->whereRaw("MONTH(tgl_order) = " . date('m') . " AND YEAR(tgl_order) = " . date('Y'))
            ->where('status', '!=', 'deleted')
            ->sum('total');
        $data['total_produk'] = Product::where('status', 'aktif')->count();
        $data['total_customer'] = DB::table('tbl_customers')->where('status', 'aktif')->count();

        // stok menipis
        $data['stok_menipis'] = DB::select("SELECT * FROM products WHERE stok <= stok_minimum AND status='aktif' ORDER BY stok ASC LIMIT 10");

        // order terakhir
        $data['order_terakhir'] = Order::where('status', '!=', 'deleted')->orderBy('id', 'desc')->take(10)->get();

        // grafik 7 hari
        $grafik = array();
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-$i days"));
            $r = DB::select("SELECT COUNT(*) as jml, IFNULL(SUM(total),0) as total FROM tbl_orders WHERE DATE(tgl_order) = '$tgl' AND status != 'deleted'");
            $grafik[] = array('tgl' => $tgl, 'jml' => $r[0]->jml, 'total' => $r[0]->total);
        }
        $data['grafik'] = $grafik;

        return view('home', $data);
    }
}
