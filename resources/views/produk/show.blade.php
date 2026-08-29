@extends('layouts.app')
@section('title', $product->nama_barang)

@section('content')
<h3>{{ $product->kode_barang }} - {{ $product->nama_barang }}
    @if(Auth::user()->type != 3)
    <a href="{{ url('/produk/'.$product->id.'/edit') }}" class="btn btn-warning btn-sm pull-right"><i class="fa fa-pencil"></i> Edit</a>
    @endif
</h3>
<hr>
<div class="row">
    <div class="col-md-4">
        <table class="table table-bordered">
            <tr><th>Kategori</th><td>{{ $product->kategori }}</td></tr>
            <tr><th>Satuan</th><td>{{ $product->satuan }}</td></tr>
            <tr><th>Rak Gudang</th><td>{{ $product->rak_gudang }}</td></tr>
            @if(Auth::user()->type == 1)
            <tr><th>Harga Beli</th><td>{{ format_rupiah($product->harga_beli) }}</td></tr>
            @endif
            <tr><th>Harga Jual</th><td>{{ format_rupiah($product->harga_jual) }}</td></tr>
            <tr><th>Stok</th><td><b class="{{ $product->stok <= $product->stok_minimum ? 'text-danger' : '' }}">{{ $product->stok }}</b> (min {{ $product->stok_minimum }})</td></tr>
            <tr><th>Status</th><td>{{ $product->status }}</td></tr>
        </table>
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">Riwayat Stok (50 terakhir)</div>
            <table class="table table-condensed table-striped">
                <thead><tr><th>Tanggal</th><th>Tipe</th><th>Qty</th><th>Keterangan</th></tr></thead>
                <tbody>
                @foreach($log as $l)
                    <tr>
                        <td>{{ $l->created_at }}</td>
                        <td>{!! $l->tipe == 'in' ? '<span class="label label-success">MASUK</span>' : '<span class="label label-danger">KELUAR</span>' !!}</td>
                        <td>{{ $l->qty }}</td>
                        <td>{{ $l->keterangan }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
