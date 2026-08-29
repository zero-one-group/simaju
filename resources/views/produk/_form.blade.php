<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('kode_barang', 'Kode Barang *') !!}
            {!! Form::text('kode_barang', null, ['class' => 'form-control', 'required']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('nama_barang', 'Nama Barang *') !!}
            {!! Form::text('nama_barang', null, ['class' => 'form-control', 'required']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('kategori', 'Kategori') !!}
            {!! Form::text('kategori', null, ['class' => 'form-control', 'list' => 'kategori-list']) !!}
            <datalist id="kategori-list">
                @foreach($kategoris as $k)<option value="{{ $k }}">@endforeach
            </datalist>
        </div>
        <div class="form-group">
            {!! Form::label('satuan', 'Satuan') !!}
            {!! Form::select('satuan', ['pcs' => 'pcs', 'box' => 'box', 'dus' => 'dus', 'karton' => 'karton', 'kg' => 'kg', 'liter' => 'liter', 'pack' => 'pack', 'lusin' => 'lusin'], null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('rak_gudang', 'Rak Gudang') !!}
            {!! Form::text('rak_gudang', null, ['class' => 'form-control', 'placeholder' => 'contoh: A-01-03']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('harga_beli', 'Harga Beli') !!}
            {!! Form::text('harga_beli', null, ['class' => 'form-control angka']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('harga_jual', 'Harga Jual *') !!}
            {!! Form::text('harga_jual', null, ['class' => 'form-control angka', 'required']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('stok', 'Stok') !!}
            {!! Form::number('stok', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('stok_minimum', 'Stok Minimum') !!}
            {!! Form::number('stok_minimum', null, ['class' => 'form-control']) !!}
        </div>
    </div>
</div>
