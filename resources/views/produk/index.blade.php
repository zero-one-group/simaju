@extends('layouts.app')
@section('title', 'Produk')

@section('content')
<h3><i class="fa fa-cubes"></i> Data Produk
    @if(Auth::user()->type != 3)
    <a href="{{ url('/produk/create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Tambah Produk</a>
    @endif
</h3>
<hr>
{!! Form::open(['url' => '/produk', 'method' => 'GET', 'class' => 'form-inline']) !!}
    {!! Form::text('q', $q, ['class' => 'form-control', 'placeholder' => 'Cari nama/kode...']) !!}
    {!! Form::select('kategori', ['' => '-- Semua Kategori --'] + $kategoris->combine($kategoris)->toArray(), $kategori, ['class' => 'form-control']) !!}
    <button class="btn btn-default"><i class="fa fa-search"></i> Cari</button>
    <a href="{{ url('/produk') }}" class="btn btn-link">Reset</a>
{!! Form::close() !!}
<br>
<div class="panel panel-default">
    <table class="table table-striped table-condensed table-hover">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Rak</th>
                <th>Satuan</th>
                <th class="text-rp">Harga Jual</th>
                <th class="text-right">Stok</th>
                <th width="130"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($products as $p)
            <tr class="{{ $p->stok <= $p->stok_minimum ? 'warning' : '' }}">
                <td>{{ $p->kode_barang }}</td>
                <td><a href="{{ url('/produk/'.$p->id) }}">{{ $p->nama_barang }}</a></td>
                <td>{{ $p->kategori }}</td>
                <td>{{ $p->rak_gudang }}</td>
                <td>{{ $p->satuan }}</td>
                <td class="text-rp">{{ format_rupiah($p->harga_jual) }}</td>
                <td class="text-right">{{ $p->stok }}</td>
                <td>
                    @if(Auth::user()->type != 3)
                    <a href="{{ url('/produk/'.$p->id.'/edit') }}" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                    @endif
                    @if(Auth::user()->type == 1)
                    {!! Form::open(['url' => '/produk/'.$p->id, 'method' => 'DELETE', 'style' => 'display:inline', 'onsubmit' => 'return confirm("Hapus produk ini?")']) !!}
                        <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                    {!! Form::close() !!}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $products->appends(request()->all())->links() }}
@endsection
