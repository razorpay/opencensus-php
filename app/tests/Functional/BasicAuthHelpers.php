<?php

namespace Tests\Functional;

trait BasicAuthHelpers
{
    /**
     * Sets the key and secret created setUp call as the
     * default to provide basicauth.
     * You can use your key and secret by overriding this
     * function in child classes.
     */
    protected function setupBasicAuthParams($user = null, $pwd = null)
    {
        if ($user === null)
        {
            $user = 'rzp_test_d9c6bf091a1a64cb5678d8c1';
        }

        if ($pwd === null)
        {
            $pwd = 'thisissupersecret';
        }

        $this->auth = array(
               'PHP_AUTH_USER' => $user,
               'PHP_AUTH_PW' => $pwd);
    }

    protected function setupAppBasicAuthParams($user = 'rzp_test', $pwd = 'DASHBOARD_AUTH_PASS')
    {
        $this->setupBasicAuthParams($user, $pwd);
    }

    protected function setupProxyBasicAuthParams($user = 'rzp_test_363e4efa820b0c06208ccd99')
    {
        $this->setupAppBasicAuthParams($user);
        $this->cloud = true;
    }
}
