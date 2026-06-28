@php
    $currentRoute = request()->route()?->getName();
    $currentMenu = $activeMenu ?? str_replace('admin.', '', $currentRoute ?? '');

    $navigation = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-table-columns', 'route' => 'admin.dashboard'],
        ['key' => 'bienes', 'label' => 'Bienes', 'icon' => 'fa-cube', 'route' => 'admin.bienes'],
        ['key' => 'personal', 'label' => 'Personal', 'icon' => 'fa-users', 'route' => 'admin.personal'],
        ['key' => 'areas', 'label' => '&Aacute;reas', 'icon' => 'fa-building', 'route' => 'admin.areas'],
        ['key' => 'asignaciones', 'label' => 'Asignaciones', 'icon' => 'fa-file-signature', 'route' => 'admin.asignaciones'],
        ['key' => 'historial', 'label' => 'Historial', 'icon' => 'fa-clock-rotate-left', 'route' => 'admin.historial'],
        ['key' => 'reportes', 'label' => 'Reportes', 'icon' => 'fa-chart-column', 'route' => 'admin.reportes'],
        ['key' => 'pendientes', 'label' => 'Pendientes', 'icon' => 'fa-circle-exclamation', 'route' => 'admin.pendientes'],
        ['key' => 'usuarios', 'label' => 'Usuarios', 'icon' => 'fa-user-gear', 'route' => 'admin.usuarios'],
    ];
@endphp

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img class="logo-img" src="{{ asset('images/logo_cbta.png') }}" alt="Logo CBTA">
            <div>
                <h2>Sistema de Inventario</h2>
                <p>Administrador</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu menu" aria-label="Menu principal">
        @foreach ($navigation as $item)
            <a href="{{ route($item['route']) }}" class="{{ $currentMenu === $item['key'] ? 'activo' : '' }}">
                <i class="fa-solid {{ $item['icon'] }}"></i>
                <span>{!! $item['label'] !!}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer logout">
        <a href="#" onclick="toggleTheme(); return false;" style="cursor:pointer;display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <i class="fa-solid fa-palette"></i>
            <span data-theme-label>Cambiar tema</span>
        </a>

        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Cerrar sesi&oacute;n</span>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </div>

    <script>
        function toggleTheme() {
            var root = document.documentElement;
            var current = root.dataset.theme === 'dark' ? 'dark' : 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            localStorage.setItem('inventario-theme', next);

            var label = document.querySelector('[data-theme-label]');
            if (label) {
                label.textContent = next === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro';
            }

            var toggle = document.querySelector('[data-theme-toggle]');
            if (toggle) toggle.checked = next === 'dark';
        }
    </script>
</aside>
