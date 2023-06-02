<?php

namespace RZP\Tests\Functional\International;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MccMarkdownTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MccMarkdownTestData.php';
        parent::setUp();
    }

    // mcc method specific config is fetched from table
    public function testMccFetchedFromTable()
    {
        $merchant = $this->fixtures->merchant->create(["id" => '10000merchant6']);
        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant['id'],
            'contact_mobile' => '1234567890',
            'contact_email' => 'user1@email.com',
        ]);
        $mccMarkdownPercent = "10";

        $config = [
            'mcc_markdown_percentage' => "10",
            'intl_bank_transfer_ach_mcc_markdown_percentage' => "20",
            'intl_bank_transfer_swift_mcc_markdown_percentage' => "30",
        ];
        $this->fixtures->merchant->addMccMarkdownPaymentConfig($mccMarkdownPercent, $merchantDetail['merchant_id'], $config);
        $testData = [
            [
                "method" => "intl_bank_transfer",
                "wallet" => "ach",
                "expected_mcc_markdown" => $config['intl_bank_transfer_ach_mcc_markdown_percentage']
            ],
            [
                "method" => "intl_bank_transfer",
                "wallet" => "swift",
                "expected_mcc_markdown" => $config['intl_bank_transfer_swift_mcc_markdown_percentage']
            ],
            [
                "method" => "cards",
                "wallet" => "",
                "expected_mcc_markdown" => $config['mcc_markdown_percentage']
            ],
        ];
        foreach ($testData as $data) {
            $payment = $this->fixtures->create('payment:authorized', [
                'merchant_id' => $merchantDetail['merchant_id'],
                'method' => $data['method'],
                'wallet' => $data['wallet']
            ]);
            $response = $merchant->getMccMarkdownMarkdownPercentage($payment);
            $this->assertEquals($data['expected_mcc_markdown'], $response,"test case failed for method ".$data['method']." ".$data['wallet']);
        }
    }

    //fetch default methods level config when value is not there in table or redis
    public function testMccFetchedFromTableWithPaymentConfigNotPresent()
    {
        $merchant = $this->fixtures->merchant->create(["id" => '10000merchant2']);
        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant['id'],
            'contact_mobile' => '1234567890',
            'contact_email' => 'user1@email.com',
        ]);
        $mccMarkdownPercent = "10";

        $config = [
            'mcc_markdown_percentage' => $mccMarkdownPercent,
        ];
        $this->fixtures->merchant->addMccMarkdownPaymentConfig($mccMarkdownPercent, $merchantDetail['merchant_id'], $config);
        $testData = [
            [
                "method" => "intl_bank_transfer",
                "wallet" => "ach",
                "expected_mcc_markdown" => \RZP\Models\Merchant\Entity::DEFAULT_INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE
            ],
            [
                "method" => "intl_bank_transfer",
                "wallet" => "swift",
                "expected_mcc_markdown" => \RZP\Models\Merchant\Entity::DEFAULT_INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE
            ],
            [
                "method" => "cards",
                "wallet" => "",
                "expected_mcc_markdown" => $config['mcc_markdown_percentage']
            ],
        ];
        foreach ($testData as $data) {
            $payment = $this->fixtures->create('payment:authorized', [
                'merchant_id' => $merchantDetail['merchant_id'],
                'method' => $data['method'],
                'wallet' => $data['wallet']
            ]);
            $response = $merchant->getMccMarkdownMarkdownPercentage($payment);
            $this->assertEquals($data['expected_mcc_markdown'], $response,"test case failed for method ".$data['method']." ".$data['wallet']);
        }
    }

    //fetch default hardcoded value when config is not present in the table and redis
    public function testMccFetchedFromTableWithConfigNotPresent()
    {
        $merchant = $this->fixtures->merchant->create(["id" => '10000merchant3']);
        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant['id'],
            'contact_mobile' => '1234567890',
            'contact_email' => 'user1@email.com',
        ]);
        $testData = [
            [
                "method" => "intl_bank_transfer",
                "wallet" => "ach",
                "expected_mcc_markdown" => \RZP\Models\Merchant\Entity::DEFAULT_INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE
            ],
            [
                "method" => "intl_bank_transfer",
                "wallet" => "swift",
                "expected_mcc_markdown" => \RZP\Models\Merchant\Entity::DEFAULT_INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE
            ],
            [
                "method" => "cards",
                "wallet" => "",
                "expected_mcc_markdown" => \RZP\Models\Merchant\Entity::DEFAULT_MCC_MARKDOWN_PERCENTAGE
            ],
        ];
        foreach ($testData as $data) {
            $payment = $this->fixtures->create('payment:authorized', [
                'merchant_id' => $merchantDetail['merchant_id'],
                'method' => $data['method'],
                'wallet' => $data['wallet']
            ]);
            $response = $merchant->getMccMarkdownMarkdownPercentage($payment);
            $this->assertEquals($data['expected_mcc_markdown'], $response,"test case failed for method ".$data['method']." ".$data['wallet']);
        }
    }

    // fetch default markdown when payment object is not passed
    public function testDefaultMccFetchedFromDefault()
    {
        $merchant = $this->fixtures->merchant->create(["id" => '10000merchant4']);
        $response = $merchant->getMccMarkdownMarkdownPercentage();
        $this->assertEquals(\RZP\Models\Merchant\Entity::DEFAULT_MCC_MARKDOWN_PERCENTAGE, $response);
    }

    //fetch mcc markdown from table
    public function testDefaultMccFetchedFromTable()
    {
        $merchant = $this->fixtures->merchant->create(["id" => '10000merchant5']);
        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant['id'],
            'contact_mobile' => '1234567890',
            'contact_email' => 'user1@email.com',
        ]);
        $config = [
            'mcc_markdown_percentage' => "10",
        ];
        $this->fixtures->merchant->addMccMarkdownPaymentConfig(4, $merchantDetail['merchant_id'], $config);
        $response = $merchant->getMccMarkdownMarkdownPercentage();
        $this->assertEquals($config['mcc_markdown_percentage'], $response);
    }

    // test the set config
    public function testCreateMccConfig()
    {
        $this->ba->proxyAuth();
        $expectedData = $this->testData[__FUNCTION__];
        $response = $this->startTest();
        $this->assertEquals($expectedData['request']['content']['config'], $response['config']);
    }

    // test the set config
    public function testCreateMccConfigWithoutMandatoryFields()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    // test the set method specific config
    public function testCreateMccMethodSpecificConfig()
    {
        $this->ba->proxyAuth();
        $expectedData = $this->testData[__FUNCTION__];
        $response = $this->startTest();
        $this->assertEquals($expectedData['request']['content']['config'], $response['config']);
    }

    //test update config
    public function testUpdateConfig()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $config = $this->fixtures->create('config', [
            'type' => 'mcc_markdown',
            'id' => 'HObznkBUFSpME2',
            'is_default' => false,
            'merchant_id' => $merchantDetail['merchant_id'],
            'name' => 'mcc_markdown',
            'config' => '{"mcc_markdown_percentage" : "2",
                "intl_bank_transfer_ach_mcc_markdown_percentage" : "1",
                "intl_bank_transfer_swift_mcc_markdown_percentage" : "1"}']);

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $config->getPublicId();

        $expectedConfig = $this->testData[__FUNCTION__]['request']['content']['config'];
        $expectedConfig['intl_bank_transfer_ach_mcc_markdown_percentage'] = "1";
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $response = $this->startTest();
        $this->assertEquals($expectedConfig, $response['config']);
        $this->assertEquals($config->getPublicId(), $this->testData[__FUNCTION__]['response']['content']['id']);

    }

    public function testUpdateMccConfigWithoutMandatoryFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $config = $this->fixtures->create('config', [
            'type' => 'mcc_markdown',
            'id' => 'HObznkBUFSpME2',
            'is_default' => false,
            'merchant_id' => $merchantDetail['merchant_id'],
            'name' => 'mcc_markdown',
            'config' => '{"mcc_markdown_percentage" : "2",
                "intl_bank_transfer_ach_mcc_markdown_percentage" : "1",
                "intl_bank_transfer_swift_mcc_markdown_percentage" : "1"}']);

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateMccConfigMandatoryFieldsButInvalidValues()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $config = $this->fixtures->create('config', [
            'type' => 'mcc_markdown',
            'id' => 'HObznkBUFSpME2',
            'is_default' => false,
            'merchant_id' => $merchantDetail['merchant_id'],
            'name' => 'mcc_markdown',
            'config' => '{"mcc_markdown_percentage" : "2",
                "intl_bank_transfer_ach_mcc_markdown_percentage" : "1",
                "intl_bank_transfer_swift_mcc_markdown_percentage" : "1"}']);

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The mcc markdown percentage must be between 0 and 99.99.';
        $this->startTest();

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer swift mcc markdown percentage must be between 0 and 99.99.';
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="-1";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer ach mcc markdown percentage must be between 0 and 99.99.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="asasd";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer ach mcc markdown percentage must be a number.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = 1;
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = "asd";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="1";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer swift mcc markdown percentage must be a number.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = "a";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = "1";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="1";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The mcc markdown percentage must be a number.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = "100";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = "1";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="1";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The mcc markdown percentage must be between 0 and 99.99.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = "10";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = "100";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="1";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer swift mcc markdown percentage must be between 0 and 99.99.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['config']['mcc_markdown_percentage'] = "10";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_swift_mcc_markdown_percentage'] = "10";
        $this->testData[__FUNCTION__]['request']['content']['config']['intl_bank_transfer_ach_mcc_markdown_percentage'] ="100";
        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'The intl bank transfer ach mcc markdown percentage must be between 0 and 99.99.';
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
        $this->startTest();
    }
}
