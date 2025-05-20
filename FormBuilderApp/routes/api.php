<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\FormAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Form Builder API Routes
Route::prefix('v1')->group(function () {
    // Form Routes
    Route::get('forms', [FormController::class, 'index']);
    Route::post('forms', [FormController::class, 'store']);
    Route::get('forms/{identifier}', [FormController::class, 'show']);
    Route::put('forms/{identifier}', [FormController::class, 'update']);
    Route::delete('forms/{identifier}', [FormController::class, 'destroy']);
    
    // Form Version Routes
    Route::get('forms/{identifier}/versions', [FormController::class, 'getVersions']);
    
    // Form Status Routes
    Route::post('forms/{identifier}/publish', [FormController::class, 'publish']);
    Route::post('forms/{identifier}/unpublish', [FormController::class, 'unpublish']);
    Route::post('forms/{identifier}/archive', [FormController::class, 'archive']);
    Route::post('forms/{identifier}/duplicate', [FormController::class, 'duplicate']);
    
    // Submission Routes
    Route::get('forms/{identifier}/submissions', [SubmissionController::class, 'index']);
    Route::post('forms/{identifier}/submissions', [SubmissionController::class, 'store']);
    Route::get('forms/{identifier}/submissions/{submissionId}', [SubmissionController::class, 'show']);
    Route::patch('forms/{identifier}/submissions/{submissionId}/status', [SubmissionController::class, 'updateStatus']);
    Route::delete('forms/{identifier}/submissions/{submissionId}', [SubmissionController::class, 'destroy']);
    Route::get('forms/{identifier}/submissions/export', [SubmissionController::class, 'export']);
    
    // Analytics Routes
    Route::get('forms/{identifier}/analytics', [FormAnalyticsController::class, 'getFormAnalytics']);
    Route::get('analytics/forms', [FormAnalyticsController::class, 'getAllFormsAnalytics']);
    Route::post('forms/{identifier}/analytics/view', [FormAnalyticsController::class, 'recordView']);
    Route::post('forms/{identifier}/analytics/start', [FormAnalyticsController::class, 'recordStart']);
}); 