<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Tests\Feature\ApiTestCase;
use Vanguard\Http\Resources\UserResource;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

class StatsControllerTest extends ApiTestCase
{
    /** @test */
    public function unauthenticated()
    {
        $this->getJson('/api/stats')->assertStatus(401);
    }

    /** @test */
    public function get_stats_as_admin()
    {
        \DB::table('users')->delete();

        $adminRole = Role::whereName('Admin')->first();

        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $this->be($user, self::API_GUARD);

        Carbon::setTestNow(Carbon::now()->startOfYear());

        User::factory()->times(4)->create(['status' => UserStatus::ACTIVE]);

        Carbon::setTestNow(null);

        User::factory()->times(2)->create(['status' => UserStatus::BANNED]);

        User::factory()->times(7)->create(['status' => UserStatus::UNCONFIRMED]);

        $users = app(UserRepository::class);

        $response = $this->getJson('/api/stats');

        $usersPerMonth = $users->countOfNewUsersPerMonthPerRole(
            now()->subYear()->startOfMonth(),
            now()->endOfMonth()
        );

        $latestRegistrations = $users->latest(7);

        $response->assertOk()
            ->assertJson([
                'users_per_month' => $usersPerMonth,
                'users_per_status' => [
                    'total' => 14,
                    'new' => $users->newUsersCount(),
                    'banned' => 2,
                    'unconfirmed' => 7,
                ],
                'latest_registrations' => UserResource::collection($latestRegistrations)->resolve(),
            ]);
    }

    /** @test */
    public function non_admin_users_cannot_get_user_stats()
    {
        $user = User::factory()->create();

        $this->actingAs($user, self::API_GUARD)->getJson('/api/stats')->assertForbidden();
    }
}
