<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SIMAJU - @yield('title', 'Sistem Informasi Maju Jaya')</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css">
    <style>
        body { padding-top: 70px; background: #f4f4f4; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        .navbar-brand small { font-size: 10px; color: #aaa; }
        .panel-heading { font-weight: bold; }
        .footer { margin-top: 40px; padding: 15px 0; border-top: 1px solid #ddd; color: #888; font-size: 12px; text-align: center; }
        .table > tbody > tr > td { vertical-align: middle; }
        .text-rp { text-align: right; white-space: nowrap; }
        .box-stat { background:#fff; border:1px solid #ddd; padding:15px; margin-bottom:15px; border-left: 4px solid #337ab7; }
        .box-stat h3 { margin:0; }
        .box-stat small { color:#999; text-transform: uppercase; font-size:11px; }
        .sidebar-menu a { display:block; padding:8px 10px; color:#333; border-bottom:1px solid #eee; }
        .sidebar-menu a:hover { background:#e9e9e9; text-decoration:none; }
        .sidebar-menu a.active { background:#337ab7; color:#fff; }
        .marquee { color: red; font-size: 12px; }
    </style>
    <!-- custom css tambahan -->
    <!-- <link rel="stylesheet" href="{{ asset('css/custom.css') }}"> -->
    @yield('css')
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ url('/home') }}">SIMAJU <small>v2.1</small></a>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                @if(Auth::check())
                <ul class="nav navbar-nav">
                    <li class="{{ Request::is('home') ? 'active' : '' }}"><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li class="{{ Request::is('order*') ? 'active' : '' }}"><a href="{{ url('/order') }}"><i class="fa fa-shopping-cart"></i> Order</a></li>
                    <li class="{{ Request::is('produk*') ? 'active' : '' }}"><a href="{{ url('/produk') }}"><i class="fa fa-cubes"></i> Produk</a></li>
                    <li class="{{ Request::is('customer*') ? 'active' : '' }}"><a href="{{ url('/customer') }}"><i class="fa fa-users"></i> Customer</a></li>
                    @if(Auth::user()->type != 3)
                    <li class="{{ Request::is('laporan*') ? 'active' : '' }}"><a href="{{ url('/laporan') }}"><i class="fa fa-bar-chart"></i> Laporan</a></li>
                    @endif
                    @if(Auth::user()->type == 1)
                    <li class="{{ Request::is('user*') ? 'active' : '' }}"><a href="{{ url('/user') }}"><i class="fa fa-user"></i> User</a></li>
                    @endif
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-user-circle"></i> {{ Auth::user()->name }} <span class="badge">{{ nama_role(Auth::user()->type) }}</span> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="{{ url('/profil') }}">Profil</a></li>
                            <li role="separator" class="divider"></li>
                            <li>
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
                @else
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="{{ route('login') }}">Login</a></li>
                </ul>
                @endif
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
        @endif
        @if(session('msg'))
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {!! session('msg') !!}
            </div>
        @endif

        @yield('content')

        <div class="footer">
            SIMAJU v2.1 &copy; {{ date('Y') }} PT Maju Jaya Distribusi &mdash; developed by CV Solusi Teknologi Prima 2018
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
        // auto close alert
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 5000);
    </script>
    @yield('js')
</body>
</html>
