@extends('layouts.app')
@section('title', 'Laporan Stok')
@section('content')
<h3><i class="fa fa-cubes"></i> Laporan Stok</h3>
<hr>
<div class="panel panel-default">
<table class="table table-condensed table-striped" id="tblStok">
    <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Rak</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Stok</th><th class="text-right">Min</th><th class="text-rp">Nilai Stok</th></tr></thead>
    <tbody>
    @foreach($products as $p)
    <tr class="{{ $p->stok <= $p->stok_minimum ? 'danger' : '' }}">
        <td>{{ $p->kode_barang }}</td>
        <td>{{ $p->nama_barang }}</td>
        <td>{{ $p->kategori }}</td>
        <td>{{ $p->rak_gudang }}</td>
        <td class="text-right">{{ $p->total_masuk }}</td>
        <td class="text-right">{{ $p->total_keluar }}</td>
        <td class="text-right"><b>{{ $p->stok }}</b></td>
        <td class="text-right">{{ $p->stok_minimum }}</td>
        <td class="text-rp">{{ format_rupiah($p->stok * $p->harga_beli) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
@section('js')
<script>$('#tblStok').DataTable({pageLength: 50});</script>
@endsection
