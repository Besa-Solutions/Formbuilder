<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionMethod;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix the vendor Form model issue with getFormBuilderArrayAttribute
        $this->patchVendorFormModel();
    }
    
    /**
     * Patch the vendor Form model to fix the getFormBuilderArrayAttribute method
     */
    protected function patchVendorFormModel(): void
    {
        // Only run this if the vendor class exists
        if (!class_exists('doode\FormBuilder\Models\Form')) {
            return;
        }
        
        // Define the correct method implementation
        \doode\FormBuilder\Models\Form::macro('getFormBuilderArrayAttribute', function ($value) {
            // If it's already an array, return it
            if (is_array($value)) {
                return $value;
            }
            
            // If it's empty, return empty array
            if (empty($value)) {
                return [];
            }
            
            // Convert to array if it's a JSON string
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            
            // Fallback to an empty array if all else fails
            return [];
        });
    }
}
