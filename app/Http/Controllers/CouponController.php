<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;

class CouponController extends Controller
{



    public function formatPrice($price){
        return env('CURRENCY_SYMBOL') . number_format($price);
    }
}
