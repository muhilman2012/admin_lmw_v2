<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use App\Services\WhatsApp\EvolutionWaService;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsAppServiceInterface::class, function ($app) {
            return new EvolutionWaService();
        });
    }

    public function boot(): void
    {
        //
    }
}