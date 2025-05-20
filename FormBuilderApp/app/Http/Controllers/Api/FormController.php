<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormController extends Controller
{
    /**
     * Display a listing of the forms.
     */
    public function index(Request $request)
    {
        $query = Form::query();
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by active/inactive
        if ($request->has('active')) {
            $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
            $now = now();
            
            if ($isActive) {
                $query->where('status', 'published')
                    ->where('is_published', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('start_date')
                            ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            } else {
                $query->where(function($q) use ($now) {
                    $q->where('status', '!=', 'published')
                        ->orWhere('is_published', false)
                        ->orWhere(function($innerQ) use ($now) {
                            $innerQ->whereNotNull('start_date')
                                ->where('start_date', '>', $now);
                        })
                        ->orWhere(function($innerQ) use ($now) {
                            $innerQ->whereNotNull('end_date')
                                ->where('end_date', '<', $now);
                        });
                });
            }
        }
        
        $perPage = $request->per_page ?? 15;
        $forms = $query->paginate($perPage);
        
        return response()->json([
            'data' => $forms->items(),
            'meta' => [
                'current_page' => $forms->currentPage(),
                'last_page' => $forms->lastPage(),
                'per_page' => $forms->perPage(),
                'total' => $forms->total(),
            ],
        ]);
    }

    /**
     * Store a newly created form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_builder_json' => 'required|json',
            'is_published' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|string|in:draft,published,archived',
            'settings' => 'nullable|json',
        ]);
        
        // Create unique identifier
        $validated['identifier'] = (string) Str::uuid();
        $validated['version'] = 1;
        
        // Set default status
        $validated['status'] = $validated['status'] ?? 'draft';
        
        // Create form
        $form = Form::create($validated);
        
        // Create initial version
        $form->versions()->create([
            'version_number' => 1,
            'name' => $form->name,
            'description' => $form->description,
            'form_builder_json' => $form->form_builder_json,
            'settings' => $form->settings,
            'created_by' => auth()->check() ? auth()->user()->name : null,
        ]);
        
        return response()->json([
            'message' => 'Form created successfully',
            'data' => $form,
        ], 201);
    }

    /**
     * Display the specified form.
     */
    public function show(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $form->load('versions');
        
        return response()->json([
            'data' => $form,
        ]);
    }

    /**
     * Update the specified form and create a new version.
     */
    public function update(Request $request, string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'form_builder_json' => 'sometimes|json',
            'is_published' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'string|in:draft,published,archived',
            'settings' => 'nullable|json',
        ]);
        
        // Create a new version if form_builder_json is changing
        $createVersion = isset($validated['form_builder_json']) && $validated['form_builder_json'] != $form->form_builder_json;
        
        // Update the form
        $form->update($validated);
        
        // Create a new version if needed
        if ($createVersion) {
            $form->createNewVersion();
        }
        
        return response()->json([
            'message' => 'Form updated successfully',
            'data' => $form->fresh(['versions']),
        ]);
    }

    /**
     * Remove the specified form (soft delete).
     */
    public function destroy(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $form->delete();
        
        return response()->json([
            'message' => 'Form deleted successfully',
        ]);
    }
    
    /**
     * Publish a form
     */
    public function publish(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $form->update([
            'is_published' => true,
            'status' => 'published',
        ]);
        
        return response()->json([
            'message' => 'Form published successfully',
            'data' => $form,
        ]);
    }
    
    /**
     * Unpublish a form
     */
    public function unpublish(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $form->update([
            'is_published' => false,
            'status' => 'draft',
        ]);
        
        return response()->json([
            'message' => 'Form unpublished successfully',
            'data' => $form,
        ]);
    }
    
    /**
     * Archive a form
     */
    public function archive(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $form->update([
            'is_published' => false,
            'status' => 'archived',
        ]);
        
        return response()->json([
            'message' => 'Form archived successfully',
            'data' => $form,
        ]);
    }
    
    /**
     * Get form versions
     */
    public function getVersions(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $versions = $form->versions()->orderBy('version_number', 'desc')->get();
        
        return response()->json([
            'data' => $versions,
        ]);
    }
    
    /**
     * Duplicate a form
     */
    public function duplicate(string $identifier)
    {
        $form = Form::where('identifier', $identifier)->first();
        
        if (!$form) {
            return response()->json([
                'message' => 'Form not found',
            ], 404);
        }
        
        $newForm = $form->replicate();
        $newForm->name = $form->name . ' (Copy)';
        $newForm->identifier = (string) Str::uuid();
        $newForm->is_published = false;
        $newForm->status = 'draft';
        $newForm->version = 1;
        $newForm->save();
        
        // Create initial version for the duplicated form
        $newForm->versions()->create([
            'version_number' => 1,
            'name' => $newForm->name,
            'description' => $newForm->description,
            'form_builder_json' => $newForm->form_builder_json,
            'settings' => $newForm->settings,
            'created_by' => auth()->check() ? auth()->user()->name : null,
        ]);
        
        return response()->json([
            'message' => 'Form duplicated successfully',
            'data' => $newForm,
        ], 201);
    }
}
