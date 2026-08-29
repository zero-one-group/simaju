<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMAJU - Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body { background: #2c3e50 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="%232c3e50"/><path d="M0 40L40 0" stroke="%2334495e" stroke-width="1"/></svg>'); }
        .login-box { max-width: 380px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,.4); }
        .login-box h2 { margin-top: 0; font-weight: bold; letter-spacing: 2px; color: #2c3e50; }
        .login-box h2 small { display:block; font-size: 12px; letter-spacing: 0; color: #888; margin-top: 5px; }
        .login-footer { text-align: center; color: #bbb; font-size: 11px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 class="text-center">SIMAJU <small>v2.1 &mdash; PT Maju Jaya Distribusi</small></h2>
        <hr>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                @if ($errors->has('email'))
                    <span class="help-block">{{ $errors->first('email') }}</span>
                @endif
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Ingat saya</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">LOGIN</button>
        </form>
        <div class="login-footer">
            Sistem Informasi Maju Jaya<br>
            developed by CV Solusi Teknologi Prima 2018
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script>
        // hapus pesan error setelah 3 detik
        setTimeout(function(){ $('.help-block').fadeOut(); }, 3000);
    </script>
</body>
</html>
