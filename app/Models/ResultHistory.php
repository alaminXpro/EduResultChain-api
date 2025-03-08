<?php

namespace Vanguard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vanguard\User;

class ResultHistory extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'result_id',
        'modified_by',
        'modification_type',
        'previous_data',
        'new_data',
        'previous_ipfs_hash',
        'new_ipfs_hash',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'previous_data' => 'array',
        'new_data' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the result that owns the history.
     */
    public function result()
    {
        return $this->belongsTo(Result::class, 'result_id', 'result_id');
    }

    /**
     * Get the user who modified the result.
     */
    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
} 