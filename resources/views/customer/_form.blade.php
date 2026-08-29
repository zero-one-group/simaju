<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama *</label>
            {!! Form::text('nama', null, ['class' => 'form-control', 'required']) !!}
        </div>
        <div class="form-group">
            <label>Tipe</label>
            {!! Form::select('tipe', ['retail' => 'Retail', 'grosir' => 'Grosir'], null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            <label>Alamat</label>
            {!! Form::textarea('alamat', null, ['class' => 'form-control', 'rows' => 3]) !!}
        </div>
        <div class="form-group">
            <label>Kota</label>
            {!! Form::text('kota', null, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Telp</label>
            {!! Form::text('telp', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            <label>Email</label>
            {!! Form::text('email', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            <label>NPWP</label>
            {!! Form::text('npwp', null, ['class' => 'form-control']) !!}
        </div>
    </div>
</div>
