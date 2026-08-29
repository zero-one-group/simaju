<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $customers = DB::table('tbl_customers')->get();
        $products = DB::table('products')->get();
        $sales = DB::table('users')->whereIn('type', [1, 2])->pluck('id')->toArray();
        $marketing = ['MKT01', 'MKT02', 'MKT03', 'MKT04', 'ONL', null];
        $status_opt = ['selesai', 'selesai', 'selesai', 'proses', 'baru', 'batal'];

        // rentang tanggal 2023-2026
        $start = strtotime('2023-01-01');
        $end = strtotime('2026-08-29');

        $order_no_counter = [];

        for ($n = 0; $n < 2000; $n++) {
            $cust = $customers[array_rand($customers->all())];
            $tgl_ts = rand($start, $end);
            $tgl_order = date('Y-m-d H:i:s', $tgl_ts);
            $bulan_key = date('Y/m', $tgl_ts);
            if (!isset($order_no_counter[$bulan_key])) {
                $order_no_counter[$bulan_key] = 0;
            }
            $order_no_counter[$bulan_key]++;
            $no_order = 'MJ/' . $bulan_key . '/' . sprintf('%05d', $order_no_counter[$bulan_key]);

            // item 1-8, rata2 ~4
            $jml_item = rand(1, 8);
            $subtotal = 0;
            $items = [];
            $dipakai = [];
            for ($i = 0; $i < $jml_item; $i++) {
                $p = $products[array_rand($products->all())];
                if (in_array($p->id, $dipakai)) continue;
                $dipakai[] = $p->id;
                $qty = rand(1, 20);
                $harga = $p->harga_jual;
                $sub = $qty * $harga;
                $subtotal += $sub;
                $items[] = ['product_id' => $p->id, 'qty' => $qty, 'harga' => $harga, 'subtotal' => $sub];
            }

            // diskon
            $diskon_input = rand(0, 4) * 2.5;
            $diskon_persen = $diskon_input;
            if ($subtotal >= 20000000) $diskon_persen += 5;
            else if ($subtotal >= 5000000) $diskon_persen += 2;
            if ($cust->tipe == 'grosir') $diskon_persen += 3;
            if ($diskon_persen > 30) $diskon_persen = 30;

            $diskon = $subtotal * $diskon_persen / 100;
            $ppn = round($subtotal * 0.1);
            $total = round($subtotal + $ppn - $diskon);
            $diskon = round($diskon);

            $status = $status_opt[array_rand($status_opt)];

            $order_id = DB::table('tbl_orders')->insertGetId([
                'no_order' => $no_order,
                'customer_id' => $cust->id,
                'user_id' => $sales[array_rand($sales)],
                'tgl_order' => $tgl_order,
                'status' => $status,
                'subtotal' => $subtotal,
                'diskon_persen' => $diskon_persen,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'total' => $total,
                'catatan' => rand(1, 5) == 1 ? 'Pengiriman via ekspedisi. Mohon dicek saat terima.' : null,
                'marketing_code' => $marketing[array_rand($marketing)],
                'created_at' => $tgl_order,
                'updated_at' => $tgl_order,
            ]);

            foreach ($items as $it) {
                $it['order_id'] = $order_id;
                $it['created_at'] = $tgl_order;
                $it['updated_at'] = $tgl_order;
                DB::table('order_items')->insert($it);
            }

            if ($n % 200 == 0) {
                echo "  seeded $n orders...\n";
            }
        }
        echo "  done 2000 orders\n";
    }
}
