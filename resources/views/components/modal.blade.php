<div id="{{ $id }}" class="component-modal" role="dialog" aria-modal="true" onclick="if(event.target===this) closeModal('{{ $id }}')">
    <div class="component-modal-content component-modal-{{ $size ?? 'md' }}">
        <div class="component-modal-header">
            <h2>{{ $title ?? '' }}</h2>
            <button type="button" class="component-modal-close" onclick="closeModal('{{ $id }}')">&times;</button>
        </div>
        <div class="component-modal-body">
            {{ $slot }}
        </div>
        @if(isset($footer))
            <div class="component-modal-footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
