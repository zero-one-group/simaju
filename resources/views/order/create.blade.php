@extends('layouts.app')
@section('title', 'Order Baru')

@section('content')
<h3><i class="fa fa-plus"></i> Buat Order Baru</h3>
<hr>
{!! Form::open(['url' => '/order', 'method' => 'POST', 'id' => 'formOrder']) !!}
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">Data Order</div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Customer *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- pilih customer --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-tipe="{{ $c->tipe }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->nama }} ({{ $c->kota }}) {{ $c->tipe == 'grosir' ? '[GROSIR]' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Order</label>
                    <input type="date" name="tgl_order" class="form-control" value="{{ old('tgl_order', date('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label>Kode Marketing</label>
                    {!! Form::select('marketing_code', ['' => '-'] + $marketing, old('marketing_code'), ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    <label>Diskon Tambahan (%)</label>
                    <input type="number" name="diskon_persen" id="diskon_persen" class="form-control" value="{{ old('diskon_persen', 0) }}" min="0" max="{{ Auth::user()->type == 1 ? 100 : 10 }}" step="0.5">
                    <p class="help-block" style="font-size:11px">Diskon otomatis: &ge; 5jt +2%, &ge; 20jt +5%, grosir +3%. Max 30%.</p>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="kirim_email" value="1" checked> Kirim email ke customer</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">Item Order <button type="button" class="btn btn-xs btn-success pull-right" id="btnTambah"><i class="fa fa-plus"></i> Tambah Baris</button></div>
            <table class="table table-condensed" id="tblItem">
                <thead>
                    <tr>
                        <th width="45%">Produk</th>
                        <th width="10%">Stok</th>
                        <th width="12%">Qty</th>
                        <th width="18%">Harga</th>
                        <th width="15%" class="text-rp">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Subtotal</th>
                        <th class="text-rp" id="tSubtotal">0</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">Diskon <span id="lblDiskon"></span></th>
                        <th class="text-rp" id="tDiskon">0</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">PPN 10%</th>
                        <th class="text-rp" id="tPpn">0</th>
                        <th></th>
                    </tr>
                    <tr class="success">
                        <th colspan="4" class="text-right" style="font-size:16px">TOTAL</th>
                        <th class="text-rp" id="tTotal" style="font-size:16px">0</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <div class="panel-footer">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Simpan Order</button>
                <a href="{{ url('/order') }}" class="btn btn-default">Batal</a>
                <span class="text-muted" style="font-size:11px; margin-left:20px">* estimasi total di layar bisa sedikit beda dengan hasil akhir (pembulatan)</span>
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}

<!-- template baris -->
<table style="display:none">
    <tr id="rowTemplate">
        <td>
            <input type="hidden" name="product_id[]" class="pid">
            <input type="text" class="form-control input-sm cari-produk" placeholder="ketik nama/kode produk..." autocomplete="off">
            <div class="list-group hasil-cari" style="position:absolute; z-index:100; width:40%; max-height:250px; overflow:auto; display:none; font-size:12px;"></div>
        </td>
        <td class="stok text-muted">-</td>
        <td><input type="number" name="qty[]" class="form-control input-sm qty" value="1" min="1"></td>
        <td><input type="text" name="harga[]" class="form-control input-sm harga text-right"></td>
        <td class="text-rp sub">0</td>
        <td><button type="button" class="btn btn-xs btn-danger btnHapus"><i class="fa fa-times"></i></button></td>
    </tr>
</table>
@endsection

@section('js')
<script>
// ===== script form order =====
// TODO: pindahin ke file js sendiri
var isAdmin = {{ Auth::user()->type == 1 ? 'true' : 'false' }};

function fmt(n) {
    n = Math.round(n);
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function tambahBaris() {
    var row = $('#rowTemplate').clone().removeAttr('id');
    $('#tblItem tbody').append(row);
    row.find('.cari-produk').focus();
}

function hitung() {
    var subtotal = 0;
    $('#tblItem tbody tr').each(function() {
        var qty = parseFloat($(this).find('.qty').val()) || 0;
        var harga = parseFloat(($(this).find('.harga').val() || '0').replace(/\./g, '')) || 0;
        var sub = qty * harga;
        $(this).find('.sub').text(fmt(sub));
        subtotal += sub;
    });

    // diskon (copy logic dari controller)
    var dp = parseFloat($('#diskon_persen').val()) || 0;
    if (subtotal >= 20000000) dp += 5;
    else if (subtotal >= 5000000) dp += 2;
    var tipe = $('select[name=customer_id] option:selected').data('tipe');
    if (tipe == 'grosir') dp += 3;
    if (dp > 30) dp = 30;

    var diskon = subtotal * dp / 100;
    var ppn = subtotal * 0.1;
    var total = subtotal + ppn - diskon;

    $('#tSubtotal').text(fmt(subtotal));
    $('#lblDiskon').text('(' + dp + '%)');
    $('#tDiskon').text(fmt(diskon));
    $('#tPpn').text(fmt(ppn));
    $('#tTotal').text(fmt(total));
}

$(function() {
    tambahBaris();
    tambahBaris();
    tambahBaris();

    $('#btnTambah').click(tambahBaris);

    $(document).on('click', '.btnHapus', function() {
        $(this).closest('tr').remove();
        hitung();
    });

    var timer;
    $(document).on('keyup', '.cari-produk', function() {
        var inp = $(this);
        var q = inp.val();
        clearTimeout(timer);
        if (q.length < 2) { inp.siblings('.hasil-cari').hide(); return; }
        timer = setTimeout(function() {
            $.get('{{ url("/produk-cari") }}', {q: q}, function(res) {
                var box = inp.siblings('.hasil-cari');
                box.html('');
                if (res.length == 0) {
                    box.append('<a class="list-group-item disabled">tidak ditemukan</a>');
                }
                $.each(res, function(i, p) {
                    var a = $('<a href="#" class="list-group-item"></a>');
                    a.html('<b>' + p.kode_barang + '</b> ' + p.nama_barang + ' <span class="pull-right">Rp ' + fmt(p.harga_jual) + ' | stok ' + p.stok + '</span>');
                    a.data('p', p);
                    box.append(a);
                });
                box.show();
            });
        }, 300);
    });

    $(document).on('click', '.hasil-cari a', function(e) {
        e.preventDefault();
        var p = $(this).data('p');
        if (!p) return;
        var tr = $(this).closest('tr');
        tr.find('.pid').val(p.id);
        tr.find('.cari-produk').val(p.kode_barang + ' - ' + p.nama_barang);
        tr.find('.stok').text(p.stok + ' ' + p.satuan);
        if (p.stok <= 0) tr.find('.stok').addClass('text-danger');
        tr.find('.harga').val(fmt(p.harga_jual));
        if (!isAdmin) {
            tr.find('.harga').attr('readonly', true);
        }
        $(this).parent().hide();
        hitung();
        tr.find('.qty').focus().select();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).hasClass('cari-produk')) $('.hasil-cari').hide();
    });

    $(document).on('keyup change', '.qty, .harga, #diskon_persen', hitung);
    $('select[name=customer_id]').change(hitung);

    $('#formOrder').submit(function() {
        var ada = false;
        $('.pid').each(function(){ if ($(this).val() != '') ada = true; });
        if (!ada) {
            alert('Minimal 1 produk harus dipilih!');
            return false;
        }
        $(this).find('button[type=submit]').attr('disabled', true).text('Menyimpan...');
        return true;
    });
});
</script>
@endsection
