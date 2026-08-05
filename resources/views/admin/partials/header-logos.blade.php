<style>
    .header-logos .theme-logo-dark{display:none;}
    :root[data-theme="dark"] .header-logos .theme-logo-light{display:none;}
    :root[data-theme="dark"] .header-logos .theme-logo-dark{display:block;}
</style>
<div class="header-logos" aria-label="Logos institucionales">
    <img class="header-logo header-logo-square" src="{{ asset('images/logo_cbta.png') }}" alt="Logo CBTA">
    <img class="header-logo header-logo-wide theme-logo-light" src="{{ asset('images/logo_2_claro.png') }}" alt="Logo institucional 2">
    <img class="header-logo header-logo-wide theme-logo-dark" src="{{ asset('images/logo_2_oscuro.png') }}" alt="Logo institucional 2">
    <img class="header-logo header-logo-wide theme-logo-light" src="{{ asset('images/logo_3_claro.png') }}" alt="Logo institucional 3">
    <img class="header-logo header-logo-wide theme-logo-dark" src="{{ asset('images/logo_3_oscuro.png') }}" alt="Logo institucional 3">
</div>
