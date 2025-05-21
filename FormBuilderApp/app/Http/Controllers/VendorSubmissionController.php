<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VendorForm;
use doode\FormBuilder\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VendorSubmissionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param integer $form_id
     * @return \Illuminate\Http\Response
     */
    public function index($form_id)
    {
        $user = Auth::user();

        // Get the form, with more flexible querying
        $form = VendorForm::where('id', $form_id)->firstOrFail();

        $submissions = $form->submissions()
                            ->latest()
                            ->paginate(100);

        // Ensure form_builder_json is correctly processed
        if (isset($form->form_builder_json) && is_string($form->form_builder_json)) {
            $form->form_builder_json = json_decode($form->form_builder_json, true);
        }

        // Extract headers from form_builder_json
        $form_headers = $this->getFormHeaders($form);

        $pageTitle = "Submitted Entries for '{$form->name}'";

        return view(
            'vendor.formbuilder.submissions.index',
            compact('form', 'submissions', 'pageTitle', 'form_headers')
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $form_id
     * @param integer $submission_id
     * @return \Illuminate\Http\Response
     */
    public function show($form_id, $submission_id)
    {
        // Update to use the appropriate relationships
        $submission = Submission::where([
                'form_id' => $form_id,
                'id' => $submission_id,
            ])
            ->firstOrFail();
                            
        // Ensure form_builder_json is correctly processed
        $form = VendorForm::findOrFail($form_id);
        
        // Make sure we're working with an array for the form builder JSON
        if (isset($form->form_builder_json) && is_string($form->form_builder_json)) {
            $form->form_builder_json = json_decode($form->form_builder_json, true);
        }
        
        // Make sure we're working with an array for the submission content
        if (isset($submission->content) && is_string($submission->content)) {
            $submission->content = json_decode($submission->content, true);
            
            // Log for debugging
            Log::info('Decoded submission content', ['content' => $submission->content]);
        }

        // Extract headers from form_builder_json
        $form_headers = $this->getFormHeaders($form);
        
        // Log information for debugging
        Log::info('Showing submission', [
            'submission_id' => $submission->id,
            'form_id' => $form->id,
            'form_headers_count' => count($form_headers),
            'submission_content_type' => gettype($submission->content),
        ]);

        $pageTitle = "View Submission";

        return view('vendor.formbuilder.submissions.show', compact('pageTitle', 'submission', 'form_headers', 'form'));
    }
    
    /**
     * Extract headers from the form configuration
     */
    private function getFormHeaders($form)
    {
        // Log the raw form_builder_json for debugging
        \Log::info('Form builder JSON', [
            'form_id' => $form->id,
            'form_builder_json_type' => gettype($form->form_builder_json)
        ]);
        
        // Ensure form_builder_json is an array
        $formData = $form->form_builder_json;
        
        // If it's a string, decode it
        if (is_string($formData)) {
            $formData = json_decode($formData, true);
            \Log::info('Decoded form builder JSON', ['decoded_type' => gettype($formData)]);
        }
        
        // If it's still not an array or is null, try more approaches
        if (!is_array($formData) || is_null($formData)) {
            \Log::warning('JSON decode failed, attempting alternative approaches');
            
            // Try getting form builder array attribute
            try {
                if (method_exists($form, 'getFormBuilderArrayAttribute')) {
                    $formData = $form->getFormBuilderArrayAttribute(null);
                    \Log::info('Retrieved from FormBuilderArrayAttribute', ['result_type' => gettype($formData)]);
                }
            } catch (\Exception $e) {
                \Log::error('Error getting form builder array', ['error' => $e->getMessage()]);
            }
            
            // If still not successful, try getting from entries header method
            if ((!is_array($formData) || empty($formData)) && method_exists($form, 'getEntriesHeader')) {
                try {
                    $headers = $form->getEntriesHeader();
                    if ($headers && $headers->count() > 0) {
                        \Log::info('Retrieved from getEntriesHeader', ['count' => $headers->count()]);
                        return $headers->toArray();
                    }
                } catch (\Exception $e) {
                    \Log::error('Error getting entries header', ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Now process the array data (if we have it)
        if (is_array($formData)) {
            \Log::info('Processing form data array', ['keys' => array_keys($formData)]);
            
            // Case 1: Direct 'fields' array
            if (isset($formData['fields'])) {
                \Log::info('Found fields key in form data', ['count' => count($formData['fields'])]);
                
                return collect($formData['fields'])
                    ->filter(function ($field) {
                        return isset($field['name']);
                    })
                    ->map(function ($field) {
                        return [
                            'name' => $field['name'],
                            'label' => $field['label'] ?? ucfirst($field['name']),
                            'type' => $field['type'] ?? 'text',
                        ];
                    })
                    ->toArray();
            }
            
            // Case 2: Array of fields directly
            $fields = collect($formData)
                ->filter(function ($field) {
                    return is_array($field) && isset($field['name']);
                });
                
            if ($fields->count() > 0) {
                \Log::info('Found direct field objects', ['count' => $fields->count()]);
                
                return $fields->map(function ($field) {
                    return [
                        'name' => $field['name'],
                        'label' => $field['label'] ?? ucfirst($field['name']),
                        'type' => $field['type'] ?? 'text',
                    ];
                })->toArray();
            }
        }
        
        // Last resort: check a submission and extract fields from there
        try {
            $submission = Submission::where('form_id', $form->id)->first();
            if ($submission) {
                $content = $submission->content;
                if (is_string($content)) {
                    $content = json_decode($content, true);
                }
                
                if (is_array($content) && !empty($content)) {
                    \Log::info('Extracted fields from submission content', [
                        'field_count' => count($content),
                        'fields' => array_keys($content)
                    ]);
                    
                    return collect($content)->map(function ($value, $key) {
                        // Clean up the key to get a more human-readable label
                        $label = ucfirst(str_replace(['-', '_'], ' ', $key));
                        
                        return [
                            'name' => $key,
                            'label' => $label,
                            'type' => 'text', // Default to text as we can't determine the real type
                        ];
                    })->values()->toArray();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error extracting fields from submission', ['error' => $e->getMessage()]);
        }
        
        // Log failure
        \Log::warning('Could not extract form headers', [
            'form_id' => $form->id,
            'form_builder_json_type' => gettype($form->form_builder_json),
        ]);
        
        // Fallback to an empty array if structure is unexpected
        return [];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $form_id
     * @param int $submission_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($form_id, $submission_id)
    {
        $submission = Submission::where(['form_id' => $form_id, 'id' => $submission_id])->firstOrFail();
        $submission->delete();

        return redirect()
                    ->route('formbuilder::forms.submissions.index', $form_id)
                    ->with('success', 'Submission successfully deleted.');
    }

    /**
     * Display a listing of the submissions made by the current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function mySubmissions()
    {
        $user = Auth::user();
        $submissions = Submission::where('user_id', $user->id)
                                ->with('form')
                                ->latest()
                                ->paginate(100);

        $pageTitle = "My Submissions";

        return view('vendor.formbuilder.my_submissions.index', compact('submissions', 'pageTitle'));
    }

    /**
     * Export form submissions to Excel
     *
     * @param  int  $form_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportToExcel($form_id)
    {
        \Log::info('exportToExcel called', ['form_id' => $form_id, 'request_url' => request()->fullUrl()]);

        try {
            // Make sure form_id is valid
            if (!$form_id || !is_numeric($form_id)) {
                \Log::error('Invalid form_id provided', ['form_id' => $form_id]);
                return redirect()->back()->with('error', 'Invalid form ID provided');
            }

            // Find the form
            try {
                $form = VendorForm::findOrFail($form_id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                \Log::error('Form not found', ['form_id' => $form_id]);
                return redirect()->back()->with('error', 'Form not found with ID: ' . $form_id);
            }
            
            // Get submissions with error handling
            try {
                $submissions = Submission::where('form_id', $form_id)->get();
                
                if ($submissions->isEmpty()) {
                    \Log::warning('No submissions found for export', ['form_id' => $form_id]);
                    return redirect()->back()->with('warning', 'No submissions to export');
                }
            } catch (\Exception $e) {
                \Log::error('Error retrieving submissions', [
                    'form_id' => $form_id,
                    'error' => $e->getMessage()
                ]);
                return redirect()->back()->with('error', 'Failed to retrieve submissions: ' . $e->getMessage());
            }
            
            \Log::info('Export process started', [
                'form_id' => $form_id,
                'form_name' => $form->name ?? 'unknown',
                'submissions_count' => $submissions->count()
            ]);
            
            // Get form headers
            $headers = $this->getFormHeaders($form);
            \Log::info('Headers extracted', ['headers_count' => count($headers)]);
            
            // Create Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add headers
            $column = 'A';
            $sheet->setCellValue($column . '1', 'Submission ID');
            $column++;
            $sheet->setCellValue($column . '1', 'Submitted By');
            $column++;
            $sheet->setCellValue($column . '1', 'User Email');
            $column++;
            $sheet->setCellValue($column . '1', 'Submission Date');
            $column++;
            
            foreach ($headers as $header) {
                $sheet->setCellValue($column . '1', $header['label'] ?? ucfirst($header['name']));
                $column++;
            }
            
            // Style the header row
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEEEEE'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:' . $column . '1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($submissions as $submission) {
                try {
                    $column = 'A';
                    $sheet->setCellValue($column . $row, $submission->id);
                    $column++;
                    
                    // Submitted by (name)
                    try {
                        $userName = $submission->user->name ?? 'Guest';
                    } catch (\Exception $e) {
                        $userName = 'Guest';
                    }
                    $sheet->setCellValue($column . $row, $userName);
                    $column++;
                    
                    // User email
                    try {
                        $userEmail = $submission->user->email ?? 'N/A';
                    } catch (\Exception $e) {
                        $userEmail = 'N/A';
                    }
                    $sheet->setCellValue($column . $row, $userEmail);
                    $column++;
                    
                    // Submission Date
                    try {
                        $date = $submission->created_at->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $date = 'Unknown';
                    }
                    $sheet->setCellValue($column . $row, $date);
                    $column++;
                    
                    // Handle content which might be stored differently in different models
                    $content = $submission->content;
                    if (is_string($content)) {
                        $content = json_decode($content, true);
                    }
                    
                    // Fallback if content is still not an array
                    if (!is_array($content)) {
                        $content = [];
                        \Log::warning('Submission content is not an array', [
                            'submission_id' => $submission->id,
                            'content_type' => gettype($submission->content)
                        ]);
                    } else {
                        \Log::info('Processing submission content', [
                            'submission_id' => $submission->id,
                            'content_keys' => array_keys($content)
                        ]);
                    }
                    
                    // Make sure we have headers for all content fields even if they weren't in the form definition
                    $contentKeys = array_keys($content);
                    $headerNames = array_column($headers, 'name');
                    $missingHeaders = array_diff($contentKeys, $headerNames);
                    
                    if (!empty($missingHeaders)) {
                        \Log::info('Found content keys not in headers', ['missing' => $missingHeaders]);
                        
                        // Add missing headers
                        foreach ($missingHeaders as $key) {
                            $headers[] = [
                                'name' => $key,
                                'label' => ucfirst(str_replace(['-', '_'], ' ', $key)),
                                'type' => 'text'
                            ];
                        }
                    }
                    
                    foreach ($headers as $header) {
                        $fieldName = $header['name'];
                        $value = $content[$fieldName] ?? '';
                        
                        // For debugging
                        if (isset($content[$fieldName])) {
                            \Log::debug('Field value found', [
                                'field' => $fieldName,
                                'value_type' => gettype($content[$fieldName])
                            ]);
                        }
                        
                        // Handle different value types
                        if (is_array($value)) {
                            $value = implode(', ', $value);
                        } elseif (is_bool($value)) {
                            $value = $value ? 'Yes' : 'No';
                        } elseif (is_object($value)) {
                            $value = json_encode($value);
                        }
                        
                        $sheet->setCellValue($column . $row, $value);
                        $column++;
                    }
                    $row++;
                } catch (\Exception $e) {
                    \Log::error('Error processing submission row', [
                        'submission_id' => $submission->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
            
            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'form_' . $form_id . '_submissions_' . date('Y-m-d_His') . '.xlsx';
            $directory = storage_path('app/public');
            $filepath = $directory . '/' . $filename;
            
            // Ensure directory exists and is writable
            if (!file_exists($directory)) {
                try {
                    \Log::info('Creating storage directory', ['path' => $directory]);
                    mkdir($directory, 0755, true);
                } catch (\Exception $e) {
                    \Log::error('Failed to create storage directory', [
                        'directory' => $directory,
                        'error' => $e->getMessage()
                    ]);
                    return redirect()->back()->with('error', 'Export failed: Unable to create storage directory');
                }
            }
            
            if (!is_writable($directory)) {
                \Log::error('Storage directory not writable', ['directory' => $directory]);
                return redirect()->back()->with('error', 'Export failed: Storage directory not writable');
            }
            
            \Log::info('Saving Excel file', ['path' => $filepath]);
            $writer->save($filepath);
            
            // Verify file was created
            if (!file_exists($filepath)) {
                \Log::error('Export file was not created', ['filepath' => $filepath]);
                return redirect()->back()->with('error', 'Export failed: File could not be created');
            }
            
            \Log::info('Export completed successfully', ['form_id' => $form_id, 'file' => $filepath]);
            
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Export failed with exception', [
                'form_id' => $form_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Show more detailed error message in debug mode
            if (config('app.debug')) {
                return response()->view('errors.custom', [
                    'message' => 'Export failed: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
} 