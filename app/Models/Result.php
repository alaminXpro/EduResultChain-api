<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vanguard\User;

class Result extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'result_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'result_id',
        'roll_number',
        'exam_name',
        'session',
        'gpa',
        'grade',
        'total_marks',
        'status',
        'ipfs_hash',
        'published',
        'published_at',
        'published_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gpa' => 'float',
        'total_marks' => 'float',
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($result) {
            // Generate result_id if not set
            if (empty($result->result_id)) {
                $result->result_id = $result->exam_name . '_' . $result->session . '_' . $result->roll_number;
            }
        });
    }

    /**
     * Get the form fillup that owns the result.
     */
    public function formFillup()
    {
        return $this->belongsTo(FormFillup::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the user who published the result.
     */
    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get the exam marks for this result.
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the revalidation requests for this result.
     */
    public function revalidationRequests()
    {
        return $this->hasMany(ResultRevalidationRequest::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the result histories for this result.
     */
    public function resultHistories()
    {
        return $this->hasMany(ResultHistory::class, 'result_id', 'result_id');
    }

    /**
     * Calculate grade based on GPA.
     *
     * @param float $gpa
     * @return string
     */
    public static function calculateGrade($gpa)
    {
        if ($gpa == 5.00) {
            return 'A+';
        } elseif ($gpa >= 4.00) {
            return 'A';
        } elseif ($gpa >= 3.50) {
            return 'A-';
        } elseif ($gpa >= 3.00) {
            return 'B';
        } elseif ($gpa >= 2.00) {
            return 'C';
        } elseif ($gpa >= 1.00) {
            return 'D';
        } else {
            return 'F';
        }
    }
} 