<?php

namespace Functional\Merchant;

use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\InternationalIntegration;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantInternationalIntegrationTest Extends TestCase
{
    const DEFAULT_MERCHANT_ID = '10000000000000';
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantInternationalIntegrationData.php';

        parent::setUp();
    }

    public function testFetchInternationalVirtualAccounts()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        //Four Currencies are Sent as 4 Objects in Response
        $this->assertCount(count(Gateway::INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES),$content);

        // Assert Keys
        foreach($content as $account){
            $this->assertArrayKeysExist($account,["va_currency","routing_code","routing_type","account_number","beneficiary_name"]);
        }
    }

    public function testFetchInternationalVirtualAccountsByValidVACurrency()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $va_currency = "USD";

        $request['url'] = "/international/virtual_account/" . $va_currency;

        $key = $this->fixtures->create('key', ['merchant_id' => $merchantDetail['merchant_id']]);

        $key = $key->getKey();

        $this->ba->publicAuth('rzp_test_' . $key);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertNotNull($content);

        $this->assertNotNull($content['account']);

        $virtual_account = $content['account'];

        $this->assertArrayKeysExist($virtual_account,["va_currency","routing_code","routing_type","account_number","beneficiary_name"]);

        $this->assertEquals($va_currency, $virtual_account['va_currency']);

    }

    public function testFetchInternationalVirtualAccountsByVACurrencyAmountAndCurrency()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $va_currency = "USD";

        $request['url'] = "/international/virtual_account/" . $va_currency . "?amount=%s&currency=%s";

        $amount = 100;
        $currency = "INR";

        $request['url'] = sprintf($request['url'], $amount, $currency);

        $key = $this->fixtures->create('key', ['merchant_id' => $merchantDetail['merchant_id']]);

        $key = $key->getKey();

        $this->ba->publicAuth('rzp_test_' . $key);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertNotNull($content);

        $this->assertNotNull($content['account']);
        $this->assertNotNull($content['amount']);
        $this->assertNotNull($content['currency']);
        $this->assertNotNull($content['symbol']);

        $virtual_account = $content['account'];

        $this->assertArrayKeysExist($virtual_account,["va_currency","routing_code","routing_type","account_number","beneficiary_name"]);

        $this->assertEquals($va_currency, $virtual_account['va_currency']);

        $this->assertEquals($va_currency, $content['currency']);

        // 10 as Mock Exchange Rate and 3 Percent Markup
        $this->assertEquals(1030, $content['amount']);

        $this->assertEquals("$", $content['symbol']);

    }

    public function testFetchInternationalVirtualAccountsByVACurrencyNotSupported()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $va_currency = "INR";

        $request['url'] = "/international/virtual_account/" . $va_currency;

        $key = $this->fixtures->create('key', ['merchant_id' => $merchantDetail['merchant_id']]);

        $key = $key->getKey();

        $this->ba->publicAuth('rzp_test_' . $key);

        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->sendRequest($request);
            },
            \RZP\Exception\BadRequestException::class,
            "Currency Not Supported for International Bank Transfer");
    }

    private function getBankAccountMockData(){
        return '[{"routing_code":"routing_code","routing_type":"ACH","account_number":"1234567889","beneficiary_name":"GemsGems","va_currency":"USD"}]';
    }
}
