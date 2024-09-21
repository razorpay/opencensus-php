<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Device;

use RZP\Models\P2p\Preferences\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Models\Upi\Turbo\Core;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\P2p\Preferences\Constants;

use RZP\Models\Admin;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;
use  RZP\Exception\BadRequestValidationFailureException;

class PreferencesTest extends TestCase
{
    use EventsTrait;
    use MetricsTrait;
    use TransactionTrait;
    use TestsWebhookEvents;

    public function testGetGatewayPreferencesWithoutPopularBankList()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('customer', $response);

        $this->assertArrayHasKey('gateways', $response);

        $this->assertArrayHasKey('popular_banks', $response);

        $this->assertEquals(8, count($response['popular_banks']));

        $this->assertArraySelectiveEquals(Constants::getStaticPopularBanksList(), $response['popular_banks']);
    }

    public function testGetGatewayPreferencesWithInvalidCustomerResp()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $payload = [
            'customer_id' => 'random_invalid_customer_id',
        ];
        $response = $helper->getGatewayPreferences($this->gateway, $payload);

        $this->assertArrayNotHasKey('customer', $response);
    }

    public function testGetGatewayPreferences()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $banklist = $this->setPopularBankListInRedis();

        $timeouts = $this->setSDKTimeoutConfigsInRedis(30);

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('customer', $response);

        $this->assertArrayHasKey('gateways', $response);

        $this->assertArrayHasKey('popular_banks', $response);

        $this->assertArrayHasKey('metadata', $response);

        $this->assertEquals(4, count($response['popular_banks']));

        $this->assertArraySelectiveEquals($banklist, $response['popular_banks']);

        $merchant = $this->getDbMerchantById(Fixtures::TEST_MERCHANT);

        $expectedMerchantName = $merchant->getDisplayNameElseName();

        $this->assertEquals($expectedMerchantName, $response['merchant']['display_name']);

        $this->assertArrayHasKey('timeouts', $response);

        $this->assertArraySelectiveEquals($timeouts, $response['timeouts']);

        $this->assertEquals('api', $response['metadata']['X-PG-Service']);

        $this->assertArrayHasKey('features', $response);

        $expectedPayerAccountTypes = [
            Constants::SUPPORTED_PAYER_ACCOUNT_TYPES => Constants::getSupportedPayerAccountTypes()
        ];

        $this->assertArraySelectiveEquals($expectedPayerAccountTypes, $response[Constants::FEATURES]);

        $expectedPayerAccountTypeMappings = Constants::getPayerAccountTypeMappings($this->gateway);

        $this->assertArraySelectiveEquals($expectedPayerAccountTypeMappings, $response[Constants::PAYER_ACCOUNT_TYPE_MAPPINGS]);

        $this->assertArraySelectiveEquals(Constants::getDefaultPrefetchConfigs(), $response[Constants::PREFETCH]);
    }

    public function testGetPrefetchBankAccountDetailsWhenGlobalRedisConfigIsSet()
    {
        $this->setPrefetchBankAccountConfigInRedis();

        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $expectedPreFetchObject = Admin\ConfigKey::get(Admin\ConfigKey::UPI_TURBO_PRE_FETCH_BANK_ACCOUNT);

        $this->assertArraySelectiveEquals($expectedPreFetchObject, $response[Constants::PREFETCH]);
    }

    public function testGetPrefetchBankListWhenMerchantLevelRedisConfigIsSet()
    {
        //Here, along with global config, we also set a merchant level config for MID 10000000000000 (used in the test)
        $merchantLevelPrefetchConfig = [
            Constants::BANKS => [
                [
                    'priority'     => 0,
                    'display_name' => 'Axis'
                ],
                [
                    'priority'     => 1,
                    'display_name' => 'IndusInd'
                ],
            ],
        ];

        $this->setPrefetchBankAccountConfigInRedis($merchantLevelPrefetchConfig);

        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        // We expect that the merchant level configs are returned even if there are global and default configs present
        $expectedPreFetchBankList = $merchantLevelPrefetchConfig;

        $this->assertArraySelectiveEquals($expectedPreFetchBankList, $response[Constants::PREFETCH][Constants::BANKS]);
    }

    public function testCreateBankAccountForCustomerForPreferences()
    {
        $helper = $this->getPreferencesHelper()->setMerchantOnAuth(true);;

        $helper->withSchemaValidated();

        $customer_id = $this->fixtures->customer->getPublicId();

        $this->fixtures->enableFeatures("tpv");

        $features = $this->fixtures->merchant->getEnabledFeatures();

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, []);

        $helper = $this->getPreferencesHelper();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('tpv', $response);

        $this->assertArrayHasKey('is_tpv', $response);

        $this->assertArrayHasKey('restrict_bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][0]);
    }

    public function testCreateMultipleBankAccountForCustomerForPreferences()
    {
        $helper = $this->getPreferencesHelper()->setMerchantOnAuth(true);;

        $helper->withSchemaValidated();

        $customer_id = $this->fixtures->customer->getPublicId();

        $this->fixtures->enableFeatures("tpv");

        $features = $this->fixtures->merchant->getEnabledFeatures();

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, []);

        $bank_account2 = [
            "ifsc_code" => "ICIC0001208",
            'account_number' => '04030403040305',
            'beneficiary_name'=> 'RATN0000002',
            "beneficiary_address1"  => "address 1",
            "beneficiary_address2"  => "address 2",
            "beneficiary_address3"  => "address 3",
            "beneficiary_address4"  => "address 4",
            "beneficiary_email"     => "random@email.com",
            "beneficiary_mobile"    => "9988776655",
            "beneficiary_city"      =>"Kolkata",
            "beneficiary_state"     => "WB",
            "beneficiary_country"   => "IN",
            "beneficiary_pin"      =>"123456"
        ];

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, $bank_account2);

        $helper = $this->getPreferencesHelper();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('tpv', $response);

        $this->assertArrayHasKey('is_tpv', $response);

        $this->assertArrayHasKey('restrict_bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][0]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][1]);
    }

    public function testGetGatewayPreferencesWithInvalidCustomerIdOnTPVMerchant()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $this->expectException(BadRequestValidationFailureException::class);

        $this->fixtures->enableFeatures("tpv");

        $helper = $this->getPreferencesHelper();

        $content = [
            'customer_id' => $this->fixtures->customer->getPublicId()."xyz",
        ];

        $response = $helper->getGatewayPreferences($this->gateway, $content);

        $this->expectExceptionMessage($this->fixtures->customer->getPublicId()."xyz is not a customer id.");
    }

    public function setPopularBankListInRedis()
    {
        $banklist = [
                        [
                            'priority'  => '1',
                            'iin'       => '119753',
                        ],
                        [
                           'priority'  => '2',
                           'iin'       => '246894',
                        ],
                        [
                           'priority'  => '3',
                           'iin'       => '607152',
                        ],
                        [
                           'priority'  => '4',
                           'iin'       => '123333',
                        ]
                   ];

        (new Admin\Service)->setConfigKeys([
               Admin\ConfigKey::UPI_TURBO_POPULAR_BANK_LIST => $banklist,
        ]);

        return $banklist;
    }

    public function setSDKTimeoutConfigsInRedis($oliveTimeout = 0)
    {
        $sdkTimeoutConfigs = [
            Constants::OLIVE_SDK_TIMEOUT => $oliveTimeout
        ];

        (new Admin\Service)->setConfigKeys([
               Admin\ConfigKey::UPI_TURBO_SDK_TIMEOUTS => $sdkTimeoutConfigs
        ]);

        return $sdkTimeoutConfigs;
    }

    public function testErrorMappingHashInPreferencesResponse()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey(Entity::ERROR_MAPPING_HASH, $response);

        [$errorMappings, $fileHash] = (new Core())->generateTurboErrorMappings([Gateway::UPI_AXISOLIVE]);

        $this->assertEquals($fileHash, $response[Entity::ERROR_MAPPING_HASH]);
    }

    public function setPrefetchBankAccountConfigInRedis($merchantConfig = [])
    {
        $config = [
            Constants::CONSENT_MESSAGE    => 'Fetch all my accounts from top banks right now!',
            Constants::FETCH_TIMEOUT      => 10,
            Constants::FETCH_RETRY        => 1,
            Constants::FETCH_CONCURRENT   => 5,
            Constants::BANKS              => [
                [
                    'priority' => 0,
                    'display_name' => 'SBI'
                ],
                [
                    'priority' => 1,
                    'display_name' => 'HDFC'
                ],
            ],
        ];

        if (empty($merchantConfig) === false) {
            $config['10000000000000'] = $merchantConfig;
        }

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::UPI_TURBO_PRE_FETCH_BANK_ACCOUNT => $config
                                           ]);

    }
}
