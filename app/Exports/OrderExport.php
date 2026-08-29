<?php

namespace App\Exports;

use App\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use DB;

class OrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $dari;
    protected $sampai;
    protected $status;

    public function __construct($dari = null, $sampai = null, $status = null)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
        $this->status = $status;
    }

    public function collection()
    {
        $q = Order::where('status', '!=', 'deleted');
        if ($this->dari) {
            $q->where('tgl_order', '>=', $this->dari . ' 00:00:00');
        }
        if ($this->sampai) {
            $q->where('tgl_order', '<=', $this->sampai . ' 23:59:59');
        }
        if ($this->status && $this->status != 'semua') {
            $q->where('status', $this->status);
        }
        return $q->orderBy('tgl_order', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No Order',
            'Tanggal',
            'Customer',
            'Kota',
            'Sales',
            'Kode Marketing',
            'Status',
            'Jml Item',
            'Subtotal',
            'Diskon %',
            'Diskon',
            'PPN',
            'Total',
        ];
    }

    public function map($order): array
    {
        // N+1 juga disini tp gpp export jarang dipake
        return [
            $order->no_order,
            $order->tgl_order,
            $order->customer ? $order->customer->nama : '-',
            $order->customer ? $order->customer->kota : '-',
            $order->user ? $order->user->name : '-',
            $order->marketing_code,
            $order->status,
            $order->items->count(),
            $order->subtotal,
            $order->diskon_persen,
            $order->diskon,
            $order->ppn,
            $order->total,
        ];
    }
}
