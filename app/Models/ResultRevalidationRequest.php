<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vanguard\User;

class ResultRevalidationRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'roll_number',
        'subject_id',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'comments',
        'original_marks',
        'updated_marks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'original_marks' => 'float',
        'updated_marks' => 'float',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the form fillup that owns the revalidation request.
     */
    public function formFillup()
    {
        return $this->belongsTo(FormFillup::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the subject for this revalidation request.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    /**
     * Get the user who requested the revalidation.
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who reviewed the revalidation.
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the exam mark for this revalidation request.
     */
    public function examMark()
    {
        return $this->belongsTo(ExamMark::class, ['roll_number', 'subject_id'], ['roll_number', 'subject_id']);
    }
} 