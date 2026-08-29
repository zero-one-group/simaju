<?php

// helper2 umum SIMAJU
// jgn dihapus, dipake dimana2

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('rupiah')) {
    // sama kaya diatas tapi udah kepake di view lama
    function rupiah($angka)
    {
        return 'Rp. ' . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('tgl_indo')) {
    function tgl_indo($tgl)
    {
        if ($tgl == null || $tgl == '') return '-';
        $bulan = array(
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );
        $split = explode('-', substr($tgl, 0, 10));
        return $split[2] . ' ' . $bulan[(int) $split[1]] . ' ' . $split[0];
    }
}

if (!function_exists('nama_role')) {
    function nama_role($type)
    {
        if ($type == 1) {
            return 'Admin';
        } else if ($type == 2) {
            return 'Staff';
        } else if ($type == 3) {
            return 'Viewer';
        } else {
            return 'Unknown';
        }
    }
}

if (!function_exists('hitung_total')) {
    /**
     * hitung total order (subtotal, diskon, ppn)
     * dipake di invoice & detail order
     * NOTE: copy dari OrderController biar konsisten
     */
    function hitung_total($subtotal, $diskon_persen, $tipe_customer = 'retail')
    {
        $diskon_persen = (float) $diskon_persen;
        // diskon otomatis
        if ($subtotal >= 20000000) {
            $diskon_persen = $diskon_persen + 5;
        } else if ($subtotal >= 5000000) {
            $diskon_persen = $diskon_persen + 2;
        }
        if ($tipe_customer == 'grosir') {
            $diskon_persen = $diskon_persen + 3;
        }
        if ($diskon_persen > 30) {
            $diskon_persen = 30; // max diskon
        }
        $diskon = $subtotal * $diskon_persen / 100;
        $ppn = $subtotal * 0.1; // ppn 10%
        $total = $subtotal + $ppn - $diskon;

        return array(
            'subtotal' => $subtotal,
            'diskon_persen' => $diskon_persen,
            'diskon' => $diskon,
            'ppn' => $ppn,
            'total' => $total,
        );
    }
}

if (!function_exists('status_label')) {
    function status_label($status)
    {
        $cls = 'default';
        if ($status == 'baru') $cls = 'info';
        if ($status == 'proses') $cls = 'warning';
        if ($status == 'selesai') $cls = 'success';
        if ($status == 'batal') $cls = 'danger';
        return '<span class="label label-' . $cls . '">' . strtoupper($status) . '</span>';
    }
}

if (!function_exists('cek_admin')) {
    function cek_admin()
    {
        if (Auth::check() && Auth::user()->type == 1) {
            return true;
        }
        return false;
    }
}
