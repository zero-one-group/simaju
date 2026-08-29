@extends('layouts.app')
@section('title', 'Edit Produk')

@section('content')
<h3>Edit Produk: {{ $product->nama_barang }}</h3>
<hr>
<div class="panel panel-default"><div class="panel-body">
{!! Form::model($product, ['url' => '/produk/'.$product->id, 'method' => 'PUT']) !!}
    @include('produk._form')
    <button class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
    <a href="{{ url('/produk') }}" class="btn btn-default">Batal</a>
{!! Form::close() !!}
</div></div>
@endsection
