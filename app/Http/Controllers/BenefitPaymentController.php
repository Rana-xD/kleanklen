<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariantOption;
use App\Models\PurchasedProducts;
use App\Models\ProductCoupon;
use App\Models\Store;
use GuzzleHttp\Client;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Utility;
use App\Models\Shipping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PhpParser\Node\Stmt\TryCatch;

class BenefitPaymentController extends Controller
{
    public function storeInitiatePayment(Request $request,$slug){
        try {
            $cart = session()->get($slug);
            $products = $cart['products'];
            $customers = $cart['customer'];
            $store = Store::where('slug', $slug)->first();
            $storepaymentSetting = Utility::getPaymentSetting($store->id);
            $secret_key = $storepaymentSetting['benefit_secret_key'];
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
                $coupon = (empty($cart['coupon'])) ? "0" : $cart['coupon'];
                $customerData =
                    [
                        "amount" => $get_amount,
                        "currency" => !empty($store->currency_code) ? $store->currency_code : 'USD',
                        "customer_initiated" => true,
                        "threeDSecure" => true,
                        "save_card" => false,
                        "description" => $product['product_name'],
                        "metadata" => ["udf1" => "Metadata 1"],
                        "reference" => ["transaction" => "txn_01", "order" => "ord_01"],
                        "receipt" => ["email" => true, "sms" => true],
                        "customer" => ["first_name" => $customers['name'], "middle_name" => "", "last_name" => "", "email" => $customers['email'], "phone" => ["country_code" => 965, "number" => 51234567]],
                        "source" => ["id" => "src_bh.benefit"],
                        "post" => ["url" => "https://webhook.site/fd8b0712-d70a-4280-8d6f-9f14407b3bbd"],
                        "redirect" => ["url" => route('store.benefit.call_back', ['product_id' => $product_id, 'amount' => $get_amount,'slug' => $slug])],
                      
                    ];
                $responseData = json_encode($customerData);
                $client = new Client();
                try {
                    $response = $client->request('POST', 'https://api.tap.company/v2/charges', [
                        'body' => $responseData,
                        'headers' => [
                            'Authorization' => 'Bearer ' . $secret_key,
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                        ],
                    ]);
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error','Currency Not Supported.Contact To Your Site Admin');
                }

                $data = $response->getBody();
                $res = json_decode($data);
                return redirect($res->transaction->url);
                
            } else {
                return redirect()->back()->with('error', __('product is not found.'));
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
    public function storeCall_back(Request $request){
        $getAmount = $request->amount;
        $product_id = $request->product_id;
        $slug = $request->slug;
        $store = Store::where('slug', $slug)->first();
        $storepaymentSetting = Utility::getPaymentSetting($store->id);
        $secret_key = $storepaymentSetting['benefit_secret_key'];
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

            $post = $request->all();
            $client = new Client();
            $response = $client->request('GET', 'https://api.tap.company/v2/charges/' . $post['tap_id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret_key,
                    'accept' => 'application/json',
                ],
            ]);

            $json = $response->getBody();
            $data = json_decode($json);
            $status_code = $data->gateway->response->code;
            if ($status_code == '00') {
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
                $order->payment_type    = 'Benefit';
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
            }
            else{
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
            }    
        }catch(Exception $e){
            return redirect()->back()->with('error', __($e));
        }
    }
}