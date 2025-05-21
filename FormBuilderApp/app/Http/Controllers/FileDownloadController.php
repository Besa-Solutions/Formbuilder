<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileDownloadController extends Controller
{
    /**
     * Download a file from a form submission
     */
    public function download($submissionId, $fieldName = 'filedownloader')
    {
        try {
            // Find the submission
            $submission = FormSubmission::findOrFail($submissionId);
            
            // Log the attempt
            Log::info('File download attempt', [
                'submission_id' => $submissionId,
                'field_name' => $fieldName,
                'has_files_meta' => !empty($submission->files_meta)
            ]);
            
            // Check if we have files_meta data
            if (!empty($submission->files_meta)) {
                $filesMeta = is_array($submission->files_meta) 
                    ? $submission->files_meta 
                    : json_decode($submission->files_meta, true);
                
                // Check if we have the requested field
                if (is_array($filesMeta) && isset($filesMeta[$fieldName])) {
                    $fileMeta = $filesMeta[$fieldName];
                    $path = $fileMeta['path'] ?? null;
                    $originalName = $fileMeta['original_name'] ?? 'document.docx';
                    
                    if ($path && Storage::disk('public')->exists($path)) {
                        Log::info('File download success (from files_meta)', [
                            'submission_id' => $submissionId,
                            'path' => $path,
                            'original_name' => $originalName
                        ]);
                        
                        // Return the file for download
                        $filePath = Storage::disk('public')->path($path);
                        $fileContent = file_get_contents($filePath);
                        
                        // Try to determine the mime type, defaulting to a safe value
                        $mimeType = 'application/octet-stream';
                        try {
                            if (function_exists('mime_content_type')) {
                                $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                            } elseif (class_exists('finfo')) {
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->file($filePath) ?: 'application/octet-stream';
                            }
                        } catch (\Exception $e) {
                            Log::warning('Could not determine MIME type: ' . $e->getMessage());
                        }
                        
                        return response($fileContent)
                            ->header('Content-Type', $mimeType)
                            ->header('Content-Disposition', 'attachment; filename="' . $originalName . '"');
                    }
                }
            }
            
            // Fallback to content field if files_meta doesn't have what we need
            $content = is_array($submission->content) 
                ? $submission->content 
                : json_decode($submission->content, true);
            
            if (is_array($content) && isset($content[$fieldName])) {
                $path = $content[$fieldName];
                
                if (Storage::disk('public')->exists($path)) {
                    Log::info('File download success (from content)', [
                        'submission_id' => $submissionId,
                        'path' => $path
                    ]);
                    
                    // Create a reasonable filename based on form and submission
                    $filename = "form{$submission->form_id}_submission{$submission->id}.docx";
                    
                    // Return the file for download
                    $filePath = Storage::disk('public')->path($path);
                    $fileContent = file_get_contents($filePath);
                    
                    // Try to determine the mime type, defaulting to a safe value
                    $mimeType = 'application/octet-stream';
                    try {
                        if (function_exists('mime_content_type')) {
                            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                        } elseif (class_exists('finfo')) {
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mimeType = $finfo->file($filePath) ?: 'application/octet-stream';
                        }
                    } catch (\Exception $e) {
                        Log::warning('Could not determine MIME type: ' . $e->getMessage());
                    }
                    
                    return response($fileContent)
                        ->header('Content-Type', $mimeType)
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                }
            }
            
            // If we get here, we couldn't find the file
            Log::error('File download failed - file not found', [
                'submission_id' => $submissionId,
                'field_name' => $fieldName
            ]);
            
            return back()->with('error', 'File not found');
        } catch (\Exception $e) {
            Log::error('File download error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error downloading file: ' . $e->getMessage());
        }
    }
}
