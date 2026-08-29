<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Customer;
use Auth;
use DB;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $q = $request->q;
        if ($q) {
            $customers = Customer::where('status', 'aktif')->where('nama', 'like', "%$q%")->orderBy('nama')->paginate(25);
        } else {
            $customers = Customer::where('status', 'aktif')->orderBy('nama')->paginate(25);
        }
        return view('customer.index', compact('customers', 'q'));
    }

    public function create()
    {
        return view('customer.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->type == 3) {
            return back()->with('error', 'Tidak ada akses');
        }
        // generate kode
        $last = DB::table('tbl_customers')->orderBy('id', 'desc')->first();
        if ($last) {
            $no = intval(substr($last->kode, 3)) + 1;
        } else {
            $no = 1;
        }
        $kode = 'CST' . sprintf('%04d', $no);

        $c = new Customer;
        $c->kode = $kode;
        $c->nama = $request->nama;
        $c->tipe = $request->tipe;
        $c->alamat = $request->alamat;
        $c->kota = $request->kota;
        $c->telp = $request->telp;
        $c->email = $request->email;
        $c->npwp = $request->npwp;
        $c->status = 'aktif';
        $c->save();

        return redirect('/customer')->with('success', 'Customer ' . $c->nama . ' berhasil ditambahkan');
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        $orders = DB::table('tbl_orders')->where('customer_id', $id)->where('status', '!=', 'deleted')->orderBy('tgl_order', 'desc')->take(20)->get();
        $total = DB::table('tbl_orders')->where('customer_id', $id)->where('status', '!=', 'deleted')->sum('total');
        return view('customer.show', compact('customer', 'orders', 'total'));
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customer.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->type == 3) {
            return back()->with('error', 'Tidak ada akses');
        }
        $c = Customer::findOrFail($id);
        $c->update($request->except(['_token', '_method']));
        return redirect('/customer')->with('success', 'Customer berhasil diupdate');
    }

    public function destroy($id)
    {
        if (Auth::user()->type != 1) {
            return back()->with('error', 'Hanya admin');
        }
        Customer::where('id', $id)->update(['status' => 'deleted']);
        return redirect('/customer')->with('success', 'Customer dihapus');
    }
}
