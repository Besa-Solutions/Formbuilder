<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VendorForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VendorFormController extends Controller
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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle = "Forms";

        // Get all forms without the user_id requirement
        $forms = VendorForm::withCount('submissions')
                ->latest()
                ->paginate(100);

        return view('vendor.formbuilder.forms.index', compact('pageTitle', 'forms'));
    }

    /**
     * Show the form for creating a new form
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = "Create New Form";
        
        return view('vendor.formbuilder.forms.create', compact('pageTitle'));
    }

    /**
     * Store a newly created form in storage
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|max:255',
            'visibility' => 'required|in:PUBLIC,PRIVATE',
            'allows_edit' => 'boolean',
            'form_builder_json' => 'required',
        ]);
        
        // If identifier is not provided, generate a unique identifier
        $identifier = $request->has('identifier') ? $request->identifier : Str::slug($request->name) . '_' . Str::random(5);
        
        // Create the form
        $form = VendorForm::create([
            'name' => $request->name,
            'visibility' => $request->visibility,
            'allows_edit' => $request->has('allows_edit'),
            'identifier' => $identifier,
            'form_builder_json' => $request->form_builder_json,
        ]);
        
        return redirect()
                ->route('formbuilder::forms.index')
                ->with('success', 'Form successfully created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Get the form without the user_id requirement
        $form = VendorForm::where('id', $id)
                    ->withCount('submissions')
                    ->firstOrFail();

        $pageTitle = "Preview Form";

        return view('vendor.formbuilder.forms.show', compact('pageTitle', 'form'));
    }

    /**
     * Show the form for editing the specified resource
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $form = VendorForm::findOrFail($id);
        
        $pageTitle = "Edit Form";
        
        return view('vendor.formbuilder.forms.edit', compact('form', 'pageTitle'));
    }

    /**
     * Update the specified resource in storage
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $form = VendorForm::findOrFail($id);
        
        $request->validate([
            'name' => 'required|max:255',
            'visibility' => 'required|in:PUBLIC,PRIVATE',
            'allows_edit' => 'boolean',
            'form_builder_json' => 'required',
        ]);
        
        $form->update([
            'name' => $request->name,
            'visibility' => $request->visibility,
            'allows_edit' => $request->has('allows_edit'),
            'form_builder_json' => $request->form_builder_json,
        ]);
        
        return redirect()
                ->route('formbuilder::forms.index')
                ->with('success', 'Form successfully updated.');
    }

    /**
     * Remove the specified resource from storage
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $form = VendorForm::findOrFail($id);
        $form->delete();
        
        return redirect()
                ->route('formbuilder::forms.index')
                ->with('success', 'Form successfully deleted.');
    }
} 