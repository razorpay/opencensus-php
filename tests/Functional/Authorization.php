<?php

namespace RZP\Tests\Functional;

class Authorization
{
    protected $test;

    protected $auth = array();

    protected $type;

    protected $proxy = false;

    protected $key = null;
    protected $secret = null;
    protected $account = null;

    protected $defaultKey = 'rzp_test_TheTestAuthKey';
    protected $defaultSecret = 'TheKeySecretForTests';
    protected $defaultDeviceToken = 'authentication_token';

    protected $defaultToken = 'SecretTokenForRazorpayAdminAuthentication';

    protected $defaultAccountId = 'acc_10000000000001';

    public function __construct($test)
    {
        $this->test = $test;
    }

    /**
     * Sets the key and secret created setUp call as the
     * default to provide basicauth.
     * You can use your key and secret by overriding this
     * function in child classes.
     */
    public function basicAuth($user = null, $pwd = null)
    {
        $this->auth = [
            'PHP_AUTH_USER' => $user,
            'PHP_AUTH_PW' => $pwd
        ];
    }

    public function appAuth($user = 'rzp_test', $pwd = '')
    {
        if ($pwd === '')
        {
            $dashboardConfig = \Config::get('applications.dashboard');
            $pwd = $dashboardConfig['secret'];
        }

        $this->basicAuth($user, $pwd);

        $this->type = 'app';
    }

    public function appAuthLive($pwd = '')
    {
        $this->appAuth('rzp_live', $pwd);
    }

    public function appAuthTest($pwd = '')
    {
        $this->appAuth('rzp_test', $pwd);
    }

    public function proxyAuth($user = 'rzp_test_10000000000000')
    {
        $this->appAuth($user);

        $this->proxy = true;
    }

    public function proxyAuthTest()
    {
        $this->proxyAuth();
    }

    public function proxyAuthLive()
    {
        $this->proxyAuth('rzp_live_10000000000000');
    }

    public function publicAuth($key = null)
    {
        $this->type = 'public';

        if ($key === null)
        {
            // $this->key = $this->defaultKey;
            // $key = $this->defaultKey;
            if ($this->key === null)
            {
                $this->key = $this->defaultKey;
                $key = $this->defaultKey;
            }
            else
            {
                $key = $this->key;
            }
        }
        else
        {
            $this->key = $key;
        }

        $this->basicAuth($key, '');
    }

    public function deviceAuth($key = null, $secret = null)
    {
        $this->type = 'device';

        if ($key === null)
        {
            $key = $this->defaultKey;

            $this->key = $key;
        }

        if ($secret === null)
        {
            $secret = $this->defaultDeviceToken;
        }

        $this->setSecret($secret);

        $this->basicAuth($key, $secret);
    }

    public function publicCallbackAuth()
    {
        $this->noAuth();

        $this->type = 'public_callback';
    }

    public function publicTestAuth()
    {
        $this->publicAuth();
    }

    public function publicLiveAuth($key = 'rzp_live_TheLiveAuthKey')
    {
        $this->publicAuth($key);
    }

    public function privateAuth($key = null, $secret = null)
    {
        $this->type = 'private';

        if ($key === null)
        {
            $key = $this->defaultKey;

            $this->key = $key;
        }

        if ($secret === null)
        {
            $secret = $this->defaultSecret;
        }

        $this->setSecret($secret);

        $this->basicAuth($key, $secret);
    }

    public function adminAuth($mode = 'test', $token = null)
    {
        $this->type = 'admin';

        $this->key = "rzp_{$mode}_admin";

        if ($token === null)
        {
            $token = $this->defaultToken;
        }

        $this->setSecret($token);

        $this->basicAuth($this->key, $token);
    }

    public function dashboardAuth($mode = 'test')
    {
        $this->appAuth('rzp_'.$mode, 'put dashboard pass here');
    }

    public function cronAuth($mode = 'test')
    {
        $cronConfig = \Config::get('applications.cron');

        $pwd = $cronConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function noAuth()
    {
        $this->type = 'direct';

        $this->basicAuth(null, null);
    }

    public function directAuth()
    {
        $this->type = 'direct';

        $this->basicAuth(null, null);
    }

    /**
     * Adds account auth to a request
     */
    public function addAccountAuth($accountId = null, $user = null, $pwd = null)
    {
        if ($accountId === null)
        {
            $accountId = $this->defaultAccountId;
        }

        $this->account = $accountId;

        $this->privateAuth($user, $pwd);
    }

    /**
     * Adds admin auth headers to a request
     */
    public function addAdminAuthHeaders(string $orgId)
    {
        $this->adminHeaders = ['X-Org-Id' => $orgId];
    }

    /**
     * Remove account auth
     */
    public function deleteAccountAuth()
    {
        $this->account = null;
    }

    public function getCreds()
    {
        return $this->auth;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isPublicAuth()
    {
        return ($this->type === 'public');
    }

    public function isPrivateAuth()
    {
        return ($this->type === 'private');
    }

    public function isAccountAuth()
    {
        return (empty($this->getAccountHeader()) === false);
    }

    public function isAdminAuth()
    {
        return ($this->type === 'admin');
    }

    public function getAccountHeader()
    {
        $headers = [];

        if ($this->account !== null)
        {
            $headers = [
                'X-Razorpay-Account'    => $this->account
            ];
        }

        return $headers;
    }

    public function getAdminHeaders()
    {
        return $this->adminHeaders;
    }

    public function getKey()
    {
        return $this->auth['PHP_AUTH_USER'];
    }

    public function getSecret()
    {
        return $this->auth['PHP_AUTH_PW'];
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function setDefaultKey($key)
    {
        $this->defaultKey = $key;

        return $this;
    }

    public function setDefaultSecret($secret)
    {
        $this->defaultSecret = $secret;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    public function setKeyAndSecret($key, $secret)
    {
        $this->setKey($key);

        $this->setSecret($secret);

        return $this;
    }

    public function isSecretNull()
    {
        return ($this->secret === null);
    }

    public function getMode()
    {
        $key = $this->getKey();

        $mode = explode('_', $key)[1];

        return $mode;
    }

    public function getAppAuthKeyForMode()
    {
        $mode = $this->getMode();

        assert(($mode === 'live') or ($mode === 'test'));

        return 'rzp_'.$mode;
    }

    public function appAuthMode()
    {
        $key = $this->getAppAuthKeyForMode();

        $this->appAuth($key);
    }

    public function getAdmin()
    {
        $token = $this->secret;

        $this->admin = (new \RZP\Models\Admin\Admin\Token\Repository)->findOrFailToken($token)->admin;

        return $this->admin;
    }
}
