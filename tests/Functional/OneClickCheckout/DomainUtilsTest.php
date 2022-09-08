<?php

namespace Functional\OneClickCheckout;


use Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;

class DomainUtilsTest extends TestCase
{
    public function testVerifyNonRZPDomain()
    {
        $domain = "https://dev.razorpay.com";
        self::assertNotTrue(DomainUtils::verifyNonRZPDomain($domain));
    }

    public function testSendExternalRequest()
    {
        $ex = null;
        try
        {
            DomainUtils::sendExternalRequest("https://192.168.1.2");
        }
        catch (\Exception $e)
        {
            $ex = $e;
        }
        self::assertNotNull($ex);
    }

    public function testSendRequestForRestrictedUrls()
    {
        $localhostBypass = [
            "http://2130706433/",
            "http://0177.0.0.1/",
            "http://o177.0.0.1/",
            "http://0o177.0.0.1/",
            "http://q177.0.0.1/ ",
            "http://[0:0:0:0:0:ffff:127.0.0.1]",
            "http://127.1",
            "http://127.0.1",
        ];
        foreach ($localhostBypass as $bypass)
        {
            $ex = null;
            try {
                DomainUtils::sendExternalRequest($bypass);
            } catch (Exception $e)
            {
                $ex = $e;
            }
            self::assertNotNull($ex);
        }
    }
}
