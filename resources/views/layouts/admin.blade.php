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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
</head>
<body>
    <div class="contenedor">
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <button class="hamburger-fixed" id="hamburgerFixed" onclick="toggleSidebar()" aria-label="Menú">
            <i class="fa-solid fa-bars"></i>
        </button>

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
                    label.textContent = theme === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro';
                }

                var themeSelect = document.getElementById('theme-select');
                if (themeSelect) {
                    themeSelect.value = theme;
                }
            }

            applyTheme(root.dataset.theme === 'dark' ? 'dark' : 'light');

            if (toggle) {
                toggle.addEventListener('change', function () {
                    applyTheme(toggle.checked ? 'dark' : 'light');
                });
            }

        })();

        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            var isDesktop = window.innerWidth > 1000;

            if (isDesktop) {
                sidebar.classList.toggle('hidden');
                document.querySelector('.contenido').classList.toggle('sidebar-hidden');
            } else {
                sidebar.classList.toggle('open');
                document.getElementById('sidebarOverlay').classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            }
        }

        function openModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('show');
        }

        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('show');
        }

        function confirmAction(event, message, confirmText, cancelText, type) {
            if (!confirm(message || '¿Estás seguro?')) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        document.addEventListener('click', function (e) {
            document.querySelectorAll('.component-modal.show').forEach(function (modal) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
