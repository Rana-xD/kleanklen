<?php

namespace App\Http\Controllers;

use App\Models\UserCoupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCoupon;
use App\Models\ProductVariantOption;
use App\Models\PurchasedProducts;
use App\Models\Shipping;
use App\Models\Store;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use YooKassa\Client;

class YooKassaController extends Controller
{
    public function storePayWithYookassa(Request $request,$slug){
        $cart     = session()->get($slug);

        if(!empty($cart))
        {
            $products = $cart['products'];
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
        $yookassa_shop_id = $store_payment_setting['yookassa_shop_id'];
        $yookassa_secret_key = $store_payment_setting['yookassa_secret'];
        $currency = isset($store->currency_code) ? $store->currency_code : 'RUB';
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
            try {
                if (is_int((int)$yookassa_shop_id)) {
                    $client = new Client();
                    $client->setAuth((int)$yookassa_shop_id, $yookassa_secret_key);
                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                    $payment = $client->createPayment(
                        array(
                            'amount' => array(
                                'value' => $price,
                                'currency' => $currency,
                            ),
                            'confirmation' => array(
                                'type' => 'redirect',
                                'return_url' => route('store.yookassa.status', [
                                    'slug'=>$slug,
                                    'amount'=>$price,
                                ]),
                            ),
                            'capture' => true,
                            'description' => 'Заказ №1',
                        ),
                        uniqid('', true)
                    );
                    Session::put('product_payment_id', $payment['id']);

                    if ($payment['confirmation']['confirmation_url'] != null) {
                        return redirect($payment['confirmation']['confirmation_url']);
                    } else {
                        return redirect()->back()->with('error', 'Something went wrong, Please try again');
                    }
                }
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Product is deleted.'));
        }
    }

    public function getStorePaymentStatus(Request $request){
        $get_amount = $request->amount;
        $store        = Store::where('slug', $request->slug)->first();
        $cart     = session()->get($request->slug);
        $cust_details = $cart['customer'];

        if(\Auth::check())
        {
            $store_payment_setting = Utility::getPaymentSetting();
        }
        else
        {
            $store_payment_setting = Utility::getPaymentSetting($store->id);
        }
        $yookassa_shop_id = $store_payment_setting['yookassa_shop_id'];
        $yookassa_secret_key = $store_payment_setting['yookassa_secret'];
        $currency = isset($store->currency_code) ? $store->currency_code : 'RUB';

        if(!empty($cart))
        {
            $products = $cart['products'];
        }
        else
        {
            return redirect()->back()->with('error', __('Please add to product into cart'));
        }
        if(isset($cart['coupon']['data_id']))
        {
            $coupon = ProductCoupon::where('id', $cart['coupon']['data_id'])->first();
        }
        else
        {
            $coupon = '';
        }
        $product_name = [];
        $product_id   = [];
        $tax_name     = [];
        $totalprice   = 0;
        
        foreach($products as $key => $product)
        {
            if($product['variant_id'] == 0)
            {
                $new_qty                = $product['originalquantity'] - $product['quantity'];
                $product_edit           = Product::find($product['product_id']);
                $product_edit->quantity = $new_qty;
                $product_edit->save();

                $tax_price = 0;
                if(!empty($product['tax']))
                {
                    foreach($product['tax'] as $key => $taxs)
                    {
                        $tax_price += $product['price'] * $product['quantity'] * $taxs['tax'] / 100;

                    }
                }
                $totalprice     += $product['price'] * $product['quantity'];
                $product_name[] = $product['product_name'];
                $product_id[]   = $product['id'];
            }
            elseif($product['variant_id'] != 0)
            {
                $new_qty                   = $product['originalvariantquantity'] - $product['quantity'];
                $product_variant           = ProductVariantOption::find($product['variant_id']);
                $product_variant->quantity = $new_qty;
                $product_variant->save();

                $tax_price = 0;
                if(!empty($product['tax']))
                {
                    foreach($product['tax'] as $key => $taxs)
                    {
                        $tax_price += $product['variant_price'] * $product['quantity'] * $taxs['tax'] / 100;

                    }
                }
                $totalprice     += $product['variant_price'] * $product['quantity'];
                $product_name[] = $product['product_name'];
                $product_id[]   = $product['id'];
            }
        }
        $price=$totalprice+$tax_price;
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

        if($products){
            try{

                if (is_int((int)$yookassa_shop_id)) {
                    $client = new Client();
                    $client->setAuth((int)$yookassa_shop_id, $yookassa_secret_key);
                    $paymentId = Session::get('product_payment_id');

                    if ($paymentId == null) {
                        return redirect()->back()->with('error', __('Transaction Unsuccesfull'));
                    }
                    $payment = $client->getPaymentInfo($paymentId);

                    Session::forget('product_payment_id');
                    if (isset($payment) && $payment->status == "succeeded") {
                        if (Utility::CustomerAuthCheck($store->slug)) {
                            $customer = Auth::guard('customers')->user()->id;
                        }else{
                            $customer = 0;
                        }
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
                        $order->price           = $price;
                        $order->coupon          = isset($cart['coupon']['data_id']) ? $cart['coupon']['data_id'] : '';
                        $order->coupon_json     = json_encode($coupon);
                        $order->discount_price  = isset($cart['coupon']['discount_price']) ? $cart['coupon']['discount_price'] : '';
                        $order->product         = json_encode($products);
                        $order->price_currency  = $store->currency_code;
                        $order->txn_id          = time();
                        $order->payment_type    = 'Yookassa';
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
                        $owner = User::find($store->created_by);
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
            
                        session()->forget($store->slug);
            
                        return $msg;
                    }
                    else {
                        return redirect()->back()->with('error', 'Transaction Unsuccesfull');
                    }
                }
                
            }catch(\Exception $e){
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
        else{
            return redirect()->back()->with('error', __('Transaction Unsuccesfull'));
        }
    }
}
