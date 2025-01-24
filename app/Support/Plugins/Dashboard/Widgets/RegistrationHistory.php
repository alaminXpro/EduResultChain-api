<?php

namespace Vanguard\Support\Plugins\Dashboard\Widgets;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Vanguard\Plugins\Widget;
use Vanguard\Repositories\User\UserRepository;

class RegistrationHistory extends Widget
{
    /**
     * {@inheritdoc}
     */
    public ?string $width = '8';

    protected string|\Closure|array $permissions = 'users.manage';

    /**
     * @var array Count of new users per month.
     */
    protected array $usersPerMonth;

    public function __construct(protected UserRepository $users)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function render(): View
    {
        return view('plugins.dashboard.widgets.registration-history', [
            'usersPerMonth' => $this->getUsersPerMonth(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function scripts(): View
    {
        return view('plugins.dashboard.widgets.registration-history-scripts', [
            'usersPerMonth' => $this->getUsersPerMonth(),
        ]);
    }

    private function getUsersPerMonth(): array
    {
        if (isset($this->usersPerMonth)) {
            return $this->usersPerMonth;
        }

        return $this->usersPerMonth = $this->users->countOfNewUsersPerMonthPerRole(
            Carbon::now()->subYear()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
    }
}
