<?php

namespace App\Providers;

use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class ApiGuard implements Guard
{
    use GuardHelpers;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function __construct(UserProvider $provider, $app)
    {
        $this->provider = $provider;
        $this->app = $app;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user))
        {
            return $this->user;
        }

        if (! is_null($userData = $this->app['session']->get('api_admin')))
        {
            $user = $this->provider->getGenericUser($userData);

            $this->setUser($user);

            return $user;
        }

        $user = null;

        $token = $this->getToken();

        if (! empty($token)) {
            $user = $this->provider->retrieveByToken(
                ['token' => $token]
            );
        }

        return $this->user = $user;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    protected function getToken()
    {
        $admin = $this->sessions->get('api_admin');

        $token = $admin['token'];

        return $token;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null)
        {
            return false;
        }

        $this->setUser($user);

        return true;
    }
}
