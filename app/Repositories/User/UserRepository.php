<?php

namespace Vanguard\Repositories\User;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Socialite\Contracts\User as SocialUser;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

interface UserRepository
{
    /**
     * Paginate registered users.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    /**
     * Find user by its id.
     */
    public function find(int $id): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user registered via social network.
     *
     * @param  $provider  string Provider used for authentication.
     * @param  $providerId  string Provider's unique identifier for authenticated user.
     */
    public function findBySocialId(string $provider, string $providerId): ?User;

    /**
     * Find user by specified session id.
     */
    public function findBySessionId(string $sessionId): ?User;

    /**
     * Create new user.
     */
    public function create(array $data): User;

    /**
     * Update user specified by its id.
     */
    public function update(int $id, array $data): User;

    /**
     * Delete user with provided id.
     */
    public function delete(int $id): bool;

    /**
     * Associate account details returned from social network
     * to user with provided user id.
     */
    public function associateSocialAccountForUser(int $userId, string $provider, SocialUser $user): bool;

    /**
     * Number of users in database.
     */
    public function count(): int;

    /**
     * Number of users registered during current month.
     *
     * @return mixed
     */
    public function newUsersCount(): int;

    /**
     * Number of users with provided status.
     */
    public function countByStatus(UserStatus $status): int;

    /**
     * Count of registered users for every month within the provided date range.
     */
    public function countOfNewUsersPerMonthPerRole(Carbon $from, Carbon $to): array;

    /**
     * Get latest {$count} users from database.
     *
     * @return Collection<User>
     */
    public function latest(int $count = 20): Collection;

    /**
     * Set specified role to specified user.
     */
    public function setRole(int $userId, int $roleId): bool;

    /**
     * Change role for all users that have role $fromRoleId to $toRoleId.
     */
    public function switchRolesForUsers(int $fromRoleId, int $toRoleId): bool;

    /**
     * Get all users with provided role.
     *
     * @return Collection<User>
     */
    public function getUsersWithRole(string $roleName): Collection;

    /**
     * Get all social login records for specified user.
     */
    public function getUserSocialLogins(int $userId): \Illuminate\Support\Collection;

    /**
     * Find user by confirmation token.
     */
    public function findByConfirmationToken(string $token): ?User;

    /**
     * Find user by phone and country code.
     */
    public function findForTwoFactor(string $phone, string $countryCode): ?User;
}
