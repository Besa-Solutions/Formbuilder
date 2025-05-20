<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'form_submissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'user_id',
        'form_version',
        'content',
        'submission_ip',
        'files_meta',
        'started_at',
        'completed_at',
        'is_complete',
        'is_anonymous',
        'user_agent',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'json',
        'files_meta' => 'json',
        'is_complete' => 'boolean',
        'is_anonymous' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the form that owns this submission.
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }
    
    /**
     * Get the user that owns this submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 