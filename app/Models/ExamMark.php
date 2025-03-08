<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vanguard\User;

class ExamMark extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'detail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'roll_number',
        'subject_id',
        'marks_obtained',
        'grade',
        'grade_point',
        'entered_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marks_obtained' => 'float',
        'grade_point' => 'float',
    ];

    /**
     * Get the form fillup that owns the exam mark.
     */
    public function formFillup()
    {
        return $this->belongsTo(FormFillup::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the subject for this exam mark.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    /**
     * Get the user who entered the mark.
     */
    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * Calculate grade and grade point based on marks obtained.
     *
     * @param float $marksObtained
     * @return array
     */
    public static function calculateGradeAndPoint($marksObtained)
    {
        if ($marksObtained >= 80) {
            return ['grade' => 'A+', 'grade_point' => 5.00];
        } elseif ($marksObtained >= 70) {
            return ['grade' => 'A', 'grade_point' => 4.00];
        } elseif ($marksObtained >= 60) {
            return ['grade' => 'A-', 'grade_point' => 3.50];
        } elseif ($marksObtained >= 50) {
            return ['grade' => 'B', 'grade_point' => 3.00];
        } elseif ($marksObtained >= 40) {
            return ['grade' => 'C', 'grade_point' => 2.00];
        } elseif ($marksObtained >= 33) {
            return ['grade' => 'D', 'grade_point' => 1.00];
        } else {
            return ['grade' => 'F', 'grade_point' => 0.00];
        }
    }
} 