<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the submissions for a form.
     */
    public function index(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $query = $form->submissions();
        
        // Filter by completion status
        if ($request->has('is_complete')) {
            $isComplete = filter_var($request->is_complete, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_complete', $isComplete);
        }
        
        // Filter by submission status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $perPage = $request->per_page ?? 15;
        $submissions = $query->paginate($perPage);
        
        return response()->json([
            'data' => $submissions->items(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    /**
     * Store a newly created submission.
     */
    public function store(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        // Check if form is active
        if (!$form->isActive()) {
            return response()->json([
                'message' => 'This form is not currently active',
            ], 403);
        }
        
        // Validate base submission data
        $validator = Validator::make($request->all(), [
            'content' => 'required|array',
            'is_anonymous' => 'boolean',
            'partial' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $validated = $validator->validated();
        
        // Process files if present
        $filesMeta = [];
        $formContent = json_decode($form->form_builder_json, true);
        $fields = $formContent['fields'] ?? [];
        
        foreach ($fields as $field) {
            // Process file uploads for file fields
            if ($field['type'] === 'file' && $request->hasFile($field['name'])) {
                $file = $request->file($field['name']);
                $path = $file->store('form_submissions/' . $form->id, 'public');
                
                $filesMeta[$field['name']] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
                
                // Store the file path in the content
                $validated['content'][$field['name']] = $path;
            }
        }
        
        // Create submission record
        $submission = new Submission([
            'form_id' => $form->id,
            'form_version' => $form->version,
            'content' => $validated['content'],
            'submission_ip' => $request->ip(),
            'files_meta' => !empty($filesMeta) ? $filesMeta : null,
            'is_complete' => !($validated['partial'] ?? false),
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'user_agent' => $request->userAgent(),
            'status' => 'new',
        ]);
        
        // Handle tracking of completion time
        if ($request->has('started_at')) {
            $submission->started_at = $request->started_at;
            $submission->completed_at = now();
        } else {
            $submission->completed_at = now();
        }
        
        $submission->save();
        
        // Update analytics
        // This would be better handled via an event listener in a real-world application
        $this->updateAnalytics($form, $submission);
        
        return response()->json([
            'message' => 'Submission saved successfully',
            'data' => $submission,
        ], 201);
    }

    /**
     * Display the specified submission.
     */
    public function show(string $identifier, string $submissionId)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $submission = $form->submissions()->find($submissionId);
        
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found',
            ], 404);
        }
        
        return response()->json([
            'data' => $submission,
        ]);
    }

    /**
     * Update the status of a submission.
     */
    public function updateStatus(Request $request, string $identifier, string $submissionId)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $submission = $form->submissions()->find($submissionId);
        
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found',
            ], 404);
        }
        
        $validated = $request->validate([
            'status' => 'required|string|in:new,reviewed,approved,rejected',
        ]);
        
        $submission->update([
            'status' => $validated['status'],
        ]);
        
        return response()->json([
            'message' => 'Submission status updated successfully',
            'data' => $submission,
        ]);
    }

    /**
     * Remove the specified submission.
     */
    public function destroy(string $identifier, string $submissionId)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $submission = $form->submissions()->find($submissionId);
        
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found',
            ], 404);
        }
        
        // Delete associated files
        if (!empty($submission->files_meta)) {
            foreach ($submission->files_meta as $fileMeta) {
                if (isset($fileMeta['path'])) {
                    Storage::disk('public')->delete($fileMeta['path']);
                }
            }
        }
        
        $submission->delete();
        
        return response()->json([
            'message' => 'Submission deleted successfully',
        ]);
    }
    
    /**
     * Export submissions as CSV
     */
    public function export(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $query = $form->submissions();
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $submissions = $query->get();
        
        if ($submissions->isEmpty()) {
            return response()->json([
                'message' => 'No submissions found to export',
            ], 404);
        }
        
        // Extract form fields from form builder JSON
        $formContent = json_decode($form->form_builder_json, true);
        $fields = collect($formContent['fields'] ?? [])->pluck('label', 'name')->toArray();
        
        // Create CSV content
        $csvFileName = 'form_' . $form->id . '_submissions_' . date('Y-m-d_H-i') . '.csv';
        $csvPath = 'exports/' . $csvFileName;
        
        $csvHandle = fopen(storage_path('app/public/' . $csvPath), 'w');
        
        // Create header row
        $headerRow = array_merge(
            ['Submission ID', 'Date', 'Status', 'IP Address'],
            array_values($fields)
        );
        fputcsv($csvHandle, $headerRow);
        
        // Add data rows
        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->created_at->format('Y-m-d H:i:s'),
                $submission->status,
                $submission->submission_ip,
            ];
            
            // Add field values
            foreach (array_keys($fields) as $fieldName) {
                $row[] = $submission->content[$fieldName] ?? '';
            }
            
            fputcsv($csvHandle, $row);
        }
        
        fclose($csvHandle);
        
        return response()->json([
            'message' => 'Export created successfully',
            'data' => [
                'file_name' => $csvFileName,
                'download_url' => asset('storage/' . $csvPath),
            ],
        ]);
    }
    
    /**
     * Update form analytics based on submission
     */
    private function updateAnalytics(Form $form, Submission $submission)
    {
        $today = now()->format('Y-m-d');
        
        $analytic = $form->analytics()
            ->firstOrCreate([
                'form_version' => $form->version,
                'date' => $today,
            ]);
        
        // Increment completions or abandonments based on submission status
        if ($submission->is_complete) {
            $analytic->completions += 1;
        } else {
            $analytic->abandonments += 1;
            
            // Track abandonment points
            $abandonmentPoints = $analytic->abandonment_points ?? [];
            
            // Analyze submission to identify where user stopped
            // This is a simplified version - in a real app, you'd have more detailed analysis
            $lastCompletedQuestion = null;
            foreach ($submission->content as $key => $value) {
                if (!empty($value)) {
                    $lastCompletedQuestion = $key;
                }
            }
            
            if ($lastCompletedQuestion) {
                if (!isset($abandonmentPoints[$lastCompletedQuestion])) {
                    $abandonmentPoints[$lastCompletedQuestion] = 0;
                }
                $abandonmentPoints[$lastCompletedQuestion]++;
                $analytic->abandonment_points = $abandonmentPoints;
            }
        }
        
        // Update average completion time
        if ($submission->is_complete && $submission->started_at && $submission->completed_at) {
            $completionTime = $submission->completed_at->diffInSeconds($submission->started_at);
            
            if ($analytic->average_completion_time === null) {
                $analytic->average_completion_time = $completionTime;
            } else {
                // Recalculate the average
                $totalCompletions = $analytic->completions;
                $currentAverage = $analytic->average_completion_time;
                
                // New average = (old average * (n-1) + new value) / n
                $newAverage = (($currentAverage * ($totalCompletions - 1)) + $completionTime) / $totalCompletions;
                $analytic->average_completion_time = round($newAverage);
            }
        }
        
        $analytic->save();
    }
}
