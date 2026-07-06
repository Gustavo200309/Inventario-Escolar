<form {{ $attributes->whereStartsWith('data-') }} method="{{ $method ?? 'POST' }}" action="{{ $action }}" style="display:inline;">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
