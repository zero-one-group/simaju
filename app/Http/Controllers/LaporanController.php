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

        $report = new \App\Queries\SalesReport($dari, $sampai, $customer_id, $status);

        // ---- summary ----
        $summary = $report->getSummary();

        // ---- per hari ----
        $per_hari = $report->getPerHari();

        // ---- per status ----
        $per_status = $report->getPerStatus();

        // ---- detail order ----
        $orders = $report->getOrders();

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
            $calc = new \App\Domain\OrderCalculator();
            $tipe = $order->customer ? $order->customer->tipe : 'retail';
            $manual_dp = $calc->extractManualDiskon($order->subtotal, $order->diskon_persen, $tipe);
            $res = $calc->calculate($order->subtotal, $manual_dp, $tipe);
            $tot = $res['total'];
            
            $row['total_hitung'] = $tot;
            $row['selisih'] = $order->total - $tot;
            $total_cek += $tot;

            $list[] = $row;
        }

        // ---- top produk ----
        $top_produk = $report->getTopProduk();
        foreach ($top_produk as $tp) {
            $p = Product::find($tp->product_id);
            $tp->nama = $p ? $p->nama_barang : '-';
            $tp->kode = $p ? $p->kode_barang : '-';
        }

        // ---- top customer ----
        $top_customer = $report->getTopCustomer();
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

        $rows = \App\Queries\SalesReport::getExportCsvData($dari, $sampai);

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
