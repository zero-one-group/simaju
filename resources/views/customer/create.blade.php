@extends('layouts.app')
@section('title', 'Tambah Customer')
@section('content')
<h3>Tambah Customer</h3>
<hr>
<div class="panel panel-default"><div class="panel-body">
{!! Form::open(['url' => '/customer']) !!}
    @include('customer._form')
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ url('/customer') }}" class="btn btn-default">Batal</a>
{!! Form::close() !!}
</div></div>
@endsection
