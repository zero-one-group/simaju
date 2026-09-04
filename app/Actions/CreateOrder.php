<?php
namespace App\Actions;

use DB;
use App\Product;
use App\OrderDetail;
use App\Exceptions\OrderValidationException;
use App\Services\PriceCalculator;
use App\Services\StockChecker;
use App\Services\InvoiceGenerator;
use App\Services\OrderNotifier;

class CreateOrder
{
    protected $stockChecker;
    protected $priceCalculator;
    protected $invoiceGenerator;
    protected $orderNotifier;

    public function __construct(
        StockChecker $stockChecker, 
        PriceCalculator $priceCalculator, 
        InvoiceGenerator $invoiceGenerator, 
        OrderNotifier $orderNotifier
    ) {
        $this->stockChecker = $stockChecker;
        $this->priceCalculator = $priceCalculator;
        $this->invoiceGenerator = $invoiceGenerator;
        $this->orderNotifier = $orderNotifier;
    }

    public function execute(array $input, $user)
    {
        $customer_id = isset($input['customer_id']) ? $input['customer_id'] : null;
        $tgl_order = isset($input['tgl_order']) ? $input['tgl_order'] : null;
        $diskon_persen = isset($input['diskon_persen']) ? $input['diskon_persen'] : null;
        $catatan = isset($input['catatan']) ? $input['catatan'] : null;
        $marketing_code = isset($input['marketing_code']) ? $input['marketing_code'] : null;
        $kirim_email = isset($input['kirim_email']) ? $input['kirim_email'] : null;

        if ($customer_id == '' || $customer_id == null) {
            throw new OrderValidationException('Customer harus dipilih');
        }
        if (!is_numeric($customer_id)) {
            throw new OrderValidationException('Customer tidak valid');
        }
        $customer = DB::table('tbl_customers')->where('id', $customer_id)->first();
        if (!$customer) {
            throw new OrderValidationException('Customer tidak ditemukan');
        }
        if ($customer->status != 'aktif') {
            throw new OrderValidationException('Customer sudah tidak aktif');
        }

        if ($tgl_order == '' || $tgl_order == null) {
            $tgl_order = date('Y-m-d');
        } else {
            $tmp = explode('-', $tgl_order);
            if (count($tmp) != 3) {
                throw new OrderValidationException('Format tanggal salah (YYYY-MM-DD)');
            }
            if (!checkdate((int) $tmp[1], (int) $tmp[2], (int) $tmp[0])) {
                throw new OrderValidationException('Tanggal tidak valid');
            }
        }
        $tgl_order_full = $tgl_order . ' ' . date('H:i:s');

        if ($diskon_persen == '' || $diskon_persen == null) {
            $diskon_persen = 0;
        } else {
            if (!is_numeric($diskon_persen)) {
                throw new OrderValidationException('Diskon harus angka');
            }
            if ($diskon_persen < 0) {
                throw new OrderValidationException('Diskon tidak boleh minus');
            }
            if ($diskon_persen > 30) {
                if ($user->type != 1) {
                    throw new OrderValidationException('Diskon maksimal 30%');
                }
            } else if ($diskon_persen > 10) {
                if ($user->type != 1) {
                    throw new OrderValidationException('Staff hanya boleh diskon maksimal 10%');
                }
            }
        }

        if ($marketing_code == '' || $marketing_code == null) {
            $marketing_code = null;
        }

        $stockPrep = $this->stockChecker->checkAndPrepareItems($input, $user);
        $data2 = $stockPrep['items'];
        $total_qty = $stockPrep['total_qty'];

        $prices = $this->priceCalculator->calculate($data2, $diskon_persen, $customer);
        $subtotal = $prices['subtotal'];
        $diskon_persen_final = $prices['diskon_persen'];
        $diskon = $prices['diskon'];
        $ppn = $prices['ppn'];
        $total = $prices['total'];

        $prefix = 'MJ/' . date('Y') . '/' . date('m') . '/';
        $last = DB::table('tbl_orders')->where('no_order', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last) {
            $tmp = explode('/', $last->no_order);
            $urut = (int) $tmp[count($tmp) - 1] + 1;
        } else {
            $urut = 1;
        }
        $no_order = $prefix . sprintf('%05d', $urut);

        $cek = DB::table('tbl_orders')->where('no_order', $no_order)->count();
        if ($cek > 0) {
            $urut = $urut + 1;
            $no_order = $prefix . sprintf('%05d', $urut);
            $cek = DB::table('tbl_orders')->where('no_order', $no_order)->count();
            if ($cek > 0) {
                $no_order = $prefix . time();
            }
        }

        DB::beginTransaction();
        try {
            $order_id = DB::table('tbl_orders')->insertGetId([
                'no_order' => $no_order,
                'customer_id' => $customer_id,
                'user_id' => $user->id,
                'tgl_order' => $tgl_order_full,
                'status' => 'baru',
                'subtotal' => $subtotal,
                'diskon_persen' => $diskon_persen_final,
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

                $p = Product::find($d['product_id']);
                $p->stok = $p->stok - $d['qty'];
                $p->save();

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
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Gagal simpan order: ' . $e->getMessage());
        }

        $invoice_file = $this->invoiceGenerator->generate(
            $order_id, $no_order, $tgl_order, $user, $customer, $marketing_code, 
            $data2, $subtotal, $diskon_persen_final, $diskon, $ppn, $total, $catatan
        );

        $this->orderNotifier->notifyCustomer(
            $kirim_email, $customer, $no_order, $tgl_order, $data2, 
            $subtotal, $diskon_persen_final, $diskon, $ppn, $total, $user, $invoice_file
        );

        $this->orderNotifier->notifyDirektur(
            $total, $customer, $no_order, $user, $data2, $total_qty, $order_id
        );

        return [
            'order_id' => $order_id,
            'no_order' => $no_order,
            'total' => $total,
        ];
    }
}
