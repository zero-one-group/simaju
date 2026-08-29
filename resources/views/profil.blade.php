@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<h3>Profil Saya</h3>
<hr>
<div class="row"><div class="col-md-5">
<div class="panel panel-default"><div class="panel-body">
<form method="POST" action="{{ url('/profil') }}">
    @csrf
    <div class="form-group"><label>Nama</label><input name="name" class="form-control" value="{{ $user->name }}"></div>
    <div class="form-group"><label>Email</label><input class="form-control" value="{{ $user->email }}" readonly></div>
    <div class="form-group"><label>Role</label><input class="form-control" value="{{ nama_role($user->type) }}" readonly></div>
    <div class="form-group"><label>No HP</label><input name="no_hp" class="form-control" value="{{ $user->no_hp }}"></div>
    <div class="form-group"><label>Password Baru</label><input name="password" type="password" class="form-control" placeholder="kosongkan jika tidak diganti"></div>
    <button class="btn btn-primary">Simpan</button>
</form>
</div></div>
</div></div>
@endsection
