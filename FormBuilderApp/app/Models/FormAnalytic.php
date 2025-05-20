<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnalytic extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'form_analytics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'form_version',
        'date',
        'views',
        'starts',
        'completions',
        'abandonments',
        'abandonment_points',
        'average_completion_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'abandonment_points' => 'json',
    ];

    /**
     * Get the form that owns this analytic.
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the completion rate for this day
     */
    public function getCompletionRate()
    {
        return $this->starts > 0 ? round(($this->completions / $this->starts) * 100, 2) : 0;
    }
}
