<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormAnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Get analytics data for a specific form
     */
    public function getFormAnalytics(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        // Default to last 30 days if not specified
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-d', strtotime('-30 days'));
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-d');
        
        // Get analytics for the date range
        $analytics = $form->analytics()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date')
            ->get();
        
        if ($analytics->isEmpty()) {
            return response()->json([
                'message' => 'No analytics data found for the specified period',
                'data' => [
                    'form_id' => $form->id,
                    'form_name' => $form->name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_views' => 0,
                    'total_starts' => 0,
                    'total_completions' => 0,
                    'completion_rate' => 0,
                    'average_completion_time' => 0,
                    'daily_data' => [],
                    'abandonment_points' => [],
                ],
            ]);
        }
        
        // Calculate totals
        $totalViews = $analytics->sum('views');
        $totalStarts = $analytics->sum('starts');
        $totalCompletions = $analytics->sum('completions');
        $completionRate = $totalStarts > 0 ? round(($totalCompletions / $totalStarts) * 100, 2) : 0;
        
        // Calculate average completion time
        $completionTimeSum = 0;
        $completionTimeCount = 0;
        
        foreach ($analytics as $analytic) {
            if ($analytic->average_completion_time !== null) {
                $completionTimeSum += $analytic->average_completion_time * $analytic->completions;
                $completionTimeCount += $analytic->completions;
            }
        }
        
        $averageCompletionTime = $completionTimeCount > 0 ? round($completionTimeSum / $completionTimeCount) : 0;
        
        // Prepare daily data for charts
        $dailyData = $analytics->map(function ($analytic) {
            return [
                'date' => $analytic->date->format('Y-m-d'),
                'views' => $analytic->views,
                'starts' => $analytic->starts,
                'completions' => $analytic->completions,
                'completion_rate' => $analytic->getCompletionRate(),
                'average_completion_time' => $analytic->average_completion_time,
            ];
        });
        
        // Aggregate abandonment points
        $abandonmentPoints = [];
        
        foreach ($analytics as $analytic) {
            if ($analytic->abandonment_points) {
                foreach ($analytic->abandonment_points as $point => $count) {
                    if (!isset($abandonmentPoints[$point])) {
                        $abandonmentPoints[$point] = 0;
                    }
                    $abandonmentPoints[$point] += $count;
                }
            }
        }
        
        // Sort abandonment points by count in descending order
        arsort($abandonmentPoints);
        
        return response()->json([
            'data' => [
                'form_id' => $form->id,
                'form_name' => $form->name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_views' => $totalViews,
                'total_starts' => $totalStarts,
                'total_completions' => $totalCompletions,
                'completion_rate' => $completionRate,
                'average_completion_time' => $averageCompletionTime,
                'average_completion_time_formatted' => $this->formatSeconds($averageCompletionTime),
                'daily_data' => $dailyData,
                'abandonment_points' => $abandonmentPoints,
            ],
        ]);
    }
    
    /**
     * Get analytics data for all forms
     */
    public function getAllFormsAnalytics(Request $request)
    {
        // Default to last 30 days if not specified
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : date('Y-m-d', strtotime('-30 days'));
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : date('Y-m-d');
        
        // Get all forms with their analytics
        $forms = Form::with(['analytics' => function ($query) use ($startDate, $endDate) {
            $query->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate);
        }])->get();
        
        $formsData = [];
        
        foreach ($forms as $form) {
            $analytics = $form->analytics;
            
            // Calculate totals
            $totalViews = $analytics->sum('views');
            $totalStarts = $analytics->sum('starts');
            $totalCompletions = $analytics->sum('completions');
            $completionRate = $totalStarts > 0 ? round(($totalCompletions / $totalStarts) * 100, 2) : 0;
            
            // Calculate average completion time
            $completionTimeSum = 0;
            $completionTimeCount = 0;
            
            foreach ($analytics as $analytic) {
                if ($analytic->average_completion_time !== null) {
                    $completionTimeSum += $analytic->average_completion_time * $analytic->completions;
                    $completionTimeCount += $analytic->completions;
                }
            }
            
            $averageCompletionTime = $completionTimeCount > 0 ? round($completionTimeSum / $completionTimeCount) : 0;
            
            $formsData[] = [
                'form_id' => $form->id,
                'form_name' => $form->name,
                'identifier' => $form->identifier,
                'status' => $form->status,
                'is_published' => $form->is_published,
                'total_views' => $totalViews,
                'total_starts' => $totalStarts,
                'total_completions' => $totalCompletions,
                'completion_rate' => $completionRate,
                'average_completion_time' => $averageCompletionTime,
                'average_completion_time_formatted' => $this->formatSeconds($averageCompletionTime),
            ];
        }
        
        // Sort by completion rate in descending order
        usort($formsData, function ($a, $b) {
            return $b['completion_rate'] <=> $a['completion_rate'];
        });
        
        return response()->json([
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'forms' => $formsData,
            ],
        ]);
    }
    
    /**
     * Record a form view
     */
    public function recordView(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $today = now()->format('Y-m-d');
        
        $analytic = $form->analytics()
            ->firstOrCreate([
                'form_version' => $form->version,
                'date' => $today,
            ]);
        
        $analytic->views += 1;
        $analytic->save();
        
        return response()->json([
            'message' => 'View recorded successfully',
        ]);
    }
    
    /**
     * Record a form start
     */
    public function recordStart(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $today = now()->format('Y-m-d');
        
        $analytic = $form->analytics()
            ->firstOrCreate([
                'form_version' => $form->version,
                'date' => $today,
            ]);
        
        $analytic->starts += 1;
        $analytic->save();
        
        return response()->json([
            'message' => 'Start recorded successfully',
            'timestamp' => now(),
        ]);
    }
    
    /**
     * Format seconds into a human-readable time
     */
    private function formatSeconds($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . ' minutes, ' . $remainingSeconds . ' seconds';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ' hours, ' . $remainingMinutes . ' minutes, ' . $remainingSeconds . ' seconds';
    }
}
