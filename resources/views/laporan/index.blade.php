@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
<h3><i class="fa fa-bar-chart"></i> Laporan Penjualan</h3>
<hr>
<div class="panel panel-default">
    <div class="panel-body">
        <form class="form-inline" method="GET">
            <label>Dari</label>
            <input type="text" name="dari" class="form-control" placeholder="YYYY-MM-DD" value="{{ $dari }}">
            <label>Sampai</label>
            <input type="text" name="sampai" class="form-control" placeholder="YYYY-MM-DD" value="{{ $sampai }}">
            <select name="customer_id" class="form-control">
                <option value="">-- Semua Customer --</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ $customer_id == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control">
                <option value="semua">-- Semua Status --</option>
                @foreach(['baru','proses','selesai','batal'] as $s)
                <option value="{{ $s }}" {{ $status == $s ? 'selected' : '' }}>{{ strtoupper($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
            <a href="{{ url('/laporan') }}" class="btn btn-link">Reset</a>
            <span class="pull-right">
                <a href="{{ url('/laporan/export') }}?{{ http_build_query(request()->all()) }}" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                <a href="{{ url('/laporan/export-csv') }}?{{ http_build_query(request()->all()) }}" class="btn btn-default"><i class="fa fa-file-text-o"></i> CSV</a>
            </span>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-3"><div class="box-stat"><small>Jumlah Order</small><h3>{{ number_format($summary->jml_order) }}</h3></div></div>
    <div class="col-md-3"><div class="box-stat"><small>Subtotal</small><h3 style="font-size:18px">{{ format_rupiah($summary->subtotal) }}</h3></div></div>
    <div class="col-md-3"><div class="box-stat"><small>Diskon / PPN</small><h3 style="font-size:14px">{{ format_rupiah($summary->diskon) }}<br>{{ format_rupiah($summary->ppn) }}</h3></div></div>
    <div class="col-md-3"><div class="box-stat" style="border-left-color:#5cb85c"><small>Total Omzet</small><h3 style="font-size:18px">{{ format_rupiah($summary->total) }}</h3></div></div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Per Hari</div>
            <div style="max-height:400px; overflow:auto">
            <table class="table table-condensed table-striped">
                <thead><tr><th>Tanggal</th><th>Order</th><th class="text-rp">Total</th></tr></thead>
                <tbody>
                @foreach($per_hari as $h)
                    <tr><td>{{ tgl_indo($h->tgl) }}</td><td>{{ $h->jml }}</td><td class="text-rp">{{ format_rupiah($h->total) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">Per Status</div>
            <table class="table table-condensed">
                @foreach($per_status as $s)
                <tr><td>{!! status_label($s->status) !!}</td><td>{{ $s->jml }}</td><td class="text-rp">{{ format_rupiah($s->total) }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Top 10 Produk</div>
            <table class="table table-condensed table-striped">
                <thead><tr><th>Produk</th><th class="text-right">Qty</th><th class="text-rp">Total</th></tr></thead>
                @foreach($top_produk as $tp)
                <tr><td><small>{{ $tp->kode }}</small> {{ $tp->nama }}</td><td class="text-right">{{ number_format($tp->qty) }}</td><td class="text-rp">{{ format_rupiah($tp->total) }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Top 10 Customer</div>
            <table class="table table-condensed table-striped">
                <thead><tr><th>Customer</th><th>Order</th><th class="text-rp">Total</th></tr></thead>
                @foreach($top_customer as $tc)
                <tr><td>{{ $tc->nama }} <small class="text-muted">{{ $tc->kota }}</small></td><td>{{ $tc->jml }}</td><td class="text-rp">{{ format_rupiah($tc->total) }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">Detail Order ({{ count($list) }} order, {{ number_format($total_item) }} item)
        <span class="pull-right text-muted" style="font-weight:normal; font-size:11px">Total hitung ulang: {{ format_rupiah($total_cek) }}</span>
    </div>
    <div class="table-responsive">
    <table class="table table-condensed table-striped" id="tblDetail" style="font-size:12px">
        <thead>
            <tr>
                <th>No Order</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Kota</th>
                <th>Sales</th>
                <th>Produk</th>
                <th>Status</th>
                <th class="text-rp">Subtotal</th>
                <th class="text-rp">Diskon</th>
                <th class="text-rp">PPN</th>
                <th class="text-rp">Total</th>
                <th class="text-rp">Selisih</th>
            </tr>
        </thead>
        <tbody>
        @foreach($list as $r)
            <tr>
                <td><a href="{{ url('/order/'.$r['id']) }}">{{ $r['no_order'] }}</a></td>
                <td>{{ date('d/m/y H:i', strtotime($r['tgl_order'])) }}</td>
                <td>{{ $r['customer'] }}</td>
                <td>{{ $r['kota'] }}</td>
                <td>{{ $r['sales'] }}</td>
                <td><small>{{ $r['produk'] }}</small></td>
                <td>{!! status_label($r['status']) !!}</td>
                <td class="text-rp">{{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                <td class="text-rp">{{ number_format($r['diskon'], 0, ',', '.') }}</td>
                <td class="text-rp">{{ number_format($r['ppn'], 0, ',', '.') }}</td>
                <td class="text-rp">
                    @if($r['total'] > 1000000 && Auth::user()->type != 3)
                        <b>{{ number_format($r['total'], 0, ',', '.') }}</b>
                    @else
                        {{ number_format($r['total'], 0, ',', '.') }}
                    @endif
                </td>
                <td class="text-rp {{ abs($r['selisih']) > 1 ? 'text-danger' : 'text-muted' }}">{{ number_format($r['selisih'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection

@section('js')
<script>
// datatable dimatiin dulu, berat kalo data banyak
// $('#tblDetail').DataTable({ pageLength: 50, order: [[1, 'desc']] });
</script>
@endsection
