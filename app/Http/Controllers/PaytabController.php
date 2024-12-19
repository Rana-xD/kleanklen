<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utility;
use App\PayTab\paypage;
use App\Models\UserCoupon;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariantOption;
use App\Models\PurchasedProducts;
use App\Models\ProductCoupon;
use App\Models\Store;
use App\Models\Shipping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Exception;

class PaytabController extends Controller
{
    public function PayWithpaytab(Request $request, $slug){
        try {
            $cart = session()->get($slug);

            if(!empty($cart) && isset($cart['products']))
            {
                $products = $cart['products'];
            }
            else
            {
                return redirect()->back()->with('error', __('Please add to product into cart'));
            }
            if(!empty($cart['customer'])){
                $customers = $cart['customer'];
            }
            else{
                $customers = [];
            }
            $store = Store::where('slug', $slug)->first();
            $storepaymentSetting = Utility::getPaymentSetting($store->id);
            $total_tax = $sub_total = $total = $sub_tax = 0;
            $product_name = [];
            $product_id = [];
            foreach ($products as $key => $product) {
                if ($product['variant_id'] != 0) {

                    $product_name[] = $product['product_name'];
                     $product_id[] = $product['id'];

                    foreach ($product['tax'] as $tax) {
                        $sub_tax = ($product['variant_price'] * $product['quantity'] * $tax['tax']) / 100;
                        $total_tax += $sub_tax;
                    }
                    $totalprice = $product['variant_price'] * $product['quantity'];
                    $total += $totalprice;
                } else {
                    $product_name[] = $product['product_name'];
                     $product_id[] = $product['id'];

                    foreach ($product['tax'] as $tax) {
                        $sub_tax = ($product['price'] * $product['quantity'] * $tax['tax']) / 100;
                        $total_tax += $sub_tax;
                    }
                    $totalprice = $product['price'] * $product['quantity'];
                    $total += $totalprice;
                }
            }
            if ($products) {
                $get_amount = $total + $total_tax;
                if (isset($cart['coupon'])) {
                    if ($cart['coupon']['coupon']['enable_flat'] == 'off') {
                    
                        $discount_value = ($get_amount / 100) * $cart['coupon']['coupon']['discount'];
                        $get_amount = $get_amount - $discount_value;
                    } else {
                    
                        $discount_value = $cart['coupon']['coupon']['flat_discount'];
                        $get_amount = $get_amount - $discount_value;
                    }
                }
                if (isset($cart['shipping']) && isset($cart['shipping']['shipping_id']) && !empty($cart['shipping'])) {
                    $shipping = Shipping::find($cart['shipping']['shipping_id']);
                    if (!empty($shipping)) {
                        $get_amount = $get_amount + $shipping->price;
                    }
                }
                config([
                    'paytabs.profile_id' => isset($storepaymentSetting['paytab_profile_id']) ? $storepaymentSetting['paytab_profile_id'] : '',
                    'paytabs.server_key' => isset($storepaymentSetting['paytab_server_key']) ? $storepaymentSetting['paytab_server_key'] : '',
                    'paytabs.region' => isset($storepaymentSetting['paytab_region']) ? $storepaymentSetting['paytab_region'] : '',
                    'paytabs.currency' => $store->currency_code,
                ]);
                $paypage = new paypage();
                $pay = $paypage->sendPaymentCode('all')
                    ->sendTransaction('sale')
                    ->sendCart(1, $get_amount, 'plan payment')
                    ->sendCustomerDetails(isset($customers->name) ? $customers->name : "", isset($customers->email) ? $customers->email : '', '', '', '', '', '', '', '')
                    ->sendURLs(
                        route('paytab.success', ['success' => 1, 'data' => $request->all(), 'slug'=>$slug, 'amount'=> $get_amount , 'product_id'=>$product_id]),
                        route('paytab.success', ['success' => 0, 'data' => $request->all(), 'slug'=>$slug, 'amount'=> $get_amount , 'product_id'=>$product_id])
                    )
                    ->sendLanguage('en')
                    ->sendFramed($on = false)
                    ->create_pay_page();
                
                return $pay;   
            
            }
        }catch(Exception $e){dd($e);
            return redirect()->back()->with('error', $e);
        }
    }

    public function PaytabGetPaymentCallback(Request $request){
        $slug=$request->slug;
		$getAmount=$request->amount;
        $product_id = $request->product_id;
        try{
            $store = Store::where('slug', $slug)->first();
            $cart = session()->get($slug);
            $products       = $cart['products'];
            $cust_details = $cart['customer'];
            if(isset($cart['coupon']['data_id']))
            {
                $coupon = ProductCoupon::where('id', $cart['coupon']['data_id'])->first();
            }
            else
            {
                $coupon = '';
            }
            if(isset($cart['shipping']) && isset($cart['shipping']['shipping_id']) && !empty($cart['shipping']))
            {
                $shipping       = Shipping::find($cart['shipping']['shipping_id']);
                $shipping_name  = $shipping->name;
                $shipping_price = $shipping->price;
                $shipping_data  = json_encode(
                    [
                        'shipping_name' => $shipping_name,
                        'shipping_price' => $shipping_price,
                        'location_id' => $cart['shipping']['location_id'],
                    ]
                );
            }
            else
            {
                $shipping_data = '';
            }

            if ($request->success == "1") {
                $customer               = Auth::guard('customers')->user();
                $order                  = new Order();
                $order->order_id        = time();
                $order->name            = isset($cust_details['name']) ? $cust_details['name'] : '' ;
                $order->email           = isset($cust_details['email']) ? $cust_details['email'] : '';
                $order->card_number     = '';
                $order->card_exp_month  = '';
                $order->card_exp_year   = '';
                $order->status          = 'pending';
                $order->user_address_id = isset($cust_details['id']) ? $cust_details['id'] : '';
                $order->shipping_data   = $shipping_data;
                $order->product_id      = implode(',', $product_id);
                $order->price           = $getAmount;
                $order->coupon          = isset($cart['coupon']['data_id']) ? $cart['coupon']['data_id'] : '';
                $order->coupon_json     = json_encode($coupon);
                $order->discount_price  = isset($cart['coupon']['discount_price']) ? $cart['coupon']['discount_price'] : '';
                $order->product         = json_encode($products);
                $order->price_currency  = $store->currency_code;
                $order->txn_id          = isset($pay_id) ? $pay_id : '';
                $order->payment_type    = 'Paytab';
                $order->payment_status  = 'approved';
                $order->receipt         = '';
                $order->user_id         = $store['id'];
                $order->customer_id     = isset($customer->id) ? $customer->id : '';
                $order->save();

                //webhook
                $module = 'New Order';
                $webhook =  Utility::webhook($module, $store->id);
                if ($webhook) {
                    $parameter = json_encode($order);
                    //
                    // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status != true) {
                        $msg  = 'Webhook call failed.';
                    }
                }

                if ((!empty(Auth::guard('customers')->user()) && $store->is_checkout_login_required == 'on') ){
            
                    foreach($products as $product_id)
                    {
                        $purchased_products = new PurchasedProducts();
                        $purchased_products->product_id  = $product_id['product_id'];
                        $purchased_products->customer_id = $customer->id;
                        $purchased_products->order_id   = $order->id;
                        $purchased_products->save();
                    }
                }
                $order_email = $order->email;
                $owner=User::find($store->created_by);
                $owner_email=$owner->email;
                $order_id = Crypt::encrypt($order->id);
                if(isset($store->mail_driver) && !empty($store->mail_driver))
                {
                    $dArr = [
                        'order_name' => $order->name,
                    ];
                    $resp = Utility::sendEmailTemplate('Order Created', $order_email, $dArr, $store, $order_id);
                    $resp1=Utility::sendEmailTemplate('Order Created For Owner', $owner_email, $dArr, $store, $order_id);
                }
                if(isset($store->is_twilio_enabled) && $store->is_twilio_enabled=="on")
                {
                    Utility::order_create_owner($order,$owner,$store);
                    Utility::order_create_customer($order,$customer,$store);
                }
                $msg = redirect()->route(
                    'store-complete.complete', [
                                                $store->slug,
                                                Crypt::encrypt($order->id),
                                            ]
                )->with('success', __('Transaction has been success'));

                session()->forget($slug);

                return $msg;
            } else {
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
            }
        }catch(Exception $e){
            return redirect()->back()->with('error', __($e));
        }
    }
}
