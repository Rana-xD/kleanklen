@php
    $orders = $order;

    if ($orders instanceof \Illuminate\Support\Collection) {
        $order = $orders->where('is_finished', true)->first();

        if (! $order) {
            $order = $orders->first();
        }
    }

    $userInfo = $order->address->id ? $order->address : $order->user;
@endphp

<div class="order-customer-info">
    <h3> {{ __('Customer information') }}</h3>
    @if ($userInfo->id)
        @if ($userInfo->name)
            <p>
                <span class="d-inline-block">{{ __('Full name') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->name }}</span>
            </p>
        @endif

        @if ($userInfo->phone)
            <p>
                <span class="d-inline-block">{{ __('Phone') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->phone }}</span>
            </p>
        @endif

        @if ($userInfo->email)
            <p>
                <span class="d-inline-block">{{ __('Email') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->email }}</span>
            </p>
        @endif

        @if ($order->full_address && in_array('address', EcommerceHelper::getHiddenFieldsAtCheckout()) && ! empty($isShowShipping))
            <p>
                <span class="d-inline-block">{{ __('Address') }}:</span>
                <span class="order-customer-info-meta">{{ $order->full_address }}</span>
            </p>
        @endif
    @endif

    @if (!empty($isShowShipping))
        <p>
            <span class="d-inline-block">{{ __('Shipping method') }}:</span>
            <span class="order-customer-info-meta">{{ $order->shipping_method_name }} -
                {{ format_price($order->shipping_amount) }}</span>
        </p>
    @endif

    @if (is_plugin_active('payment') && $order->payment->id)
        <p>
            <span class="d-inline-block">{{ __('Payment method') }}:</span>
            <span class="order-customer-info-meta">{{ $order->payment->payment_channel->label() }}</span>
        </p>
        <p>
            <span class="d-inline-block">{{ __('Payment status') }}:</span>
            <span
                class="order-customer-info-meta"
                style="text-transform: uppercase"
                data-bb-target="ecommerce-order-payment-status"
            >{!! BaseHelper::clean($order->payment->status->toHtml()) !!}</span>
        </p>

        @if (setting('payment_bank_transfer_display_bank_info_at_the_checkout_success_page', false) &&
                ($bankInfo = OrderHelper::getOrderBankInfo($orders)))
            {!! $bankInfo !!}
        @endif
    @endif

    {!! apply_filters('ecommerce_thank_you_customer_info', null, $order) !!}
</div>

@if ($tax = $order->taxInformation)
    <div class="order-customer-info">
        <h3> {{ __('Tax information') }}</h3>
        <p>
            <span class="d-inline-block">{{ __('Company name') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_name }}</span>
        </p>

        <p>
            <span class="d-inline-block">{{ __('Company tax code') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_tax_code }}</span>
        </p>

        <p>
            <span class="d-inline-block">{{ __('Company email') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_email }}</span>
        </p>

        <p>
            <span class="d-inline-block">{{ __('Company address') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_address }}</span>
        </p>
    </div>
@endif
