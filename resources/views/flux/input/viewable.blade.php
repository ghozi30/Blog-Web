@props([
    'iconVariant' => 'mini',
    'size' => null,
])

@php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-1',
    'square' => true,
    'size' => null,
]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'sm' || $size === 'xs' ? 'xs' : 'sm'"
    x-data="{
        open: false,
        toggle() {
            this.open = !this.open;
            let input = this.$el.closest('[data-flux-input]')?.querySelector('input');
            if (input) {
                input.setAttribute('type', this.open ? 'text' : 'password');
            }
        }
    }"
    x-on:click="toggle()"
    aria-label="{{ __('Toggle password visibility') }}"
>
    <flux:icon.eye-slash :variant="$iconVariant" x-show="open" style="display: none;" />
    <flux:icon.eye :variant="$iconVariant" x-show="!open" />
</flux:button>
