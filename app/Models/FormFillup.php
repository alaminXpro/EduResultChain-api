<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vanguard\User;

class FormFillup extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'roll_number';

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
        'registration_number',
        'exam_name',
        'session',
        'group',
        'board_id',
        'institution_id',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($formFillup) {
            // Generate roll number if not set
            if (empty($formFillup->roll_number)) {
                $formFillup->roll_number = static::generateRollNumber(
                    $formFillup->exam_name,
                    $formFillup->session,
                    $formFillup->institution_id
                );
            }
        });
    }

    /**
     * Generate a unique roll number.
     *
     * @param string $examName
     * @param string $session
     * @param int $institutionId
     * @return string
     */
    public static function generateRollNumber($examName, $session, $institutionId)
    {
        // Format: Exam code (2 digits) + Session (2 digits) + Institution ID (3 digits) + Sequential number (4 digits)
        $examCode = static::getExamCode($examName);
        $sessionCode = substr($session, -2);
        $institutionCode = str_pad($institutionId % 1000, 3, '0', STR_PAD_LEFT);
        
        $prefix = $examCode . $sessionCode . $institutionCode;
        
        $lastFormFillup = static::where('roll_number', 'like', $prefix . '%')
            ->orderBy('roll_number', 'desc')
            ->first();
            
        if ($lastFormFillup) {
            $lastNumber = (int) substr($lastFormFillup->roll_number, 7);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get a 2-digit code for the exam name.
     *
     * @param string $examName
     * @return string
     */
    private static function getExamCode($examName)
    {
        $examCodes = [
            'SSC' => '01',
            'HSC' => '02',
            'JSC' => '03',
            'PSC' => '04',
        ];
        
        return $examCodes[$examName] ?? '99';
    }

    /**
     * Get the student that owns the form fillup.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'registration_number', 'registration_number');
    }

    /**
     * Get the board user that created the form fillup.
     */
    public function board()
    {
        return $this->belongsTo(User::class, 'board_id');
    }

    /**
     * Get the institution that registered the student.
     */
    public function institution()
    {
        return $this->belongsTo(User::class, 'institution_id');
    }

    /**
     * Get the exam marks for this form fillup.
     */
    public function examMarks()
    {
        return $this->hasMany(ExamMark::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the result for this form fillup.
     */
    public function result()
    {
        return $this->hasOne(Result::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the revalidation requests for this form fillup.
     */
    public function revalidationRequests()
    {
        return $this->hasMany(ResultRevalidationRequest::class, 'roll_number', 'roll_number');
    }
} 