<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SOS Consultas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 50%, #0d0d2b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e0e0e0;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        .login-card {
            background: rgba(20, 20, 50, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 2.5rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 1rem;
        }
        .login-header h1 { font-size: 1.4rem; color: #fff; }
        .login-header p { font-size: 0.85rem; color: #7777aa; margin-top: 0.3rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            color: #9999bb;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #00b4d8;
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15);
        }
        .form-control::placeholder { color: #555580; }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3); }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .checkbox-group label { font-size: 0.85rem; color: #8888aa; margin-bottom: 0; }
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">SOS</div>
                <h1>SOS Consultas</h1>
                <p>Servicio Occidental de Salud</p>
            </div>

            @if($errors->any())
                <div class="alert-error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" class="form-control" placeholder="usuario@ejemplo.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Recordarme</label>
                </div>
                <button type="submit" class="btn-login">Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
