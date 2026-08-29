@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<h3><i class="fa fa-dashboard"></i> Dashboard</h3>
<hr>
<div class="row">
    <div class="col-md-3">
        <div class="box-stat">
            <small>Total Order</small>
            <h3>{{ number_format($total_order) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="box-stat" style="border-left-color:#5cb85c">
            <small>Order Bulan Ini</small>
            <h3>{{ number_format($order_bulan_ini) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="box-stat" style="border-left-color:#f0ad4e">
            <small>Omzet Bulan Ini</small>
            <h3 style="font-size:20px">{{ format_rupiah($omzet_bulan_ini) }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="box-stat" style="border-left-color:#d9534f">
            <small>Produk Aktif / Customer</small>
            <h3>{{ $total_produk }} / {{ $total_customer }}</h3>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="panel panel-default">
            <div class="panel-heading">Order Terakhir</div>
            <table class="table table-condensed table-striped">
                <thead>
                    <tr><th>No Order</th><th>Tanggal</th><th>Customer</th><th>Status</th><th class="text-rp">Total</th></tr>
                </thead>
                <tbody>
                @foreach($order_terakhir as $o)
                    <tr>
                        <td><a href="{{ url('/order/'.$o->id) }}">{{ $o->no_order }}</a></td>
                        <td>{{ date('d/m/Y H:i', strtotime($o->tgl_order)) }}</td>
                        <td>{{ $o->customer ? $o->customer->nama : '-' }}</td>
                        <td>{!! status_label($o->status) !!}</td>
                        <td class="text-rp">
                            @if($o->total > 1000000 && Auth::user()->type != 3)
                                <b>{{ format_rupiah($o->total) }}</b>
                            @elseif(Auth::user()->type == 3)
                                ***
                            @else
                                {{ format_rupiah($o->total) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Order 7 Hari Terakhir</div>
            <table class="table table-condensed">
                <thead><tr><th>Tanggal</th><th>Jml Order</th><th class="text-rp">Total</th></tr></thead>
                <tbody>
                @foreach($grafik as $g)
                    <tr>
                        <td>{{ tgl_indo($g['tgl']) }}</td>
                        <td>{{ $g['jml'] }}</td>
                        <td class="text-rp">{{ format_rupiah($g['total']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="panel panel-danger">
            <div class="panel-heading">Stok Menipis</div>
            <table class="table table-condensed">
                <thead><tr><th>Kode</th><th>Nama</th><th>Stok</th><th>Min</th></tr></thead>
                <tbody>
                @foreach($stok_menipis as $s)
                    <tr class="{{ $s->stok <= 0 ? 'danger' : 'warning' }}">
                        <td>{{ $s->kode_barang }}</td>
                        <td><a href="{{ url('/produk/'.$s->id) }}">{{ $s->nama_barang }}</a></td>
                        <td>{{ $s->stok }}</td>
                        <td>{{ $s->stok_minimum }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if(Auth::user()->type != 3)
        <a href="{{ url('/order/create') }}" class="btn btn-primary btn-lg btn-block"><i class="fa fa-plus"></i> Buat Order Baru</a>
        @endif
    </div>
</div>
@endsection
