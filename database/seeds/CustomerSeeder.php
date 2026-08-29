<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $kota = ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Denpasar', 'Bekasi', 'Tangerang', 'Depok', 'Bogor', 'Malang', 'Solo', 'Palembang'];
        $prefix = ['Toko', 'UD', 'CV', 'PT', 'Toko', 'Toko', 'Warung', 'Grosir', 'Toko'];
        $nama_toko = ['Sumber Rejeki', 'Makmur Jaya', 'Berkah Abadi', 'Sinar Terang', 'Maju Bersama', 'Sentosa', 'Cahaya Baru', 'Rukun Makmur', 'Sejahtera', 'Bahagia', 'Mulia', 'Anugrah', 'Barokah', 'Amanah', 'Sido Makmur', 'Karya Utama', 'Jaya Abadi', 'Sumber Waras', 'Mitra Usaha', 'Prima', 'Gemilang', 'Harapan Baru', 'Subur', 'Lancar Jaya', 'Sri Rejeki'];
        $orang = ['Budi', 'Siti', 'Ahmad', 'Dewi', 'Eko', 'Rina', 'Hendra', 'Wati', 'Joko', 'Ani', 'Rudi', 'Lestari', 'Bambang', 'Yanti', 'Agus'];

        $now = date('Y-m-d H:i:s');
        $rows = [];
        for ($i = 1; $i <= 60; $i++) {
            $isPerson = rand(1, 10) > 8;
            if ($isPerson) {
                $nama = $orang[array_rand($orang)] . ' ' . $nama_toko[array_rand($nama_toko)];
            } else {
                $nama = $prefix[array_rand($prefix)] . ' ' . $nama_toko[array_rand($nama_toko)];
            }
            $k = $kota[array_rand($kota)];
            $tipe = rand(1, 10) > 6 ? 'grosir' : 'retail';
            $rows[] = [
                'kode' => 'CST' . sprintf('%04d', $i),
                'nama' => $nama,
                'tipe' => $tipe,
                'alamat' => 'Jl. ' . $nama_toko[array_rand($nama_toko)] . ' No. ' . rand(1, 200),
                'kota' => $k,
                'telp' => '08' . rand(11, 99) . rand(1000000, 9999999),
                'email' => rand(1, 10) > 3 ? strtolower(str_replace([' ', '.'], '', $nama)) . rand(1, 99) . '@gmail.com' : null,
                'npwp' => $tipe == 'grosir' ? sprintf('%02d.%03d.%03d.%d-%03d.%03d', rand(1, 99), rand(1, 999), rand(1, 999), rand(1, 9), rand(1, 999), rand(1, 999)) : null,
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('tbl_customers')->insert($rows);
    }
}
