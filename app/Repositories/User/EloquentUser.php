<?php

namespace Vanguard\Repositories\User;

use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Socialite\Contracts\User as SocialUser;
use Vanguard\Http\Filters\UserKeywordSearch;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Role;
use Vanguard\Services\Auth\Social\ManagesSocialAvatarSize;
use Vanguard\Services\Upload\UserAvatarManager;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

class EloquentUser implements UserRepository
{
    use ManagesSocialAvatarSize;

    public function __construct(
        private readonly UserAvatarManager $avatarManager,
        private readonly RoleRepository $roles
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySocialId(string $provider, string $providerId): ?User
    {
        return User::leftJoin('social_logins', 'users.id', '=', 'social_logins.user_id')
            ->select('users.*')
            ->where('social_logins.provider', $provider)
            ->where('social_logins.provider_id', $providerId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySessionId(string $sessionId): ?User
    {
        return User::leftJoin('sessions', 'users.id', '=', 'sessions.user_id')
            ->select('users.*')
            ->where('sessions.id', $sessionId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function associateSocialAccountForUser(int $userId, string $provider, SocialUser $user): bool
    {
        return DB::table('social_logins')->insert([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_id' => $user->getId(),
            'avatar' => $this->getAvatarForProvider($provider, $user),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = User::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            (new UserKeywordSearch)($query, $search);
        }

        $result = $query->orderBy('id', 'desc')
            ->paginate($perPage);

        if ($search) {
            $result->appends(['search' => $search]);
        }

        if ($status) {
            $result->appends(['status' => $status]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): User
    {
        if (isset($data['country_id']) && $data['country_id'] == 0) {
            $data['country_id'] = null;
        }

        $user = $this->find($id);

        $user->update($data);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $user = $this->find($id);

        $this->avatarManager->deleteAvatarIfUploaded($user);

        return $user->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return User::count();
    }

    /**
     * {@inheritdoc}
     */
    public function newUsersCount(): int
    {
        return User::whereBetween('created_at', [Carbon::now()->firstOfMonth(), Carbon::now()])
            ->count();
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(UserStatus $status): int
    {
        return User::where('status', $status)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function latest($count = 20): \Illuminate\Database\Eloquent\Collection
    {
        return User::orderBy('created_at', 'DESC')
            ->limit($count)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function countOfNewUsersPerMonthPerRole(Carbon $from, Carbon $to): array
    {
        $result = User::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['created_at'])
            ->groupBy(function ($user) {
                return $user->created_at->format('Y_n');
            });

        $counts = [];

        while ($from->lt($to)) {
            $key = $from->format('Y_n');

            $counts[$this->parseDate($key)] = count($result->get($key, []));

            $from->addMonth();
        }

        return $counts;
    }

    /**
     * Parse date from "Y_m" format to "{Month Name} {Year}" format.
     */
    private function parseDate(string $yearMonth): string
    {
        [$year, $month] = explode('_', $yearMonth);

        $month = trans("app.months.{$month}");

        return "{$month} {$year}";
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersWithRole(string $roleName): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('name', $roleName)
            ->first()
            ->users;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSocialLogins(int $userId): \Illuminate\Support\Collection
    {
        return DB::table('social_logins')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function setRole(int $userId, int $roleId): bool
    {
        return $this->find($userId)->setRole($roleId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByConfirmationToken(string $token): ?User
    {
        return User::where('confirmation_token', $token)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function switchRolesForUsers(int $fromRoleId, int $toRoleId): bool
    {
        return User::where('role_id', $fromRoleId)->update(['role_id' => $toRoleId]);
    }

    /**
     * {@inheritdoc}
     */
    public function findForTwoFactor(string $phone, string $countryCode): ?User
    {
        return User::where('two_factor_phone', $phone)
            ->where('two_factor_country_code', $countryCode)
            ->first();
    }
}
