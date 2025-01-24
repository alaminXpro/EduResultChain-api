<?php

namespace Vanguard\Support\Plugins;

use Vanguard\Plugins\Plugin;
use Vanguard\Support\Sidebar\Item;
use Vanguard\User;

class Settings extends Plugin
{
    public function sidebar(): Item
    {
        $general = Item::create(__('General'))
            ->route('settings.general')
            ->active('settings')
            ->permissions('settings.general');

        $authAndRegistration = Item::create(__('Auth & Registration'))
            ->route('settings.auth')
            ->active('settings/auth')
            ->permissions('settings.auth');

        $notifications = Item::create(__('Notifications'))
            ->route('settings.notifications')
            ->active('settings/notifications')
            ->permissions(function (User $user) {
                return $user->hasPermission('settings.notifications');
            });

        return Item::create(__('Settings'))
            ->href('#settings-dropdown')
            ->icon('fas fa-cogs')
            ->permissions(function (User $user) {
                return $user->hasPermission(
                    ['settings.general', 'settings.auth', 'settings.notifications'],
                    allRequired: false
                );
            })
            ->addChildren([
                $general,
                $authAndRegistration,
                $notifications,
            ]);
    }
}
