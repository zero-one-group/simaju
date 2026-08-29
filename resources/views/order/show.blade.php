@extends('layouts.app')
@section('title', 'Order '.$order->no_order)

@section('content')
<h3>Order {{ $order->no_order }} {!! status_label($order->status) !!}
    <span class="pull-right">
        <a href="{{ url('/order/'.$order->id.'/invoice') }}" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-file-pdf-o"></i> Cetak Invoice</a>
        @if($order->invoice_file)
        <a href="{{ url('/order/'.$order->id.'/download') }}" class="btn btn-default btn-sm"><i class="fa fa-download"></i> Invoice Asli</a>
        @endif
        <a href="{{ url('/order') }}" class="btn btn-default btn-sm">Kembali</a>
    </span>
</h3>
<hr>
<div class="row">
    <div class="col-md-4">
        <table class="table table-bordered">
            <tr><th width="35%">Tanggal</th><td>{{ date('d/m/Y H:i', strtotime($order->tgl_order)) }} WIB</td></tr>
            <tr><th>Customer</th><td>
                @if($order->customer)
                <a href="{{ url('/customer/'.$order->customer->id) }}">{{ $order->customer->nama }}</a><br>
                <small>{{ $order->customer->alamat }}, {{ $order->customer->kota }}</small>
                @else - @endif
            </td></tr>
            <tr><th>Sales</th><td>{{ $order->user ? $order->user->name : '-' }}</td></tr>
            <tr><th>Marketing</th><td>{{ $order->marketing_code ? $order->marketing_code : '-' }}</td></tr>
            <tr><th>Catatan</th><td>{{ $order->catatan }}</td></tr>
        </table>

        @if(Auth::user()->type != 3 && $order->status != 'batal')
        <div class="panel panel-default">
            <div class="panel-heading">Update Status</div>
            <div class="panel-body">
                <form method="POST" action="{{ url('/order/'.$order->id.'/status') }}" class="form-inline" onsubmit="return confirm('Yakin ubah status?')">
                    @csrf
                    <select name="status" class="form-control">
                        @foreach(['baru','proses','selesai','batal'] as $s)
                        <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ strtoupper($s) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-warning">Update</button>
                </form>
            </div>
        </div>
        @endif
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
            <table class="table table-condensed">
                <thead><tr><th>#</th><th>Kode</th><th>Nama Barang</th><th class="text-right">Qty</th><th class="text-rp">Harga</th><th class="text-rp">Subtotal</th></tr></thead>
                <tbody>
                @foreach($items as $i => $it)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $it->product ? $it->product->kode_barang : '?' }}</td>
                        <td>{{ $it->product ? $it->product->nama_barang : 'produk dihapus' }}</td>
                        <td class="text-right">{{ $it->qty }} {{ $it->product ? $it->product->satuan : '' }}</td>
                        <td class="text-rp">{{ format_rupiah($it->harga) }}</td>
                        <td class="text-rp">{{ format_rupiah($it->subtotal) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="5" class="text-right">Subtotal</th><th class="text-rp">{{ format_rupiah($order->subtotal) }}</th></tr>
                    <tr><th colspan="5" class="text-right">Diskon ({{ $order->diskon_persen + 0 }}%)</th><th class="text-rp">- {{ format_rupiah($order->diskon) }}</th></tr>
                    <tr><th colspan="5" class="text-right">PPN 10%</th><th class="text-rp">{{ format_rupiah($order->ppn) }}</th></tr>
                    <tr class="success"><th colspan="5" class="text-right" style="font-size:16px">TOTAL</th><th class="text-rp" style="font-size:16px">
                        @if($order->total > 1000000 && Auth::user()->type != 3)
                            <b>{{ format_rupiah($order->total) }}</b>
                        @else
                            {{ format_rupiah($order->total) }}
                        @endif
                    </th></tr>
                </tfoot>
            </table>
        </div>
        @if(Auth::user()->type == 1 && abs($rekap['total'] - $order->total) > 1)
        <div class="alert alert-warning" style="font-size:12px">
            <i class="fa fa-warning"></i> Hasil hitung ulang: <b>{{ format_rupiah($rekap['total']) }}</b> (selisih {{ format_rupiah($rekap['total'] - $order->total) }} dari total tersimpan). Cek manual.
        </div>
        @endif
    </div>
</div>
@endsection
