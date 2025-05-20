<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'identifier',
        'form_builder_json',
        'custom_submit_url',
        'version',
        'is_published',
        'start_date',
        'end_date',
        'status',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_builder_json' => 'json',
        'settings' => 'json',
        'is_published' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get a json decoded version of the form_builder_json string
     *
     * @return array
     */
    public function getFormBuilderArrayAttribute() : array
    {
        // Check if form_builder_json is already an array
        if (is_array($this->form_builder_json)) {
            return $this->form_builder_json;
        }
        
        // If it's empty, return empty array
        if (empty($this->form_builder_json)) {
            return [];
        }
        
        // Otherwise, decode the JSON string
        $decoded = json_decode($this->form_builder_json, true);
        
        // Ensure we return an array
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get all submissions for this form.
     */
    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Get all versions of this form.
     */
    public function versions()
    {
        return $this->hasMany(FormVersion::class);
    }

    /**
     * Get analytics for this form.
     */
    public function analytics()
    {
        return $this->hasMany(FormAnalytic::class);
    }

    /**
     * Create a clone of the form
     */
    public function duplicate()
    {
        $newForm = $this->replicate();
        $newForm->name = $this->name . ' (Copy)';
        $newForm->identifier = uniqid();
        $newForm->save();
        
        return $newForm;
    }

    /**
     * Check if the form is active
     */
    public function isActive()
    {
        if (!$this->is_published || $this->status !== 'published') {
            return false;
        }

        $now = now();
        
        // Check start date
        if ($this->start_date && $this->start_date > $now) {
            return false;
        }
        
        // Check end date
        if ($this->end_date && $this->end_date < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Create a new version of this form
     */
    public function createNewVersion()
    {
        // Increment version number
        $this->version += 1;
        $this->save();
        
        // Create new version record
        return $this->versions()->create([
            'version_number' => $this->version,
            'name' => $this->name,
            'description' => $this->description,
            'form_builder_json' => $this->form_builder_json,
            'settings' => $this->settings,
            'created_by' => 'System',
        ]);
    }
} 