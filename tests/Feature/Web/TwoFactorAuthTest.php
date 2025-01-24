<?php

namespace Tests\Feature\Web;

use Carbon\Carbon;
use Event;
use Facades\Tests\Setup\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;
use Tests\UpdatesSettings;
use Vanguard\Events\User\TwoFactorEnabled;
use Vanguard\User;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase, UpdatesSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');
    }

    /** @test */
    public function the_2fa_form_is_visible_on_profile_page_if_2fa_is_enabled()
    {
        config(['services.authy.key' => 'test']);

        $this->setSettings(['2fa.enabled' => false]);

        $this->actingAsAdmin()
            ->get('profile')
            ->assertDontSee('Two-Factor Authentication');

        $this->setSettings(['2fa.enabled' => true]);

        $this->actingAsAdmin()
            ->get('profile')
            ->assertSee('Two-Factor Authentication');
    }

    /** @test */
    public function the_2fa_form_is_visible_on_edit_user_page_if_2fa_is_enabled()
    {
        config(['services.authy.key' => 'test']);

        $this->setSettings(['2fa.enabled' => false]);

        $user = UserFactory::create();

        $this->actingAsAdmin()
            ->get("/users/{$user->id}/edit")
            ->assertDontSee('Two-Factor Authentication');

        $this->setSettings(['2fa.enabled' => true]);

        $this->actingAsAdmin()
            ->get("/users/{$user->id}/edit")
            ->assertSee('Two-Factor Authentication');
    }

    /** @test */
    public function enable_2fa_from_profile_page()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::user()->create();

        $this->actingAs($user)
            ->post('/two-factor/enable')
            ->assertRedirect('/')
            ->assertSessionHas('tab', '2fa');

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNotNull('two_factor_secret')
                ->exists()
        );
    }

    /** @test */
    public function enable_2fa_from_edit_user_page()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::user()->create();

        $formData = ['user' => $user->id];

        $this->actingAsAdmin()
            ->post("users/{$user->id}/two-factor/enable", $formData)
            ->assertRedirect("/")
            ->assertSessionHas('tab', '2fa');

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNotNull('two_factor_secret')
                ->exists()
        );
    }

    /** @test */
    public function users_without_appropriate_permissions_cannot_enable_2fa_for_other_users()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $this->be(UserFactory::user()->create());

        $user = UserFactory::user()->create();

        $this->post('two-factor/enable', [
            'user' => $user->id,
        ])->assertStatus(403);

        $this->assertTrue(
            \DB::table('users')
                ->where('id', $user->id)
                ->whereNull('two_factor_secret')
                ->exists()
        );
    }

    /** @test */
    public function code_field_is_required_during_2fa_verification()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::user()->create();
        $user->two_factor_secret = '123123';
        $user->save();

        $this->actingAs($user)
            ->post('two-factor/verify')
            ->assertSessionHasErrors('code');
    }

    /** @test */
    public function the_2fa_verification_with_wrong_token_will_fail()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->withoutExceptionHandling();
        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::user()->create();
        $user->two_factor_secret = $secret;
        $user->save();

        $this->actingAs($user)
            ->post('two-factor/verify', ['code' => '123123']);

        $this->assertSessionHasError('Invalid 2FA token.');
    }

    /** @test */
    public function successful_2fa_code_verification()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        Event::fake([
            TwoFactorEnabled::class,
        ]);

        $user = UserFactory::user()->create();
        $user->two_factor_secret = $secret;
        $user->save();

        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->actingAs($user)
            ->post('two-factor/verify', ['code' => $validCode])
            ->assertRedirect('/profile');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => $secret,
        ]);

        Event::assertDispatched(TwoFactorEnabled::class);
    }

    /** @test */
    public function user_can_disable_2fa()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::user()->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->be($user);

        Event::fake([
            \Vanguard\Events\User\TwoFactorDisabled::class,
        ]);

        $this->from('/profile')
            ->post('two-factor/disable')
            ->assertRedirect('/profile');

        $this->assertSessionHasSuccess('Two-Factor Authentication disabled successfully.');

        Event::assertDispatched(\Vanguard\Events\User\TwoFactorDisabled::class);
    }

    /** @test */
    public function user_can_disable_2fa_for_another_user()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        Event::fake([
            \Vanguard\Events\User\TwoFactorDisabled::class,
        ]);

        $this->beAdmin();

        $user = UserFactory::user()->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->from("/users/{$user->id}/edit")
            ->post("/users/{$user->id}/two-factor/disable", ['user' => $user->id])
            ->assertRedirect("/users/{$user->id}/edit");

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
        ]);

        $this->assertSessionHasSuccess('Two-Factor Authentication disabled successfully.');
        Event::assertDispatched(\Vanguard\Events\User\TwoFactorDisabled::class);
    }

    /** @test */
    public function user_without_appropriate_permissions_cannot_disable_2fa_for_another_user()
    {
        $this->setSettings(['2fa.enabled' => true]);

        $this->be(UserFactory::user()->create());

        $user = User::factory()->create();

        $this->post('two-factor/disable', ['user' => $user->id])->assertForbidden();
    }
}
