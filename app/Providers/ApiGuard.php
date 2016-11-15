<?php

namespace App\Providers;

use App\Admin;
use DB;
use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class ApiGuard implements Guard
{
    use GuardHelpers;

    private $sessionKey;

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

        $this->sessionKey = config('auth.guards.api.session_key');
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

        if (! is_null($userData = $this->app['session']->get($this->sessionKey)))
        {
            if (isset($userData['token']))
            {
                $user = $this->provider->getGenericUser($userData);

                $this->setUser($user);

                return $user;
            }
        }

        return $this->user = null;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    protected function getToken()
    {
        $admin = $this->app['session']->get($this->sessionKey);

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
        // $user = $this->provider->retrieveByCredentials($credentials);
        //
        // if ($user === null)
        // {
        //     return false;
        // }
        //
        // $this->setUser($user);

        return true;
    }

    public function logout()
    {
        $user = $this->user();

        $this->app['session']->forget($this->sessionKey);

        $this->user = null;
    }

    public function inviteMerchantThroughEmail($input)
    {
        $formData = $this->getFormDataFromInput($input);

        $leadId = DB::table('admin_leads')->insertGetId(
            array(
                'admin_id'   => $this->user()->id,
                'email'      => $input['contact_email'],
                'token'      => str_random(40),
                'form_data'  => $formData,
                'created_at' => time(),
                'updated_at' => time(),
            )
        );

        $lead = DB::table('admin_leads')
                ->select('*')
                ->where('id', $leadId)
                ->first();
        return $lead;
    }

    protected function getFormDataFromInput($input)
    {
        return json_encode($input);
    }
}
