<?php

namespace RZP\Tests\Unit\Models\Merchant\AutoKyc\Bvs\BvsClient;


use RZP\Http\RequestHeader;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsValidationClient;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class BvsValidationClientTest extends TestCase
{

    public function testGetSourceForMetaData()
    {
        $bvsValClient = new BvsValidationClient();
        $internalAppName = "dashboard";
        $request = $this->app['request'];
        $headers = $request->headers;
        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"1");

        $actual = $bvsValClient->getSourceForMetaData($headers,$internalAppName);
        $expected = $internalAppName.(Constant::ADMIN_LOGGED_IN_AS_MERCHANT_MESSAGE);
        $this->assertEquals($expected,$actual);

        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"0");

        $actual = $bvsValClient->getSourceForMetaData($headers,$internalAppName);
        $expected = $internalAppName;
        $this->assertEquals($expected,$actual);

        // reset header
        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"");
    }

    public function testgetActorDetailsForMetaData()
    {
        $bvsValClient = new BvsValidationClient();
        $request = $this->app['request'];

        $headers = $request->headers;
        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"1");
        $headers->set(RequestHeader::X_DASHBOARD_USER_EMAIL,"email");
        $headers->set(RequestHeader::X_DASHBOARD_USER_ID,"id");
        $headers->set(RequestHeader::X_DASHBOARD_USER_ROLE,"owner");

        $expected =[];
        $actual = $bvsValClient->getActorDetailsForMetaData($headers);
        $this->assertEquals($expected,$actual);

        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"0");
        $expected = $this->getExpectedActorDetails();
        $actual = $bvsValClient->getActorDetailsForMetaData($headers);
        $this->assertEquals($expected,$actual);

        // reset headers
        $headers->set(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT,"");
        $headers->set(RequestHeader::X_DASHBOARD_USER_EMAIL,"");
        $headers->set(RequestHeader::X_DASHBOARD_USER_ID,"");
        $headers->set(RequestHeader::X_DASHBOARD_USER_ROLE,"");

    }

    private function getExpectedActorDetails() {
        $expected =[];
        $expected[Constant::ACTOR_EMAIL]   = "email";
        $expected[Constant::ACTOR_ID]      = "id";
        $expected[Constant::ACTOR_ROLE]    = "owner";
        return $expected;
    }
}
