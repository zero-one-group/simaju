@extends('layouts.app')
@section('title', $customer->nama)
@section('content')
<h3>{{ $customer->kode }} - {{ $customer->nama }} <small>{{ $customer->tipe }}</small></h3>
<hr>
<div class="row">
    <div class="col-md-4">
        <table class="table table-bordered">
            <tr><th>Alamat</th><td>{{ $customer->alamat }}</td></tr>
            <tr><th>Kota</th><td>{{ $customer->kota }}</td></tr>
            <tr><th>Telp</th><td>{{ $customer->telp }}</td></tr>
            <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
            <tr><th>NPWP</th><td>{{ $customer->npwp }}</td></tr>
            <tr><th>Total Belanja</th><td><b>{{ format_rupiah($total) }}</b></td></tr>
        </table>
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">20 Order Terakhir</div>
            <table class="table table-condensed table-striped">
                <thead><tr><th>No Order</th><th>Tanggal</th><th>Status</th><th class="text-rp">Total</th></tr></thead>
                <tbody>
                @foreach($orders as $o)
                    <tr>
                        <td><a href="{{ url('/order/'.$o->id) }}">{{ $o->no_order }}</a></td>
                        <td>{{ date('d/m/Y', strtotime($o->tgl_order)) }}</td>
                        <td>{!! status_label($o->status) !!}</td>
                        <td class="text-rp">{{ format_rupiah($o->total) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
