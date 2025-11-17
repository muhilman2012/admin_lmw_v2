<?php

namespace App\Providers;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::disk('complaints')->buildTemporaryUrlsUsing(function ($path, $expiration, $options) {
            $minutes = now()->diffInMinutes($expiration);
            return signMinioUrlSmart(env('AWS_COMPLAINT_BUCKET'), $path, $minutes);
        });

        Storage::disk('uploads')->buildTemporaryUrlsUsing(function ($path, $expiration, $options) {
            $minutes = now()->diffInMinutes($expiration);
            return signMinioUrlSmart(env('AWS_UPLOADS_BUCKET'), $path, $minutes);
        });

        if (config('app.env') === 'testing') {
            URL::forceScheme('https'); 
        }
    }
}
