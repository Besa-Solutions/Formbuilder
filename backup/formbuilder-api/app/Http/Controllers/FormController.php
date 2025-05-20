<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    /**
     * Toon alle formulieren van de ingelogde gebruiker.
     */
    public function index()
    {
        return response()->json(Form::where('user_id', Auth::id())->get(), 200);
    }

    /**
     * Maak een nieuw formulier aan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $form = Form::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        return response()->json($form, 201);
    }

    /**
     * Toon een specifiek formulier.
     */
    public function show($id)
    {
        $form = Form::where('user_id', Auth::id())->find($id);

        if (!$form) {
            return response()->json(['message' => 'Form not found'], 404);
        }

        return response()->json($form, 200);
    }

    /**
     * Update een bestaand formulier.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $form = Form::where('user_id', Auth::id())->find($id);

        if (!$form) {
            return response()->json(['message' => 'Form not found'], 404);
        }

        $form->update($validated);

        return response()->json($form, 200);
    }

    /**
     * Verwijder een formulier.
     */
    public function destroy($id)
    {
        $form = Form::where('user_id', Auth::id())->find($id);

        if (!$form) {
            return response()->json(['message' => 'Form not found'], 404);
        }

        $form->delete();

        return response()->json(['message' => 'Form deleted successfully'], 200);
    }
}
