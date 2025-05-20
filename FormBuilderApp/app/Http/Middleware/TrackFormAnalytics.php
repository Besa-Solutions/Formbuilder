<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Form;
use App\Models\FormAnalytic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrackFormAnalytics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only track GET requests to the form render route
        if ($request->isMethod('get') && $request->route() && $request->route()->getName() === 'public.form.render') {
            try {
                $identifier = $request->route('identifier');
                
                // Find the form
                $form = Form::where('identifier', $identifier)->first();
                
                if ($form) {
                    // Get today's date
                    $today = Carbon::now()->format('Y-m-d');
                    
                    // Create or update analytics for today
                    $analytic = FormAnalytic::firstOrCreate(
                        [
                            'form_id' => $form->id,
                            'form_version' => $form->version ?? 1,
                            'date' => $today,
                        ],
                        [
                            'views' => 0,
                            'starts' => 0,
                            'completions' => 0,
                            'abandonments' => 0,
                        ]
                    );
                    
                    // Increment views
                    $analytic->increment('views');
                    
                    Log::info('Form view tracked', [
                        'form_id' => $form->id,
                        'form_name' => $form->name,
                        'date' => $today
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error tracking form analytics', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return $response;
    }
}
