<div class="number-items-available">
    @if ($product->stock_status == 'on_backorder')
        <p class="text-warning fw-medium fs-6">{{ __('Warning: This product is on backorder and may take longer to ship.') }}</p>
    @elseif ($product->isOutOfStock())
        <span class="text-danger">{{ __('Out of stock') }}</span>
    @else
         <p></p>
    @endif
</div>
