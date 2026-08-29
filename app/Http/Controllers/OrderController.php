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
    public function store(Request $request)
    {
        $user = Auth::user();
        $flag = true;
        $errors = array();
        $data2 = array();
        $tmp = null;
        $tmp2 = null;
        $total_qty = 0;

        // ================= CEK AKSES =================
        if ($user->type == 3) {
            return redirect('/order')->with('error', 'Viewer tidak bisa buat order');
        } else {
            if ($user->type != 1) {
                if ($user->type != 2) {
                    // type aneh
                    return redirect('/home')->with('error', 'Tipe user tidak dikenal: ' . $user->type);
                }
            }
        }

        // ================= VALIDASI MANUAL =================
        $customer_id = $request->input('customer_id');
        $tgl_order = $request->input('tgl_order');
        $diskon_persen = $request->input('diskon_persen');
        $catatan = $request->input('catatan');
        $marketing_code = $request->input('marketing_code');
        $kirim_email = $request->input('kirim_email');
        $product_id = $request->input('product_id');
        $qty = $request->input('qty');
        $harga = $request->input('harga');

        if ($customer_id == '' || $customer_id == null) {
            $flag = false;
            $errors[] = 'Customer harus dipilih';
        } else {
            if (!is_numeric($customer_id)) {
                $flag = false;
                $errors[] = 'Customer tidak valid';
            } else {
                $customer = DB::table('tbl_customers')->where('id', $customer_id)->first();
                if (!$customer) {
                    $flag = false;
                    $errors[] = 'Customer tidak ditemukan';
                } else {
                    if ($customer->status != 'aktif') {
                        $flag = false;
                        $errors[] = 'Customer sudah tidak aktif';
                    }
                }
            }
        }

        if ($tgl_order == '' || $tgl_order == null) {
            // default hari ini
            $tgl_order = date('Y-m-d');
        } else {
            $tmp = explode('-', $tgl_order);
            if (count($tmp) != 3) {
                $flag = false;
                $errors[] = 'Format tanggal salah (YYYY-MM-DD)';
            } else {
                if (!checkdate((int) $tmp[1], (int) $tmp[2], (int) $tmp[0])) {
                    $flag = false;
                    $errors[] = 'Tanggal tidak valid';
                }
            }
        }
        // jam pake jam sekarang biar urut
        $tgl_order_full = $tgl_order . ' ' . date('H:i:s');

        if ($diskon_persen == '' || $diskon_persen == null) {
            $diskon_persen = 0;
        } else {
            if (!is_numeric($diskon_persen)) {
                $flag = false;
                $errors[] = 'Diskon harus angka';
            } else {
                if ($diskon_persen < 0) {
                    $flag = false;
                    $errors[] = 'Diskon tidak boleh minus';
                } else {
                    if ($diskon_persen > 30) {
                        // staff max 10%, admin max 30%
                        if ($user->type == 1) {
                            // ok
                        } else {
                            $flag = false;
                            $errors[] = 'Diskon maksimal 30%';
                        }
                    } else {
                        if ($diskon_persen > 10) {
                            if ($user->type != 1) {
                                $flag = false;
                                $errors[] = 'Staff hanya boleh diskon maksimal 10%';
                            }
                        }
                    }
                }
            }
        }

        if ($marketing_code != '' && $marketing_code != null) {
            $tmp2 = ['MKT01', 'MKT02', 'MKT03', 'MKT04', 'ONL'];
            if (!in_array($marketing_code, $tmp2)) {
                // $flag = false;
                // $errors[] = 'Kode marketing tidak valid';
                // biarin aja dulu, kadang ada kode baru dari HRD
            }
        } else {
            $marketing_code = null;
        }

        // ================= VALIDASI ITEM =================
        if (!is_array($product_id) || count($product_id) == 0) {
            $flag = false;
            $errors[] = 'Order harus ada minimal 1 item';
        } else {
            for ($i = 0; $i < count($product_id); $i++) {
                $pid = isset($product_id[$i]) ? $product_id[$i] : null;
                $q = isset($qty[$i]) ? $qty[$i] : null;
                $h = isset($harga[$i]) ? $harga[$i] : null;

                if ($pid == '' || $pid == null) {
                    // baris kosong, skip aja
                    continue;
                } else {
                    if (!is_numeric($pid)) {
                        $flag = false;
                        $errors[] = 'Produk baris ' . ($i + 1) . ' tidak valid';
                        continue;
                    } else {
                        $prod = Product::find($pid);
                        if (!$prod) {
                            $flag = false;
                            $errors[] = 'Produk baris ' . ($i + 1) . ' tidak ditemukan';
                            continue;
                        } else {
                            if ($prod->status != 'aktif') {
                                $flag = false;
                                $errors[] = 'Produk ' . $prod->nama_barang . ' sudah tidak aktif';
                                continue;
                            } else {
                                if ($q == '' || $q == null || !is_numeric($q)) {
                                    $flag = false;
                                    $errors[] = 'Qty produk ' . $prod->nama_barang . ' harus diisi';
                                    continue;
                                } else {
                                    if ($q <= 0) {
                                        $flag = false;
                                        $errors[] = 'Qty produk ' . $prod->nama_barang . ' harus lebih dari 0';
                                        continue;
                                    } else {
                                        // cek stok
                                        if ($prod->stok < $q) {
                                            // admin boleh minus stok (kasus barang baru dateng blm diinput)
                                            if ($user->type == 1) {
                                                // lanjut tp dikasih warning
                                                $tmp = 'Stok ' . $prod->nama_barang . ' kurang (' . $prod->stok . '), order tetap disimpan (admin)';
                                                session()->flash('msg', $tmp);
                                            } else {
                                                $flag = false;
                                                $errors[] = 'Stok ' . $prod->nama_barang . ' tidak cukup (tersedia: ' . $prod->stok . ')';
                                                continue;
                                            }
                                        }

                                        // harga
                                        if ($h == '' || $h == null) {
                                            $h = $prod->harga_jual;
                                        } else {
                                            $h = str_replace('.', '', $h);
                                            $h = str_replace(',', '', $h);
                                            if (!is_numeric($h)) {
                                                $h = $prod->harga_jual;
                                            } else {
                                                // staff gak boleh ubah harga dibawah harga jual
                                                if ($h < $prod->harga_jual) {
                                                    if ($user->type != 1) {
                                                        $flag = false;
                                                        $errors[] = 'Harga ' . $prod->nama_barang . ' tidak boleh dibawah harga jual';
                                                        continue;
                                                    } else {
                                                        if ($h < $prod->harga_beli) {
                                                            $flag = false;
                                                            $errors[] = 'Harga ' . $prod->nama_barang . ' dibawah harga beli!';
                                                            continue;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // cek duplikat produk
                                        $dup = false;
                                        foreach ($data2 as $k => $d) {
                                            if ($d['product_id'] == $pid) {
                                                $dup = true;
                                                // gabungin qty nya
                                                $data2[$k]['qty'] = $data2[$k]['qty'] + $q;
                                                $data2[$k]['subtotal'] = $data2[$k]['qty'] * $data2[$k]['harga'];
                                                break;
                                            }
                                        }
                                        if (!$dup) {
                                            $data2[] = array(
                                                'product_id' => $pid,
                                                'nama' => $prod->nama_barang,
                                                'kode' => $prod->kode_barang,
                                                'satuan' => $prod->satuan,
                                                'qty' => (int) $q,
                                                'harga' => (float) $h,
                                                'subtotal' => (int) $q * (float) $h,
                                                'stok_sebelum' => $prod->stok,
                                            );
                                        }
                                        $total_qty = $total_qty + $q;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (count($data2) == 0 && $flag == true) {
                $flag = false;
                $errors[] = 'Order harus ada minimal 1 item';
            }
        }

        // debug order gede, kadang error
        if (count($data2) > 50) {
            dd($data2);
        }

        if ($flag == false) {
            return redirect('/order/create')->withInput()->with('error', implode('<br>', $errors));
        }

        // ================= HITUNG TOTAL =================
        $subtotal = 0;
        foreach ($data2 as $d) {
            $subtotal = $subtotal + $d['subtotal'];
        }

        // diskon otomatis
        $diskon_persen = (float) $diskon_persen;
        if ($subtotal >= 20000000) {
            $diskon_persen = $diskon_persen + 5;
        } else if ($subtotal >= 5000000) {
            $diskon_persen = $diskon_persen + 2;
        }
        if ($customer->tipe == 'grosir') {
            $diskon_persen = $diskon_persen + 3;
        }
        if ($diskon_persen > 30) {
            $diskon_persen = 30; // max diskon
        }
        $diskon = $subtotal * $diskon_persen / 100;
        $ppn = $subtotal * 0.1; // ppn 10%
        $total = $subtotal + $ppn - $diskon;

        // pembulatan
        $total = round($total);
        $ppn = round($ppn);
        $diskon = round($diskon);

        // old logic bawah ini jgn dipake
        // $ppn = $subtotal * 0.1;
        // $total = $subtotal + $ppn - $diskon;
        // if ($customer->tipe == 'grosir') {
        //     $total = $total * 0.97;
        // }

        // ================= GENERATE NO ORDER =================
        $prefix = 'MJ/' . date('Y') . '/' . date('m') . '/';
        $last = DB::table('tbl_orders')->where('no_order', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last) {
            $tmp = explode('/', $last->no_order);
            $urut = (int) $tmp[count($tmp) - 1] + 1;
        } else {
            $urut = 1;
        }
        $no_order = $prefix . sprintf('%05d', $urut);

        // cek lagi biar gak dobel
        $cek = DB::table('tbl_orders')->where('no_order', $no_order)->count();
        if ($cek > 0) {
            $urut = $urut + 1;
            $no_order = $prefix . sprintf('%05d', $urut);
            $cek = DB::table('tbl_orders')->where('no_order', $no_order)->count();
            if ($cek > 0) {
                // masih dobel, pake timestamp aja
                $no_order = $prefix . time();
            }
        }

        // ================= SIMPAN =================
        DB::beginTransaction();
        try {
            $order_id = DB::table('tbl_orders')->insertGetId([
                'no_order' => $no_order,
                'customer_id' => $customer_id,
                'user_id' => $user->id,
                'tgl_order' => $tgl_order_full,
                'status' => 'baru',
                'subtotal' => $subtotal,
                'diskon_persen' => $diskon_persen,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'total' => $total,
                'catatan' => $catatan,
                'marketing_code' => $marketing_code,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($data2 as $d) {
                $item = new OrderDetail;
                $item->order_id = $order_id;
                $item->product_id = $d['product_id'];
                $item->qty = $d['qty'];
                $item->harga = $d['harga'];
                $item->subtotal = $d['subtotal'];
                $item->save();

                // kurangi stok
                $p = Product::find($d['product_id']);
                $p->stok = $p->stok - $d['qty'];
                $p->save();

                // log stok
                DB::table('tbl_log_stok')->insert([
                    'product_id' => $d['product_id'],
                    'order_id' => $order_id,
                    'qty' => $d['qty'],
                    'tipe' => 'out',
                    'keterangan' => 'Order ' . $no_order,
                    'user_id' => $user->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // warning stok minimum
                if ($p->stok <= $p->stok_minimum) {
                    if ($p->stok <= 0) {
                        // TODO kirim notif ke gudang
                        // mail('gudang@majujaya.co.id', 'Stok habis', $p->nama_barang);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            // \Log::error($e->getMessage());
            return redirect('/order/create')->withInput()->with('error', 'Gagal simpan order: ' . $e->getMessage());
        }

        // ================= GENERATE INVOICE PDF =================
        $order = Order::find($order_id);
        $invoice_file = null;
        try {
            $html = '';
            $html .= '<html><head><style>';
            $html .= 'body { font-family: Arial, sans-serif; font-size: 12px; }';
            $html .= 'table { width: 100%; border-collapse: collapse; }';
            $html .= 'th, td { border: 1px solid #000; padding: 4px; }';
            $html .= 'th { background: #eee; }';
            $html .= '.right { text-align: right; }';
            $html .= '.header { text-align:center; margin-bottom: 20px; }';
            $html .= '.header h2 { margin: 0; }';
            $html .= '</style></head><body>';
            $html .= '<div class="header">';
            $html .= '<h2>SIMAJU - PT Maju Jaya Distribusi</h2>';
            $html .= '<p>Jl. Raya Industri No. 88, Kawasan Industri Jababeka, Cikarang 17530<br>Telp. (021) 8934-5678 | Email: sales@majujaya.co.id</p>';
            $html .= '<h3>INVOICE</h3>';
            $html .= '</div>';
            $html .= '<table style="border:none; margin-bottom:10px;">';
            $html .= '<tr><td style="border:none; width:50%;">';
            $html .= '<b>No. Invoice:</b> ' . $no_order . '<br>';
            $html .= '<b>Tanggal:</b> ' . tgl_indo($tgl_order) . '<br>';
            $html .= '<b>Sales:</b> ' . $user->name . '<br>';
            if ($marketing_code) {
                $html .= '<b>Kode Marketing:</b> ' . $marketing_code . '<br>';
            }
            $html .= '</td><td style="border:none;">';
            $html .= '<b>Kepada:</b><br>' . $customer->nama . '<br>' . $customer->alamat . '<br>' . $customer->kota . '<br>Telp: ' . $customer->telp;
            if ($customer->npwp) {
                $html .= '<br>NPWP: ' . $customer->npwp;
            }
            $html .= '</td></tr></table>';
            $html .= '<table>';
            $html .= '<thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Subtotal</th></tr></thead>';
            $html .= '<tbody>';
            $no = 1;
            foreach ($data2 as $d) {
                $html .= '<tr>';
                $html .= '<td>' . $no . '</td>';
                $html .= '<td>' . $d['kode'] . '</td>';
                $html .= '<td>' . $d['nama'] . '</td>';
                $html .= '<td class="right">' . $d['qty'] . '</td>';
                $html .= '<td>' . $d['satuan'] . '</td>';
                $html .= '<td class="right">' . number_format($d['harga'], 0, ',', '.') . '</td>';
                $html .= '<td class="right">' . number_format($d['subtotal'], 0, ',', '.') . '</td>';
                $html .= '</tr>';
                $no++;
            }
            $html .= '</tbody>';
            $html .= '<tfoot>';
            $html .= '<tr><td colspan="6" class="right"><b>Subtotal</b></td><td class="right">' . number_format($subtotal, 0, ',', '.') . '</td></tr>';
            $html .= '<tr><td colspan="6" class="right"><b>Diskon (' . $diskon_persen . '%)</b></td><td class="right">' . number_format($diskon, 0, ',', '.') . '</td></tr>';
            $html .= '<tr><td colspan="6" class="right"><b>PPN 10%</b></td><td class="right">' . number_format($ppn, 0, ',', '.') . '</td></tr>';
            $html .= '<tr><td colspan="6" class="right"><b>TOTAL</b></td><td class="right"><b>' . number_format($total, 0, ',', '.') . '</b></td></tr>';
            $html .= '</tfoot>';
            $html .= '</table>';
            if ($catatan) {
                $html .= '<p><b>Catatan:</b> ' . nl2br($catatan) . '</p>';
            }
            $html .= '<br><br>';
            $html .= '<table style="border:none;"><tr>';
            $html .= '<td style="border:none; text-align:center; width:33%;">Hormat kami,<br><br><br><br>( ' . $user->name . ' )</td>';
            $html .= '<td style="border:none; width:33%;"></td>';
            $html .= '<td style="border:none; text-align:center; width:33%;">Penerima,<br><br><br><br>( ......................... )</td>';
            $html .= '</tr></table>';
            $html .= '<p style="font-size:10px; color:#666; margin-top:30px;">Dicetak dari SIMAJU v2.1 pada ' . date('d/m/Y H:i') . ' - PT Maju Jaya Distribusi</p>';
            $html .= '</body></html>';

            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            $invoice_file = 'invoice_' . str_replace('/', '-', $no_order) . '.pdf';
            if (!file_exists(storage_path('app/invoices'))) {
                mkdir(storage_path('app/invoices'), 0777, true);
            }
            file_put_contents(storage_path('app/invoices/' . $invoice_file), $pdf->output());

            DB::table('tbl_orders')->where('id', $order_id)->update(['invoice_file' => $invoice_file]);
        } catch (\Exception $e) {
            // gagal generate pdf gpp, bisa cetak ulang dari detail
            // \Log::error('PDF error: ' . $e->getMessage());
            $invoice_file = null;
        }

        // ================= KIRIM EMAIL =================
        if ($kirim_email == 1 || $kirim_email == 'on') {
            if ($customer->email != '' && $customer->email != null) {
                if (filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        // set config mail manual, di .env kadang kosong di server
                        config(['mail.host' => $this->smtp_host]);
                        config(['mail.username' => $this->smtp_user]);
                        config(['mail.password' => $this->smtp_pass]);

                        $body = '';
                        $body .= '<html><body style="font-family: Arial; font-size: 13px;">';
                        $body .= '<p>Yth. <b>' . $customer->nama . '</b>,</p>';
                        $body .= '<p>Terima kasih atas pesanan Anda. Berikut rincian order Anda:</p>';
                        $body .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; font-size:12px;">';
                        $body .= '<tr style="background:#eee;"><th>No</th><th>Barang</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>';
                        $no = 1;
                        foreach ($data2 as $d) {
                            $body .= '<tr>';
                            $body .= '<td>' . $no . '</td>';
                            $body .= '<td>' . $d['nama'] . '</td>';
                            $body .= '<td align="right">' . $d['qty'] . ' ' . $d['satuan'] . '</td>';
                            $body .= '<td align="right">Rp ' . number_format($d['harga'], 0, ',', '.') . '</td>';
                            $body .= '<td align="right">Rp ' . number_format($d['subtotal'], 0, ',', '.') . '</td>';
                            $body .= '</tr>';
                            $no++;
                        }
                        $body .= '<tr><td colspan="4" align="right">Subtotal</td><td align="right">Rp ' . number_format($subtotal, 0, ',', '.') . '</td></tr>';
                        if ($diskon > 0) {
                            $body .= '<tr><td colspan="4" align="right">Diskon ' . $diskon_persen . '%</td><td align="right">- Rp ' . number_format($diskon, 0, ',', '.') . '</td></tr>';
                        }
                        $body .= '<tr><td colspan="4" align="right">PPN 10%</td><td align="right">Rp ' . number_format($ppn, 0, ',', '.') . '</td></tr>';
                        $body .= '<tr><td colspan="4" align="right"><b>TOTAL</b></td><td align="right"><b>Rp ' . number_format($total, 0, ',', '.') . '</b></td></tr>';
                        $body .= '</table>';
                        $body .= '<p>No. Order: <b>' . $no_order . '</b><br>Tanggal: ' . tgl_indo($tgl_order) . '</p>';
                        $body .= '<p>Invoice terlampir. Pembayaran dapat dilakukan ke rekening BCA 123-456-7890 a.n. PT Maju Jaya Distribusi.</p>';
                        $body .= '<p>Hormat kami,<br>' . $user->name . '<br>PT Maju Jaya Distribusi</p>';
                        $body .= '<hr><p style="font-size:10px; color:#999;">Email ini dikirim otomatis oleh SIMAJU v2.1 - Sistem Informasi Maju Jaya. Mohon tidak membalas email ini.</p>';
                        $body .= '</body></html>';

                        $to = $customer->email;
                        $subject = 'Order ' . $no_order . ' - PT Maju Jaya Distribusi';
                        $attach = $invoice_file ? storage_path('app/invoices/' . $invoice_file) : null;

                        Mail::send([], [], function ($m) use ($to, $subject, $body, $attach, $customer) {
                            $m->to($to, $customer->nama);
                            $m->cc('sales@majujaya.co.id');
                            $m->subject($subject);
                            $m->setBody($body, 'text/html');
                            if ($attach && file_exists($attach)) {
                                $m->attach($attach);
                            }
                        });
                    } catch (\Exception $e) {
                        // email gagal gpp
                        session()->flash('msg', 'Order tersimpan tapi email gagal dikirim: ' . $e->getMessage());
                    }
                } else {
                    session()->flash('msg', 'Order tersimpan, email customer tidak valid: ' . $customer->email);
                }
            } else {
                session()->flash('msg', 'Order tersimpan, customer belum punya email');
            }
        }

        // ================= NOTIF ORDER BESAR =================
        if ($total > 50000000) {
            // kirim ke bos
            // TODO: pake queue, sekarang sync dulu
            try {
                $body2 = '<p>Ada order besar dari <b>' . $customer->nama . '</b> senilai <b>Rp ' . number_format($total, 0, ',', '.') . '</b></p>';
                $body2 .= '<p>No Order: ' . $no_order . '<br>Sales: ' . $user->name . '<br>Total item: ' . count($data2) . ' (' . $total_qty . ' pcs)</p>';
                $body2 .= '<p><a href="' . url('/order/' . $order_id) . '">Lihat detail</a></p>';
                $body2 .= '<p><small>SIMAJU v2.1</small></p>';
                Mail::send([], [], function ($m) use ($body2, $no_order) {
                    $m->to('direktur@majujaya.co.id');
                    $m->subject('[SIMAJU] Order Besar ' . $no_order);
                    $m->setBody($body2, 'text/html');
                });
            } catch (\Exception $e) {
                // skip
            }
        }

        // fix 2019-03 jangan dihapus
        // if ($request->has('cetak')) {
        //     return redirect('/order/' . $order_id . '/invoice');
        // }

        return redirect('/order/' . $order_id)->with('success', 'Order ' . $no_order . ' berhasil disimpan. Total: Rp ' . number_format($total, 0, ',', '.'));
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
