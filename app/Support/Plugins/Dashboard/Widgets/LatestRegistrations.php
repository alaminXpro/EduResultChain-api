<?php

namespace Vanguard\Support\Plugins\Dashboard\Widgets;

use Illuminate\Contracts\View\View;
use Vanguard\Plugins\Widget;
use Vanguard\Repositories\User\UserRepository;

class LatestRegistrations extends Widget
{
    /**
     * {@inheritdoc}
     */
    public ?string $width = '4';

    /**
     * {@inheritdoc}
     */
    protected string|\Closure|array $permissions = 'users.manage';

    public function __construct(protected readonly UserRepository $users)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function render(): View
    {
        return view('plugins.dashboard.widgets.latest-registrations', [
            'latestRegistrations' => $this->users->latest(6),
        ]);
    }
}
