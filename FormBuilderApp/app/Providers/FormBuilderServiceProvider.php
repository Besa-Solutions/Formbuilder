<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use doode\FormBuilder\Models\Form as VendorForm;
use App\Models\VendorForm as CustomVendorForm;

class FormBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No special registration needed
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Nothing to do here, we'll rely on our custom VendorForm class
    }
} 