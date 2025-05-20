<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::withCount('submissions')->get();
        
        // Add title property for compatibility with the view
        foreach ($forms as $form) {
            $form->title = $form->name;
        }
        
        return view('admin.forms.index', compact('forms'));
    }

    /**
     * Display a listing of published forms for guest users.
     */
    public function publicIndex()
    {
        $forms = Form::where('is_published', true)
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
            
        // Check if this was requested from the guest route or the public route
        $routeName = request()->route()->getName();
        if ($routeName === 'guest.forms.index') {
            return view('guest.forms.index', compact('forms'));
        } else {
            // Public view for anonymous users
            return view('public.forms.index', compact('forms'));
        }
    }

    public function create()
    {
        return view('admin.forms.create');
    }

    public function store(Request $request)
    {
        Log::info('Form creation request received', $request->all());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_builder_json' => 'required|string',
            'is_published' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'identifier' => 'required|string|unique:forms,identifier',
        ]);

        try {
            $form = Form::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'form_builder_json' => $validated['form_builder_json'],
                'identifier' => $validated['identifier'],
                'is_published' => $validated['is_published'] ?? false,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['is_published'] ?? false ? 'published' : 'draft',
            ]);
            
            Log::info('Form created successfully', ['form_id' => $form->id]);

            return redirect()
                ->route('admin.forms.show', $form)
                ->with('success', 'Form created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating form', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Error creating form: ' . $e->getMessage());
        }
    }

    public function show(Form $form)
    {
        $form->loadCount('submissions');
        return view('admin.forms.show', compact('form'));
    }

    public function edit(Form $form)
    {
        return view('admin.forms.edit', compact('form'));
    }

    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_builder_json' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Explicitly handle the is_published field as a boolean
        // The checkbox will only be present in the request if it's checked
        $isPublished = $request->has('is_published');

        // Log for debugging
        Log::info('Form update request', [
            'form_id' => $form->id,
            'is_published_in_request' => $request->has('is_published'),
            'is_published_value' => $isPublished,
            'request_data' => $request->all()
        ]);

        $form->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $form->description,
            'form_builder_json' => $validated['form_builder_json'],
            'is_published' => $isPublished,
            'start_date' => $validated['start_date'] ?? $form->start_date,
            'end_date' => $validated['end_date'] ?? $form->end_date,
            'status' => $isPublished ? 'published' : 'draft',
        ]);

        return redirect()
            ->route('admin.forms.show', $form)
            ->with('success', 'Form updated successfully.');
    }

    public function destroy(Form $form)
    {
        $form->delete();
        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form deleted successfully.');
    }
    
    /**
     * Display analytics for a specific form.
     */
    public function analytics(Form $form)
    {
        // Get analytics for the last 30 days
        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $analytics = $form->analytics()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date')
            ->get();
            
        return view('admin.forms.analytics', compact('form', 'analytics'));
    }
} 