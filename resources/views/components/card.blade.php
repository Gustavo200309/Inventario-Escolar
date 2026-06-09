<div {{ $attributes->merge(['class' => 'component-card' . ($hover ? ' component-card-hover' : '')]) }}>
    @if(isset($header))
        <div class="component-card-header">
            {{ $header }}
        </div>
    @endif
    <div class="component-card-body">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="component-card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
