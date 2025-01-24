<?php

namespace Vanguard;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Mail;
use Vanguard\Events\User\RequestedPasswordResetEmail;
use Vanguard\Presenters\Traits\Presentable;
use Vanguard\Presenters\UserPresenter;
use Vanguard\Support\Authorization\AuthorizationUserTrait;
use Vanguard\Support\CanImpersonateUsers;
use Vanguard\Support\Enum\UserStatus;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $username
 * @property string $phone
 * @property string|null $avatar
 * @property string|null $address
 * @property int|null $country_id
 * @property Carbon $last_login
 * @property Carbon $birthday
 * @property UserStatus $status
 * @property string|null $confirmation_token
 * @property string|null $remember_token
 * @property int $role_id
 * @property Carbon|null $email_verified_at
 * @property string $two_factor_country_code
 * @property string $two_factor_phone
 * @property Carbon $created_at
 * @property Carbon $deleted_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use AuthorizationUserTrait,
        CanImpersonateUsers,
        CanResetPassword,
        HasApiTokens,
        HasFactory,
        Notifiable,
        Presentable,
        TwoFactorAuthenticatable;

    protected string $presenter = UserPresenter::class;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    protected $casts = [
        'last_login' => 'datetime',
        'birthday' => 'date',
        'status' => UserStatus::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'username', 'first_name', 'last_name', 'phone', 'avatar',
        'address', 'country_id', 'birthday', 'last_login', 'confirmation_token', 'status',
        'remember_token', 'role_id', 'email_verified_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Always encrypt password when it is updated.
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function setBirthdayAttribute($value): void
    {
        $this->attributes['birthday'] = trim($value) ?: null;
    }

    public function gravatar(): string
    {
        $hash = hash('md5', strtolower(trim($this->attributes['email'])));

        return sprintf('https://www.gravatar.com/avatar/%s?size=150', $hash);
    }

    public function isUnconfirmed(): bool
    {
        return $this->status == UserStatus::UNCONFIRMED;
    }

    public function isActive(): bool
    {
        return $this->status == UserStatus::ACTIVE;
    }

    public function isBanned(): bool
    {
        return $this->status == UserStatus::BANNED;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this)->send(new \Vanguard\Mail\ResetPassword($token));

        event(new RequestedPasswordResetEmail($this));
    }

    public function twoFactorEnabled(): bool
    {
        return !!$this->two_factor_confirmed_at && !!$this->two_factor_secret;
    }

    public function needsTwoFactorVerification(): bool
    {
        return !$this->two_factor_confirmed_at && !!$this->two_factor_secret;
    }
}
