<?php

namespace App\Providers;

use App\Services\MarketData\AlphaVantageMarketDataProvider;
use App\Services\MarketData\DemoMarketDataProvider;
use App\Services\MarketData\MarketDataProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MarketDataProvider::class, function () {
            $provider = config('services.market_data.provider', 'demo');

            return $provider === 'alphavantage'
                ? new AlphaVantageMarketDataProvider(
                    apiKey: (string) config('services.alphavantage.key', ''),
                    baseUrl: (string) config('services.alphavantage.base_url', 'https://www.alphavantage.co'),
                )
                : new DemoMarketDataProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::addLocation(dirname(base_path()).'/web/views');
    }
}
