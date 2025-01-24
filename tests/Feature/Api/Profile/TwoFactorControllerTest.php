<?php

namespace Tests\Feature\Api\Profile;

use Carbon\Carbon;
use Event;
use PragmaRX\Google2FA\Google2FA;
use Tests\Feature\ApiTestCase;
use Vanguard\Events\User\TwoFactorEnabled;
use Vanguard\Http\Resources\UserResource;
use Vanguard\User;

class TwoFactorControllerTest extends ApiTestCase
{
    /** @test */
    public function update_2fa_unathenticated()
    {
        $this->setSettings(['2fa.enabled' => true]);

        User::factory()->create();

        $this->putJson('api/me/2fa')
            ->assertStatus(401);
    }

    /** @test */
    public function enable_two_factor_auth()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $this->withoutExceptionHandling();

        Event::fake([
            TwoFactorEnabled::class,
        ]);

        $user = $this->login();

        $this->putJson('api/me/2fa')
            ->assertOk()
            ->assertJson(['message' => 'Verification token sent.'])
            ->assertJsonStructure([
                'message',
                'qrcode'
            ]);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNotNull('two_factor_secret')
                ->exists()
        );

        Event::assertNotDispatched(TwoFactorEnabled::class);
    }

    /** @test */
    public function verify_user_auth_app_with_correct_code()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        Event::fake([
            TwoFactorEnabled::class,
        ]);

        $user = $this->login();
        $user->two_factor_secret = $secret;
        $user->save();

        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->postJson('api/me/2fa/verify', ['code' => $validCode]);

        $updatedUser = (new UserResource($user->fresh()))->resolve();

        $response->assertOk()
            ->assertJsonFragment($updatedUser);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNotNull('two_factor_confirmed_at')
                ->exists()
        );

        Event::assertDispatched(TwoFactorEnabled::class);
    }

    /** @test */
    public function verify_user_app_with_invalid_token()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = $this->login();

        $this->postJson('api/me/2fa/verify', ['code' => '123123'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Invalid 2FA token.']);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNull('two_factor_confirmed_at')
                ->exists()
        );
    }

    /** @test */
    public function enable_two_factor_auth_when_it_is_already_enabled()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = $this->login();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->putJson('api/me/2fa')
            ->assertStatus(422)
            ->assertJson([
                'message' => '2FA is already enabled for this user.',
            ]);
    }

    /** @test */
    public function disable_two_factor_auth()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => Carbon::now(),
        ]);

        $this->be($user, self::API_GUARD);

        $response = $this->deleteJson('api/me/2fa');

        $user = (new UserResource($user->fresh()))->resolve();

        $response->assertOk()
            ->assertJsonFragment($user);
    }

    /** @test */
    public function disable_2fa_when_it_is_already_disabled()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $this->login();

        $this->deleteJson('api/me/2fa')
            ->assertStatus(422)
            ->assertJson([
                'message' => '2FA is not enabled for this user.',
            ]);
    }
}
