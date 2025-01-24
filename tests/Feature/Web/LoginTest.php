<?php

namespace Tests\Feature\Web;

use Carbon\Carbon;
use Event;
use Facades\Tests\Setup\UserFactory;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;
use Setting;
use Tests\TestCase;
use Tests\UpdatesSettings;
use Vanguard\Events\User\LoggedIn;
use Vanguard\User;

class LoginTest extends TestCase
{
    use RefreshDatabase, UpdatesSettings;

    /** @test */
    public function successful_login()
    {
        $user = UserFactory::withCredentials('foo', 'bar')->create();

        $this->loginUser('foo', 'bar')
            ->assertRedirect('/');

        $this->assertTrue(auth()->check());
        $this->assertTrue($user->is(auth()->user()));
    }

    /** @test */
    public function last_login_timestamp_is_updated_after_successful_login()
    {
        $testDate = Carbon::now();

        Carbon::setTestNow($testDate);

        $user = UserFactory::withCredentials('foo', 'bar')->create();

        $this->assertNull($user->last_login);

        $this->loginUser('foo', 'bar');

        $this->assertEquals($testDate->timestamp, $user->fresh()->last_login->timestamp);
    }

    /** @test */
    public function login_with_wrong_credentials_will_fail()
    {
        $this->loginUser('foo', 'bar')
            ->assertRedirect('/login');

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function country_id_remains_the_same_after_login()
    {
        $user = User::factory()->create([
            'username' => 'foo',
            'password' => 'bar',
            'country_id' => 688,
        ]);

        $this->loginUser('foo', 'bar')
            ->assertRedirect('/');

        $this->assertEquals(688, $user->fresh()->country_id);
    }

    /** @test */
    public function throttling()
    {
        $this->setSettings([
            'throttle_enabled' => true,
            'throttle_attempts' => 3,
            'throttle_lockout_time' => 2, // 2 minutes
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->loginUser('foo', 'bar');
        }

        $this->loginUser('foo', 'bar')
            ->assertRedirect('login')
            ->assertSessionHasErrors('username');

        $this->assertTrue(app(RateLimiter::class)->tooManyAttempts('foo|127.0.0.1', 3));
    }

    /** @test */
    public function login_with_remember()
    {
        $user = UserFactory::withCredentials('foo', 'bar')->create();

        Setting::set('remember_me', false);

        $this->get('login')
            ->assertDontSeeText('name="remember"', false);

        Setting::set('remember_me', true);

        $this->get('login')
            ->assertSee('name="remember"', false);

        $this->loginUser('foo', 'bar', true)
            ->assertRedirect('/');

        $this->assertNotNull($user->fresh()->remember_token);
        $this->assertNotNull($user->fresh()->last_login);
    }

    /** @test */
    public function login_max_number_of_sessions_reached()
    {
        $user = UserFactory::withCredentials('foo', 'bar')->create();

        Setting::set('max_active_sessions', 1);

        \DB::table('sessions')->insert([
            'id' => \Str::random(),
            'user_id' => $user->id,
            'ip_address' => fake()->ipv4,
            'user_agent' => fake()->userAgent,
            'payload' => 'test',
            'last_activity' => Carbon::now()->timestamp,
        ]);

        $this->loginUser('foo', 'bar')
            ->assertRedirect('/login');

        $this->assertSessionHasError(trans('auth.max_sessions_reached'));

        $this->assertFalse(\Auth::check());
    }

    /** @test */
    public function banned_user_cannot_log_in()
    {
        UserFactory::withCredentials('foo', 'bar')->banned()->create();

        $this->loginUser('foo', 'bar')
            ->assertRedirect('/login');

        $this->assertSessionHasError('Your account is banned by administrator.');
    }

    /** @test */
    public function login_with_2fa_enabled()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->withoutExceptionHandling();
        $this->setSettings(['2fa.enabled' => true]);

        Event::fake([LoggedIn::class]);

        $user = UserFactory::withCredentials('foo', 'bar')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->loginUser('foo', 'bar')
            ->assertRedirect('auth/two-factor-authentication')
            ->assertSessionHas('auth.2fa.id', $user->id);

        $this->post('auth/two-factor-authentication', ['code' => $validCode])
            ->assertRedirect('/');

        $this->assertTrue(auth()->check());

        Event::assertDispatched(LoggedIn::class);
    }

    /** @test */
    public function login_with_2fa_enabled_redirect_to_custom_page()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->withoutExceptionHandling();
        $this->setSettings(['2fa.enabled' => true]);

        Event::fake([LoggedIn::class]);

        $user = UserFactory::withCredentials('foo', 'bar')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->loginUser('foo', 'bar', redirectTo: 'https://google.com')
            ->assertRedirect('auth/two-factor-authentication')
            ->assertSessionHas('auth.2fa.id', $user->id);

        $this->post('auth/two-factor-authentication', ['code' => $validCode])
            ->assertRedirect('https://google.com');

        $this->assertTrue(auth()->check());

        Event::assertDispatched(LoggedIn::class);
    }

    /** @test */
    public function login_with_wrong_2fa_token()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::withCredentials('foo', 'bar')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->loginUser('foo', 'bar')
            ->assertRedirect('auth/two-factor-authentication')
            ->assertSessionHas('auth.2fa.id', $user->id);

        $this->post('auth/two-factor-authentication', ['code' => '123123'])->assertRedirect('login');

        $this->assertSessionHasError('Invalid 2FA token.');
    }

    /** @test */
    public function login_with_wrong_2fa_token_when_custom_redirect_page_is_provided()
    {
        $google2fa = new Google2FA();
        $secret = encrypt($google2fa->generateSecretKey());

        $this->setSettings(['2fa.enabled' => true]);

        $user = UserFactory::withCredentials('foo', 'bar')->create();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = Carbon::now();
        $user->save();

        $this->loginUser('foo', 'bar', redirectTo: 'https://google.com')
            ->assertRedirect('auth/two-factor-authentication')
            ->assertSessionHas('auth.2fa.id', $user->id);

        $this->post('auth/two-factor-authentication', ['code' => '123'])
            ->assertRedirect('login?to=https://google.com');

        $this->assertSessionHasError('Invalid 2FA token.');
    }

    private function loginUser($username, $password, $remember = false, $redirectTo = null): TestResponse
    {
        $url = 'login';

        if ($redirectTo) {
            $url .= '?to='.urlencode($redirectTo);
        }

        return $this->post($url, [
            'username' => $username,
            'password' => $password,
            'remember' => $remember,
        ]);
    }
}
