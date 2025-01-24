<?php

namespace Vanguard\Http\Controllers\Web\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vanguard\Events\User\ChangedAvatar;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Services\Upload\UserAvatarManager;

class AvatarController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function update(Request $request, UserAvatarManager $avatarManager): RedirectResponse
    {
        $request->validate(['avatar' => 'image']);

        $name = $avatarManager->uploadAndCropAvatar(
            $request->file('avatar'),
            $request->get('points')
        );

        if ($name) {
            return $this->handleAvatarUpdate($name);
        }

        return redirect()->route('profile')
            ->withErrors(__('Avatar image cannot be updated. Please try again.'));
    }

    /**
     * Update avatar for currently logged-in user and fire appropriate event.
     */
    private function handleAvatarUpdate(string|null $avatar): RedirectResponse
    {
        $this->users->update(auth()->id(), ['avatar' => $avatar]);

        event(new ChangedAvatar);

        return redirect()->route('profile')
            ->withSuccess(__('Avatar changed successfully.'));
    }

    /**
     * Update user's avatar from external location/url.
     */
    public function updateExternal(Request $request, UserAvatarManager $avatarManager): RedirectResponse
    {
        $avatarManager->deleteAvatarIfUploaded(auth()->user());

        return $this->handleAvatarUpdate($request->get('url'));
    }
}
