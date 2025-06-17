<div class="number-items-available">
    @if ($product->isOutOfStock())
        <span class="text-danger">{{ __('Out of stock') }}</span>
    @else
        <p></p>
    @endif
</div>
