<?php

namespace Tests\Feature\Api\Users;

use Carbon\Carbon;
use Facades\Tests\Setup\UserFactory;
use PragmaRX\Google2FA\Google2FA;
use Tests\Feature\ApiTestCase;
use Vanguard\Events\User\TwoFactorDisabledByAdmin;
use Vanguard\Events\User\TwoFactorEnabledByAdmin;
use Vanguard\Http\Resources\UserResource;
use Vanguard\User;

class TwoFactorControllerTest extends ApiTestCase
{
    /** @test */
    public function update_2fa_unathenticated()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = User::factory()->create();

        $this->putJson("api/users/{$user->id}/2fa")
            ->assertStatus(401);
    }

    /** @test */
    public function update_2fa_without_permission()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = User::factory()->create();

        $this->actingAs($user, self::API_GUARD)
            ->putJson("api/users/{$user->id}/2fa")
            ->assertForbidden();
    }

    /** @test */
    public function enable_two_factor_auth_for_user()
    {
        $this->setSettings(['2fa.enabled' => true]);

        \Event::fake([TwoFactorEnabledByAdmin::class]);

        $user = UserFactory::withPermissions('users.manage')->create();

        $this->actingAs($user, self::API_GUARD)->putJson("api/users/{$user->id}/2fa")
            ->assertOk()
            ->assertJson(['message' => 'Verification token sent.'])
            ->assertJsonStructure([
                'message',
                'qrcode',
            ]);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNotNull('two_factor_secret')
                ->exists()
        );

        \Event::assertNotDispatched(TwoFactorEnabledByAdmin::class);
    }

    /** @test */
    public function verify_user_app_with_correct_code()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        \Event::fake([TwoFactorEnabledByAdmin::class]);

        $user = UserFactory::withPermissions('users.manage')->create();
        $user->two_factor_secret = $secret;
        $user->save();

        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->actingAs($user, self::API_GUARD)
            ->postJson("api/users/{$user->id}/2fa/verify", ['code' => $validCode]);

        $updatedUser = (new UserResource($user->fresh()))->resolve();

        $response->assertOk()
            ->assertJsonFragment($updatedUser);

        $this->assertTrue($user->fresh()->twoFactorEnabled());

        \Event::assertDispatched(TwoFactorEnabledByAdmin::class);
    }

    /** @test */
    public function verify_user_app_with_invalid_token()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::withPermissions('users.manage')->create();

        $this->actingAs($user, self::API_GUARD)
            ->postJson("api/users/{$user->id}/2fa/verify", ['code' => '123123'])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid 2FA token.',
            ]);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNull('two_factor_confirmed_at')
                ->exists()
        );
    }

    /** @test */
    public function enable_two_factor_auth_for_user_when_it_is_already_enabled()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::withPermissions('users.manage')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->actingAs($user, self::API_GUARD)
            ->putJson("api/users/{$user->id}/2fa")
            ->assertStatus(422)
            ->assertJson([
                'message' => '2FA is already enabled for this user.',
            ]);
    }

    /** @test */
    public function disable_two_factor_auth_for_user()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        \Event::fake([TwoFactorDisabledByAdmin::class]);

        $user = UserFactory::withPermissions('users.manage')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->be($user, self::API_GUARD);

        $response = $this->deleteJson("api/users/{$user->id}/2fa");

        $user = (new UserResource($user->fresh()))->resolve();

        $response->assertOk()
            ->assertJsonFragment($user);

        \Event::assertDispatched(TwoFactorDisabledByAdmin::class);
    }

    /** @test */
    public function disable_2fa_for_user_when_it_is_already_disabled()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::withPermissions('users.manage')->create();

        $this->actingAs($user, self::API_GUARD)
            ->deleteJson("api/users/{$user->id}/2fa")
            ->assertStatus(422)
            ->assertJson([
                'message' => '2FA is not enabled for this user.',
            ]);
    }
}
