@extends('layouts.app')
@section('title', 'Order')

@section('content')
<h3><i class="fa fa-shopping-cart"></i> Data Order
    @if(Auth::user()->type != 3)
    <a href="{{ url('/order/create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Order Baru</a>
    @endif
</h3>
<hr>
<form class="form-inline" method="GET">
    <input type="text" name="q" class="form-control" placeholder="No order / customer" value="{{ $q }}">
    <select name="status" class="form-control">
        <option value="semua">-- Semua Status --</option>
        @foreach(['baru','proses','selesai','batal'] as $s)
        <option value="{{ $s }}" {{ $status == $s ? 'selected' : '' }}>{{ strtoupper($s) }}</option>
        @endforeach
    </select>
    <input type="date" name="dari" class="form-control" value="{{ $dari }}">
    s/d
    <input type="date" name="sampai" class="form-control" value="{{ $sampai }}">
    <button class="btn btn-default"><i class="fa fa-search"></i> Filter</button>
    <a href="{{ url('/order') }}" class="btn btn-link">Reset</a>
</form>
<br>
<div class="panel panel-default">
    <table class="table table-striped table-condensed table-hover">
        <thead>
            <tr>
                <th>No Order</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Sales</th>
                <th>Item</th>
                <th>Status</th>
                <th class="text-rp">Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($orders as $o)
            <tr>
                <td><a href="{{ url('/order/'.$o->id) }}">{{ $o->no_order }}</a></td>
                <td>{{ date('d/m/Y H:i', strtotime($o->tgl_order)) }}</td>
                <td>{{ $o->customer ? $o->customer->nama : '-' }}</td>
                <td>{{ $o->user ? $o->user->name : '-' }}</td>
                <td>{{ $o->items->count() }}</td>
                <td>{!! status_label($o->status) !!}</td>
                <td class="text-rp">
                    @if($o->total > 1000000 && Auth::user()->type != 3)
                        <b>{{ format_rupiah($o->total) }}</b>
                    @else
                        {{ format_rupiah($o->total) }}
                    @endif
                </td>
                <td>
                    <a href="{{ url('/order/'.$o->id.'/invoice') }}" class="btn btn-xs btn-default" target="_blank" title="Invoice"><i class="fa fa-file-pdf-o"></i></a>
                    @if(Auth::user()->type == 1)
                    <form method="POST" action="{{ url('/order/'.$o->id) }}" style="display:inline" onsubmit="return confirm('Hapus order {{ $o->no_order }}?')">
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
{{ $orders->appends(request()->all())->links() }}
@endsection
