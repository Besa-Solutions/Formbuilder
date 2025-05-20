<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'version_number',
        'name',
        'description',
        'form_builder_json',
        'settings',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_builder_json' => 'json',
        'settings' => 'json',
    ];

    /**
     * Get the form that owns this version.
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get submissions for this specific form version
     */
    public function submissions()
    {
        return $this->form->submissions()->where('form_version', $this->version_number);
    }
}
