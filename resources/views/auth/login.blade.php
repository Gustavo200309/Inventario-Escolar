<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login Inventario</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --bg:#eef1ec;
    --surface:#f7f8f6;
    --text:#2f3e34;
    --primary:#2f943c;
    --primary-dark:#21692c;
    --muted:#6d746b;
    --border:#d8ddd4;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
    background:
        radial-gradient(circle at top left, rgba(47,148,60,0.09), transparent 28%),
        radial-gradient(circle at bottom right, rgba(33,105,44,0.08), transparent 30%),
        var(--bg);
    color:var(--text);
}

.contenedor{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-box{
    width:min(520px, 100%);
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:45px;
    box-shadow:0 14px 30px rgba(31,80,43,0.07);
    text-align:center;
}

.logo{
    width:110px;
    height:110px;
    margin:0 auto 30px;
    border-radius:50%;
    background:var(--primary);
    display:flex;
    justify-content:center;
    align-items:center;
    box-shadow:0 8px 18px rgba(47,148,60,0.18);
}

.logo i{
    font-size:48px;
    color:#fff;
}

.login-box h1{
    font-size:34px;
    line-height:1.3;
    color:var(--primary-dark);
    margin-bottom:12px;
}

.login-box p{
    color:var(--muted);
    font-size:18px;
    margin-bottom:40px;
}

form{
    text-align:left;
}

.grupo-input{
    margin-bottom:25px;
}

.grupo-input label{
    display:block;
    margin-bottom:12px;
    color:#245c2d;
    font-size:16px;
    font-weight:600;
}

.input-box{
    height:65px;
    border-radius:16px;
    background:#fff;
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    padding:0 20px;
    transition:border-color 0.2s ease, box-shadow 0.2s ease;
}

.input-box:hover,
.input-box.activo{
    border-color:var(--primary);
}

.input-box.activo{
    box-shadow:0 0 0 4px rgba(47,148,60,0.10);
}

.input-box i{
    color:#6d7a6e;
    font-size:20px;
    margin-right:14px;
}

.input-box input{
    width:100%;
    background:transparent;
    border:0;
    outline:0;
    color:var(--text);
    font-size:16px;
}

.input-box input::placeholder{
    color:#93a094;
}

button{
    width:100%;
    height:65px;
    border:0;
    border-radius:16px;
    background:var(--primary);
    color:#fff;
    font-size:18px;
    font-weight:600;
    cursor:pointer;
    transition:background 0.2s ease, transform 0.2s ease;
    margin-top:10px;
}

button:hover{
    background:#25772f;
    transform:translateY(-2px);
}

.demo{
    display:block;
    margin-top:35px;
    color:#6f7a70;
    font-size:15px;
}

@media(max-width:600px){
    .login-box{
        padding:35px 25px;
    }

    .login-box h1{
        font-size:28px;
    }

    .login-box p{
        font-size:16px;
    }
}
</style>
</head>
<body>

<div class="contenedor">
    <div class="login-box">
        <div class="logo">
            <i class="fa-solid fa-cube"></i>
        </div>

        <h1>Sistema de Gesti&oacute;n de Inventario</h1>
        <p>Control de Bienes Institucionales</p>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            @if ($errors->any())
                <div style="margin-bottom:20px;color:#c72c41;font-weight:600;">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grupo-input">
                <label>Usuario</label>

                <div class="input-box">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="usuario" value="{{ old('usuario') }}" placeholder="admin@prueba.com" required>
                </div>
            </div>

            <div class="grupo-input">
                <label>Contrase&ntilde;a</label>

                <div class="input-box activo">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
                </div>
            </div>

            <button type="submit">
                Iniciar sesi&oacute;n
            </button>
        </form>

        <span class="demo">
            Demo: admin@prueba.com / Admin1234 o visualizador@prueba.com / Viewer1234
        </span>
    </div>
</div>

</body>
</html>
