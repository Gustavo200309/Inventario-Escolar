<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'component-btn component-btn-' . ($variant ?? 'primary') . ($size ? ' component-btn-' . $size : '') . ($icon ? ' component-btn-icon' : ''),
]) }}>
    @if($icon)<i class="{{ $icon }}"></i>@endif
    {{ $slot }}
</button>
