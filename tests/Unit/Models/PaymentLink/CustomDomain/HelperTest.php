<?php

namespace Unit\Models\PaymentLink\CustomDomain;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Models\PaymentLink\CustomDomain\Helper;

class HelperTest extends BaseTest
{
    const MERCHANT_ID = "10000000000";
    const DOMAIN_NAME = "razorpay.in";

    /**
     * @group nocode_cds
     * @group nocode_cds_helper
     * @return void
     */
    public function testCaching()
    {
        $prefix = "TESTING";
        $ttl = 1;

        $key = $prefix . ":DOMAIN:" . self::DOMAIN_NAME;

        Config::shouldReceive("get")->with("services.custom_domain_service.cache.prefix")->andReturn($prefix);
        Config::shouldReceive("get")->with("services.custom_domain_service.cache.ttl")->andReturn($ttl);

        Cache::shouldReceive("put")->withAnyArgs()->andReturn(true);

        // cache a domain
        Helper::cacheDomain([Helper::DOMAIN_NAME_KEY => self::DOMAIN_NAME, Helper::MERCHANT_ID_KEY => self::MERCHANT_ID]);


        Cache::shouldReceive("get")->with($key)->andReturn(self::MERCHANT_ID);

        // get from cache
        $this->assertEquals(self::MERCHANT_ID, Helper::getDomainFromCache(self::DOMAIN_NAME));
    }

    /**
     * @group nocode_cds
     * @group nocode_cds_helper
     * @return void
     */
    public function testCachingMiss()
    {
        $prefix = "TESTING";
        $key = $prefix . ":DOMAIN:" . self::DOMAIN_NAME;


        Config::shouldReceive("get")->with("services.custom_domain_service.cache.prefix")->andReturn($prefix);
        Cache::shouldReceive("get")->with($key)->andReturn(null);

        // get from cache
        $this->assertNull(Helper::getDomainFromCache(self::DOMAIN_NAME));
    }
}
