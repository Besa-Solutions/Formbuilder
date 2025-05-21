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
                mkdir($directory, 0755, true);
            }
            
            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'form_' . $form_id . '_submissions_' . date('Y-m-d_His') . '.xlsx';
            $filepath = $directory . '/' . $filename;
            
            Log::info('Saving Excel file', ['path' => $filepath]);
            $writer->save($filepath);
            
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
     * Extract headers from the form
     */
    private function getFormHeaders($form)
    {
        Log::info('Extracting form headers', ['form_id' => $form->id]);
        
        // Try various approaches to get the form structure
        $headers = [];
        
        // First, check if the form_builder_json field exists and is not null
        if (isset($form->form_builder_json)) {
            $json = $form->form_builder_json;
            
            // Convert string to array if needed
            if (is_string($json)) {
                $json = json_decode($json, true);
                Log::info('Decoded form JSON from string', ['json_type' => gettype($json)]);
            }
            
            // Structure 1: Form JSON with 'fields' property
            if (is_array($json) && isset($json['fields']) && is_array($json['fields'])) {
                Log::info('Found fields property in form JSON', ['fields_count' => count($json['fields'])]);
                
                foreach ($json['fields'] as $field) {
                    if (isset($field['name'])) {
                        $headers[] = [
                            'name' => $field['name'],
                            'label' => $field['label'] ?? ucfirst($field['name']),
                            'type' => $field['type'] ?? 'text'
                        ];
                    }
                }
            }
            // Structure 2: Form JSON is directly an array of fields
            elseif (is_array($json)) {
                foreach ($json as $field) {
                    if (is_array($field) && isset($field['name'])) {
                        $headers[] = [
                            'name' => $field['name'],
                            'label' => $field['label'] ?? ucfirst($field['name']),
                            'type' => $field['type'] ?? 'text'
                        ];
                    }
                }
            }
        }
        
        // If we still don't have headers, try to get them from a sample submission
        if (empty($headers)) {
            Log::info('No headers found in form_builder_json, checking submissions');
            
            // Try to get headers from the first submission's content
            $submission = null;
            
            try {
                // Try with App\Models\Submission
                $submission = \App\Models\Submission::where('form_id', $form->id)->first();
            } catch (\Exception $e) {
                try {
                    // Try with vendor Submission
                    $submission = VendorSubmission::where('form_id', $form->id)->first();
                } catch (\Exception $e2) {
                    Log::warning('No submissions found for form', ['form_id' => $form->id]);
                }
            }
            
            if ($submission) {
                $content = $submission->content;
                
                // Convert string content to array if needed
                if (is_string($content)) {
                    $content = json_decode($content, true);
                }
                
                if (is_array($content)) {
                    Log::info('Getting headers from submission content', ['keys_count' => count(array_keys($content))]);
                    
                    foreach ($content as $key => $value) {
                        $headers[] = [
                            'name' => $key,
                            'label' => ucfirst(str_replace('_', ' ', $key)),
                            'type' => 'text'
                        ];
                    }
                }
            }
        }
        
        Log::info('Extracted headers', ['count' => count($headers)]);
        return $headers;
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
