<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $pageTitle ?? 'Sistema de Inventario')</title>

    <script>
        (function () {
            var savedTheme = localStorage.getItem('inventario-theme');

            if (savedTheme === 'dark' || savedTheme === 'light') {
                document.documentElement.dataset.theme = savedTheme;
            }
        })();
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="contenedor">
        @include('admin.partials.sidebar')

        <main class="contenido">
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var toggle = document.querySelector('[data-theme-toggle]');
            var label = document.querySelector('[data-theme-label]');

            function applyTheme(theme) {
                root.dataset.theme = theme;
                localStorage.setItem('inventario-theme', theme);

                if (toggle) {
                    toggle.checked = theme === 'dark';
                }

                if (label) {
                    label.textContent = theme === 'dark' ? 'Modo oscuro activado' : 'Modo claro activado';
                }
            }

            applyTheme(root.dataset.theme === 'dark' ? 'dark' : 'light');

            if (toggle) {
                toggle.addEventListener('change', function () {
                    applyTheme(toggle.checked ? 'dark' : 'light');
                });
            }
        })();
    </script>
</body>
</html>
