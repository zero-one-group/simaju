@extends('layouts.app')
@section('title', 'Tambah Produk')

@section('content')
<h3>Tambah Produk</h3>
<hr>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<div class="panel panel-default"><div class="panel-body">
{!! Form::open(['url' => '/produk', 'method' => 'POST']) !!}
    @include('produk._form')
    <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
    <a href="{{ url('/produk') }}" class="btn btn-default">Batal</a>
{!! Form::close() !!}
</div></div>
@endsection
