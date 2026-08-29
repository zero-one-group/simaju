@extends('layouts.app')
@section('title', 'User')
@section('content')
<h3><i class="fa fa-user"></i> Manajemen User <button class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#modalTambah"><i class="fa fa-plus"></i> Tambah</button></h3>
<hr>
<div class="panel panel-default">
<table class="table table-striped">
    <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>No HP</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
        <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td><span class="label label-{{ $u->type == 1 ? 'danger' : ($u->type == 2 ? 'primary' : 'default') }}">{{ nama_role($u->type) }}</span></td>
            <td>{{ $u->no_hp }}</td>
            <td>
                <button class="btn btn-xs btn-warning btn-edit" data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}" data-type="{{ $u->type }}" data-hp="{{ $u->no_hp }}"><i class="fa fa-pencil"></i></button>
                @if($u->id != Auth::user()->id)
                <form method="POST" action="{{ url('/user/'.$u->id) }}" style="display:inline" onsubmit="return confirm('Hapus user?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ url('/user') }}">
            @csrf
            <div class="modal-header"><h4>Tambah User</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>Nama</label><input name="name" class="form-control" required></div>
                <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control" required></div>
                <div class="form-group"><label>Role</label><select name="type" class="form-control"><option value="1">Admin</option><option value="2" selected>Staff</option><option value="3">Viewer</option></select></div>
                <div class="form-group"><label>No HP</label><input name="no_hp" class="form-control"></div>
                <div class="form-group"><label>Password</label><input name="password" type="text" class="form-control" placeholder="default: password"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="modalEdit">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" id="formEdit">
            @csrf @method('PUT')
            <div class="modal-header"><h4>Edit User</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>Nama</label><input name="name" id="e_name" class="form-control" required></div>
                <div class="form-group"><label>Email</label><input name="email" id="e_email" type="email" class="form-control" required></div>
                <div class="form-group"><label>Role</label><select name="type" id="e_type" class="form-control"><option value="1">Admin</option><option value="2">Staff</option><option value="3">Viewer</option></select></div>
                <div class="form-group"><label>No HP</label><input name="no_hp" id="e_hp" class="form-control"></div>
                <div class="form-group"><label>Password baru</label><input name="password" type="text" class="form-control" placeholder="kosongkan jika tidak diganti"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Update</button></div>
        </form>
    </div></div>
</div>
@endsection
@section('js')
<script>
$('.btn-edit').click(function(){
    var b = $(this);
    $('#formEdit').attr('action', '{{ url("/user") }}/' + b.data('id'));
    $('#e_name').val(b.data('name'));
    $('#e_email').val(b.data('email'));
    $('#e_type').val(b.data('type'));
    $('#e_hp').val(b.data('hp'));
    $('#modalEdit').modal('show');
});
</script>
@endsection
