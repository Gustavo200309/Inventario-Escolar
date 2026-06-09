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
        ['key' => 'usuarios', 'label' => 'Gesti&oacute;n de Usuarios', 'icon' => 'fa-user-gear', 'route' => 'admin.usuarios'],
    ];
@endphp

<aside class="sidebar">
    <div>
        <div class="logo">
            <h2>Sistema de Inventario</h2>
            <p>Administrador</p>
        </div>

        <nav class="menu" aria-label="Menu principal">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" class="{{ $currentMenu === $item['key'] ? 'activo' : '' }}">
                    <i class="fa-solid {{ $item['icon'] }}"></i>
                    <span>{!! $item['label'] !!}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div>
        <button class="theme-btn" data-theme-btn>
            <i class="fa-solid fa-moon"></i>
            <span data-theme-label>Tema oscuro</span>
        </button>
    </div>

    <div class="logout">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Cerrar sesi&oacute;n</span>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </div>
</aside>
