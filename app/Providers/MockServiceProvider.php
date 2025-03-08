<?php

namespace Vanguard\Providers;

use Illuminate\Support\ServiceProvider;
use Vanguard\Services\IPFSService;
use Vanguard\Services\MockIPFSService;
use Vanguard\Services\PhoneVerificationService;
use Vanguard\Services\MockPhoneVerificationService;
use Vanguard\Services\Interfaces\PhoneVerificationServiceInterface;

class MockServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Determine if we should use mock services based on environment
        $useMockServices = $this->app->environment('local', 'testing') || 
                          config('app.use_mock_services', false);
        
        // Bind IPFS service
        if ($useMockServices) {
            $this->app->singleton(IPFSService::class, function ($app) {
                return new MockIPFSService();
            });
        }
        
        // Bind Phone Verification service
        if ($useMockServices) {
            $this->app->singleton(PhoneVerificationServiceInterface::class, function ($app) {
                return new MockPhoneVerificationService();
            });
        } else {
            $this->app->singleton(PhoneVerificationServiceInterface::class, function ($app) {
                return new PhoneVerificationService();
            });
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 