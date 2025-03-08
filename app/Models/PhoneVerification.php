<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'registration_number',
        'phone_number',
        'verification_code',
        'verified',
        'verified_at',
        'code_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'code_expires_at' => 'datetime',
    ];

    /**
     * Get the student that owns the phone verification.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'registration_number', 'registration_number');
    }

    /**
     * Check if the verification code has expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->code_expires_at && now()->gt($this->code_expires_at);
    }
} 