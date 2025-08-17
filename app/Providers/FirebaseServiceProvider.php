<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Auth::class, function ($app) {
            $projectId = config('services.firebase.project_id');
            $credentialsPath = config('services.firebase.credentials_path');
            
            if (!$projectId || !$credentialsPath) {
                throw new \Exception('Firebase not configured');
            }
            
            $fullCredentialsPath = storage_path($credentialsPath);
            
            if (!file_exists($fullCredentialsPath)) {
                throw new \Exception('Firebase credentials file not found');
            }
            
            // Read the service account file to get project ID
            $serviceAccount = json_decode(file_get_contents($fullCredentialsPath), true);
            $actualProjectId = $serviceAccount['project_id'] ?? $projectId;
            
            $factory = (new Factory())
                ->withServiceAccount($fullCredentialsPath);
            
            return $factory->createAuth();
        });
    }

    public function boot()
    {
        //
    }
}
