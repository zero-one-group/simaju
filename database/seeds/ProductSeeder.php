<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $kategori = [
            'Makanan' => ['Indomie Goreng', 'Beras Ramos', 'Minyak Goreng Bimoli', 'Gula Pasir Gulaku', 'Kecap ABC', 'Sarden ABC', 'Kornet Pronas', 'Biskuit Roma', 'Wafer Tango', 'Kopi Kapal Api', 'Teh Sariwangi', 'Susu Dancow', 'Tepung Segitiga Biru', 'Saos Sambal ABC', 'Mie Sedaap', 'Kerupuk Udang', 'Margarin Blueband', 'Garam Dolphin', 'Santan Kara', 'Sambal Terasi'],
            'Minuman' => ['Aqua Botol 600ml', 'Teh Botol Sosro', 'Coca Cola 1.5L', 'Fanta Strawberry', 'Sprite 1.5L', 'Pocari Sweat', 'Ultra Milk Coklat', 'Nutrisari Jeruk', 'Marimas', 'Kopi ABC Susu', 'Good Day Cappuccino', 'Extra Joss', 'Kratingdaeng', 'Floridina', 'Frestea'],
            'Sabun & Deterjen' => ['Rinso Deterjen 800gr', 'Sunlight 800ml', 'Lifebuoy Sabun', 'Molto Pewangi', 'So Klin Lantai', 'Wipol', 'Bayclin', 'Vixal', 'Harpic', 'Deterjen Attack', 'Sabun Giv', 'Sabun Nuvo', 'Downy Pewangi', 'Sunlight Jeruk Nipis'],
            'Perawatan Tubuh' => ['Pepsodent 190gr', 'Shampoo Clear', 'Shampoo Pantene', 'Sabun Dove', 'Rexona Roll On', 'Lifebuoy Shampoo', 'Head & Shoulders', 'Sunsilk Hitam', 'Sikat Gigi Formula', 'Hand Body Citra', 'Vaseline Lotion', 'Nivea Cream'],
            'Rokok' => ['Gudang Garam Filter', 'Sampoerna Mild', 'Djarum Super', 'Marlboro Merah', 'LA Lights', 'Surya 16', 'Class Mild', 'Dji Sam Soe'],
            'Alat Tulis' => ['Pulpen Standard', 'Buku Tulis Sinar Dunia', 'Pensil 2B Faber Castell', 'Penghapus Joyko', 'Spidol Snowman', 'Kertas HVS A4', 'Amplop Coklat', 'Lem Fox', 'Isolasi Nachi', 'Stapler Kenko'],
            'Peralatan Rumah' => ['Sapu Ijuk', 'Pel Lantai', 'Ember 20L', 'Gayung Plastik', 'Sikat WC', 'Kemoceng', 'Tempat Sampah', 'Rak Piring Plastik', 'Toples Kaca', 'Panci Set'],
            'Rempah & Bumbu' => ['Bawang Merah', 'Bawang Putih', 'Cabai Kering', 'Merica Bubuk', 'Ketumbar', 'Kunyit Bubuk', 'Jahe', 'Royco Ayam', 'Masako Sapi', 'Ladaku'],
        ];
        $satuan_opt = ['pcs', 'box', 'dus', 'karton', 'pack', 'lusin'];
        $rak_huruf = ['A', 'B', 'C', 'D', 'E'];

        $now = date('Y-m-d H:i:s');
        $rows = [];
        $no = 1;
        // ulang tiap kategori sampe total 500 produk
        while (count($rows) < 500) {
            foreach ($kategori as $kat => $items) {
                foreach ($items as $item) {
                    if (count($rows) >= 500) break 2;
                    $variant = count($rows) >= count($kategori, COUNT_RECURSIVE) ? ' - Varian ' . rand(2, 9) : '';
                    $harga_beli = rand(2, 200) * 1000;
                    $margin = rand(10, 40) / 100;
                    $harga_jual = round($harga_beli * (1 + $margin) / 500) * 500;
                    $rows[] = [
                        'kode_barang' => 'BRG' . sprintf('%05d', $no),
                        'nama_barang' => $item . $variant,
                        'kategori' => $kat,
                        'satuan' => $satuan_opt[array_rand($satuan_opt)],
                        'harga_beli' => $harga_beli,
                        'harga_jual' => $harga_jual,
                        'stok' => rand(0, 500),
                        'stok_minimum' => rand(5, 30),
                        'rak_gudang' => $rak_huruf[array_rand($rak_huruf)] . '-' . sprintf('%02d', rand(1, 20)) . '-' . sprintf('%02d', rand(1, 10)),
                        'status' => 'aktif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $no++;
                }
            }
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('products')->insert($chunk);
        }
    }
}
