<form {{ $attributes->whereStartsWith('data-') }} method="{{ $method ?? 'POST' }}" action="{{ $action }}" style="display:inline;" onsubmit="return confirmAction(event, '{{ $message ?? '¿Está seguro?' }}', '{{ $confirmText ?? 'Sí, confirmar' }}', '{{ $cancelText ?? 'Cancelar' }}', '{{ $icon ?? 'warning' }}')">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
