<?php

namespace Tests\Functional;

class Authorization
{
    protected $test;

    protected $auth = array();

    protected $type;

    protected $proxy = false;

    protected $key = null;
    protected $secret = null;

    protected $defaultKey = 'rzp_test_TheTestAuthKey';
    protected $defaultSecret = 'TheKeySecretForTests';

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
        $this->auth = array(
               'PHP_AUTH_USER' => $user,
               'PHP_AUTH_PW' => $pwd);
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

    public function publicAuth($key = null)
    {
        $this->type = 'public';

        if ($key === null)
        {
            $this->key = $this->defaultKey;
            $key = $this->defaultKey;
        }
        else
        {
            $this->key = $key;
        }

        $this->basicAuth($key, '');
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

    public function publicLiveAuth()
    {
        $this->publicAuth('rzp_live_TheLiveAuthKey');
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
            $this->setSecret($this->defaultSecret);
            $secret = $this->defaultSecret;
        }
        else
        {
            $this->setSecret($secret);
        }

        $this->basicAuth($key, $secret);
    }

    public function dashboardAuth($mode = 'test')
    {
        $this->appAuth('rzp_'.$mode, 'put dashboard pass here');
    }

    public function noAuth()
    {
        $this->type = '';

        $this->basicAuth(null, null);
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
}
