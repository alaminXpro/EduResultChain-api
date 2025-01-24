<?php

namespace Vanguard\Http\Controllers\Web\Users;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vanguard\Events\User\Banned;
use Vanguard\Events\User\UpdatedByAdmin;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Http\Requests\User\UpdateDetailsRequest;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

class DetailsController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function update(User $user, UpdateDetailsRequest $request): RedirectResponse
    {
        $data = $request->all();

        if (! data_get($data, 'country_id')) {
            $data['country_id'] = null;
        }

        $this->users->update($user->id, $data);
        $this->users->setRole($user->id, $request->role_id);

        event(new UpdatedByAdmin($user));

        // If user status was updated to "Banned",
        // fire the appropriate event.
        if ($this->userWasBanned($user, $request)) {
            event(new Banned($user));
        }

        return redirect()->back()
            ->withSuccess(__('User updated successfully.'));
    }

    /**
     * Check if user is banned during last update.
     */
    private function userWasBanned(User $user, Request $request): bool
    {
        return $user->status != $request->status
            && $request->status == UserStatus::BANNED->value;
    }
}
