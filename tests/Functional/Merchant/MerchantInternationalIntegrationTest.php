<?php

namespace Functional\Merchant;

use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\InternationalIntegration;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Unit\Models\Merchant\Methods\CoreTest;

class MerchantInternationalIntegrationTest Extends TestCase
{
    const DEFAULT_MERCHANT_ID = '10000000000000';
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantInternationalIntegrationData.php';

        parent::setUp();
    }

    protected function addIntlBankTransferMethodForMerchant($intlbankTransferModes, $merchantId)
    {
        $methods = [
            'addon_methods' => [
                'intl_bank_transfer' => $intlbankTransferModes,
            ],
            'disabled_banks' => [],
            'banks' => '[]',
        ];
        return $this->fixtures->edit('methods',$merchantId, $methods);
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

        $intlBankTransferModes = [
            'ach' => 1
        ];
        $methods = $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        //Four Currencies are Sent as 4 Objects in Response
        $this->assertCount(1,$content);

        // Assert Keys
        foreach($content as $account){
            $this->assertArrayKeysExist($account,["va_currency","routing_code","routing_type","account_number","beneficiary_name","bank_name","bank_address","status"]);
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

        $intlBankTransferModes = [
            'ach' => 1
        ];
        $methods = $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantDetail['merchant_id']);

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

        $this->assertArrayKeysExist($virtual_account,["va_currency","routing_code","routing_type","account_number","beneficiary_name","bank_name","bank_address","status"]);

        $this->assertEquals($va_currency, $virtual_account['va_currency']);

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

        $intlBankTransferModes = [
            'ach' => 1
        ];
        $methods = $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantDetail['merchant_id']);

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
            "Currency/Method Not Supported for International Bank Transfer");
    }

    public function testFetchIntlVAWithPreferredRoutingCodeConfigPresent()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData("USD"),
        ]);

        $intlBankTransferModes = [
            'ach' => 1
        ];
        $methods = $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        // Assert Keys
        foreach($content as $account){
            $this->assertArrayKeysExist($account,["va_currency","routing_code","routing_type","account_number","beneficiary_name","bank_name","bank_address","status"]);
        }

        $virtual_account = $content[0];

        // Asserting Routing Code and Type as First Index (Default)
        $this->assertEquals($virtual_account['routing_code'],"026073150");
        $this->assertEquals($virtual_account['routing_type'],"ach_routing_number");
    }

    public function testFetchIntlVAWithPreferredRoutingCodeConfigNotPresentSWIFT()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantDetail['merchant_id'],
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData("SWIFT"),
        ]);

        $intlBankTransferModes = [
            'swift' => 1
        ];
        $methods = $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        // Assert Keys
        foreach($content as $account){
            $this->assertArrayKeysExist($account,["va_currency","routing_code","routing_type","account_number","beneficiary_name","bank_name","bank_address","status"]);
        }

        $virtual_account = $content[0];

        // Asserting Routing Code and Type as First Index (Default)
        $this->assertEquals($virtual_account['routing_code'],"TCCLGB123");
        $this->assertEquals($virtual_account['routing_type'],"bic_swift");
    }

    private function getBankAccountMockData($va_currency = "USD") : string{
        switch($va_currency){
            case "USD":
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "USD", "bank_address": "810 Seventh Avenue, New York, NY 10019, US", "account_number": "0335086498", "routing_details": [{"routing_code": "026073150", "routing_type": "ach_routing_number"}, {"routing_code": "026073008", "routing_type": "wire_routing_number"}], "beneficiary_name": "ALPHA CORP"}]';
            case "GBP":
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "GBP", "bank_address": "12 Steward Street, The Steward Building, London, E1 6FQ, GB", "account_number": "92979037", "routing_details": [{"routing_code": "123456", "routing_type": "sort_code"}], "beneficiary_name": "ALPHA CORP"}]';
            case "SWIFT":
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "SWIFT", "bank_address": "12 Steward Street, The Steward Building, London, E1 6FQ, GB", "account_number": "GB51TCCL12345692979037", "routing_details": [{"routing_code": "TCCLGB123", "routing_type": "bic_swift"}], "beneficiary_name": "ALPHA CORP"}]';
        }
    }
}
