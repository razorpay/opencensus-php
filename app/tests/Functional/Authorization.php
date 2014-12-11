<?php

namespace Tests\Functional;

class Authorization
{
    protected $auth = array();

    protected $type;

    protected $proxy = false;

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
            $dashboardConfig = \Config::get('applications.dasboard');
            $pwd = $dashboardConfig['secret'];
        }

        $this->basicAuth($user, $pwd);

        $this->type = 'app';
    }

    public function proxyAuth($user = 'rzp_test_10000000000000')
    {
        $this->setupAppBasicAuthParams($user);

        $this->proxy = true;
    }

    public function publicAuth($user = 'rzp_test_TheTestAuthKey')
    {
        $this->basicAuth($user, '');

        $this->type = 'public';
    }

    public function privateAuth($user = null, $pwd = null)
    {
        if ($user === null)
        {
            $user = 'rzp_test_TheTestAuthKey';
        }

        if ($pwd === null)
        {
            $pwd = 'TheKeySecretForTests';
        }

        $this->basicAuth($user, $pwd);

        $this->type = 'private';
    }

    public function dashboardAuth($mode = 'test')
    {
        $this->appAuth('rzp_'.$mode, 'DASHBOARD_AUTH_PASS');
    }

    public function getCreds()
    {
        return $this->auth;
    }
}
