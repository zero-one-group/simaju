<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\OrderDetail;
use App\Product;
use App\Customer;
use App\Exports\OrderExport;
use Maatwebsite\Excel\Facades\Excel;
use Auth;
use DB;

class LaporanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (Auth::user()->type == 3) {
            return redirect('/home')->with('error', 'Viewer tidak bisa akses laporan');
        }

        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $customer_id = $request->get('customer_id');
        $status = $request->get('status');

        // default semua data kalo gak difilter (request dari pak Hendra biar bisa liat semua)
        $where = " WHERE 1=1 ";
        if ($dari != '') {
            $where .= " AND o.tgl_order >= '$dari 00:00:00' "; // TODO sanitize?
        }
        if ($sampai != '') {
            $where .= " AND o.tgl_order <= '$sampai 23:59:59' ";
        }
        if ($customer_id != '') {
            $where .= " AND o.customer_id = " . intval($customer_id);
        }
        if ($status != '' && $status != 'semua') {
            $where .= " AND o.status = '" . $status . "' ";
        }

        // ---- summary ----
        // NOTE: summary ini jumlah semua order sesuai filter
        $summary = DB::select("SELECT COUNT(*) as jml_order, IFNULL(SUM(o.subtotal),0) as subtotal, IFNULL(SUM(o.diskon),0) as diskon, IFNULL(SUM(o.ppn),0) as ppn, IFNULL(SUM(o.total),0) as total FROM tbl_orders o " . $where);
        $summary = $summary[0];

        // ---- per hari ----
        // tgl_order disimpan UTC (default laravel), convert ke WIB dulu +7 jam
        $per_hari = DB::select("SELECT DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR)) as tgl, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $where . " AND o.status != 'deleted' GROUP BY DATE(DATE_ADD(o.tgl_order, INTERVAL 7 HOUR)) ORDER BY tgl DESC");

        // ---- per status ----
        $per_status = DB::select("SELECT o.status, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $where . " AND o.status != 'deleted' GROUP BY o.status");

        // ---- detail order ----
        $orders = DB::select("SELECT o.* FROM tbl_orders o " . $where . " AND o.status != 'deleted' ORDER BY o.tgl_order DESC");

        // convert ke model biar bisa pake relasi
        $list = array();
        $total_cek = 0;
        $total_item = 0;
        foreach ($orders as $o) {
            $order = Order::find($o->id);
            $row = array();
            $row['id'] = $order->id;
            $row['no_order'] = $order->no_order;
            $row['tgl_order'] = $order->tgl_order;
            $row['customer'] = $order->customer ? $order->customer->nama : '-';
            $row['kota'] = $order->customer ? $order->customer->kota : '-';
            $row['sales'] = $order->user ? $order->user->name : '-';
            $row['status'] = $order->status;
            $row['jml_item'] = $order->items->count();
            $row['qty'] = 0;
            $row['produk'] = '';
            foreach ($order->items as $it) {
                $row['qty'] += $it->qty;
                $total_item += $it->qty;
                if ($it->product) {
                    $row['produk'] .= $it->product->nama_barang . ' (' . $it->qty . '), ';
                }
            }
            $row['produk'] = rtrim($row['produk'], ', ');
            $row['subtotal'] = $order->subtotal;
            $row['diskon'] = $order->diskon;
            $row['ppn'] = $order->ppn;
            $row['total'] = $order->total;

            // hitung ulang total buat cek selisih (kadang beda sama yg di db)
            // copy dari OrderController biar sama
            $sub = $order->subtotal;
            $dp = $order->diskon_persen;
            $dk = $sub * $dp / 100;
            $dpp = $sub - $dk;
            $pp = $dpp * 0.1;
            $tot = round($dpp + $pp);
            $row['total_hitung'] = $tot;
            $row['selisih'] = $order->total - $tot;
            $total_cek += $tot;

            $list[] = $row;
        }

        // ---- top produk ----
        $top_produk = DB::select("SELECT oi.product_id, SUM(oi.qty) as qty, SUM(oi.subtotal) as total FROM order_items oi JOIN tbl_orders o ON o.id = oi.order_id " . $where . " AND o.status != 'deleted' GROUP BY oi.product_id ORDER BY qty DESC LIMIT 10");
        foreach ($top_produk as $tp) {
            $p = Product::find($tp->product_id);
            $tp->nama = $p ? $p->nama_barang : '-';
            $tp->kode = $p ? $p->kode_barang : '-';
        }

        // ---- top customer ----
        $top_customer = DB::select("SELECT o.customer_id, COUNT(*) as jml, SUM(o.total) as total FROM tbl_orders o " . $where . " AND o.status != 'deleted' GROUP BY o.customer_id ORDER BY total DESC LIMIT 10");
        foreach ($top_customer as $tc) {
            $c = Customer::find($tc->customer_id);
            $tc->nama = $c ? $c->nama : '-';
            $tc->kota = $c ? $c->kota : '-';
        }

        $customers = Customer::where('status', 'aktif')->orderBy('nama')->get();

        return view('laporan.index', compact('dari', 'sampai', 'customer_id', 'status', 'summary', 'per_hari', 'per_status', 'list', 'top_produk', 'top_customer', 'customers', 'total_cek', 'total_item'));
    }

    public function exportExcel(Request $request)
    {
        if (Auth::user()->type == 3) {
            return redirect('/home')->with('error', 'Tidak ada akses');
        }
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $nama = 'laporan_order_' . ($dari ? $dari : 'all') . '_' . ($sampai ? $sampai : 'all') . '.xlsx';
        return Excel::download(new OrderExport($dari, $sampai, $request->get('status')), $nama);
    }

    public function exportCsv(Request $request)
    {
        if (Auth::user()->type == 3) {
            return redirect('/home')->with('error', 'Tidak ada akses');
        }
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        $sql = "SELECT o.no_order, o.tgl_order, c.nama as customer, c.kota, u.name as sales, o.status, o.subtotal, o.diskon_persen, o.diskon, o.ppn, o.total, o.marketing_code
                FROM tbl_orders o
                LEFT JOIN tbl_customers c ON c.id = o.customer_id
                LEFT JOIN users u ON u.id = o.user_id
                WHERE o.status != 'deleted' ";
        if ($dari) $sql .= " AND o.tgl_order >= '$dari 00:00:00' ";
        if ($sampai) $sql .= " AND o.tgl_order <= '$sampai 23:59:59' ";
        $sql .= " ORDER BY o.tgl_order DESC";
        $rows = DB::select($sql);

        $filename = 'export_order_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['No Order', 'Tanggal', 'Customer', 'Kota', 'Sales', 'Status', 'Subtotal', 'Diskon %', 'Diskon', 'PPN', 'Total', 'Kode Marketing'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r->no_order,
                $r->tgl_order,
                $r->customer,
                $r->kota,
                $r->sales,
                $r->status,
                $r->subtotal,
                $r->diskon_persen,
                $r->diskon,
                $r->ppn,
                $r->total,
                $r->marketing_code,
            ], ';');
        }
        fclose($out);
        exit;
    }

    // laporan stok
    public function stok(Request $request)
    {
        $products = DB::select("SELECT p.*, (SELECT IFNULL(SUM(qty),0) FROM tbl_log_stok WHERE product_id = p.id AND tipe='out') as total_keluar, (SELECT IFNULL(SUM(qty),0) FROM tbl_log_stok WHERE product_id = p.id AND tipe='in') as total_masuk FROM products p WHERE p.status = 'aktif' ORDER BY p.stok ASC");
        return view('laporan.stok', compact('products'));
    }
}
