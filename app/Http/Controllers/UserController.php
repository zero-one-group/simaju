<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (Auth::user()->type != 1) {
            return redirect('/home')->with('error', 'Hanya admin');
        }
        $users = User::orderBy('name')->get();
        return view('user.index', compact('users'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->type != 1) {
            return redirect('/home')->with('error', 'Hanya admin');
        }
        $u = new User;
        $u->name = $request->name;
        $u->email = $request->email;
        $u->type = $request->type;
        $u->no_hp = $request->no_hp;
        $u->password = Hash::make($request->password ? $request->password : 'password');
        $u->save();
        return redirect('/user')->with('success', 'User ditambahkan');
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->type != 1) {
            return redirect('/home')->with('error', 'Hanya admin');
        }
        $u = User::findOrFail($id);
        $u->name = $request->name;
        $u->email = $request->email;
        $u->type = $request->type;
        $u->no_hp = $request->no_hp;
        if ($request->password != '') {
            $u->password = Hash::make($request->password);
        }
        $u->save();
        return redirect('/user')->with('success', 'User diupdate');
    }

    public function destroy($id)
    {
        if (Auth::user()->type != 1) {
            return redirect('/home')->with('error', 'Hanya admin');
        }
        if ($id == Auth::user()->id) {
            return back()->with('error', 'Tidak bisa hapus diri sendiri');
        }
        User::destroy($id);
        return back()->with('success', 'User dihapus');
    }
}
