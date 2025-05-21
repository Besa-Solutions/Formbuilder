<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;
use App\Http\Controllers\RenderFormController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\VendorSubmissionController;
use App\Http\Controllers\VendorFormController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\TrackFormAnalytics;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FileDownloadController;

Route::get('/', function () {
    return view('welcome');
});

// Remove the old route and add it with more explicit middleware
Route::get('/public-forms', function() {
    // Get all published and active forms
    $forms = \App\Models\Form::where('is_published', true)
        ->where('status', 'published')
        ->where(function($query) {
            $query->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
        })
        ->where(function($query) {
            $query->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        })
        ->withCount('submissions')
        ->get();
        
    return view('public.forms.index', compact('forms'));
})->name('public.forms.index');

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Admin Routes
Route::group(['middleware' => ['web', 'auth', CheckRole::class . ':admin'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    // Form Builder Management Routes
    Route::get('forms', [FormController::class, 'index'])->name('forms.index');
    Route::get('forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::post('forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('forms/{form}', [FormController::class, 'show'])->name('forms.show');
    Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    
    // Form Submissions Management
    Route::get('forms/{form}/submissions', [SubmissionController::class, 'index'])->name('forms.submissions');
    Route::get('forms/{form}/submissions/{submission}', [SubmissionController::class, 'show'])->name('forms.submissions.show');
    Route::delete('forms/{form}/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('forms.submissions.destroy');
    
    // Analytics Routes
    Route::get('forms/{form}/analytics', [FormController::class, 'analytics'])->name('forms.analytics');
    
    // User Management Routes
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}/role', [UserController::class, 'changeRole'])->name('users.change-role');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Export submissions to Excel (admin)
    Route::get('forms/{form_id}/submissions/export', [\App\Http\Controllers\VendorSubmissionController::class, 'exportToExcel'])
        ->name('forms.submissions.export')
        ->middleware(['auth', 'verified']);
});

// Logged in Guest Routes
Route::group(['middleware' => ['web', 'auth', CheckRole::class . ':guest'], 'prefix' => 'guest', 'as' => 'guest.'], function () {
    // View available forms
    Route::get('forms', [FormController::class, 'publicIndex'])->name('forms.index');
    
    // View form submissions history (only their own)
    Route::get('my-submissions', [SubmissionController::class, 'userSubmissions'])->name('my-submissions');
    Route::get('my-submissions/{submission}', [SubmissionController::class, 'showUserSubmission'])->name('my-submissions.show');
});

// Public Form Routes (accessible by anyone including anonymous users)
Route::group(['middleware' => ['web', CheckRole::class . ':anonymous'], 'prefix' => 'forms', 'as' => 'public.'], function () {
    // View and submit forms
    Route::get('{identifier}', [RenderFormController::class, 'show'])
        ->middleware(TrackFormAnalytics::class)
        ->name('form.render');
    Route::post('{identifier}', [RenderFormController::class, 'submit'])->name('form.submit');
});

// Override vendor form and submission routes with our fixed controllers
Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'form-builder', 'as' => 'formbuilder::'], function () {
    // Forms routes
    Route::get('forms', [VendorFormController::class, 'index'])->name('forms.index');
    Route::get('forms/{id}', [VendorFormController::class, 'show'])->name('forms.show');
    Route::get('forms/create', [VendorFormController::class, 'create'])->name('forms.create'); 
    Route::post('forms', [VendorFormController::class, 'store'])->name('forms.store');
    Route::get('forms/{id}/edit', [VendorFormController::class, 'edit'])->name('forms.edit');
    Route::put('forms/{id}', [VendorFormController::class, 'update'])->name('forms.update');
    Route::delete('forms/{id}', [VendorFormController::class, 'destroy'])->name('forms.destroy');
    
    // Form render for users
    Route::get('form/{identifier}', [RenderFormController::class, 'show'])->name('form.render');
    
    // Submissions routes
    Route::get('forms/{fid}/submissions', [VendorSubmissionController::class, 'index'])
        ->name('forms.submissions.index');
    
    Route::get('forms/{fid}/submissions/{submission}', [VendorSubmissionController::class, 'show'])
        ->name('forms.submissions.show');
        
    Route::delete('forms/{fid}/submissions/{submission}', [VendorSubmissionController::class, 'destroy'])
        ->name('forms.submissions.destroy');
        
    // My Submissions route
    Route::get('my-submissions', [VendorSubmissionController::class, 'mySubmissions'])->name('my-submissions.index');

    // Form submissions routes
    Route::get('forms/{form_id}/submissions/export', [VendorSubmissionController::class, 'exportToExcel'])
        ->name('formbuilder::forms.submissions.export')
        ->middleware(['auth', 'verified']);
});

// Test upload route
Route::get('test-upload/{identifier}', [RenderFormController::class, 'testUpload'])->name('test.upload');
Route::post('test-upload/{identifier}', [RenderFormController::class, 'processTestUpload'])->name('test.upload.process');

// File download route
Route::get('download-file/{submission}/{field?}', [FileDownloadController::class, 'download'])->name('download.file');
