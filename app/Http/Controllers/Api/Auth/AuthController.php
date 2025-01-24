<?php

namespace Vanguard\Http\Controllers\Api\Auth;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Vanguard\Events\User\LoggedIn;
use Vanguard\Events\User\LoggedOut;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Auth\ApiLoginRequest;
use Vanguard\User;

class AuthController extends ApiController
{
    public function __construct()
    {
        $this->middleware('guest')->only('login');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Attempt to log the user in and generate unique JWT token on successful authentication.
     *
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    public function token(ApiLoginRequest $request): JsonResponse
    {
        $user = $this->findUser($request);

        if ($user->isBanned()) {
            return $this->errorUnauthorized(trans('auth.banned'));
        }

        Auth::setUser($user);

        event(new LoggedIn);

        return $this->respondWithArray([
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]);
    }

    /**
     * Find the user instance from the API request.
     *
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    private function findUser(ApiLoginRequest $request): ?User
    {
        $user = User::where($request->getCredentials())->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')],
            ]);
        }

        return $user;
    }

    /**
     * Logout user and invalidate token.
     */
    public function logout(): JsonResponse
    {
        event(new LoggedOut);

        auth()->user()->currentAccessToken()->delete();

        return $this->respondWithSuccess();
    }
}
