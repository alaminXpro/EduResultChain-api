<?php

namespace Vanguard\Http\Controllers\Web\Profile;

use Illuminate\Http\RedirectResponse;
use Vanguard\Events\User\UpdatedProfileDetails;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Http\Requests\User\UpdateProfileDetailsRequest;
use Vanguard\Repositories\User\UserRepository;

class DetailsController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function update(UpdateProfileDetailsRequest $request): RedirectResponse
    {
        $this->users->update(auth()->id(), $request->except('role_id', 'status'));

        event(new UpdatedProfileDetails);

        return redirect()->back()
            ->withSuccess(__('Profile updated successfully.'));
    }
}
