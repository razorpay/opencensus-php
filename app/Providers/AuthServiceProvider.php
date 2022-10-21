<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as BaseAuthServiceProvider;
use Config;
use Auth;
use Gate;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $userRoles = Config::get('user-roles');

        foreach ($userRoles as $route => $roles)
        {
            Gate::define($route, function($user) use ($roles)
            {
                $currentMerchant = $user->currentMerchant();

                $currentRole = null;

                if (empty($currentMerchant) === false)
                {
                    $currentRole = $currentMerchant->role;
                }

                return (in_array($currentRole, $roles, true));
            });
        }
    }
}
