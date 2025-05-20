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
        Log::info('Form builder JSON', [
            'form_id' => $form->id,
            'form_builder_json' => $form->form_builder_json
        ]);
        
        // Try handling different JSON structures
        if (is_array($form->form_builder_json)) {
            // Case 1: Direct 'fields' array
            if (isset($form->form_builder_json['fields'])) {
                return collect($form->form_builder_json['fields'])
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
            
            // Case 2: Top-level array of fields
            $fields = collect($form->form_builder_json)
                ->filter(function ($field) {
                    return is_array($field) && isset($field['name']);
                });
                
            if ($fields->count() > 0) {
                return $fields->map(function ($field) {
                    return [
                        'name' => $field['name'],
                        'label' => $field['label'] ?? ucfirst($field['name']),
                        'type' => $field['type'] ?? 'text',
                    ];
                })->toArray();
            }
        }
        
        // Try to extract directly from the FormBuilderArray attribute if available
        if (method_exists($form, 'getFormBuilderArrayAttribute')) {
            $formArray = $form->getFormBuilderArrayAttribute(null);
            
            if (is_array($formArray) && isset($formArray['fields'])) {
                return collect($formArray['fields'])
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

        $form = VendorForm::findOrFail($form_id);
        $submissions = Submission::where('form_id', $form_id)->get();
        
        // Get form headers
        $headers = $this->getFormHeaders($form);
        
        // Create Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add headers
        $column = 'A';
        $sheet->setCellValue($column++, 'Submission ID');
        $sheet->setCellValue($column++, 'Submitted By');
        $sheet->setCellValue($column++, 'Submission Date');
        
        foreach ($headers as $header) {
            $sheet->setCellValue($column++, $header['label'] ?? ucfirst($header['name']));
        }
        
        // Add data
        $row = 2;
        foreach ($submissions as $submission) {
            $column = 'A';
            $sheet->setCellValue($column . $row, $submission->id);
            $column++;
            $sheet->setCellValue($column . $row, $submission->user->name ?? 'Guest');
            $column++;
            $sheet->setCellValue($column . $row, $submission->created_at->format('Y-m-d H:i:s'));
            $column++;
            
            $content = is_array($submission->content) ? $submission->content : json_decode($submission->content, true);
            
            foreach ($headers as $header) {
                $value = $content[$header['name']] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'form_' . $form_id . '_submissions_' . date('Y-m-d_His') . '.xlsx';
        $filepath = storage_path('app/public/' . $filename);
        $writer->save($filepath);
        
        return response()->download($filepath, $filename)->deleteFileAfterSend(true);
    }
} 