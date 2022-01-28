<?php


namespace Unit\Models\Merchant\Bvs\ManualVerificationRequestDispatcher;


use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher\NeedsClarification;
use RZP\Tests\Functional\TestCase;

class NeedsClarificationTest extends TestCase
{
    protected $dispatcherClass  = NeedsClarification::class;
    const COMPANY_PAN_NAME     = "kitty su business pan";
    const ACTION_ID             = "SOME_ACTION";
    const PERSONAL_PAN_NAME     = "Manthan";
    const BUSINESS_PAN_NUMBER   = "ABCD1234X";

    public function testRequestPayload()
    {
        $merchant = $this->fixtures->create(
            'merchant',
            [   'id'    => '10000000000002',
                'email' => 'razorpay@razorpay.com'
            ]
        );

        $merchantAttributes = [
            'promoter_pan_name' => self::PERSONAL_PAN_NAME,
            'company_pan_name'  => self::COMPANY_PAN_NAME
        ];

        $merchant_details = $this->fixtures->create(
            'merchant_detail:valid_fields',
            $merchantAttributes
        );

        $notes = [
           "reason" => "A",
           "reason2" => "B"
        ];

        $merchant->merchantDetail = $merchant_details;

        $dispatcher = new $this->dispatcherClass($merchant, $notes );

        $requestPayload = $dispatcher->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            Constant::COMMON_MANUAL_VERIFICATION,
            $notes
        );
    }

    protected function verifyRequestPayload($requestPayload, $expectedconfigName, $notes)
    {
        $this->assertTrue(isset($requestPayload['details']));

        // assert that the config name matches the expected name
        $this->assertTrue(isset($requestPayload[Constant::CONFIG_NAME]));
        $this->assertEquals($expectedconfigName, $requestPayload[Constant::CONFIG_NAME]);

        // assert that the config name matches the expected name
        $this->assertEquals($expectedconfigName, $requestPayload[Constant::CONFIG_NAME]);

        // assert that the state of merchant details is set to needs clarification
        $this->assertEquals(Constant::NEEDS_CLARIFICATION, $requestPayload[Constant::DETAILS][Constant::DATA][Constant::STATUS]);

        // assert that notes is correctly assigned
        $this->assertEquals($notes, $requestPayload[Constant::DETAILS][Constant::DATA][Constant::NOTES]);

        // assert that merchant details like Company Pan Name is correctly assigned
        $this->assertEquals(self::COMPANY_PAN_NAME, $requestPayload[Constant::DETAILS][Constant::DATA][Constant::MERCHANT_DATA][Constant::KYC_DETAILS][Constant::PAN_NAME]);

        // assert that the config class exists
        $configClass = DefaultProcessor::BVS_CONFIG_NAME_SPACE . '\\' . ucfirst(strtolower($expectedconfigName));
        $this->assertTrue(class_exists($configClass));
    }
}
