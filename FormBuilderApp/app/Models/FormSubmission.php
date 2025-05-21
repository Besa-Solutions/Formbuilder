<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
    
    /**
     * Get the download URL for a specific file field
     *
     * @param string $fieldName
     * @return string|null
     */
    public function getFileDownloadUrl($fieldName)
    {
        if (empty($this->files_meta) || !isset($this->files_meta[$fieldName])) {
            return null;
        }
        
        $fileMeta = $this->files_meta[$fieldName];
        
        if (isset($fileMeta['path'])) {
            return asset('storage/' . $fileMeta['path']);
        }
        
        return null;
    }
    
    /**
     * Check if this submission has attached files
     *
     * @return bool
     */
    public function hasFiles()
    {
        return !empty($this->files_meta);
    }
    
    /**
     * Get all files attached to this submission
     *
     * @return array
     */
    public function getFiles()
    {
        if (empty($this->files_meta)) {
            return [];
        }
        
        $files = [];
        
        foreach ($this->files_meta as $fieldName => $meta) {
            $files[$fieldName] = [
                'name' => $meta['original_name'] ?? 'Unknown File',
                'mime_type' => $meta['mime_type'] ?? 'application/octet-stream',
                'size' => $meta['size'] ?? 0,
                'url' => asset('storage/' . ($meta['path'] ?? ''))
            ];
        }
        
        return $files;
    }
} 