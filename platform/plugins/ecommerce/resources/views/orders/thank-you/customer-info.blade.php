@php
    $orders = $order;

    if ($orders instanceof \Illuminate\Support\Collection) {
        $order = $orders->where('is_finished', true)->first();

        if (! $order) {
            $order = $orders->first();
        }
    }

    $userInfo = $order->address->id ? $order->address : $order->user;
    
    // Get available states to display the state name instead of ID
    $states = EcommerceHelper::getAvailableStatesByCountry($order->address->country ?? EcommerceHelper::getDefaultCountryId());
    
    // Special handling for Phnom Penh (ID: 15)
    if ($userInfo->state == '15' || $userInfo->state == 15) {
        $stateName = 'Phnom Penh';
    } else {
        $stateName = $states[$userInfo->state] ?? $userInfo->state;
    }
@endphp

<style>
    .order-info-card {
        background: linear-gradient(145deg, #ffffff, #f9fafb);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        padding: 30px;
        margin-bottom: 35px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(226, 232, 240, 0.8);
        overflow: hidden;
        position: relative;
    }
    
    .order-info-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #4f46e5, #6366f1);
    }
    
    .order-info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .order-info-card h3 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 25px;
        padding-bottom: 18px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        position: relative;
        display: flex;
        align-items: center;
        letter-spacing: -0.025em;
    }
    
    .order-info-card h3 i {
        margin-right: 12px;
        color: #4f46e5;
        font-size: 1.25rem;
        background: rgba(79, 70, 229, 0.1);
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .customer-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-left: 10px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .customer-info-item:hover {
        background-color: rgba(243, 244, 246, 0.5);
        border-radius: 8px;
        padding: 10px;
        margin-left: -10px;
    }
    
    .customer-info-item i {
        margin-right: 15px;
        color: #4f46e5;
        min-width: 18px;
        margin-top: 3px;
        transition: transform 0.3s ease;
    }
    
    .customer-info-item:hover i {
        transform: scale(1.2);
    }
    
    .customer-info-label {
        font-weight: 600;
        color: #475569;
        min-width: 130px;
        margin-right: 15px;
        font-size: 0.95rem;
    }
    
    .order-customer-info-meta {
        color: #1e293b;
        font-weight: 500;
        flex: 1;
        word-break: break-word;
        line-height: 1.5;
    }
    
    .payment-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        background: linear-gradient(to right, #4f46e5, #6366f1);
        color: white;
        box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3);
        letter-spacing: 0.025em;
    }
</style>

<div class="order-info-card">
    <h3><i class="fas fa-user-circle"></i> {{ __('Customer information') }}</h3>
    <div class="customer-info-container">
    @if ($userInfo->id)
        @if ($userInfo->name)
            <div class="customer-info-item">
                <i class="fas fa-user"></i>
                <span class="customer-info-label">{{ __('Full name') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->name }}</span>
            </div>
        @endif

        @if ($userInfo->phone)
            <div class="customer-info-item">
                <i class="fas fa-phone-alt"></i>
                <span class="customer-info-label">{{ __('Phone') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->phone }}</span>
            </div>
        @endif

        @if ($userInfo->state)
            <div class="customer-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span class="customer-info-label">{{ __('Location') }}:</span>
                <span class="order-customer-info-meta">{{ $stateName }}</span>
            </div>
        @endif

        @if ($userInfo->email)
            <div class="customer-info-item">
                <i class="fas fa-envelope"></i>
                <span class="customer-info-label">{{ __('Email') }}:</span>
                <span class="order-customer-info-meta">{{ $userInfo->email }}</span>
            </div>
        @endif

        @if ($order->full_address && $stateName == 'Phnom Penh')
            <div class="customer-info-item">
                <i class="fas fa-home"></i>
                <span class="customer-info-label">{{ __('Address') }}:</span>
                <span class="order-customer-info-meta">{{ $order->full_address }}</span>
            </div>
        @endif
        
        @if ($order->description)
            <div class="customer-info-item">
                <i class="fas fa-sticky-note"></i>
                <span class="customer-info-label">{{ __('Note') }}:</span>
                <span class="order-customer-info-meta">{{ $order->description }}</span>
            </div>
        @endif
    @endif

    @if (!empty($isShowShipping))
        <div class="customer-info-item">
            <i class="fas fa-shipping-fast"></i>
            <span class="customer-info-label">{{ __('Shipping method') }}:</span>
            <span class="order-customer-info-meta">{{ $order->shipping_method_name }} -
                {{ format_price($order->shipping_amount) }}</span>
        </div>
    @endif

    @if (is_plugin_active('payment') && $order->payment->id)
        <div class="customer-info-item">
            <i class="fas fa-credit-card"></i>
            <span class="customer-info-label">{{ __('Payment method') }}:</span>
            <span class="order-customer-info-meta">{{ $order->payment->payment_channel->label() }}</span>
        </div>
        <div class="customer-info-item">
            <i class="fas fa-check-circle"></i>
            <span class="customer-info-label">{{ __('Payment status') }}:</span>
            <span
                class="payment-status-badge"
                data-bb-target="ecommerce-order-payment-status"
            >{!! BaseHelper::clean($order->payment->status->toHtml()) !!}</span>
        </div>

        @if (setting('payment_bank_transfer_display_bank_info_at_the_checkout_success_page', false) &&
                ($bankInfo = OrderHelper::getOrderBankInfo($orders)))
            {!! $bankInfo !!}
        @endif
    @endif

    {!! apply_filters('ecommerce_thank_you_customer_info', null, $order) !!}
    </div>
</div>

@if ($tax = $order->taxInformation)
    <div class="order-info-card">
        <h3><i class="fas fa-file-invoice-dollar"></i> {{ __('Tax information') }}</h3>
        <div class="customer-info-container">
        <div class="customer-info-item">
            <i class="fas fa-building"></i>
            <span class="customer-info-label">{{ __('Company name') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_name }}</span>
        </div>

        <div class="customer-info-item">
            <i class="fas fa-hashtag"></i>
            <span class="customer-info-label">{{ __('Company tax code') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_tax_code }}</span>
        </div>

        <div class="customer-info-item">
            <i class="fas fa-envelope"></i>
            <span class="customer-info-label">{{ __('Company email') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_email }}</span>
        </div>

        <div class="customer-info-item">
            <i class="fas fa-map-marked-alt"></i>
            <span class="customer-info-label">{{ __('Company address') }}:</span>
            <span class="order-customer-info-meta">{{ $tax->company_address }}</span>
        </div>
    </div>
</div>
@endif
