<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Customer;
use App\Models\Store;
Use App\Models\Utility; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class customerauth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $slug    = \Request::segment(1);
        $auth_customer = Auth::guard('customers')->user();
        if (!empty($auth_customer)) {
            if(Utility::CustomerAuthCheck()==false){
                return redirect($slug.'/customer-login');
            }else{
                return $next($request);
            }
        }
        return redirect($slug.'/customer-login');
    }
}
