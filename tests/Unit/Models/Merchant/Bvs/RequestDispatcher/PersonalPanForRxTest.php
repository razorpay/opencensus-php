<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;


use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\BankingAccount\Activation\Detail\Validator;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PersonalPanForBankingAccount;

class PersonalPanForRxTest extends TestCase
{
    protected $dispatcherClass = PersonalPanForBankingAccount::class;

    const BUSINESS_PAN_NAME     = "kitty su business pan";
    const PERSONAL_PAN_NUMBER   = "AAAPA1234J";

    protected function getMerchantDetailFixture()
    {
        return $this->fixtures->create('merchant_detail:valid_fields');
    }

    public function testRequestPayload()
    {
        $merchantDetail = $this->getMerchantDetailFixture();

        $mid = $merchantDetail->getId();

        $bankingAccount = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId1',
            'account_type'  => 'current',
            'merchant_id'   => $mid,
        ]);

        $bankingAccountId = $bankingAccount->getId();

        $activationDetails = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccountId,
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'business_pan'              => self::PERSONAL_PAN_NUMBER,
            'merchant_poc_name'         => self::BUSINESS_PAN_NAME,
            'sales_team'                => Validator::SME,
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $activationDetails);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            Constant::PERSONAL_PAN
        );
    }

    protected function verifyRequestPayload($requestPayload, $expectedconfigName)
    {
        $this->assertTrue(isset($requestPayload['details']));

        $this->assertEquals(Constant::RX, $requestPayload[Constant::PLATFORM]);

        // assert that the config name matches the expected name
        $this->assertTrue(isset($requestPayload[Constant::CONFIG_NAME]));
        $this->assertEquals($expectedconfigName, $requestPayload[Constant::CONFIG_NAME]);

        // assert that the config class exists
        $configClass = DefaultProcessor::BVS_CONFIG_NAME_SPACE . '\\' . ucfirst(strtolower($expectedconfigName));
        $this->assertTrue(class_exists($configClass));

        $this->assertEquals(self::PERSONAL_PAN_NUMBER, $requestPayload['details'][Constant::PAN_NUMBER]);
        $this->assertEquals(self::BUSINESS_PAN_NAME, $requestPayload['details'][Constant::NAME]);
    }
}
