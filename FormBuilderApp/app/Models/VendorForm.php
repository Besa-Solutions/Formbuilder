<?php

namespace App\Models;

use doode\FormBuilder\Models\Form as BaseForm;
use doode\FormBuilder\Models\Submission;
use Illuminate\Support\Collection;

class VendorForm extends BaseForm
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'forms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'visibility',
        'form_builder_json',
        'allows_edit',
        'identifier',
        'status',
        'is_published',
        'start_date',
        'end_date',
        'description',
    ];

    /**
     * Get the submissions for this form.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class, 'form_id');
    }

    /**
     * Get a json decoded version of the form_builder_json string
     *
     * @param mixed $value Not used but required for compatibility
     * @return array
     */
    public function getFormBuilderArrayAttribute($value) : array
    {
        // Check if the attribute exists
        if (!isset($this->attributes['form_builder_json'])) {
            return [];
        }
        
        // Check if form_builder_json is already an array
        if (is_array($this->attributes['form_builder_json'])) {
            return $this->attributes['form_builder_json'];
        }
        
        // If it's empty, return empty array
        if (empty($this->attributes['form_builder_json'])) {
            return [];
        }
        
        // Otherwise, decode the JSON string
        $decoded = json_decode($this->attributes['form_builder_json'], true);
        
        // Ensure we return an array
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if the form allows edit - with a fix for null values
     *
     * @return boolean
     */
    public function allowsEdit() : bool
    {
        // Return false if allows_edit is null or false, true otherwise
        return $this->allows_edit ?? false;
    }
    
    /**
     * Get an array containing the name of the fields in the form and their label
     *
     * @return Illuminate\Support\Collection
     */
    public function getEntriesHeader() : Collection
    {
        $formArray = $this->getFormBuilderArrayAttribute(null);
        
        return collect($formArray)
                    ->filter(function ($entry) {
                        return !empty($entry['name']);
                    })
                    ->map(function ($entry) {
                        return [
                            'name' => $entry['name'],
                            'label' => $entry['label'] ?? null,
                            'type' => $entry['type'] ?? null,
                        ];
                    });
    }
} 