<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Kasir</title>
</head>
<body style="background: #f4f6f9; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">
    <div style="background: #fff; width: 100%; max-width: 400px; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h3 style="text-align: center; margin-bottom: 25px;">Masuk Aplikasi Kasir</h3>
        
        @if($errors->has('loginError'))
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                {{ $errors->first('loginError') }}
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email</label>
                <input type="text" name="email" value="{{ old('email') }}" required style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Password</label>
                <input type="password" name="password" required style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box;">
            </div>
            <button type="submit" style="width: 100%; background: #0d6efd; color: white; height: 42px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Masuk</button>
        </form>
    </div>
</body>
</html>