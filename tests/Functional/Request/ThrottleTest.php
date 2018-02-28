<?php

namespace RZP\Tests\Functional\Request;

/**
 * End to end functional test to assert rate limiting is working fine.
 */
class ThrottleTest extends \RZP\Tests\AbstractThrottleTest
{
    public function testGetOrderWhenNotThrottled()
    {
    }

    public function testGetOrderWhenThrottled()
    {
        // By making specific setting's max bucket size 0.
    }

    public function testGetOrderWhenRedisErrors()
    {
    }

    public function testGetOrderWhenRedisSettingsMissing()
    {
    }

    public function testGetOrderWhenSpecificProxyAuthSettingsAndThrottled()
    {
        // Similar to testGetOrderWhenThrottled and with different settings
    }
}
