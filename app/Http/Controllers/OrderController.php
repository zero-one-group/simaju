<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\OrderDetail;
use App\Product;
use App\Customer;
use App\User;
use Auth;
use DB;
use Mail;
use PDF;
use Carbon\Carbon;

class OrderController extends Controller
{
    // config email, nanti dipindah ke .env kalo sempet
    // update 2019-05: server mail ganti, password baru
    var $smtp_host = 'mail.majujaya.co.id';
    var $smtp_user = 'noreply@majujaya.co.id';
    var $smtp_pass = 'M4juJ4y4@2018!';

    // api key sms gateway (zenziva), belum dipake
    const SMS_API_KEY = 'a7f3c9e1b2d84f60a1c2e3f4b5d6c7e8';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $q = $request->get('q');
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        $orders = Order::where('status', '!=', 'deleted');

        if ($status != '' && $status != 'semua') {
            $orders = $orders->where('status', $status);
        }
        if ($q != '') {
            $orders = $orders->where(function ($qq) use ($q) {
                $qq->where('no_order', 'like', '%' . $q . '%')
                    ->orWhereIn('customer_id', function ($sub) use ($q) {
                        $sub->select('id')->from('tbl_customers')->where('nama', 'like', '%' . $q . '%');
                    });
            });
        }
        if ($dari != '') {
            $orders = $orders->where('tgl_order', '>=', $dari . ' 00:00:00');
        }
        if ($sampai != '') {
            $orders = $orders->where('tgl_order', '<=', $sampai . ' 23:59:59');
        }

        $orders = $orders->orderBy('tgl_order', 'desc')->orderBy('id', 'desc')->paginate(30);

        return view('order.index', compact('orders', 'status', 'q', 'dari', 'sampai'));
    }

    public function create()
    {
        if (Auth::user()->type == 3) {
            return redirect('/order')->with('error', 'Viewer tidak bisa buat order');
        }
        $customers = Customer::where('status', 'aktif')->orderBy('nama')->get();
        $marketing = ['MKT01' => 'Budi (Jakarta)', 'MKT02' => 'Sari (Bandung)', 'MKT03' => 'Agus (Surabaya)', 'MKT04' => 'Dewi (Semarang)', 'ONL' => 'Online'];
        return view('order.create', compact('customers', 'marketing'));
    }

    /**
     * Simpan order baru
     * NOTE: jangan diubah2 lagi, udah banyak yang bergantung disini
     * last update 2019-11 (tambah marketing_code)
     */
    public function store(Request $request, \App\Actions\CreateOrder $createOrder)
    {
        $user = Auth::user();
        if ($user->type == 3) {
            return redirect('/order')->with('error', 'Viewer tidak bisa buat order');
        } elseif ($user->type != 1 && $user->type != 2) {
            return redirect('/home')->with('error', 'Tipe user tidak dikenal: ' . $user->type);
        }

        try {
            $result = $createOrder->execute($request->all(), $user);
            return redirect('/order/' . $result['order_id'])->with('success', 'Order ' . $result['no_order'] . ' berhasil disimpan. Total: Rp ' . number_format($result['total'], 0, ',', '.'));
        } catch (\App\Exceptions\OrderValidationException $e) {
            return redirect('/order/create')->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect('/order/create')->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = Order::find($id);
        if (!$order || $order->status == 'deleted') {
            abort(404);
        }
        $items = OrderDetail::where('order_id', $id)->get();
        // hitung ulang buat cek (kadang total di db beda)
        $rekap = hitung_total($order->subtotal, $order->diskon_persen - $this->diskonOtomatis($order), $order->customer ? $order->customer->tipe : 'retail');
        return view('order.show', compact('order', 'items', 'rekap'));
    }

    // buat balikin diskon persen input asli (sebelum ditambah otomatis)
    private function diskonOtomatis($order)
    {
        $d = 0;
        if ($order->subtotal >= 20000000) {
            $d = 5;
        } else if ($order->subtotal >= 5000000) {
            $d = 2;
        }
        if ($order->customer && $order->customer->tipe == 'grosir') {
            $d = $d + 3;
        }
        return $d;
    }

    public function invoice($id)
    {
        $order = Order::findOrFail($id);
        $items = OrderDetail::where('order_id', $id)->get();
        $rekap = hitung_total($order->subtotal, $order->diskon_persen - $this->diskonOtomatis($order), $order->customer ? $order->customer->tipe : 'retail');

        $pdf = PDF::loadView('order.invoice', compact('order', 'items', 'rekap'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('invoice_' . str_replace('/', '-', $order->no_order) . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        if (Auth::user()->type == 3) {
            return back()->with('error', 'Tidak ada akses');
        }
        $order = Order::findOrFail($id);
        $status_baru = $request->status;

        if ($status_baru == 'batal') {
            if ($order->status == 'selesai') {
                return back()->with('error', 'Order yang sudah selesai tidak bisa dibatalkan');
            }
            // kembalikan stok
            $items = DB::table('order_items')->where('order_id', $id)->get();
            foreach ($items as $it) {
                DB::table('products')->where('id', $it->product_id)->increment('stok', $it->qty);
                DB::table('tbl_log_stok')->insert([
                    'product_id' => $it->product_id,
                    'order_id' => $id,
                    'qty' => $it->qty,
                    'tipe' => 'in',
                    'keterangan' => 'Batal order ' . $order->no_order,
                    'user_id' => Auth::user()->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $order->status = $status_baru;
        $order->save();

        return back()->with('success', 'Status order diupdate ke ' . strtoupper($status_baru));
    }

    public function destroy($id)
    {
        if (Auth::user()->type != 1) {
            return back()->with('error', 'Hanya admin yang bisa hapus order');
        }
        // soft delete manual (jangan pake delete() beneran, data buat audit)
        DB::table('tbl_orders')->where('id', $id)->update(['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect('/order')->with('success', 'Order dihapus');
    }

    // ajax cek stok
    public function cekStok(Request $request)
    {
        $id = $request->get('id');
        $p = DB::table('products')->where('id', $id)->first();
        if ($p) {
            return response()->json(['ok' => true, 'stok' => $p->stok, 'harga' => $p->harga_jual, 'nama' => $p->nama_barang, 'satuan' => $p->satuan]);
        }
        return response()->json(['ok' => false]);
    }
}
