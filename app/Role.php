<?php

namespace Vanguard;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Vanguard\Support\Authorization\AuthorizationRoleTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property bool $removable
 * @property Collection<User> $users
 * @property Carbon $created_at
 * @property Carbon $deleted_at
 */
class Role extends Model
{
    use AuthorizationRoleTrait, HasFactory;

    public const DEFAULT_USER_ROLE = 'User';

    public const DEFAULT_ADMIN_ROLE = 'Admin';

    protected $table = 'roles';

    protected $casts = [
        'removable' => 'boolean',
    ];

    protected $fillable = ['name', 'display_name', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return new RoleFactory;
    }
}
