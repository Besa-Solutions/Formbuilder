<?php

// This is a simple debug script to test the export functionality

// Load the Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Get form ID from the query string
$formId = $_GET['form_id'] ?? null;

if (!$formId) {
    die('Form ID is required. Add ?form_id=YOUR_FORM_ID to the URL.');
}

// Log the request
file_put_contents(
    __DIR__.'/../storage/logs/export-debug.log', 
    date('Y-m-d H:i:s') . " - Export debug requested for form ID: $formId\n", 
    FILE_APPEND
);

// Output to browser
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Export Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #f5f5f5; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        p { line-height: 1.6; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Export Debug Tool</h1>
        <div class="info">
            <p>Testing export for form ID: <strong>{$formId}</strong></p>
            <p>This page helps diagnose issues with the form export functionality.</p>
            <p>A log entry has been created in <code>storage/logs/export-debug.log</code>.</p>
        </div>
        
        <p>Click the button below to try the direct export URL:</p>
        <a href="/admin/forms/{$formId}/submissions/export" class="button" target="_blank">Test Export URL</a>
        
        <hr>
        <p>Alternative method:</p>
        <a href="/index.php/admin/forms/{$formId}/submissions/export" class="button" target="_blank">Test With index.php</a>
        
        <hr>
        <p>Simple direct export test:</p>
        <a href="/admin/forms/{$formId}/direct-export" class="button" target="_blank" style="background: #ff9800;">Test Direct Export</a>
        
        <hr>
        <p>Simple Excel Test (requires PhpSpreadsheet):</p>
        <a href="test-excel.php?form_id={$formId}" class="button" target="_blank" style="background: #e91e63;">Test Basic Excel</a>
    </div>
</body>
</html>
HTML; 