<?php

namespace App\User;

use DB;
use Auth;
use Hash;
use Input;
use Queue;
use Config;
use Session;
use App\Base;
use App\Generic;
use App\Merchant;
use App\AdminLead;
use App\Trace\TraceCode;
use App\MerchantDetails;
use App\Providers\GenericUser;
use App\Session as SessionTable;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Application;


class Service extends Base\Service
{
    const OAUTH_SESSION_TOKEN         = 'oauth_session_token';

    // Users who signed up before this date
    // are not exposed to the pre signup flow
    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    /**
     * @var Application
     */
    protected $app;

    protected $trace;

    /**
     * @var Store
     */
    protected $cache;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];
    }

    /**
     * Main registration method. Contains most business logic for deciding what to
     * register and as what (user|merchant) and with what details. See
     * HACKING.md for a bit more details.
     *
     * @param  array  $input [description]
     *
     * @return array
     *
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function register($input)
    {
        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($input)->send('users/register', 'POST');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function login(array $input)
    {
        $res = null;

        list($error, $genericUser) = $this->loginOnApi($input);

        if (empty($error) === false)
        {
            return [['Email or password is invalid.'], null];
        }

        Auth::login($genericUser, false);

        $this->app['session']->put('dashboard_user_payload', $genericUser);

        if (empty($error))
        {
            $res = [
                'id' => $genericUser->id,
            ];
        }

        $user = Auth::user();

        $traceData = [
            'id'          => $user->id,
            'email'       => $user->email,
            'merchant_id' => $user->currentMerchant()->id,
        ];

        $this->trace->info(TraceCode::USER_LOGIN, $traceData);

        return [$error, $res];
    }

    public function changePassword(array $input)
    {
        $user = Auth::user();

        if ($user->currentMerchant() and $user->currentMerchant()->isTestAccount())
        {
            return [["Password change forbidden on this account"], null];
        }

        list($error, $data) = $this->updatePasswordOnApi($input);

        $currentSessionId = Session::getId();

        (new SessionTable\Entity)->deleteAllOtherSessionsForUser($user->id, $currentSessionId);

        return [$error, $data];
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return \Illuminate\Http\Response
     */
    public function switchCurrentMerchantForUser($merchantId, GenericUser $user)
    {
        list($error, $genericUser) = $this->getUserFromApi($user->id);

        if (empty($error) === true)
        {
            $currentMerchant = $genericUser->merchants
                                           ->where('id', $merchantId)
                                           ->first();

            if ($currentMerchant !== null)
            {
                Session::put('current_merchant_id', $currentMerchant->id);

                $traceData = [
                    'id'          => $genericUser->id,
                    'email'       => $genericUser->email,
                    'merchant_id' => $currentMerchant->id,
                ];

                $this->trace->info(TraceCode::SWITCH_MERCHANT, $traceData);

                return [];
            }
        }

        return ["Couldn't find the merchant you are looking for."];
    }

    public function upgradeUserToMerchant($input)
    {
        $authUser = Auth::user();

        $data = [
            'business_name' =>  $input['business_name'],
            'user_id'       =>  $authUser->id,
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($data)->send('users/upgrade-merchant', 'POST');

        $genericUser = null;

        if (empty($error) === true)
        {
            list($error, $genericUser) = $this->getUserFromApi($authUser->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }

        return [$error, $data];
    }

    public function updatePasswordOnApi($data)
    {
        $passwordData = [
            'password'              => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
        ];

        if (isset($data['old_password']))
        {
            $passwordData['old_password'] = $data['old_password'];
        }

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($passwordData)->send("users/change_password", 'PUT');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    /**
     * Get data from the current user session
     *
     * @param array $queryParams
     *
     * @return array
     */
    public function getSessionData(array $queryParams): array
    {
        $user = Auth::user();

        $data = $error = null;

        if ($user === null)
        {
            //
            // If user is null, no active session exists
            // We simply return null, and allow the Authenticate middleware
            // to send a 401 response.
            //
            return [$error, $data];
        }

        $currentMerchant = $user->currentMerchant();

        // Create and cache a random token tying the user to the request
        $token = str_random(30);

        $data = [
            'user_id'       => $user->id,
            'user_email'    => $user->email,
            'merchant_id'   => $currentMerchant->id,
            'role'          => $currentMerchant->role,
            'query_params'  => $queryParams['query'] ?? []
        ];

        $cacheKey = $this->getOAuthSessionTokenCacheKey($token);

        $this->cache->put($cacheKey, $data, 10);

        $response = [
            'token'         => $token,
            'email'         => $user->email,
            'name'          => $user->name,
            'role'          => $currentMerchant->role,
            'merchant_name' => $currentMerchant->name,
            'logo'          => $currentMerchant->logo_url
        ];

        return [$error, $response];
    }

    /**
     * Fetch cached data for a session token
     * Used in auth-service for verifying user creds, S2S
     *
     * @param string $token
     *
     * @return array
     */
    public function getDetailsFromSessionToken(string $token): array
    {
        $error = $data = null;
        $cacheKey = $this->getOAuthSessionTokenCacheKey($token);

        $data = $this->cache->get($cacheKey);

        if ($data !== null)
        {
            $user = $this->getUserFromApi($data['user_id']);

            $data['user'] = $user;
            $data['user']['merchant_id'] = $data['merchant_id'];
        }
        else
        {
            $error[] = 'User data not found';
        }

        return [$error, $data];
    }

    /**
     * Defines the cache key for OAuth session tokens
     *
     * @param string $token
     *
     * @return string
     */
    private function getOAuthSessionTokenCacheKey(string $token): string
    {
        return self::OAUTH_SESSION_TOKEN . '.' . $token;
    }

    public function getUserDetails()
    {
        $data = [
            'current'   =>  null
        ];

        $user = Auth::user();

        if (!$user)
        {
            return [['Not logged in'], null];
        }

        list($error, $genericUser) = $this->getUserFromApi($user->id);

        if (empty($error) === false)
        {
            return [$error, $data];
        }

        $userDetails = $genericUser->toArray();

        $merchants = $userDetails['merchants'];

        $data['user'] = $userDetails;

        $activated = false;

        // Default values in case no merchant is associated
        // with the user account
        $data['pre_signup'] = [];
        $data['pre_signup_complete'] = true;

        $currentMerchant = (new Helper)->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], $data];
        }

        $data = $data + $currentMerchant->toArray();

        if ($currentMerchant->role === 'owner')
        {
            $data['primaryOwner'] = true;
        }
        else
        {
            $data['primaryOwner'] = false;
        }

        $currentMerchantId = $currentMerchant->id;

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            // Fetch merchant details for current merchant
            $data = $data + (new MerchantDetails\Service)->fetchDetails();

            $data["pre_signup"] = (new Merchant\Service)->getPreSignupDetails($currentMerchantId);

            foreach ($merchants as $merchant) {

                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    $data['current'] = $currentMerchantId;

                    $data['tags'] = (new Merchant\Service)->getMerchantTags($currentMerchantId);
                }

                if (((bool) $merchant['activated']) === true)
                {
                    $activated = true;
                }
            }

            $preSignupValues = array_values($data['pre_signup']);

            // This is same as on UserController
            $data['pre_signup_complete'] = array_reduce($preSignupValues, function($carry, $item)
            {
                return $carry and !empty($item);
            }, true);

            // We don't show presignup form for user
            // created before this date
            if ($user->created_at < self::PRE_SIGNUP_TIMESTAMP)
            {
                $data['pre_signup_complete'] = true;
            }

            // There are approx 3k merchants who have not
            // filled "role" or "department", but are
            // already activated.
            if ($activated)
            {
                $data['pre_signup_complete'] = true;
            }

            if ($currentMerchant->role !== 'owner')
            {
                $data['pre_signup_complete'] = true;
            }
        }

        return [[], $data];
    }

    public function loginOnApi(array $input)
    {
        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($input)->send('users/login', 'POST');

        $genericUser = null;

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);
        }

        return [$error, $genericUser];
    }

    public function getUserFromApi($userId)
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $request = new \App\Admin\ApiRequestAny([
                'client_type' => 'admin'
            ]);

            list($error, $data) = $request->send("users-admin/$userId", "GET");
        }
        else
        {
            $request = new \App\Admin\ApiRequestAny();

            list($error, $data) = $request->send("users/$userId", "GET");
        }

        $genericUser = null;

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);
        }

        return [$error, $genericUser];
    }
}
