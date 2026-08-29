<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->no_order }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background: #eee; }
        .right { text-align: right; }
        .header { text-align:center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .noborder td { border: none; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SIMAJU - PT Maju Jaya Distribusi</h2>
        <p>Jl. Raya Industri No. 88, Kawasan Industri Jababeka, Cikarang 17530<br>Telp. (021) 8934-5678 | Email: sales@majujaya.co.id</p>
        <h3>INVOICE</h3>
    </div>
    <table class="noborder" style="margin-bottom:10px">
        <tr>
            <td width="50%">
                <b>No. Invoice:</b> {{ $order->no_order }}<br>
                <b>Tanggal:</b> {{ tgl_indo($order->tgl_order) }}<br>
                <b>Sales:</b> {{ $order->user ? $order->user->name : '-' }}<br>
                <b>Status:</b> {{ strtoupper($order->status) }}
            </td>
            <td>
                <b>Kepada:</b><br>
                @if($order->customer)
                {{ $order->customer->nama }}<br>{{ $order->customer->alamat }}<br>{{ $order->customer->kota }}<br>Telp: {{ $order->customer->telp }}
                @if($order->customer->npwp)<br>NPWP: {{ $order->customer->npwp }}@endif
                @endif
            </td>
        </tr>
    </table>
    <table>
        <thead>
            <tr><th>No</th><th>Kode</th><th>Nama Barang</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
        @foreach($items as $i => $it)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $it->product ? $it->product->kode_barang : '-' }}</td>
                <td>{{ $it->product ? $it->product->nama_barang : '-' }}</td>
                <td class="right">{{ $it->qty }}</td>
                <td>{{ $it->product ? $it->product->satuan : '' }}</td>
                <td class="right">{{ number_format($it->harga, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            {{-- pake hasil hitung ulang biar akurat --}}
            <tr><td colspan="6" class="right"><b>Subtotal</b></td><td class="right">{{ number_format($rekap['subtotal'], 0, ',', '.') }}</td></tr>
            <tr><td colspan="6" class="right"><b>Diskon ({{ $rekap['diskon_persen'] + 0 }}%)</b></td><td class="right">{{ number_format($rekap['diskon'], 0, ',', '.') }}</td></tr>
            <tr><td colspan="6" class="right"><b>PPN 10%</b></td><td class="right">{{ number_format($rekap['ppn'], 0, ',', '.') }}</td></tr>
            <tr><td colspan="6" class="right"><b>TOTAL</b></td><td class="right"><b>{{ number_format($rekap['total'], 0, ',', '.') }}</b></td></tr>
        </tfoot>
    </table>
    @if($order->catatan)
    <p><b>Catatan:</b> {{ $order->catatan }}</p>
    @endif
    <br><br>
    <table class="noborder">
        <tr>
            <td style="text-align:center; width:33%">Hormat kami,<br><br><br><br>( {{ $order->user ? $order->user->name : '' }} )</td>
            <td style="width:33%"></td>
            <td style="text-align:center; width:33%">Penerima,<br><br><br><br>( ......................... )</td>
        </tr>
    </table>
    <p style="font-size:10px; color:#666; margin-top:30px">Dicetak dari SIMAJU v2.1 pada {{ date('d/m/Y H:i') }} - PT Maju Jaya Distribusi</p>
</body>
</html>
