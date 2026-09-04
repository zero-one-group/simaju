<?php
namespace App\Services;

use Mail;

class OrderNotifier
{
    private $smtp_host = 'mail.majujaya.co.id';
    private $smtp_user = 'noreply@majujaya.co.id';
    private $smtp_pass = 'M4juJ4y4@2018!';

    public function notifyCustomer($kirim_email, $customer, $no_order, $tgl_order, $items, $subtotal, $diskon_persen, $diskon, $ppn, $total, $user, $invoice_file)
    {
        if ($kirim_email == 1 || $kirim_email == 'on') {
            if ($customer->email != '' && $customer->email != null) {
                if (filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                    try {
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
                        foreach ($items as $d) {
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
                        session()->flash('msg', 'Order tersimpan tapi email gagal dikirim: ' . $e->getMessage());
                    }
                } else {
                    session()->flash('msg', 'Order tersimpan, email customer tidak valid: ' . $customer->email);
                }
            } else {
                session()->flash('msg', 'Order tersimpan, customer belum punya email');
            }
        }
    }

    public function notifyDirektur($total, $customer, $no_order, $user, $items, $total_qty, $order_id)
    {
        if ($total > 50000000) {
            try {
                $body2 = '<p>Ada order besar dari <b>' . $customer->nama . '</b> senilai <b>Rp ' . number_format($total, 0, ',', '.') . '</b></p>';
                $body2 .= '<p>No Order: ' . $no_order . '<br>Sales: ' . $user->name . '<br>Total item: ' . count($items) . ' (' . $total_qty . ' pcs)</p>';
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
    }
}
