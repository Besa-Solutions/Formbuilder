<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'form_version',
        'content',
        'submission_ip',
        'files_meta',
        'started_at',
        'completed_at',
        'is_complete',
        'is_anonymous',
        'user_agent',
        'status',
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
        'completed_at' => 'datetime',
    ];

    /**
     * Get the form that owns this submission.
     */
    public function form()
    {
        return $this->belongsTo(VendorForm::class, 'form_id');
    }

    /**
     * Get completion time in seconds
     */
    public function getCompletionTime()
    {
        if (!$this->is_complete || !$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Format completion time as human readable
     */
    public function getFormattedCompletionTime()
    {
        $seconds = $this->getCompletionTime();
        if ($seconds === null) {
            return 'Incomplete';
        }
        
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return $minutes . ' minutes ' . $remainingSeconds . ' seconds';
    }
    
    /**
     * Render the value of a form entry.
     *
     * @param string $fieldName
     * @param string $fieldType
     * @param bool $trim Whether to trim the value for display in a table
     * @return string
     */
    public function renderEntryContent($fieldName, $fieldType = 'text', $trim = false)
    {
        // Get the content of the submission as an array
        $content = is_array($this->content) ? $this->content : json_decode($this->content, true);
        
        // Default to empty array if not an array
        if (!is_array($content)) {
            $content = [];
        }
        
        // Get the value from the content based on the field name
        $value = $content[$fieldName] ?? '';
        
        // If empty, just return empty string
        if (empty($value)) {
            return '';
        }
        
        // Format the value based on the field type
        switch ($fieldType) {
            case 'checkbox-group':
            case 'radio-group':
            case 'select':
                if (is_array($value)) {
                    $displayValue = implode(', ', $value);
                } else {
                    $displayValue = $value;
                }
                break;
                
            case 'file':
                if (is_array($value)) {
                    $displayValue = count($value) . ' files';
                } else {
                    $displayValue = '1 file';
                }
                break;
                
            case 'date':
                $displayValue = date('M j, Y', strtotime($value));
                break;
                
            case 'textarea':
                $displayValue = nl2br($value);
                if ($trim && strlen($displayValue) > 100) {
                    $displayValue = substr($displayValue, 0, 100) . '...';
                }
                break;
                
            default:
                $displayValue = $value;
                if ($trim && is_string($displayValue) && strlen($displayValue) > 100) {
                    $displayValue = substr($displayValue, 0, 100) . '...';
                }
        }
        
        return $displayValue;
    }
} 