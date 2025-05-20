<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormAnalytic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateFormAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-form-analytics {--days=30 : Number of days to generate analytics for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate analytics data from form submissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $this->info("Generating analytics from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Get all forms
        $forms = Form::all();
        $this->info("Found {$forms->count()} forms");

        foreach ($forms as $form) {
            $this->info("Processing form: {$form->name} (ID: {$form->id})");
            
            // Get date range as array of dates
            $dates = [];
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Process each day
            foreach ($dates as $date) {
                $dayStart = Carbon::parse($date . ' 00:00:00');
                $dayEnd = Carbon::parse($date . ' 23:59:59');
                
                // Count submissions for this day
                $completions = FormSubmission::where('form_id', $form->id)
                    ->where('is_complete', true)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();
                    
                // We don't have "started" data from past submissions, 
                // so we'll estimate as 120% of completions to simulate some dropoff
                $starts = max(1, ceil($completions * 1.2));
                
                // Assume each view has a 50% chance of starting the form
                $views = max(1, ceil($starts * 2));
                
                // Abandonment is starts minus completions
                $abandonments = $starts - $completions;
                
                // Calculate average completion time (fictional for historical data)
                $avgTime = null;
                if ($completions > 0) {
                    $avgTime = rand(60, 300); // Random time between 1-5 minutes
                }
                
                // Create or update the analytics record
                FormAnalytic::updateOrCreate(
                    [
                        'form_id' => $form->id,
                        'form_version' => $form->version ?? 1,
                        'date' => $date,
                    ],
                    [
                        'views' => $views,
                        'starts' => $starts,
                        'completions' => $completions,
                        'abandonments' => $abandonments,
                        'average_completion_time' => $avgTime,
                    ]
                );
                
                $this->info("  - {$date}: {$views} views, {$completions} completions");
            }
        }
        
        $this->info('Analytics generation completed!');
    }
}
