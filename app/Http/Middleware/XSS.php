<?php

namespace App\Http\Middleware;

use App\Models\LandingPageSections;
use App\Models\Utility;
use Closure;
use Illuminate\Http\Request;

class XSS
{
    use \RachidLaasri\LaravelInstaller\Helpers\MigrationsHelper;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(\Auth::check())
        {
            $user=\Auth::user()->lang;
            \App::setLocale($user);
            if(\Auth::user()->type == 'Owner')
            {
                $migrations             = $this->getMigrations();
                $dbMigrations           = $this->getExecutedMigrations();

                $Modulemigrations = glob(base_path().'/Modules/LandingPage/Database'.DIRECTORY_SEPARATOR.'Migrations'.DIRECTORY_SEPARATOR.'*.php');

                $numberOfUpdatesPending = (count($migrations) + count($Modulemigrations)) - count($dbMigrations);
                if($numberOfUpdatesPending > 0)
                {
                    Utility::addNewData();
                    return redirect()->route('LaravelUpdater::welcome');
                }

            }
        }

        $input = $request->all();
        // array_walk_recursive(
        //     $input, function (&$input){
        //     $input = strip_tags($input);
        // }
        // );
        $request->merge($input);

        return $next($request);
    }
}
