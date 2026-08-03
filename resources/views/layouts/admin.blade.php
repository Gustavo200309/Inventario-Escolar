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
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
</head>
<body>
    <div class="contenedor">
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

        <button class="hamburger-fixed" id="hamburgerFixed" onclick="toggleSidebar()" aria-label="Menú">
            <i class="fa-solid fa-bars"></i>
        </button>

        @include('admin.partials.sidebar')

        <main class="contenido">
            @yield('content')
        </main>
    </div>

    <!-- Modal de Confirmación -->
    <div id="confirmModal" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2 id="confirmModalTitle">Confirmar</h2>
                <button type="button" class="component-modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="component-modal-body">
                <p id="confirmModalMessage" style="font-size:15px;line-height:1.6;"></p>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeConfirmModal()">Cancelar</button>
                <button type="button" class="btn-agregar" id="confirmModalBtn">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Alerta -->
    <div id="alertModal" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2 id="alertModalTitle">Aviso</h2>
                <button type="button" class="component-modal-close" onclick="closeAlertModal()">&times;</button>
            </div>
            <div class="component-modal-body">
                <p id="alertModalMessage" style="font-size:15px;line-height:1.6;"></p>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-agregar" onclick="closeAlertModal()">Aceptar</button>
            </div>
        </div>
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

        function getSidebarElements() {
            return {
                sidebar: document.getElementById('mainSidebar') || document.querySelector('.sidebar'),
                content: document.querySelector('.contenido'),
                overlay: document.getElementById('sidebarOverlay'),
                button: document.getElementById('hamburgerFixed')
            };
        }

        function isDesktopSidebar() {
            return window.innerWidth > 1000;
        }

        function setSidebarButtonExpanded(expanded) {
            var button = document.getElementById('hamburgerFixed');
            if (button) {
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            }
        }

        function clearMobileSidebarState(elements) {
            if (!elements) elements = getSidebarElements();
            if (elements.sidebar) elements.sidebar.classList.remove('open');
            if (elements.overlay) elements.overlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }

        function setMobileSidebar(open, elements) {
            if (!elements) elements = getSidebarElements();
            if (!elements.sidebar) return;

            elements.sidebar.classList.remove('hidden');
            if (elements.content) elements.content.classList.remove('sidebar-hidden');
            elements.sidebar.classList.toggle('open', open);
            if (elements.overlay) elements.overlay.classList.toggle('show', open);
            document.body.classList.toggle('sidebar-open', open);
            setSidebarButtonExpanded(open);
        }

        function syncSidebarForViewport() {
            var elements = getSidebarElements();
            if (!elements.sidebar) return;

            clearMobileSidebarState(elements);

            if (isDesktopSidebar()) {
                if (elements.content) {
                    elements.content.classList.toggle('sidebar-hidden', elements.sidebar.classList.contains('hidden'));
                }
                setSidebarButtonExpanded(!elements.sidebar.classList.contains('hidden'));
            } else {
                elements.sidebar.classList.remove('hidden');
                if (elements.content) elements.content.classList.remove('sidebar-hidden');
                setSidebarButtonExpanded(false);
            }
        }

        function toggleSidebar() {
            var elements = getSidebarElements();
            if (!elements.sidebar) return;

            if (isDesktopSidebar()) {
                clearMobileSidebarState(elements);
                elements.sidebar.classList.toggle('hidden');
                if (elements.content) {
                    elements.content.classList.toggle('sidebar-hidden', elements.sidebar.classList.contains('hidden'));
                }
                setSidebarButtonExpanded(!elements.sidebar.classList.contains('hidden'));
            } else {
                setMobileSidebar(!elements.sidebar.classList.contains('open'), elements);
            }
        }

        function closeSidebar() {
            var elements = getSidebarElements();

            if (isDesktopSidebar()) {
                clearMobileSidebarState(elements);
                if (elements.content && elements.sidebar) {
                    elements.content.classList.toggle('sidebar-hidden', elements.sidebar.classList.contains('hidden'));
                }
                setSidebarButtonExpanded(elements.sidebar && !elements.sidebar.classList.contains('hidden'));
                return;
            }

            setMobileSidebar(false, elements);
        }

        var lastSidebarDesktopState = isDesktopSidebar();
        window.addEventListener('resize', function () {
            var currentSidebarDesktopState = isDesktopSidebar();
            if (currentSidebarDesktopState !== lastSidebarDesktopState) {
                lastSidebarDesktopState = currentSidebarDesktopState;
                syncSidebarForViewport();
            }
        });

        document.addEventListener('DOMContentLoaded', syncSidebarForViewport);

        function openModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('show');
        }

        function closeModal(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('show');
        }

        document.addEventListener('click', function (e) {
            document.querySelectorAll('.component-modal.show').forEach(function (modal) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
        });

        var confirmCallback = null;

        function showConfirm(message, callback, title) {
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModalTitle').textContent = title || 'Confirmar';
            confirmCallback = callback;
            openModal('confirmModal');
        }

        function closeConfirmModal() {
            confirmCallback = null;
            closeModal('confirmModal');
        }

        document.addEventListener('DOMContentLoaded', function () {
            var confirmBtn = document.getElementById('confirmModalBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    if (confirmCallback) {
                        confirmCallback();
                        confirmCallback = null;
                    }
                    closeModal('confirmModal');
                });
            }
        });

        function showAlert(message, title) {
            document.getElementById('alertModalMessage').textContent = message;
            document.getElementById('alertModalTitle').textContent = title || 'Aviso';
            openModal('alertModal');
        }

        function closeAlertModal() {
            closeModal('alertModal');
        }

        function confirmThenSubmit(button, message) {
            showConfirm(message, function () {
                button.closest('form').submit();
            });
        }
    </script>
</body>
</html>
