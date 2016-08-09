<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        $gate->define('post_refund', function($user){
            return ($user->getUserRoleWithCurrentMerchant() !== 'finance');
        });

        $gate->define('post_capture', function($user){
            return ($user->getUserRoleWithCurrentMerchant() !== 'finance');
        });

        $gate->define('get_keys', function($user){
            return ($user->getUserRoleWithCurrentMerchant() === 'owner');
        });

        $gate->define('post_keys', function($user){
            return ($user->getUserRoleWithCurrentMerchant() === 'owner');
        });

        $gate->define('get_activation_details', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('post_activation', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('post_activation_save_step', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('post_activation_save_file', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('get_webhooks', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('post_webhooks', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('edit_webhooks', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('get_config', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('put_config', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });

        $gate->define('post_config_logo', function($user){
            return (($user->getUserRoleWithCurrentMerchant() === 'owner') || ($user->getUserRoleWithCurrentMerchant() === 'manager'));
        });
    }
}
