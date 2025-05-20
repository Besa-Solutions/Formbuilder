<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubmissionController extends Controller
{
    public function index(Form $form)
    {
        // The form is already resolved by Laravel's route model binding
        // No need to query it again with user_id conditions
        $submissions = $form->submissions()->latest()->paginate(10);
        return view('formbuilder.submissions.index', compact('form', 'submissions'));
    }

    public function show(Form $form, FormSubmission $submission)
    {
        // Ensure form_builder_json is correctly processed
        if (isset($form->form_builder_json) && is_string($form->form_builder_json)) {
            $form->form_builder_json = json_decode($form->form_builder_json, true);
        }
        
        // Get the form headers from form_builder_array
        $form_headers = $this->getFormHeaders($form);
        
        return view('formbuilder.submissions.show', compact('form', 'submission', 'form_headers'));
    }
    
    /**
     * Extract headers from the form configuration
     */
    private function getFormHeaders(Form $form)
    {
        // Extract headers from form_builder_json
        if (is_array($form->form_builder_json) && isset($form->form_builder_json['fields'])) {
            return collect($form->form_builder_json['fields'])
                ->filter(function ($field) {
                    return isset($field['name']);
                })
                ->map(function ($field) {
                    return [
                        'name' => $field['name'],
                        'label' => $field['label'] ?? null,
                        'type' => $field['type'] ?? null,
                    ];
                })
                ->toArray();
        }
        
        // Fallback to an empty array if form_builder_json structure is unexpected
        return [];
    }
    
    /**
     * Display submissions made by the currently logged in user
     */
    public function userSubmissions()
    {
        // Query submissions by the current user ID
        $submissions = FormSubmission::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);
        
        return view('guest.my-submissions', compact('submissions'));
    }
    
    /**
     * Display a specific submission to the user who created it
     */
    public function showUserSubmission(FormSubmission $submission)
    {
        // Check if this submission belongs to the current user
        if ($submission->user_id !== Auth::id() && !(Auth::check() && Auth::user()->role === 'admin')) {
            abort(403, 'You do not have permission to view this submission.');
        }
        
        // Get the form that this submission belongs to
        $form = $submission->form;
        
        // Ensure form_builder_json is correctly processed
        if (isset($form->form_builder_json) && is_string($form->form_builder_json)) {
            $form->form_builder_json = json_decode($form->form_builder_json, true);
        }
        
        // Get the submission content directly - ensure it's decoded properly
        $submissionData = is_array($submission->content) ? $submission->content : json_decode($submission->content, true);
        
        // If submission data is still not an array, create an empty array to avoid errors
        if (!is_array($submissionData)) {
            Log::error('Failed to decode submission data', ['content' => $submission->content]);
            $submissionData = [];
        }
        
        // Extract form structure to display the submission properly
        $formData = is_array($form->form_builder_json) ? $form->form_builder_json : [];
        $formFields = isset($formData['fields']) ? $formData['fields'] : [];
        
        // For debugging purposes
        Log::info('Form fields', ['fields' => $formFields]);
        Log::info('Submission data', ['data' => $submissionData]);
        
        // Convert form data to a displayable format
        $displayData = [];
        
        // If we have submission data, format it for display
        if (!empty($submissionData)) {
            foreach ($submissionData as $key => $value) {
                // Try to find a matching field in the form definition
                $matchedField = null;
                foreach ($formFields as $field) {
                    if (isset($field['name']) && $field['name'] === $key) {
                        $matchedField = $field;
                        break;
                    }
                }
                
                // Add to display data
                $displayData[] = [
                    'label' => $matchedField['label'] ?? str_replace('-', ' ', ucfirst($key)),
                    'value' => is_array($value) ? implode(', ', $value) : $value,
                    'type' => $matchedField['type'] ?? 'text'
                ];
            }
        }
        
        return view('guest.my-submission-details', compact('submission', 'form', 'displayData'));
    }
    
    /**
     * Delete a submission
     */
    public function destroy(Form $form, FormSubmission $submission)
    {
        // Only admin or the submission owner can delete the submission
        if (!(Auth::check() && Auth::user()->role === 'admin') && $submission->user_id !== Auth::id()) {
            abort(403, 'You do not have permission to delete this submission.');
        }
        
        // Delete any associated files if present
        if (!empty($submission->files_meta)) {
            foreach ($submission->files_meta as $fileMeta) {
                if (isset($fileMeta['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($fileMeta['path']);
                }
            }
        }
        
        $submission->delete();
        
        // Redirect based on user role
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()
                ->route('admin.forms.submissions', $form)
                ->with('success', 'Submission deleted successfully.');
        } else {
            return redirect()
                ->route('guest.my-submissions')
                ->with('success', 'Submission deleted successfully.');
        }
    }
} 