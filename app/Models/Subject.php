<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'subject_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject_name',
        'subject_category',
        'subject_code',
        'full_marks',
        'pass_marks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'full_marks' => 'float',
        'pass_marks' => 'float',
    ];

    /**
     * Get the exam marks for this subject.
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class, 'subject_id', 'subject_id');
    }
} 