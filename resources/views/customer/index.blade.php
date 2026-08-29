@extends('layouts.app')
@section('title', 'Customer')

@section('content')
<h3><i class="fa fa-users"></i> Data Customer
    @if(Auth::user()->type != 3)
    <a href="{{ url('/customer/create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Tambah</a>
    @endif
</h3>
<hr>
<form class="form-inline" method="GET">
    <input type="text" name="q" class="form-control" placeholder="Cari nama..." value="{{ $q }}">
    <button class="btn btn-default"><i class="fa fa-search"></i></button>
</form>
<br>
<div class="panel panel-default">
    <table class="table table-striped table-condensed">
        <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Kota</th><th>Telp</th><th>Email</th><th></th></tr></thead>
        <tbody>
        @foreach($customers as $c)
            <tr>
                <td>{{ $c->kode }}</td>
                <td><a href="{{ url('/customer/'.$c->id) }}">{{ $c->nama }}</a></td>
                <td>{{ $c->tipe }}</td>
                <td>{{ $c->kota }}</td>
                <td>{{ $c->telp }}</td>
                <td>{{ $c->email }}</td>
                <td>
                    @if(Auth::user()->type != 3)
                    <a href="{{ url('/customer/'.$c->id.'/edit') }}" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                    @endif
                    @if(Auth::user()->type == 1)
                    <form method="POST" action="{{ url('/customer/'.$c->id) }}" style="display:inline" onsubmit="return confirm('Hapus?')">
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
{{ $customers->appends(request()->all())->links() }}
@endsection
