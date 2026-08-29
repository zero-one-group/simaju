@extends('layouts.app')
@section('title', 'Edit Customer')
@section('content')
<h3>Edit Customer: {{ $customer->nama }}</h3>
<hr>
<div class="panel panel-default"><div class="panel-body">
{!! Form::model($customer, ['url' => '/customer/'.$customer->id, 'method' => 'PUT']) !!}
    @include('customer._form')
    <button class="btn btn-primary">Update</button>
    <a href="{{ url('/customer') }}" class="btn btn-default">Batal</a>
{!! Form::close() !!}
</div></div>
@endsection
