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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            var btn = document.querySelector('[data-theme-btn]');
            var label = document.querySelector('[data-theme-label]');

            function applyTheme(theme, updateBtn) {
                root.dataset.theme = theme;
                localStorage.setItem('inventario-theme', theme);

                if (updateBtn !== false && btn) {
                    var isDark = theme === 'dark';
                    btn.querySelector('i').className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
                    if (label) label.textContent = isDark ? 'Tema claro' : 'Tema oscuro';
                }
            }

            applyTheme(root.dataset.theme === 'dark' ? 'dark' : 'light', true);

            if (btn) {
                btn.addEventListener('click', function () {
                    var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
                    applyTheme(next, true);
                });
            }
        })();
    </script>

    <script>
        function openModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('component-modal-show');
        }
        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('component-modal-show');
        }
        function confirmAction(event, message, confirmText, cancelText, icon) {
            event.preventDefault();
            var form = event.target;
            Swal.fire({
                title: message || '¿Está seguro?',
                icon: icon || 'warning',
                showCancelButton: true,
                confirmButtonText: confirmText || 'Sí, confirmar',
                cancelButtonText: cancelText || 'Cancelar',
                confirmButtonColor: '#2f943c',
                cancelButtonColor: '#6d746b',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
        function showSuccess(title, text) {
            Swal.fire({ icon: 'success', title: title || 'Operación exitosa', text: text || '', timer: 2500, showConfirmButton: false });
        }
        function showError(title, text) {
            Swal.fire({ icon: 'error', title: title || 'Error', text: text || '' });
        }
        function showInfo(title, text) {
            Swal.fire({ icon: 'info', title: title || 'Información', text: text || '' });
        }
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function() { showSuccess('{{ session('success') }}'); });
        @endif
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', function() { showError('{{ session('error') }}'); });
        @endif
    </script>
</body>
</html>
