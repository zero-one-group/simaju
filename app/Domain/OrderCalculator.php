<?php
namespace App\Domain;

class OrderCalculator
{
    public function calculate($subtotal, $diskon_persen, $tipe_customer = 'retail')
    {
        $diskon_persen = (float) $diskon_persen;
        
        if ($subtotal >= 20000000) {
            $diskon_persen += 5;
        } else if ($subtotal >= 5000000) {
            $diskon_persen += 2;
        }

        if ($tipe_customer == 'grosir') {
            $diskon_persen += 3;
        }

        if ($diskon_persen > 30) {
            $diskon_persen = 30; // max diskon
        }

        $diskon = $subtotal * $diskon_persen / 100;
        $ppn = $subtotal * 0.1; // ppn 10%
        $total = $subtotal + $ppn - $diskon;

        return [
            'subtotal'      => $subtotal,
            'diskon_persen' => $diskon_persen,
            'diskon'        => round($diskon),
            'ppn'           => round($ppn),
            'total'         => round($total),
        ];
    }

    public function extractManualDiskon($subtotal, $final_diskon_persen, $tipe_customer = 'retail')
    {
        $d = 0;
        if ($subtotal >= 20000000) {
            $d = 5;
        } else if ($subtotal >= 5000000) {
            $d = 2;
        }
        if ($tipe_customer == 'grosir') {
            $d += 3;
        }
        return (float) $final_diskon_persen - $d;
    }
}
