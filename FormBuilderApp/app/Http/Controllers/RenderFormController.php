<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorForm;
use App\Models\FormSubmission;
use App\Models\FormAnalytic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RenderFormController extends Controller
{
    public function show($identifier)
    {
        $form = VendorForm::where('identifier', $identifier)->firstOrFail();
        
        // Ensure form_builder_json is properly formatted
        if (isset($form->form_builder_json) && is_string($form->form_builder_json)) {
            $form->form_builder_json = json_decode($form->form_builder_json, true);
        }
        
        // Track form view in analytics
        $this->trackFormView($form);
        
        return view('formbuilder.forms.render', compact('form'));
    }

    public function submit(Request $request, $identifier)
    {
        $form = VendorForm::where('identifier', $identifier)->firstOrFail();
        
        // Get all input data except token
        $formData = $request->except('_token');
        
        // Check if we have form data
        if (empty($formData) && !$request->hasFile('filedownloader')) {
            return back()->withErrors(['content' => 'Please fill out the form before submitting.']);
        }
        
        // Get start time from session if available
        $startTime = session("form_start_time.{$form->id}");
        $completionTime = null;
        
        if ($startTime) {
            $startTime = Carbon::parse($startTime);
            $completionTime = $startTime->diffInSeconds(Carbon::now());
        }
        
        // Process files if present
        $filesMeta = [];
        $formContent = is_string($form->form_builder_json) 
            ? json_decode($form->form_builder_json, true) 
            : $form->form_builder_json;
            
        $fields = $formContent['fields'] ?? [];
        
        // Log file information for debugging
        Log::info('Processing form submission', [
            'form_id' => $form->id,
            'has_files' => $request->hasFile('filedownloader'),
            'all_files' => $request->allFiles(),
            'form_fields' => $fields
        ]);
        
        // Check if we have the direct file upload field (simplified check)
        if ($request->hasFile('filedownloader')) {
            $file = $request->file('filedownloader');
            $path = $file->store('form_submissions/' . $form->id, 'public');
            
            $filesMeta['filedownloader'] = [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
            
            $formData['filedownloader'] = $path;
            
            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize()
            ]);
        }
        
        // Check general form fields for file uploads
        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            // Process file uploads for file fields
            if ($field['type'] === 'file' && $request->hasFile($fieldName)) {
                $file = $request->file($fieldName);
                $path = $file->store('form_submissions/' . $form->id, 'public');
                
                $filesMeta[$fieldName] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
                
                // Store the file path in the form data
                $formData[$fieldName] = $path;
                
                Log::info("Field $fieldName uploaded successfully", [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path
                ]);
            }
        }
        
        // Create the submission with all necessary fields
        $filesMetaJson = !empty($filesMeta) ? json_encode($filesMeta) : null;
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'form_version' => $form->version ?? 1,
            'content' => json_encode($formData),
            'submission_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_complete' => true,
            'is_anonymous' => !Auth::check(),
            'started_at' => $startTime,
            'completed_at' => now(),
            'status' => 'new',
            'files_meta' => $filesMetaJson,
        ]);

        Log::info('Form submission created', [
            'form_id' => $form->id,
            'submission_id' => $submission->id,
            'user_id' => Auth::id() ?? 'guest',
            'completion_time' => $completionTime,
            'has_files' => !empty($filesMeta),
            'files_meta' => $filesMetaJson
        ]);
        
        // Track form completion in analytics
        $this->trackFormCompletion($form, $completionTime);
        
        // Clear form start time from session
        session()->forget("form_start_time.{$form->id}");

        return view('vendor.formbuilder.render.feedback', compact('form'));
    }
    
    /**
     * Track when a user views a form
     */
    private function trackFormView(VendorForm $form)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            
            // Track form view time in session to calculate completion time later
            session(["form_start_time.{$form->id}" => Carbon::now()]);
            
            // Track form view in analytics - this is also handled by middleware
            // but we're including it here for completeness
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
            
            // Track that a user started filling out the form
            $analytic->increment('starts');
            
            Log::info('Form start tracked', [
                'form_id' => $form->id,
                'form_name' => $form->name,
                'date' => $today
            ]);
        } catch (\Exception $e) {
            Log::error('Error tracking form view', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Track when a user completes a form
     */
    private function trackFormCompletion(VendorForm $form, $completionTime = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            
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
            
            // Increment completions
            $analytic->increment('completions');
            
            // Update average completion time if we have one
            if ($completionTime) {
                // If we already have an average, update it
                if ($analytic->average_completion_time) {
                    $totalCompletions = $analytic->completions;
                    $currentAverage = $analytic->average_completion_time;
                    
                    // Calculate new average
                    $newAverage = (($currentAverage * ($totalCompletions - 1)) + $completionTime) / $totalCompletions;
                    $analytic->average_completion_time = round($newAverage);
                } else {
                    // First completion, just set it
                    $analytic->average_completion_time = $completionTime;
                }
                
                $analytic->save();
            }
            
            Log::info('Form completion tracked', [
                'form_id' => $form->id,
                'form_name' => $form->name,
                'date' => $today,
                'completion_time' => $completionTime
            ]);
        } catch (\Exception $e) {
            Log::error('Error tracking form completion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Show the test upload form
     */
    public function testUpload($identifier)
    {
        $form = VendorForm::where('identifier', $identifier)->firstOrFail();
        return view('formbuilder.forms.test-upload', compact('form'));
    }
    
    /**
     * Process a test file upload
     */
    public function processTestUpload(Request $request, $identifier)
    {
        $form = VendorForm::where('identifier', $identifier)->firstOrFail();
        
        if (!$request->hasFile('filedownloader')) {
            return back()->withErrors(['filedownloader' => 'Please select a file to upload.']);
        }
        
        $file = $request->file('filedownloader');
        $path = $file->store('form_submissions/' . $form->id, 'public');
        
        $filesMeta = [
            'filedownloader' => [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]
        ];
        
        $formData = [
            'filedownloader' => $path
        ];
        
        // Log file information
        Log::info('Test file uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize()
        ]);
        
        // Create the submission with all necessary fields
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'form_version' => $form->version ?? 1,
            'content' => json_encode($formData),
            'submission_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_complete' => true,
            'is_anonymous' => !Auth::check(),
            'started_at' => now(),
            'completed_at' => now(),
            'status' => 'new',
            'files_meta' => json_encode($filesMeta),
        ]);
        
        Log::info('Test submission created', [
            'form_id' => $form->id,
            'submission_id' => $submission->id,
            'file_path' => $path
        ]);
        
        return redirect()->route('formbuilder::forms.submissions.show', [$form->id, $submission->id])
            ->with('success', 'File uploaded successfully. You can download it from this submission.');
    }
} 