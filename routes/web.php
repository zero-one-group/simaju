<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// SIMAJU v2.1
// routes utama
// update terakhir 2019-11 (tambah marketing)

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false, 'verify' => false]);

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/dashboard', 'HomeController@index'); // alias, dipake di bookmark orang2
Route::get('/index', function () {
    return redirect('/home');
});
Route::get('/index.php', function () {
    return redirect('/home');
});

// ==================== PRODUK ====================
Route::get('/produk', 'ProductController@index');
Route::get('/produk/create', 'ProductController@create');
Route::post('/produk', 'ProductController@store');
Route::get('/produk/{id}', 'ProductController@show');
Route::get('/produk/{id}/edit', 'ProductController@edit');
Route::put('/produk/{id}', 'ProductController@update');
Route::post('/produk/{id}/update', 'ProductController@update'); // buat form lama yg gak support PUT
Route::delete('/produk/{id}', 'ProductController@destroy');
Route::get('/produk/{id}/hapus', 'ProductController@destroy'); // link hapus dari halaman lama
Route::get('/produk-cari', 'ProductController@cari');
Route::get('/ajax/produk', 'ProductController@cari'); // alias
Route::any('/ajax/cek-stok', 'OrderController@cekStok');

// alias english (awalnya mau pake english semua tp ga jadi)
Route::get('/product', function () {
    return redirect('/produk');
});
Route::get('/products', function () {
    return redirect('/produk');
});
Route::get('/barang', function () {
    return redirect('/produk');
});

// ==================== CUSTOMER ====================
Route::get('/customer', 'CustomerController@index');
Route::get('/customer/create', 'CustomerController@create');
Route::post('/customer', 'CustomerController@store');
Route::get('/customer/{id}', 'CustomerController@show');
Route::get('/customer/{id}/edit', 'CustomerController@edit');
Route::put('/customer/{id}', 'CustomerController@update');
Route::post('/customer/{id}/update', 'CustomerController@update');
Route::delete('/customer/{id}', 'CustomerController@destroy');
Route::get('/customers', function () {
    return redirect('/customer');
});
Route::get('/pelanggan', function () {
    return redirect('/customer');
});

// ajax customer (buat select2, blm dipake)
Route::get('/ajax/customer', function (Illuminate\Http\Request $request) {
    $q = $request->get('q');
    $rows = DB::select("SELECT id, kode, nama, kota, tipe FROM tbl_customers WHERE status='aktif' AND nama LIKE '%$q%' ORDER BY nama LIMIT 20");
    return response()->json($rows);
})->middleware('auth');

// ==================== ORDER ====================
Route::get('/order', 'OrderController@index');
Route::get('/order/create', 'OrderController@create');
Route::post('/order', 'OrderController@store');
Route::post('/order/store', 'OrderController@store'); // alias
Route::post('/order/simpan', 'OrderController@store'); // alias lama
Route::get('/order/{id}', 'OrderController@show');
Route::get('/order/{id}/invoice', 'OrderController@invoice');
Route::get('/order/{id}/cetak', 'OrderController@invoice'); // alias
Route::get('/order/{id}/pdf', 'OrderController@invoice'); // alias
Route::post('/order/{id}/status', 'OrderController@updateStatus');
Route::get('/order/{id}/status/{status}', function ($id, $status) {
    // shortcut buat update status via link (dipake di email dulu, skrg ga)
    if (Auth::user()->type == 3) {
        return redirect('/order/' . $id)->with('error', 'Tidak ada akses');
    }
    DB::table('tbl_orders')->where('id', $id)->update(['status' => $status]);
    return redirect('/order/' . $id)->with('success', 'Status diupdate');
})->middleware('auth');
Route::delete('/order/{id}', 'OrderController@destroy');
Route::get('/order/{id}/hapus', 'OrderController@destroy');

// download invoice asli yg digenerate waktu order dibuat
Route::get('/order/{id}/download', function ($id) {
    $order = DB::table('tbl_orders')->where('id', $id)->first();
    if (!$order || !$order->invoice_file) {
        abort(404);
    }
    $path = storage_path('app/invoices/' . $order->invoice_file);
    if (!file_exists($path)) {
        return redirect('/order/' . $id)->with('error', 'File invoice tidak ditemukan di server');
    }
    return response()->download($path);
})->middleware('auth');

Route::get('/orders', function () {
    return redirect('/order');
});
Route::get('/penjualan', function () {
    return redirect('/order');
});
Route::get('/transaksi', function () {
    return redirect('/order');
});

// ==================== LAPORAN ====================
Route::get('/laporan', 'LaporanController@index');
Route::get('/laporan/penjualan', 'LaporanController@index');
Route::get('/laporan/export', 'LaporanController@exportExcel');
Route::get('/laporan/export-excel', 'LaporanController@exportExcel');
Route::get('/laporan/excel', 'LaporanController@exportExcel');
Route::get('/laporan/export-csv', 'LaporanController@exportCsv');
Route::get('/laporan/csv', 'LaporanController@exportCsv');
Route::get('/laporan/stok', 'LaporanController@stok');
Route::get('/report', function () {
    return redirect('/laporan');
});
Route::get('/reports', function () {
    return redirect('/laporan');
});

// laporan cepat per bulan (request pak Hendra)
Route::get('/laporan/bulan/{tahun}/{bulan}', function ($tahun, $bulan) {
    $dari = $tahun . '-' . sprintf('%02d', $bulan) . '-01';
    $sampai = date('Y-m-t', strtotime($dari));
    return redirect('/laporan?dari=' . $dari . '&sampai=' . $sampai);
});

// ==================== USER ====================
Route::get('/user', 'UserController@index');
Route::post('/user', 'UserController@store');
Route::put('/user/{id}', 'UserController@update');
Route::delete('/user/{id}', 'UserController@destroy');
Route::get('/users', function () {
    return redirect('/user');
});

// profil sendiri
Route::get('/profil', function () {
    return view('profil', ['user' => Auth::user()]);
})->middleware('auth');
Route::post('/profil', function (Illuminate\Http\Request $request) {
    $u = Auth::user();
    $u->name = $request->name;
    $u->no_hp = $request->no_hp;
    if ($request->password != '') {
        $u->password = Hash::make($request->password);
    }
    $u->save();
    return redirect('/profil')->with('success', 'Profil diupdate');
})->middleware('auth');

// ==================== UTILITY ====================
// cek koneksi (buat monitoring uptime robot)
Route::get('/ping', function () {
    return 'pong ' . date('Y-m-d H:i:s');
});
Route::get('/health', function () {
    try {
        DB::select('SELECT 1');
        return response()->json(['status' => 'ok', 'db' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'db' => $e->getMessage()], 500);
    }
});

// clear cache dari browser (kalo ada masalah view)
Route::get('/clear-cache', function () {
    if (!Auth::check() || Auth::user()->type != 1) {
        abort(403);
    }
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    return 'cache cleared';
});

// info php (buat cek server)
// Route::get('/phpinfo', function () {
//     phpinfo();
// });

// backup db manual
// Route::get('/backup', function () {
//     $file = storage_path('app/backup_' . date('Ymd_His') . '.sql');
//     exec('mysqldump -u root simaju > ' . $file);
//     return response()->download($file);
// });

// ==================== API (buat aplikasi android, blm jadi) ====================
Route::any('/api/v1/produk', function () {
    return response()->json(DB::table('products')->where('status', 'aktif')->get());
});
Route::any('/api/v1/cek-stok/{kode}', function ($kode) {
    $p = DB::table('products')->where('kode_barang', $kode)->first();
    if ($p) {
        return response()->json(['ok' => true, 'stok' => $p->stok, 'nama' => $p->nama_barang]);
    }
    return response()->json(['ok' => false]);
});
// Route::post('/api/v1/order', 'Api\OrderController@store');
// Route::get('/api/v1/order/{id}', 'Api\OrderController@show');

// ==================== OLD / DEPRECATED ====================
// fix 2019-03 jangan dihapus, masih ada yg bookmark
Route::get('/order/list', function () {
    return redirect('/order');
});
Route::get('/order/baru', function () {
    return redirect('/order/create');
});
Route::get('/laporan/harian', function () {
    return redirect('/laporan?dari=' . date('Y-m-d') . '&sampai=' . date('Y-m-d'));
});
Route::get('/laporan/bulanan', function () {
    return redirect('/laporan?dari=' . date('Y-m-01') . '&sampai=' . date('Y-m-t'));
});

// old logic bawah ini jgn dipake
// Route::get('/laporan/old', 'ReportController@index');
// Route::get('/laporan/old/export', 'ReportController@export');
// Route::resource('barang', 'BarangController');

// test email
Route::get('/test-email', function () {
    if (!Auth::check() || Auth::user()->type != 1) {
        abort(403);
    }
    Mail::send([], [], function ($m) {
        $m->to('admin@majujaya.co.id');
        $m->subject('Test email SIMAJU');
        $m->setBody('<p>Test email dari SIMAJU v2.1 ' . date('Y-m-d H:i:s') . '</p>', 'text/html');
    });
    return 'email terkirim (cek log)';
});

// logout via GET (buat link di email/bookmark)
Route::get('/logout', function () {
    Auth::logout();
    return redirect('/login');
});
