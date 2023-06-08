<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Base\EsDao;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Models\Merchant\InternationalIntegration;

class CheckoutInternationalPreferencesTest extends TestCase {

    use PaymentTrait;
    use CreatesInvoice;
    use DbEntityFetchTrait;
    use MocksRedisTrait;
    use MocksRazorx;

    const DEFAULT_MERCHANT_ID     = '10000000000000';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }

    public function testGetCheckoutPersonalisationForNonLoggedInUserWithInternationalContact()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInInternationalUser()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInAustralianUsers()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInEuropeanUsers()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInIndianUsers()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    protected function addIntlBankTransferMethodForMerchant($intlBankTransferModes, $merchantId)
    {
        $methods = [
            'addon_methods' => [
                'intl_bank_transfer' => $intlBankTransferModes,
            ],
            'disabled_banks' => [],
            'banks' => '[]',
        ];
        return $this->fixtures->edit('methods',$merchantId, $methods);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInUnitedStatesUsers()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }


    public function testGetCheckoutPreferencesForCurrencyCloudACHEnabledWithPL()
    {
        $intlBankTransferModes = [
            'ach' => 1,
            'swift'=> 0,
        ];
        $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes,self::DEFAULT_MERCHANT_ID);
        $order = $this->fixtures->order->create(['product_type' => 'payment_link_v2']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest($testData);
        $this->assertEquals(1,$response['methods']['intl_bank_transfer']['usd']);
        $this->assertEquals(0,$response['methods']['intl_bank_transfer']['swift']);
    }

    public function testGetCheckoutPreferencesForCurrencyCloudSWIFTEnabledWithPL()
    {
        $intlBankTransferModes = [
            'ach' => 0,
            'swift'=> 1,
        ];
        $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes,self::DEFAULT_MERCHANT_ID);
        $order = $this->fixtures->order->create(['product_type' => 'payment_link_v2']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest($testData);

        $this->assertEquals(0,$response['methods']['intl_bank_transfer']['usd']);
        $this->assertEquals(1,$response['methods']['intl_bank_transfer']['swift']);
    }

    public function testGetCheckoutPreferencesForCurrencyCloudEnabledWithoutPL()
    {
        $intlBankTransferModes = [
            'ach' => 1,
            'swift'=> 0,
        ];
        $this->addIntlBankTransferMethodForMerchant($intlBankTransferModes,self::DEFAULT_MERCHANT_ID);
        // product type is not 'payment_link_v2'
        $order = $this->fixtures->order->create(['product_type' => 'payment_page']);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Gateway::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => "1029329285-19298",
            InternationalIntegration\Entity::NOTES => [],
            InternationalIntegration\Entity::BANK_ACCOUNT => $this->getBankAccountMockData(),
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest($testData);

        $this->assertNotNull($response['methods']['intl_bank_transfer']);
        $this->assertEmpty($response['methods']['intl_bank_transfer']);
    }

    public function testGetCheckoutPreferencesForCurrencyCloudNotEnabled()
    {
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $response = $this->startTest($testData);
        $this->assertNotNull($response['methods']['intl_bank_transfer']);
        $this->assertEquals(0,$response['methods']['intl_bank_transfer']['usd']);
        $this->assertEquals(0,$response['methods']['intl_bank_transfer']['swift']);
    }

    private function getBankAccountMockData($va_currency = "USD") : string{
        switch($va_currency){
            case "USD":
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "USD", "bank_address": "810 Seventh Avenue, New York, NY 10019, US", "account_number": "0335086498", "routing_details": [{"routing_code": "026073150", "routing_type": "ach_routing_number"}, {"routing_code": "026073008", "routing_type": "wire_routing_number"}], "beneficiary_name": "ALPHA CORP"}]';
            case "GBP":
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "GBP", "bank_address": "12 Steward Street, The Steward Building, London, E1 6FQ, GB", "account_number": "92979037", "routing_details": [{"routing_code": "123456", "routing_type": "sort_code"}], "beneficiary_name": "ALPHA CORP"}]';
            default:
                return '[{"bank_name": "Community Federal Savings Bank", "va_currency": "SWIFT", "bank_address": "12 Steward Street, The Steward Building, London, E1 6FQ, GB", "account_number": "GB51TCCL12345692979037", "routing_details": [{"routing_code": "TCCLGB123", "routing_type": "bic_swift"}], "beneficiary_name": "ALPHA CORP"}]';
        }
    }


}
