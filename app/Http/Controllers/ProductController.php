<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use Auth;
use DB;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $q = $request->get('q');
        $kategori = $request->get('kategori');

        $products = Product::where('status', '!=', 'deleted');
        if ($q != '') {
            $products = $products->where(function ($query) use ($q) {
                $query->where('nama_barang', 'like', '%' . $q . '%')
                    ->orWhere('kode_barang', 'like', '%' . $q . '%');
            });
        }
        if ($kategori != '') {
            $products = $products->where('kategori', $kategori);
        }
        $products = $products->orderBy('nama_barang')->paginate(25);

        $kategoris = DB::table('products')->select('kategori')->distinct()->orderBy('kategori')->pluck('kategori');

        return view('produk.index', compact('products', 'q', 'kategori', 'kategoris'));
    }

    public function create()
    {
        if (Auth::user()->type == 3) {
            return redirect('/produk')->with('error', 'Anda tidak punya akses');
        }
        $kategoris = DB::table('products')->select('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
        return view('produk.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->type == 3) {
            return redirect('/produk')->with('error', 'Anda tidak punya akses');
        }

        $request->validate([
            'kode_barang' => 'required',
            'nama_barang' => 'required',
            'harga_jual' => 'required|numeric',
        ]);

        $product = new Product;
        $product->kode_barang = $request->kode_barang;
        $product->nama_barang = $request->nama_barang;
        $product->kategori = $request->kategori;
        $product->satuan = $request->satuan ? $request->satuan : 'pcs';
        $product->harga_beli = str_replace('.', '', $request->harga_beli);
        $product->harga_jual = str_replace('.', '', $request->harga_jual);
        $product->stok = $request->stok ? $request->stok : 0;
        $product->stok_minimum = $request->stok_minimum ? $request->stok_minimum : 10;
        $product->rak_gudang = $request->rak_gudang;
        $product->status = 'aktif';
        $product->save();

        if ($product->stok > 0) {
            DB::table('tbl_log_stok')->insert([
                'product_id' => $product->id,
                'qty' => $product->stok,
                'tipe' => 'in',
                'keterangan' => 'stok awal',
                'user_id' => Auth::user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect('/produk')->with('success', 'Produk berhasil disimpan');
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            abort(404);
        }
        $log = DB::table('tbl_log_stok')->where('product_id', $id)->orderBy('id', 'desc')->take(50)->get();
        return view('produk.show', compact('product', 'log'));
    }

    public function edit($id)
    {
        if (Auth::user()->type == 3) {
            return redirect('/produk')->with('error', 'Anda tidak punya akses');
        }
        $product = Product::findOrFail($id);
        $kategoris = DB::table('products')->select('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
        return view('produk.edit', compact('product', 'kategoris'));
    }

    public function update(Request $request, $id)
    {
        // TODO validasi
        $product = Product::findOrFail($id);
        $stok_lama = $product->stok;

        $product->kode_barang = $request->kode_barang;
        $product->nama_barang = $request->nama_barang;
        $product->kategori = $request->kategori;
        $product->satuan = $request->satuan;
        $product->harga_beli = str_replace('.', '', $request->harga_beli);
        $product->harga_jual = str_replace('.', '', $request->harga_jual);
        $product->stok = $request->stok;
        $product->stok_minimum = $request->stok_minimum;
        $product->rak_gudang = $request->rak_gudang;
        $product->save();

        // log kalo stok berubah
        if ($stok_lama != $request->stok) {
            $selisih = $request->stok - $stok_lama;
            DB::table('tbl_log_stok')->insert([
                'product_id' => $product->id,
                'qty' => abs($selisih),
                'tipe' => $selisih > 0 ? 'in' : 'out',
                'keterangan' => 'penyesuaian manual',
                'user_id' => Auth::user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect('/produk')->with('success', 'Produk berhasil diupdate');
    }

    public function destroy($id)
    {
        if (Auth::user()->type != 1) {
            return redirect('/produk')->with('error', 'Hanya admin yang bisa hapus produk');
        }
        // soft delete manual
        DB::table('products')->where('id', $id)->update(['status' => 'deleted']);
        return redirect('/produk')->with('success', 'Produk dihapus');
    }

    // ajax untuk form order
    public function cari(Request $request)
    {
        $q = $request->get('q');
        $rows = DB::select("SELECT id, kode_barang, nama_barang, harga_jual, stok, satuan FROM products WHERE status='aktif' AND (nama_barang LIKE '%$q%' OR kode_barang LIKE '%$q%') ORDER BY nama_barang LIMIT 20");
        return response()->json($rows);
    }
}
