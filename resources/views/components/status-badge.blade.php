<span {{ $attributes->merge(['class' => 'component-badge component-badge-' . ($type ?? 'default')]) }}>
    {{ $slot }}
</span>
