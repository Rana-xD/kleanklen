<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductCoupon;
use App\Models\PurchasedProducts;
use App\Models\Shipping;
use App\Models\Store;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class NepalstePaymnetController extends Controller
{

    public function orderPayWithnepalste(Request $request, $slug)
    {
        $cart     = session()->get($slug);

        if(!empty($cart))
        {
            $products = $cart['products'];
            $cust_details = $cart['customer'];
        }
        else
        {
            return redirect()->back()->with('error', __('Please add to product into cart'));
        }

        $store = Store::where('slug', $slug)->first();
        if(\Auth::check())
        {
            $store_payment_setting = Utility::getPaymentSetting();
        }
        else
        {
            $store_payment_setting = Utility::getPaymentSetting($store->id);
        }

        $nepalste_secret_key = $store_payment_setting['nepalste_secret_key'];
        $nepalste_public_key = $store_payment_setting['nepalste_public_key'];
        $nepalste_mode = isset($store_payment_setting['nepalste_mode']) ? $store_payment_setting['nepalste_mode'] : 'sandbox';
        $currency = isset($store->currency_code) ? $store->currency_code : 'NPR';
        $total        = 0;
        $sub_tax      = 0;
        $total_tax    = 0;
        $product_name = [];
        $product_id   = [];
        foreach($products as $key => $product)
        {
            if($product['variant_id'] != 0)
            {

                $product_name[] = $product['product_name'];
                $product_id[]   = $product['id'];

                foreach($product['tax'] as $tax)
                {
                    $sub_tax   = ($product['variant_price'] * $product['quantity'] * $tax['tax']) / 100;
                    $total_tax += $sub_tax;
                }
                $totalprice = $product['variant_price'] * $product['quantity'];
                $total      += $totalprice;
            }
            else
            {
                $product_name[] = $product['product_name'];
                $product_id[]   = $product['id'];

                foreach($product['tax'] as $tax)
                {
                    $sub_tax   = ($product['price'] * $product['quantity'] * $tax['tax']) / 100;
                    $total_tax += $sub_tax;
                }
                $totalprice = $product['price'] * $product['quantity'];
                $total      += $totalprice;
            }
        }
        if($products)
        {
            $price     = $total + $total_tax;
            if(isset($cart['coupon']))
            {
                if($cart['coupon']['coupon']['enable_flat'] == 'off')
                {
                    $discount_value = ($price / 100) * $cart['coupon']['coupon']['discount'];
                    $price          = $price - $discount_value;
                }
                else
                {
                    $discount_value = $cart['coupon']['coupon']['flat_discount'];
                    $price          = $price - $discount_value;
                }
            }
            
            if(isset($cart['shipping']) && isset($cart['shipping']['shipping_id']) && !empty($cart['shipping']))
            {
                $shipping = Shipping::find($cart['shipping']['shipping_id']);
                if(!empty($shipping))
                {
                    $price = $price + $shipping->price;
                }
            }
            if(isset($cart['customer']) && !empty($cart['customer']))
            {

                $pdata['phone']   = isset($cart['customer']['phone']) ? $cart['customer']['phone'] : '';
                $pdata['email']   = isset($cart['customer']['email']) ? $cart['customer']['email'] : '';
                $pdata['user_id'] = isset($cart['customer']['id']) ? $cart['customer']['id'] : '';
            }
            else
            {
                $pdata['phone']   = '';
                $pdata['email']   = '';
                $pdata['user_id'] = '';
            }

            // $currency = 'NPR';

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $response = ['product_id' => $product_id, 'amount' => $price, 'orderId' => $orderID, 'slug' => $slug];  //'user' => $user

            try {
                $parameters = [
                    'identifier' => 'DFU80XZIKS',
                    'currency' => $currency,
                    'amount' => $price,
                    'details' => 'Invoice',
                    'ipn_url' => route('order.nepalste.status', $response),
                    'cancel_url' => route('order.nepalste.cancel'),
                    'success_url' => route('order.nepalste.status', $response),
                    'public_key' => $nepalste_public_key,
                    'site_logo' => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                    'checkout_theme' => 'dark',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@mail.com',
                ];

                //live end point
                $nepalste_mode_live = "https://nepalste.com.np/payment/initiate";

                //test end point
                $nepalste_mode_sandbox = "https://nepalste.com.np/sandbox/payment/initiate";

                $url = ($nepalste_mode == 'live') ? $nepalste_mode_live : $nepalste_mode_sandbox;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);

                $result = json_decode($result, true);

                if (isset($result['success'])) {
                    return redirect($result['url']);
                } else {
                    return redirect()->back()->with('error', __($result['message']));
                }

            } catch (\Throwable $e) {
                // dd($e);
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Product is deleted.'));
        }

    }
    public function orderGetNepalsteStatus(Request $request)
    {
        $getAmount = $request->amount;
        $product_id = $request->product_id;
        $slug = $request->slug;
        $store = Store::where('slug', $slug)->first();
        $storepaymentSetting = Utility::getPaymentSetting($store->id);
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

                $customer               = Auth::guard('customers')->user();
                $order                  = new Order();
                $order->order_id        = $request->orderId;
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
                $order->payment_type    = 'Neplaste';
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
                // if(isset($store->mail_driver) && !empty($store->mail_driver))
                // {
                    $dArr = [
                        'order_name' => $order->name,
                    ];
                    $resp = Utility::sendEmailTemplate('Order Created', $order_email, $dArr, $store, $order_id);
                    $resp1=Utility::sendEmailTemplate('Order Created For Owner', $owner_email, $dArr, $store, $order_id);
                // }
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
  
        }catch(\Exception $e){
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function orderGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error', __('Transaction has failed'));
    }
}
