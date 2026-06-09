<div {{ $attributes->merge(['class' => 'component-alert component-alert-' . ($type ?? 'info')]) }}>
    @if(isset($icon))
        <div class="component-alert-icon">
            <i class="{{ $icon }}"></i>
        </div>
    @endif
    <div class="component-alert-content">
        {{ $slot }}
    </div>
    @if($dismissible ?? false)
        <button type="button" class="component-alert-close" onclick="this.parentElement.remove()">&times;</button>
    @endif
</div>
