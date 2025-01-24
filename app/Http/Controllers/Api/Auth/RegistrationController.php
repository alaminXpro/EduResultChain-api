<?php

namespace Vanguard\Http\Controllers\Api\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Auth\RegisterRequest;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;
use Vanguard\Support\Enum\UserStatus;

class RegistrationController extends ApiController
{
    public function __construct(private readonly UserRepository $users, private readonly RoleRepository $roles)
    {
    }

    public function index(RegisterRequest $request): JsonResponse
    {
        $role = $this->roles->findByName(Role::DEFAULT_USER_ROLE);

        $user = $this->users->create(
            array_merge($request->validFormData(), ['role_id' => $role->id])
        );

        event(new Registered($user));

        return $this->setStatusCode(201)
            ->respondWithArray([
                'requires_email_confirmation' => (bool) setting('reg_email_confirmation'),
            ]);
    }

    /**
     * Verify email via email confirmation token.
     */
    public function verifyEmail($token): JsonResponse
    {
        if (! setting('reg_email_confirmation')) {
            return $this->errorNotFound();
        }

        if ($user = $this->users->findByConfirmationToken($token)) {
            $this->users->update($user->id, [
                'status' => UserStatus::ACTIVE,
                'confirmation_token' => null,
            ]);

            return $this->respondWithSuccess();
        }

        return $this->setStatusCode(400)
            ->respondWithError('Invalid confirmation token.');
    }
}
