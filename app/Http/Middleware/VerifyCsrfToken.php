<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'plan/paytm/*',
        'invoice/paytm/*',
        'paytm-payment-plan',
        'paymentwall-payment/*',
        '/planpayment',
        '*/order-pay-with-paymentwall',
        'iyzipay/callback/*',
        'paytab-success/'
    ];
}
