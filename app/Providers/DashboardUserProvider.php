<?php

namespace App\Providers;

use App\User;
use App\Merchant\GenericMerchant;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class DashboardUserProvider implements UserProvider
{
    protected $conn;

    public function __construct($app, $config)
    {
        $this->app = $app;

        $this->config = $config;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $genericUser = $this->app['session']->get('dashboard_user_payload');

        return $genericUser;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        throw new \Exception;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        throw new \Exception;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        list($error, $genericUser) = (new User\Service)->loginOnApi($credentials);

        if (empty($error) === true)
        {
            $this->app['session']->put('dashboard_user_payload', $genericUser);
        }

        return $genericUser;
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    public function getGenericUser($user)
    {
        if ($user !== null)
        {
            return new GenericUser((array) $user);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return true;
    }

    protected function getUserDetails($userId)
    {
        list($error, $userDetails) = (new User\Service)->getUserFromApi($user->id);
    }
}
