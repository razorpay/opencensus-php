<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;


use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PersonalPan;
use RZP\Tests\Functional\TestCase;

class PersonalPanTest extends TestCase
{
    protected $dispatcherClass = PersonalPan::class;

    const PERSONAL_PAN_NAME     = "kitty su personal pan";
    const BUSINESS_PAN_NAME     = "kitty su business pan";
    const PERSONAL_PAN_NUMBER   = "AAAPA1234J";

    protected function getMerchantDetailFixture($businessType)
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
            'promoter_pan_name' => self::PERSONAL_PAN_NAME,
            'business_name'     => self::BUSINESS_PAN_NAME,
            'promoter_pan'      => self::PERSONAL_PAN_NUMBER
        ];

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }

    public function testRequestPayload()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);
        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            Constant::PERSONAL_PAN
        );
    }

    protected function verifyRequestPayload($requestPayload, $expectedconfigName)
    {
        $this->assertTrue(isset($requestPayload['details']));

        // assert that the config name matches the expected name
        $this->assertTrue(isset($requestPayload[Constant::CONFIG_NAME]));
        $this->assertEquals($expectedconfigName, $requestPayload[Constant::CONFIG_NAME]);

        // assert that the config class exists
        $configClass = DefaultProcessor::BVS_CONFIG_NAME_SPACE . '\\' . ucfirst(strtolower($expectedconfigName));
        $this->assertTrue(class_exists($configClass));

        $this->assertEquals(self::PERSONAL_PAN_NUMBER, $requestPayload['details'][Constant::PAN_NUMBER]);
        $this->assertEquals(self::PERSONAL_PAN_NAME, $requestPayload['details'][Constant::NAME]);
    }
}
