<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Submission;
use App\Models\VendorForm;
use doode\FormBuilder\Models\Submission as VendorSubmission;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    /**
     * Export form submissions to Excel
     *
     * @param  int  $form_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportFormSubmissions($form_id)
    {
        Log::info('Export started', ['form_id' => $form_id, 'request_url' => request()->fullUrl()]);

        try {
            // Try to find the form using different form models
            try {
                $form = Form::findOrFail($form_id);
                $submissions = \App\Models\Submission::where('form_id', $form_id)->get();
            } catch (\Exception $e) {
                Log::info('Form not found in Form model, trying VendorForm', ['form_id' => $form_id, 'error' => $e->getMessage()]);
                try {
                    $form = VendorForm::findOrFail($form_id);
                    
                    // Try with App\Models\Submission first
                    try {
                        $submissions = \App\Models\Submission::where('form_id', $form_id)->get();
                    } catch (\Exception $submissionError) {
                        // Fall back to vendor Submission model
                        Log::info('Using vendor Submission model', ['form_id' => $form_id]);
                        $submissions = VendorSubmission::where('form_id', $form_id)->get();
                    }
                } catch (\Exception $e2) {
                    Log::error('Form not found in either model', ['form_id' => $form_id]);
                    return redirect()->back()->with('error', 'Form not found with ID: ' . $form_id);
                }
            }
            
            Log::info('Form found, processing submissions', [
                'form_id' => $form_id, 
                'form_name' => $form->name ?? 'unknown',
                'submissions_count' => $submissions->count()
            ]);
            
            // Extract field headers from the form
            $headers = $this->getFormHeaders($form);
            
            Log::info('Headers extracted', ['headers_count' => count($headers)]);
            
            // Create Excel file
            Log::info('Creating Excel spreadsheet');
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add headers
            Log::info('Adding headers to spreadsheet');
            
            // Commonly used columns
            $headerColumns = [
                'A' => 'Submission ID',
                'B' => 'Submitted By',
                'C' => 'User Email',
                'D' => 'IP Address',
                'E' => 'User Agent',
                'F' => 'Submission Date'
            ];
            
            // Add the common columns first
            foreach ($headerColumns as $col => $label) {
                $sheet->setCellValue($col . '1', $label);
            }
            
            // Start the form fields after the common columns
            $column = 'G';
            
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
            
            // Make the header row bold and freeze it
            $sheet->getStyle('A1:' . $column . '1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            
            // Add data
            Log::info('Adding data to spreadsheet', ['rows' => $submissions->count()]);
            $row = 2;
            foreach ($submissions as $submission) {
                try {
                    // Add common fields
                    $sheet->setCellValue('A' . $row, $submission->id);
                    
                    // Submitted by (name)
                    try {
                        $userName = $submission->user->name ?? 'Guest';
                    } catch (\Exception $e) {
                        $userName = 'Guest';
                    }
                    $sheet->setCellValue('B' . $row, $userName);
                    
                    // User email
                    try {
                        $userEmail = $submission->user->email ?? 'N/A';
                    } catch (\Exception $e) {
                        $userEmail = 'N/A';
                    }
                    $sheet->setCellValue('C' . $row, $userEmail);
                    
                    // IP Address
                    $sheet->setCellValue('D' . $row, $submission->submission_ip ?? 'N/A');
                    
                    // User Agent
                    $sheet->setCellValue('E' . $row, $submission->user_agent ?? 'N/A');
                    
                    // Submission Date
                    try {
                        $date = $submission->created_at->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $date = 'Unknown';
                    }
                    $sheet->setCellValue('F' . $row, $date);
                    
                    // Start field data from column G
                    $column = 'G';
                    
                    // Handle content which might be stored differently in different models
                    if (is_string($submission->content)) {
                        $content = json_decode($submission->content, true);
                    } else {
                        $content = $submission->content;
                    }
                    
                    // Fallback if content is still not an array
                    if (!is_array($content)) {
                        $content = [];
                    }
                    
                    foreach ($headers as $header) {
                        $value = $content[$header['name']] ?? '';
                        if (is_array($value)) {
                            $value = implode(', ', $value);
                        }
                        $sheet->setCellValue($column . $row, $value);
                        $column++;
                    }
                    $row++;
                } catch (\Exception $rowError) {
                    Log::warning('Error processing row', [
                        'submission_id' => $submission->id ?? 'unknown',
                        'error' => $rowError->getMessage()
                    ]);
                    // Continue with next submission
                    continue;
                }
            }
            
            // Auto-size columns
            Log::info('Auto-sizing columns');
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Apply some basic styling to the data
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ];
            $lastRow = $row - 1;
            if ($lastRow >= 2) { // Only apply if we have data rows
                $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($dataStyle);
            }
            
            // Set alternating row colors
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 == 0) {
                    $sheet->getStyle('A' . $i . ':' . $sheet->getHighestColumn() . $i)
                          ->getFill()
                          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()
                          ->setRGB('F9F9F9');
                }
            }
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('Form Builder Application')
                ->setLastModifiedBy('Form Builder Application')
                ->setTitle('Form Submissions - ' . ($form->name ?? 'Unknown Form'))
                ->setSubject('Export of form submissions')
                ->setDescription('Export of submissions for form ID ' . $form_id)
                ->setKeywords('form submissions export')
                ->setCategory('Form Data');
                
            // Name the worksheet
            $sheet->setTitle('Form Submissions');
            
            // Create directory if it doesn't exist
            Log::info('Preparing to save file');
            $directory = storage_path('app/public');
            if (!file_exists($directory)) {
                try {
                    Log::info('Creating storage directory', ['path' => $directory]);
                    mkdir($directory, 0755, true);
                } catch (\Exception $e) {
                    Log::error('Failed to create storage directory', [
                        'directory' => $directory,
                        'error' => $e->getMessage()
                    ]);
                    return redirect()->back()->with('error', 'Export failed: Unable to create storage directory');
                }
            }
            
            if (!is_writable($directory)) {
                Log::error('Storage directory not writable', ['directory' => $directory]);
                return redirect()->back()->with('error', 'Export failed: Storage directory not writable');
            }
            
            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'form_' . $form_id . '_submissions_' . date('Y-m-d_His') . '.xlsx';
            $filepath = $directory . '/' . $filename;
            
            Log::info('Saving Excel file', ['path' => $filepath]);
            try {
                $writer->save($filepath);
            } catch (\Exception $e) {
                Log::error('Failed to save Excel file', [
                    'filepath' => $filepath,
                    'error' => $e->getMessage()
                ]);
                return redirect()->back()->with('error', 'Export failed: Unable to save Excel file - ' . $e->getMessage());
            }
            
            // Verify file was created
            if (!file_exists($filepath)) {
                Log::error('Export file was not created', ['filepath' => $filepath]);
                return redirect()->back()->with('error', 'Export failed: File could not be created');
            }
            
            Log::info('Export completed', ['form_id' => $form_id, 'file' => $filepath]);
            
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'form_id' => $form_id, 
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // For debugging, show detailed error
            return response()->view('errors.custom', [
                'message' => 'Export failed: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Get form headers from JSON structure
     */
    private function getFormHeaders($form)
    {
        // Log the form builder JSON structure
        Log::info('Getting form headers', [
            'form_id' => $form->id,
            'form_builder_json_type' => gettype($form->form_builder_json)
        ]);
        
        // Ensure form_builder_json is an array
        $formData = $form->form_builder_json;
        
        // If it's a string, decode it
        if (is_string($formData)) {
            $formData = json_decode($formData, true);
            Log::info('Decoded form builder JSON', ['decoded_type' => gettype($formData)]);
        }
        
        // If it's still not an array or is null, try more approaches
        if (!is_array($formData) || is_null($formData)) {
            Log::warning('JSON decode failed, attempting alternative approaches');
            
            // Try getting form builder array attribute
            try {
                if (method_exists($form, 'getFormBuilderArrayAttribute')) {
                    $formData = $form->getFormBuilderArrayAttribute(null);
                    Log::info('Retrieved from FormBuilderArrayAttribute', ['result_type' => gettype($formData)]);
                }
            } catch (\Exception $e) {
                Log::error('Error getting form builder array', ['error' => $e->getMessage()]);
            }
            
            // If still not successful, try getting from entries header method
            if ((!is_array($formData) || empty($formData)) && method_exists($form, 'getEntriesHeader')) {
                try {
                    $headers = $form->getEntriesHeader();
                    if ($headers && $headers->count() > 0) {
                        Log::info('Retrieved from getEntriesHeader', ['count' => $headers->count()]);
                        return $headers->toArray();
                    }
                } catch (\Exception $e) {
                    Log::error('Error getting entries header', ['error' => $e->getMessage()]);
                }
            }
        }
        
        // Now process the array data (if we have it)
        if (is_array($formData)) {
            Log::info('Processing form data array', ['keys' => array_keys($formData)]);
            
            // Case 1: Direct 'fields' array
            if (isset($formData['fields'])) {
                Log::info('Found fields key in form data', ['count' => count($formData['fields'])]);
                
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
                Log::info('Found direct field objects', ['count' => $fields->count()]);
                
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
            $submission = null;
            
            // Try to get submission from Form model
            try {
                $submission = \App\Models\FormSubmission::where('form_id', $form->id)->first();
            } catch (\Exception $e) {
                Log::info('Error getting FormSubmission, trying vendor model', ['error' => $e->getMessage()]);
            }
            
            // Try vendor model if Form model failed
            if (!$submission) {
                try {
                    $submission = \doode\FormBuilder\Models\Submission::where('form_id', $form->id)->first();
                } catch (\Exception $e) {
                    Log::info('Error getting vendor Submission', ['error' => $e->getMessage()]);
                }
            }
            
            if ($submission) {
                $content = $submission->content;
                if (is_string($content)) {
                    $content = json_decode($content, true);
                }
                
                if (is_array($content) && !empty($content)) {
                    Log::info('Extracted fields from submission content', [
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
            Log::error('Error extracting fields from submission', ['error' => $e->getMessage()]);
        }
        
        // Log failure
        Log::warning('Could not extract form headers', [
            'form_id' => $form->id,
            'form_builder_json_type' => gettype($form->form_builder_json),
        ]);
        
        // Fallback to an empty array if structure is unexpected
        return [];
    }

    /**
     * Direct test export - simpler version for debugging
     *
     * @param  int  $form_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function directExport($form_id)
    {
        Log::info('Direct export started', ['form_id' => $form_id, 'request_url' => request()->fullUrl()]);

        try {
            // Create a simple test file
            $content = "Form ID: $form_id\nExport Date: " . date('Y-m-d H:i:s') . "\n\nThis is a test export file.";
            $directory = storage_path('app/public');
            
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $filename = 'test_export_' . date('Y-m-d_His') . '.txt';
            $filepath = $directory . '/' . $filename;
            
            file_put_contents($filepath, $content);
            
            Log::info('Test export completed', ['file' => $filepath]);
            
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Test export failed', ['form_id' => $form_id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
