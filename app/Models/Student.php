<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'registration_number';

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
        'first_name',
        'last_name',
        'date_of_birth',
        'father_name',
        'mother_name',
        'phone_number',
        'email',
        'image',
        'permanent_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            // Generate registration number if not set
            if (empty($student->registration_number)) {
                $student->registration_number = static::generateRegistrationNumber();
            }
        });
    }

    /**
     * Generate a unique registration number.
     *
     * @return string
     */
    public static function generateRegistrationNumber()
    {
        // Format: Current year + 6 digit sequential number
        $year = date('Y');
        $lastStudent = static::where('registration_number', 'like', $year . '%')
            ->orderBy('registration_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->registration_number, 4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the form fillups for the student.
     */
    public function formFillups()
    {
        return $this->hasMany(FormFillup::class, 'registration_number', 'registration_number');
    }

    /**
     * Get the phone verification for the student.
     */
    public function phoneVerification()
    {
        return $this->hasOne(PhoneVerification::class, 'registration_number', 'registration_number');
    }

    /**
     * Get the full name of the student.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
} 