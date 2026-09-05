<?php
namespace App\Services;

use App\Order;
use PDF;
use DB;

class InvoiceGenerator
{
    public function generate($order_id, $no_order, $tgl_order, $user, $customer, $marketing_code, $items, $subtotal, $diskon_persen, $diskon, $ppn, $total, $catatan)
    {
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
            foreach ($items as $d) {
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
            $invoice_file = null;
        }

        return $invoice_file;
    }
}
