<?php
namespace App\Services;

class PriceCalculator
{
    public function calculate(array $items, $diskon_persen, $customer)
    {
        $subtotal = 0;
        foreach ($items as $d) {
            $subtotal = $subtotal + $d['subtotal'];
        }

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

        return [
            'subtotal' => $subtotal,
            'diskon_persen' => $diskon_persen,
            'diskon' => $diskon,
            'ppn' => $ppn,
            'total' => $total,
        ];
    }
}
