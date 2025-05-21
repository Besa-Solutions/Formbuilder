<?php

// This is a simple test script to test PhpSpreadsheet Excel export

// Load the Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Get form ID from the query string
$formId = $_GET['form_id'] ?? 'test';

try {
    // Create a simple test spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add some test data
    $sheet->setCellValue('A1', 'Test ID');
    $sheet->setCellValue('B1', 'Test Name');
    $sheet->setCellValue('C1', 'Test Date');
    
    $sheet->setCellValue('A2', $formId);
    $sheet->setCellValue('B2', 'Test Form');
    $sheet->setCellValue('C2', date('Y-m-d H:i:s'));
    
    // Auto-size columns
    foreach (range('A', 'C') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Create directory if it doesn't exist
    $directory = __DIR__ . '/../storage/app/public';
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Create Excel file
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'test_excel_' . date('Y-m-d_His') . '.xlsx';
    $filepath = $directory . '/' . $filename;
    
    $writer->save($filepath);
    
    // Provide download or success message
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    readfile($filepath);
    unlink($filepath); // Delete the file after sending
    exit;
    
} catch (\Exception $e) {
    echo '<h2>Error Creating Excel File</h2>';
    echo '<p>Error: ' . $e->getMessage() . '</p>';
    echo '<p>Trace: <pre>' . $e->getTraceAsString() . '</pre></p>';
} 