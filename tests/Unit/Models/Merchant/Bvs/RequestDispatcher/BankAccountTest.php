<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\BankAccount;
use RZP\Tests\Functional\TestCase;

class BankAccountTest extends TestCase
{
    protected $dispatcherClass = BankAccount::class;

    const PERSONAL_PAN_NAME = "kitty su personal pan";
    const BUSINESS_PAN_NAME = "kitty su business pan";

    protected function getMerchantDetailFixture($businessType)
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
            'promoter_pan_name' => self::PERSONAL_PAN_NAME,
            'business_name'     => self::BUSINESS_PAN_NAME,
        ];

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }

    public function testRequestPayloadForUnregistered()
    {
        $merchantDetail = $this->getMerchantDetailFixture(11);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::PERSONAL_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_PERSONAL_PAN
        );
    }

    public function testRequestPayloadForPrivateLtd()
    {
        $merchantDetail = $this->getMerchantDetailFixture(4);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN
        );
    }

    public function testRequestPayloadForPublicLtd()
    {
        $merchantDetail = $this->getMerchantDetailFixture(4);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN
        );
    }

    public function testRequestPayloadForLLP()
    {
        $merchantDetail = $this->getMerchantDetailFixture(4);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN
        );
    }

    public function testRequestPayloadForPartnership()
    {
        $merchantDetail = $this->getMerchantDetailFixture(4);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN
        );
    }

    public function testRequestPayloadForHUF()
    {
        $merchantDetail = $this->getMerchantDetailFixture(13);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_PAN
        );
    }

    public function testRequestPayloadForProprietorship()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);
        $bankAccount = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail);

        $requestPayload = $bankAccount->getRequestPayload();

        $this->verifyRequestPayload(
            $requestPayload,
            [self::BUSINESS_PAN_NAME, self::PERSONAL_PAN_NAME],
            Constant::BANK_ACCOUNT_WITH_BUSINESS_OR_PROMOTER_PAN
        );
    }

    protected function verifyRequestPayload($requestPayload, $expectedNames, $expectedconfigName)
    {
        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::ACCOUNT_HOLDER_NAMES]));

        $accountHolderNames = $requestPayload['details'][Constant::ACCOUNT_HOLDER_NAMES];

        $this->assertEquals(count($expectedNames), count($accountHolderNames));

        $diff = array_diff($expectedNames, $requestPayload['details'][Constant::ACCOUNT_HOLDER_NAMES]);
        $this->assertTrue(empty($diff), "account_holder_names array not matching");

        // assert that the config name matches the expected name
        $this->assertTrue(isset($requestPayload[Constant::CONFIG_NAME]));
        $this->assertEquals($expectedconfigName, $requestPayload[Constant::CONFIG_NAME]);

        // assert that the config class exists
        $configClass = DefaultProcessor::BVS_CONFIG_NAME_SPACE . '\\' . ucfirst(strtolower($expectedconfigName));
        $this->assertTrue(class_exists($configClass));
    }

}
