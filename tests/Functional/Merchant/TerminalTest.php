<?php

namespace RZP\Tests\Functional\Merchant;

use Crypt;
use Event;
use Mockery;
use Carbon\Carbon;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

use RZP\Http\Controllers\TerminalController;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use \RZP\Models\Terminal\Shared;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Services\RazorXClient;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailsEntity;

class TerminalTest extends TestCase
{
    use PaymentTrait;

    use PartnerTrait;

    use TerminalTrait;

    use DbEntityFetchTrait;

    protected $razorxValue = RazorXClient::DEFAULT_CASE;

    protected $terminalsServiceMock;
    protected $salesforceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return $this->razorxValue;

                }) );

        $this->setUpSalesforceMock();
    }

    public function testProxyFetchMerchantTerminals()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'merchant_id'   => '10000000000000',
            'gateway'       => 'wallet_paypal',
        ];

        $terminal   = $this->fixtures->create(
            'terminal', $attributes);

        $resp = $this->startTest();

        $this->assertEquals($resp['items'][0]['id'], $terminal->getPublicId());
    }

    public function testProxyTerminalOnboardStatus()
    {
        $this->ba->proxyAuth();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function () {
            return $this->getProxyTerminalOnboardStatusResponse();
        }, 1);

        $this->startTest();
    }

    public function testProxyFetchMerchantTerminalsWithNoTerminalInApi()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAssignTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignTerminalWithInvalidGatewayAcquirer()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignHitachiTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePaysecureTerminal()
    {
        $this->ba->terminalsAuth();

        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePaysecureTerminalNonRzpOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $this->testData[__FUNCTION__]['request']['content']['org_id'] = 'org_' . $org->getId();

        $merchant = $this->fixtures->create('merchant');

        $this->ba->terminalsAuth();

        $url = '/merchants/'.$merchant->getKey().'/terminals/internal';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = $org->getId();

        $this->startTest();
    }

    // uses assignTerminal on admin dashboard
    public function testAssignPaysecureTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // uses assignTerminal on admin dashboard
    public function testAssignPaysecureTerminalNonRzpOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $this->testData[__FUNCTION__]['request']['content']['org_id'] = 'org_' . $org->getId();

        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
    public function testAssignTerminalWhenDuplicateDeactivatedTerminalExist()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $terminalId = $this->startTest()['id'];

        $terminalEntity = (new Terminal\Repository)->findOrFail($terminalId);

        $terminalEntity->setStatus(Terminal\Status::DEACTIVATED);

        $terminalEntity->saveOrFail();

        $this->startTest();
    }

    public function testAssignTerminalWhenDuplicateDisabledTerminalExist()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $terminalId = $this->startTest()['id'];

        $terminalEntity = (new Terminal\Repository)->findOrFail($terminalId);

        (new Terminal\Core)->toggle($terminalEntity, false);


        $request = $this->testData[__FUNCTION__]['request'];

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage("A terminal for this gateway for this merchant already exists");

        $this->makeRequestAndGetContent($request);
    }

    public function testAssignTerminalWhenDuplicateTerminalExist()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('terminal', [
            'merchant_id'           => $merchant->getId(),
            'gateway'               => 'hdfc',
            'category'              => '1234',
            'gateway_merchant_id'   => '1234567',
            'gateway_acquirer'      => 'hdfc',
            'international'         => true,
        ]);

        $url = '/merchants/'.$merchant->getId().'/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignTerminalWhenDuplicateTerminalExistSharedAccount()
    {
        $this->fixtures->create('terminal', [
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'hdfc',
            'category'              => '1234',
            'gateway_merchant_id'   => '1234567',
            'gateway_acquirer'      => 'hdfc',
            'international'         => true,
        ]);

        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignTerminalWhenDuplicateTerminalExistHitachiGateway()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('terminal', [
            'merchant_id'               => $merchant->getId(),
            'gateway'                   => 'hitachi',
            'gateway_acquirer'          => 'ratn',
            'gateway_merchant_id'       => '12345',
            'gateway_terminal_id'       => '12345678',
            'category'                  => '4321',
            'international'             => true,
        ]);

        $url = '/merchants/'.$merchant->getId().'/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testBankAccountTerminalValidationRules()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateSameRootBankAccountTerminalWithDifferentMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100002Razorpay']);

        // Now try creating another numeric terminal for different merchant and gateway
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditUsedBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id'   => $merchant->getKey(),
            'used'          => true,
        ];
        $terminal   = $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/'.$terminal['id'];

        $this->startTest();
    }

    public function testEditBankAccountTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id'   => $merchant->getKey(),
            'used'          => false,
        ];

        $terminal   = $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/'.$terminal['id'];

        $this->startTest();
    }

    public function testAssignDifferentTypeBankAccountTerminalForSameMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        // Now try creating an alpha numeric terminal for same merchant and gateway
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignSameTypeBankAccountTerminalForSameMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'merchant_id' => $merchant->getKey(),
            'used'        => true,
        ];
        // Create a numeric terminal
        $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        // Now try creating a numeric terminal again for same merchant and gateway with different root and handle
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignSameRootAndSameTypeBankAccountTerminalAfterSharedTerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $attributes = [
            'shared'      => 1,
            'used'        => true,
        ];
        // Create a numeric terminal with shared merchant
        $terminal = $this->fixtures->create(
            'terminal:bank_account_terminal', $attributes);

        $errorDescription = PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS . $terminal['id'];

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $errorDescription;

        // Now try creating the same numeric terminal against the merchant
        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignHitachiTerminalWithInvalidGatewayAcquirer()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAddEmiTerminal()
    {
        $merchant = $this->getEntityById('merchant', '100000Razorpay', true);

        $url = '/merchants/'.$merchant['id'].'/terminals';

//        $url = '/merchants/100000Razorpay/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiIciciOnlineTerminal()
    {
        $this->startTest();
    }

    public function testEditUpiIciciOnlineTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_icici_dedicated_terminal');

        $data = [
            'gateway'                   => 'upi_icici',
            'type'                      => [
                'online' => '1',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['non_recurring','pay', 'collect', 'online']);

    }

    public function testCreateUpiIciciOfflineTerminal()
    {
        $this->startTest();
    }

    public function testEditUpiIciciOfflineTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_icici_dedicated_terminal');

        $data = [
            'gateway'                   => 'upi_icici',
            'type'                      => [
                'offline' => '1',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['non_recurring','pay', 'collect', 'offline']);
    }

    public function testCreateUpiIciciOnlineAndOfflineTerminal()
    {
        $this->startTest();
    }

    public function testCreateUPIInAppTerminal()
    {
        $this->startTest();
    }

    public function testEditUPIInAppTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_in_app_terminal');

        $data = [
            'gateway'                   => 'upi_axisolive',
            'type'                      => [
                'in_app' => '0',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['non_recurring']);
    }

    public function testCreateUPIInAppIOSTerminal()
    {
        $this->startTest();
    }

    public function testEditUPIInAppIOSTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_in_app_ios_terminal');

        $data = [
            'gateway'                   => 'upi_axisolive',
            'type'                      => [
                'ios' => '0',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['in_app']);
        $this->assertNOTEquals($content['type'], ['ios']);
    }

    public function testCreateUPIInAppAndroidTerminal()
    {
        $this->startTest();
    }

    public function testEditUPIInAppAndroidTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_in_app_android_terminal');

        $data = [
            'gateway'                   => 'upi_axisolive',
            'type'                      => [
                'android' => '0',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['in_app']);
        $this->assertNOTEquals($content['type'], ['android']);
    }

    public function testCreateUPIInAppIOSAndAndroidTerminal()
    {
        $this->startTest();
    }

    public function testEditUPIInAppIOSAndAndroidTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_in_app_ios_and_android_terminal');

        $data = [
            'gateway'                   => 'upi_axisolive',
            'type'                      => [
                'ios'      => '0',
                'android'  => '0',
            ],
        ];

        $content = $this->editTerminal($terminal->getId(), $data);

        $this->assertEquals($content['type'], ['in_app']);
        $this->assertNOTEquals($content['type'], ['ios']);
        $this->assertNOTEquals($content['type'], ['android']);
    }

    public function testAddBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddUpiMindgateBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAssignHitachiBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddBharatQrTerminalWithExpected()
    {
        $request = $this->testData['testAddBharatQrTerminal'];

        $request['request']['content']['expected'] = true;

        $this->startTest($request);
    }

    public function testReassignBharatQrTerminal()
    {
        $terminal = $this->fixtures->create('terminal:bharat_qr_terminal');

        $errorDescription = PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS . $terminal['id'];

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $errorDescription;

        $this->startTest();
    }

    public function testAddUpiBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testReassignUpiBharatQrTerminal()
    {
        $terminal = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $errorDescription = PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS . $terminal['id'];

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $errorDescription;

        $this->startTest();
    }

    public function testReassignTerminalForSameGateway()
    {
        $this->startTest();
    }

    public function testAssignTerminalForDifferentGateway()
    {
        $this->startTest();
    }

    public function testCreateTerminalWithNetworkCategory()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // Test for upi_juspay terminal create validation
    public function testCreateTerminalUpiJuspay()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // Test upi_rzprbl terminal create validation
    public function testCreateUpiRzprblTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // Test upi_rzprbl terminal edit validation
    public function testEditUpiRzprblTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_rzprbl_terminal');

        $this->assertTrue($terminal->refresh()->isPay());

        $data = [
            'type' => [
                'pay' => '0'
            ]
        ];

        $this->editTerminal($terminal->GetId(), $data);

        $this->assertFalse($terminal->refresh()->isPay());
    }

    public function testCreateTerminalBilldeskSiHub()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditBilldeskSiHubTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_billdesk_sihub_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_access_code' => 'random'
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals('random', $terminal['gateway_access_code']);
    }

    public function testCreateTerminalMandateHq()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditMandateHqTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_access_code' => 'random'
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals('random', $terminal['gateway_access_code']);
    }

    public function testCreateTerminalWithInvalidNetworkCategory()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTerminalWithPendingStatus()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals('pending', $terminal['status']);
    }

    public function testCreateHitachiDebitRecurringTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTerminalPendingStatus()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals('pending', $terminal['status']);
    }

    public function testCreateUpiCollectTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateGooglePayTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateGooglePayTerminalDuplicateVpa()
    {
        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'upi_sbi',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'vpa' => 'abc@sbi',
                'upi' => 1,
            ]);
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTpvTerminalWithInvalidMethod()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTpvTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }


    public function testTpvValidationRule()
    {
        $validator = new Terminal\Validator();

        $input = [
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => '12345',
            'gateway_merchant_id2'      => '12345678',
            'gateway_terminal_password' => '12345678',
            'upi'                       => 1,
            'tpv'                       => '2',
            'merchant_id'               => '10000000000000'
        ];

        $ret = $validator->validateInput('create', $input);

        $this->assertNull($ret);
    }

    public function testCreatePaytmTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditPaytmTerminal()
    {
        // create two paytm terminals for merchant having same attributes except gateway_mid
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant'
            ]);

        $terminal2 = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVq',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '54321',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant'
            ]);


        $tid = $terminal2['id'];

        $data = [
            'gateway_terminal_id' => "1211",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "1211", $content['gateway_terminal_id']);
    }

    public function testEditTokenisationTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'tokenisation_visa',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant'
            ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "razorpay",
        ];

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceProxyRequest(
            ["id" => "AqdfGh5460opVt",
                "gateway" => "tokenisation_visa",
                "procurer" => "razorpay",
                "merchant_id" => "10000000000000"]);

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "razorpay", $content['procurer']);
    }

    public function testValidateDeletev3Terminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'eghl',
                'gateway_terminal_id'      => '12344',
                'gateway_merchant_id'      => '12344',
                'procurer'                 => 'merchant'
            ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "razorpay",
        ];

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceProxyRequest(
            ["id" => "AqdfGh5460opVt",
                "gateway" => "eghl",
                "procurer" => "razorpay",
                "merchant_id" => "10000000000000"]);

        $content = $this->validateDeleteTerminalv3($tid, $data);

        $this->assertEquals( "razorpay", $content['procurer']);
    }

    public function testDeletev3Terminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'eghl',
                'gateway_terminal_id'      => '12344',
                'gateway_merchant_id'      => '12344',
                'procurer'                 => 'merchant'
            ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "razorpay",
        ];

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceProxyRequest(
            ["id" => "AqdfGh5460opVt",
                "gateway" => "eghl",
                "procurer" => "razorpay",
                "merchant_id" => "10000000000000"]);

        $content = $this->deleteTerminalv3($tid, $data);

        $this->assertEquals( "razorpay", $content['procurer']);
    }

    public function testEditNonPaysecureTerminalWithNonAxisTerminalOrgId()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "merchant",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "merchant", $content['procurer']);
    }

    public function testEditPaysecureTerminalWithAxisOrgIdTerminalByRzpAdminUser()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $terminal2 = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVq',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'paysecure',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '54321',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant',
                'org_id'                   => $org->getId(),
            ]);


        $tid = $terminal2['id'];

        $data = [
            'status' => "deactivated",
            'gateway_merchant_id' => 'axisgatewaymid1',
        ];

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['status'],$data['status']);

        $this->assertEquals($content['gateway_merchant_id'],$data['gateway_merchant_id']);
    }

    public function testEditPaysecureTerminalWithAxisOrgIdTerminalByAxisAdminUser()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $terminal2 = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVq',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'paysecure',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '54321',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant',
                'org_id'                   => $org->getId(),
            ]);


        $tid = $terminal2['id'];

        $data = [
            'type'    => [
                'non_recurring' => '1',
            ],
            'status' => "deactivated",
            'gateway_merchant_id' => 'axisgatewaymid1',
        ];

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $content = $this->editTerminalExternalOrg($tid, $data);

        $this->assertEquals($content['status'],$data['status']);

        $this->assertEquals($content['type'][0],'non_recurring');

        $this->assertEquals($content['gateway_merchant_id'],$data['gateway_merchant_id']);
    }

    public function testEditNonPaysecureTerminalWithByAxisAdminUser()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "merchant",
        ];

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionMessage(
            'Access Denied');

        $content = $this->editTerminalExternalOrg($tid, $data);
    }

    public function testCreateCredTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateOfflineTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateOfflineBadRequestTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateEzetapBadRequestTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTwidTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(true, $terminal['app']);

        $this->assertEquals('twid', $terminal['enabled_apps'][0]);

        $data = ['gateway_merchant_id' => 'new_mid','app'=> 1];

        $content = $this->editTerminal($terminal['id'], $data);

        $this->assertEquals('new_mid', $content['gateway_merchant_id']);
    }

    public function testCreateDirectSettlemtTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateDirectSettlementIndusIndTerminal()
    {
        $this->startTest();
    }

    public function testCreateDirectSettlementTerminalValidationFailure()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateDirectSettlemtTerminalFailure()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTerminalWithMerchantProcurer()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCardlessEmiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePayLaterTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateFlexmoneyTerminalWithEnabledBanks()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCardlessEmiFlexmoneyTerminalWithEnabledBanks()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $res = $this->startTest();
    }

    public function testCreateUpiAirtelTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiCitiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateTerminalWithWrongGatewayCase()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals('upi_airtel', $terminal['gateway']);
    }

    public function testCreateMpgsTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateMpgsAcquirerOcbcTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->fixtures->merchant->edit('10000000000000',[
            MerchantEntity::COUNTRY_CODE => 'MY'
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateEghlTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->fixtures->merchant->edit('10000000000000',[
            MerchantEntity::COUNTRY_CODE => 'MY'
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditEghlTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway'                  => 'eghl',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant'
            ]);


        $tid = $terminal['id'];

        $data = [
            'procurer' => "razorpay",
        ];

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceProxyRequest(
            ["id" => "AqdfGh5460opVt",
                "gateway" => "eghl",
                "procurer" => "razorpay",
                "merchant_id" => "10000000000000"]);

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "razorpay", $content['procurer']);
    }

    public function testDeleteEghlTerminal()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'eghl',
            'status'      => 'pending'
        ]);

        $terminalId = $terminal->getId();

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/' . $terminalId;

        $terminal = $this->getEntityById('terminal', $terminalId, true);

        $content = $this->startTest();

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_INVALID_ID);

        $terminal = $this->getEntityById('terminal', $terminalId, true);
    }


    public function testCreateTerminalInvalidAcquirerForCountry()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->fixtures->merchant->edit('10000000000000',[
            MerchantEntity::COUNTRY_CODE => 'IN'
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_INVALID_ACQUIRER_FOR_COUNTRY);

        $this->startTest();

    }

    public function testCreateIsgCardTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateMpgsPurchaseTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditMpgsTerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'mpgs',
            'card'                      => 1,
            'international'             => 1,
            'gateway_merchant_id'       => '9387723',
            'gateway_terminal_password' => 'random',
            'mode'                      => Terminal\Mode::DUAL,
            'type'                      => [
                'non_recurring'  => '1',
            ],
            'enabled'                   => 1,
            'gateway_acquirer'          => 'dummy',
        ];

        $terminal = $this->fixtures->create('terminal', $attributes);

        $tid = $terminal['id'];

        $data = ['international' => 0, 'mode' => Terminal\Mode::PURCHASE, 'gateway_acquirer' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['international'], 0);

        $this->assertEquals($content['gateway_acquirer'], 'random');
    }

    public function testEditFulcrumTerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'fulcrum',
            'mode'                      => 1,
            'international'             => 1,
            'gateway_merchant_id'       => '10000000000000d',
            'gateway_terminal_id'       => '1000000d',
            'gateway_terminal_password' => 'random',
            'mode'                      => 1,
            'type'                      => [
                'non_recurring'  => '1',
            ],
            'enabled'                   => 1,
            'card'                      => '1',
        ];

        $terminal = $this->fixtures->create('terminal', $attributes);

        $tid = $terminal['id'];

        $data = ['mode' => 2];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['mode'], 2);
    }

    public function testCreatePaytmCardTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testDeleteTerminal()
    {
        $merchant = $this->fixtures
                         ->create('merchant_fluid', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom'])
                         ->get();

        $this->ba->getAdmin()->merchants()->attach($merchant);

        $content = $this->startTest();
    }

    public function testDeleteTerminal2()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'     => false,
            'gateway'     => 'worldline',
            'status'      => 'pending'
        ]);

        $terminalId = $terminal->getId();

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/' . $terminalId;

        $terminal = $this->getEntityById('terminal', $terminalId, true);

        $content = $this->startTest();

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_INVALID_ID);

        $terminal = $this->getEntityById('terminal', $terminalId, true);
    }

    public function testDeleteTerminal2WithAxisOrgId()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $terminalId = $terminal->getId();

        $this->testData[__FUNCTION__]['request']['url'] = '/terminals/' . $terminalId;

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $content = $this->startTest();

        $this->assertNotEmpty($content['id']);
    }
    public function testRestoreTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->defaultAuthPayment();

        $t = $this->deleteTerminal2('1000HdfcShared');
        $this->assertNotNull($t['deleted_at']);

        $t = $this->restoreTerminal('1000HdfcShared');
        $this->assertNull($t['deleted_at']);
    }

    public function testDeleteTerminalWithSubMerchantAssigned()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal->merchants()->attach('10000000000000');

        $payment = $this->defaultAuthPayment();

        $t = $this->deleteTerminal2('1000HdfcShared');
        $this->assertNotNull($t['deleted_at']);

        $tId = $t['id'];

        $this->fixtures->stripSign($tId);

        $dt = Terminal\Entity::withTrashed()->findOrFail($tId);

        $this->assertEquals(0, $dt->merchants->count());
    }

    public function testCopyTerminal()
    {
        $this->markTestSkipped();
        $terminal = $this->fixtures->create('terminal:ebs_terminal', ['used' => true]);

        $tid = $terminal['id'];
        $mid = $terminal['merchant_id'];

        $input = ['merchant_ids' => ['100000Razorpay']];

        $response = $this->copyTerminal($tid, $mid, $input);

        $newTerminal = $this->getEntityById('terminal', $response[0]['terminal'], true);

        $oldTerminal = $terminal->toArray();

        $unsetKeys = ['id', 'created_at', 'updated_at', 'merchant_id'];
        foreach ($unsetKeys as $key)
        {
            unset($oldTerminal[$key]);
        }

        $this->assertEquals('100000Razorpay', $newTerminal['merchant_id']);
        $this->assertEquals(false, $newTerminal['used']);
        $this->assertArraySelectiveEquals($oldTerminal, $newTerminal);
    }

    public function testCopySharedTerminal()
    {
        $this->markTestSkipped();
        $terminal = $this->fixtures->create('terminal:shared_axis_terminal', ['used' => true]);

        $tid = $terminal['id'];
        $mid = $terminal['merchant_id'];

        $input = ['merchant_ids' => ['100000Razorpay']];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($tid, $mid, $input)
        {
            $this->copyTerminal($tid, $mid, $input);
        });
    }

    public function testEditHitachiDebitRecurringTerminal()
    {
        $attributes = [
            'used'   => true,
            'status' => "activated",
            'type'   => [
                'recurring_non_3ds' => '1',
                'recurring_3ds'     => '1',
            ],
        ];
        $terminal   = $this->fixtures->create(
            'terminal:hitachi_recurring_terminal_with_both_recurring_types', $attributes);

        $tid = $terminal['id'];

        $data = [
            'type'    => [
                'recurring_non_3ds' => '1',
                'recurring_3ds'     => '1',
                'debit_recurring'   => '1',
            ],
            'status' => "deactivated",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['type'], ['recurring_3ds', 'recurring_non_3ds', 'debit_recurring']);
        $this->assertEquals($content['status'], "deactivated");

    }

    public function testEditPayuTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testEditCollectTerminal()
    {
        $this->fixtures->create('terminal:shared_upi_icici_terminal', ['used' => true]);

        $tid = Terminal\Shared::UPI_ICICI_RAZORPAY_TERMINAL;

        $data = [
            'gateway'                   => 'upi_icici',
            'type'                      => [
                'collect' => '1',
            ],
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['type'], ['non_recurring', 'collect']);
    }

    public function testEditAxisMigsTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true]);

        $tid = $terminal['id'];

        $data = ['gateway_terminal_id' => 'random', 'gateway_terminal_password' => 'random', 'gateway_acquirer' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['gateway_terminal_id'], 'random');

        $this->assertEquals($content['gateway_acquirer'], 'random');
    }

    public function testEditCardFssTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'card_fss',
                'gateway_acquirer'     => 'dummy',
                'procurer'             => 'razorpay'
            ]);


        $tid = $terminal['id'];

        $data = ['gateway_acquirer' => 'random', 'gateway_merchant_id' => '123', 'procurer' => 'merchant'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $content['gateway_acquirer']);

        $this->assertEquals('merchant', $content['procurer']);

    }

    public function testEditCardFssTerminalSecret()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'card_fss',
                'gateway_acquirer'     => 'dummy',
                'procurer'             => 'razorpay'
            ]);


        $tid = $terminal['id'];

        $data = ['gateway_merchant_id' => '123', 'gateway_secure_secret' => 'random1', 'gateway_terminal_password' => 'random2'];

        $this->editTerminal($tid, $data);

        $terminalArray = $terminal->reload()->toArrayWithPassword();

        $this->assertEquals('random1', $terminalArray['gateway_secure_secret'] );
        $this->assertEquals('random2', $terminalArray['gateway_terminal_password'] );
    }

    public function testEditCybersourceTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'cybersource',
                'gateway_acquirer'     => 'dummy',
            ]);


        $tid = $terminal['id'];

        $data = ['gateway_acquirer' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $content['gateway_acquirer']);
    }

    public function testEditNetbankingAxisTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $tid = $terminal['id'];

        $data = ['gateway_acquirer' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $content['gateway_acquirer']);
    }

    public function testEditNetbankingCubTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $tid = $terminal['id'];

        $data = ['network_category' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $content['network_category']);
    }

    public function testEditNetbankingIciciTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'netbanking_icici',
                'gateway_merchant_id'  => '250000002',
                'enabled'              => 1,
                'gateway_merchant_id2' => '350000002',
                'corporate'            => 1,
            ]);

        $tid = $terminal['id'];

        $data = ['corporate' => 2];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals(2, $content['corporate']);
    }

    public function testEditHdfcTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['used' => true, 'gateway_recon_password' => 'boo']);

        $tid = $terminal['id'];

        $data = ['gateway_recon_password' => 'random'];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $terminal->reload()->getGatewayReconPassword());
    }

    //For testing edit terminals with zero used count
    //but some authorised payments
    public function testEditHdfcTerminalWithPayments()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['gateway_recon_password' => 'boo']);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->defaultAuthPayment();

        $tid = $terminal['id'];

        $data = array('gateway_recon_password' => 'random');

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('random', $terminal->reload()->getGatewayReconPassword());
    }

    public function testEditUpiIciciTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_upi_icici_terminal', ['used' => true, 'upi' => 0]);

        $tid = $terminal['id'];

        $data = array('upi' => '1');

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals(true, $terminal->reload()->upi);
    }

    public function testEditUpiCitiTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_upi_citi_terminal', ['used' => true, 'upi' => 0]);

        $data = [
            'type' => [
                'non_recurring' => '1'
            ]
        ];

        $content = $this->editTerminal($terminal['id'], $data);

        $this->assertTrue($terminal->refresh()->isNonRecurring());
    }

    public function testToggleTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used' => true, 'enabled' => '1']);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }

    public function testToggleTerminalWithAxisOrgId()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', [
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $tid = $terminal['id'];

        $url = '/terminals/'.$tid.'/toggle';

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

    }
    public function testEditWalletAirtelmoneyTerminalWithNotRequiredFields()
    {
        $terminal = $this->fixtures->create('terminal:shared_airtelmoney_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_secure_code'       => 'test_random_id',
        ];

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($tid, $data)
        {
            $this->editTerminal($tid, $data);
        });
    }

    public function testHitachiTerminalsCurrencyUpdateCron()
    {
        $merchant = $this->fixtures->create('merchant');

        $attributes = array(
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => $merchant->getId(),
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'currency'            => ['INR'],
        );

         $t1 = $this->fixtures->create('terminal', $attributes);

        $attributes = array(
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123457',
            'currency'            => ['USD'],
        );

        $t2 = $this->fixtures->create('terminal', $attributes);

        $this->ba->cronAuth();

        $this->startTest();

        $terminal1 = (new Terminal\Repository)->getById($t1->getId());

        $terminal2 = (new Terminal\Repository)->getById($t2->getId());

        $this->assertEquals(Currency::SUPPORTED_CURRENCIES, $terminal1->getCurrency());
        $this->assertEquals('activated', $terminal1->getStatus());
        $this->assertEquals('deactivated', $terminal2->getStatus());
    }

    public function testEditCashfreeTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'cashfree',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testEditPayuUpiTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'upi' => 1,
                'card' => 0,
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'upi' => "1",
            'type'    => [
                'non_recurring' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( "1", $content['upi']);
        $this->assertEquals( ["non_recurring", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditPaytmTerminalOptimiser()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                       => 'AqdfGh5460opVt',
                'merchant_id'              => '10000000000000',
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant',
                'type'                     => [
                    'direct_settlement_with_refund' => '1',
                    'optimizer'                     => '1',
                    'disable_optimizer_refunds'     => '1',
                ],
            ]);

        $tid = $terminal['id'];

        $data = [
            'id'                       => 'AqdfGh5460opVt',
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'paytm',
            'gateway_terminal_id'      => '12344',
            'gateway_access_code'      => '12344',
            'gateway_merchant_id'      => '12344',
            'gateway_secure_secret'    => '12345',
            'procurer'                 => 'merchant',
            'type'    => [
                'direct_settlement_with_refund' => '1',
                'optimizer'                     => '1',
                'disable_optimizer_refunds'     => '0',
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( ["direct_settlement_with_refund", "optimizer"], $content['type']);
    }

    public function testCreatePaytmTerminalEnableAutoDebitOptimiser()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                       => 'AqdfGh5460opVv',
                'merchant_id'              => '10000000000000',
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '1234ad4',
                'gateway_merchant_id'      => '12344a',
                'gateway_merchant_id2'     => '18793a',
                'gateway_secure_secret'    => '12345',
                'gateway_secure_secret2'   => '1397435',
                'procurer'                 => 'merchant',
                'type'                     => [
                    'direct_settlement_with_refund' => '1',
                    'optimizer'                     => '1',
                    'enable_auto_debit'             => '1',
                ],
            ]);

        $this->assertEquals( ["direct_settlement_with_refund", "optimizer", "enable_auto_debit"], $terminal['type']);
    }

    public function testEditPaytmTerminalEnableAutoDebitOptimiser()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id'                       => 'AqdfGh5460opVt',
                'merchant_id'              => '10000000000000',
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '1234ad4',
                'gateway_merchant_id'      => '12344',
                'gateway_merchant_id2'     => '18793',
                'gateway_secure_secret'    => '12345',
                'gateway_secure_secret2'   => '1397435',
                'procurer'                 => 'merchant',
                'type'                     => [
                    'direct_settlement_with_refund' => '1',
                    'optimizer'                     => '1',
                    'enable_auto_debit'             => '1',
                ],
            ]);

        $tid = $terminal['id'];

        $data = [
            'id'                       => 'AqdfGh5460opVt',
            'merchant_id'              => '10000000000000',
            'gateway'                  => 'paytm',
            'gateway_terminal_id'      => '12344',
            'gateway_access_code'      => '12344',
            'gateway_merchant_id'      => '12344',
            'gateway_merchant_id2'     => '18793',
            'gateway_secure_secret'    => '12345',
            'gateway_secure_secret2'   => '13974',
            'procurer'                 => 'merchant',
            'type'    => [
                'direct_settlement_with_refund' => '1',
                'optimizer'                     => '1',
                'enable_auto_debit'             => '1',
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( ["direct_settlement_with_refund", "optimizer", "enable_auto_debit"], $content['type']);
    }

    public function testEditBilldeskOptimizerUpiTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'billdesk_optimizer',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret2' => "1231424",
                'upi' => 0,
                'card' => 1,
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'upi' => "1",
            'type'    => [
                'non_recurring' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( "1", $content['upi']);
        $this->assertEquals( ["non_recurring", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditPayuEmiTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'card' => 1,
                'emi'  => 1,
                'mode' => 2,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'emi' => "1",
            'emi_subvention' => 'customer',
            'type'    => [
                'non_recurring' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( "1", $content['emi']);
        $this->assertEquals( ["non_recurring", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditPayuEmandateTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'emandate' => 1,
                'mode' => 2,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'type'    => [
                'recurring_3ds' => '1',
                'recurring_non_3ds' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( "1", $content['emandate']);
        $this->assertEquals( ["recurring_3ds", "recurring_non_3ds", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditCashfreeUpiTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'cashfree',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'upi' => 1,
                'card' => 0,
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'upi' => "1",
            'type'    => [
                'non_recurring' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals( "1", $content['upi']);
        $this->assertEquals( ["non_recurring", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditCcavenueTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'ccavenue',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'gateway_access_code'   => "dummy",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testEditPinelabsTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'pinelabs',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'gateway_access_code'   => "dummy",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
            'upi'  => 1,
            'type'    => [
                'non_recurring' => '1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
        $this->assertEquals( "1", $content['upi']);
        $this->assertEquals( ["non_recurring", "direct_settlement_with_refund"], $content['type']);
    }

    public function testEditBilldeskOptimizerTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'billdesk_optimizer',
                'gateway_merchant_id' => '250000003',
                'gateway_secure_secret2' => "1231424",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testEditIngenicoTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'ingenico',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'gateway_access_code'   => "dummy",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testEditZaakpayTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'zaakpay',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'gateway_secure_secret2' => "12314245",
                'gateway_access_code'   => "dummy",
                'mode' => 3,
                'type'    => [
                    'direct_settlement_with_refund' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'mode' => "2",
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "2", $content['mode']);
    }

    public function testTerminalModeDual()
    {
        $this->startTest();
    }

    public function testTerminalModePurchase()
    {
        $this->startTest();
    }

    public function testTerminalModeAuthCapture()
    {
        $this->startTest();
    }

    public function testTerminalModeDualFailure()
    {
        $this->startTest();
    }

    public function testTerminalModePurchaseSuccess()
    {
        $this->startTest();
    }

    public function testPaypalTerminalModeNotPurchase()
    {
        $this->startTest();
    }

    public function testTerminalModePurchaseError()
    {
        $this->startTest();
    }

    public function testTerminalModePurchaseFailure()
    {
        $this->startTest();
    }

    public function testTerminalModeAuthCaptureFailure()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurringNon3DS()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurring3DS()
    {
        $this->startTest();
    }

    public function testTerminalTypeRecurringBoth()
    {
        $this->startTest();
    }

    public function testTerminalTypeIvr()
    {
        $this->startTest();
    }

    // should be able to test status update even if terminal editing not defined for the gateway
    public function testEditTerminalStatus()
    {
        $terminal = $this->fixtures->create(
            'terminal:ebs_terminal');

        $tid = $terminal['id'];

        $data = [
            'status' => 'pending'
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('pending', $content['status']);

        $data = [
            'status' => 'activated'
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('activated', $content['status']);

        $data = [
            'status' => 'deactivated'
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('deactivated', $content['status']);

        $data = [
            'status' => 'failed'
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('failed', $content['status']);
    }

    public function testEditTerminalTypeRecurringBoth()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_hdfc_terminal', ['used' => 1]);

        $tid = $terminal['id'];

        $data = [
            'type' => [
                'non_recurring'     => '0',
                'recurring_3ds'     => '1',
                'recurring_non_3ds' => '1',
            ]
        ];

        $content = $this->editTerminal($tid, $data);

        $types = [
            'recurring_3ds',
            'recurring_non_3ds'
        ];

        $this->assertEquals($types, $content['type']);
    }

    public function testTerminalCheckAutoDisable()
    {
        $this->markTestSkipped();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create(
                        'terminal:shared_hdfc_terminal',
                        [
                            'id'          => '12HDFCTerminal',
                            'merchant_id' => '10000000000000'
                        ]);

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'authorize')
            {
                $content['result'] = 'GW00154';
            }
        }, 'hdfc');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->defaultAuthPayment();
        });

        $this->assertFalse($terminal->reload()->isEnabled());
    }

    public function testTerminalSecretCheck()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'gateway_terminal_password'  => '1234',
                'gateway_terminal_password2' => '21234',
                'gateway_secure_secret'      => '0123',
                'gateway_secure_secret2'     => '20123',
            ]);

        $url = '/terminals/' . $terminal->getKey() . '/secret';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAddAmazonPayTerminal()
    {
        $this->startTest();
    }

    public function testAssignIsgBharatQrTerminal()
    {
        $this->startTest();
    }

    public function testAddIsgBharatQrTerminalWithExpected()
    {
        $request = $this->testData['testAssignIsgBharatQrTerminal'];

        $request['request']['content']['expected'] = true;

        $this->startTest($request);
    }

    public function testAddIsgBharatQrTerminalFailed()
    {
        $this->startTest();
    }

    public function testAddHulkTerminalWithAppAuth()
    {
        $this->startTest();
    }

    public function testGetTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalWallets()
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'wallet_amazonpay',
            'gateway_merchant_id'   => 'gateway_merchant_random',
            'gateway_access_code'   => 'gateway_access_code',
            'gateway_terminal_password' => 'abcdef',
            'enabled_wallets'       => ['amazonpay']
        );

        $terminal = $this->fixtures->create('terminal', $attributes);

        $url = '/terminals/' . $terminal['id'] . '/wallets';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFillEnabledWallets()
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'wallet_amazonpay',
            'gateway_merchant_id'   => 'gateway_merchant_random',
            'gateway_access_code'   => 'gateway_access_code',
            'gateway_terminal_password' => 'abcdef',
        );

        $terminal1 = $this->fixtures->create('terminal', $attributes);

        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'ccavenue',
            'gateway_merchant_id'   => 'gateway_merchant_random',
            'gateway_access_code'   => 'gateway_access_code',
            'gateway_secure_secret' => 'abcdef',
        );

        $terminal2 = $this->fixtures->create('terminal', $attributes);

        $this->ba->cronAuth();

        $this->startTest();

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal1->getId(),
            true
        );

        $this->assertEquals(['amazonpay'], $updatedTerminal['enabled_wallets']);
    }

    public function testGetTerminalBanksForBilldesk()
    {
        $terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForDirectNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForPaylater()
    {
        $terminal = $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForCardlessEmi()
    {
        $terminal = $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTpvTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_tpv_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetCorpTerminalBanks()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetTerminalBanksForNonNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_fss_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetWalletsForTerminal()
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'ccavenue',
            'gateway_merchant_id'   => 'gateway_merchant_random',
            'gateway_access_code'   => 'gateway_access_code',
            'gateway_secure_secret' => 'abcdef',
            'enabled_wallets'       => ['paytm','mobikwik']
        );

        $terminal = $this->fixtures->create('terminal', $attributes);
        $url = '/terminals/' . $terminal['id'] . '/wallets';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForDirectNetbankingTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForPaylaterTerminal()
    {
        $terminal = $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForCardlessEmiTerminal()
    {
        $terminal = $this->fixtures->create('terminal:cardlessEmiFlexMoneyTerminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetUnsupportedBankForTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksForNonNetbankingGateway()
    {
        $terminal = $this->fixtures->create('terminal:shared_fss_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetBanksWithIncorrectInput()
    {
        $terminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $url = '/terminals/' . $terminal['id'] . '/banks';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateAllahabadTpvTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateSbiTpvTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditNetbankingSBITerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_sbi',
            'gateway_merchant_id'       => 'netbanking_sbi_merchant_id',
            'gateway_secure_secret'     => 'random_secret',
            'netbanking'                => '1',
            'tpv'                       => '1',
            'network_category'          => 'ecommerce',
            'gateway_terminal_password' => 'random',
            'enabled'                   => 1,
            'corporate'                 => 0,
        ];

        $terminal = $this->fixtures->create('terminal', $attributes);

        $tid = $terminal['id'];

        $data = ['corporate' => '1', 'tpv' => '0',];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals($content['corporate'], 1);

        $this->assertEquals($content['tpv'], '0');
    }

    public function testAssignUpiYesbankTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankUnsupportedMethod()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankWithTerminalNotExist()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkTerminalUpdateForBankRemoveMethod()
    {
        $url = '/terminals/banks/bulk';

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $response = $this->startTest();

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;
        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->assertArrayNotHasKey('ANDB', $response[$atomTermId]);

        $this->assertArrayNotHasKey('ANDB', $response[$ebsTermId]);
    }

    public function testBulkTerminalUpdateForBulkBankRemoveMethod()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $banksToBeRemoved = $this->testData[__FUNCTION__]['request']['content']['banks'];

        foreach([Shared::ATOM_RAZORPAY_TERMINAL, Shared::EBS_RAZORPAY_TERMINAL] as $terminalId)
        {
            $terminal = (new Terminal\Repository)->getById($terminalId);

            $enabledBanks = $terminal->getEnabledBanks();

            foreach($banksToBeRemoved as $bank)
            {
                $this->assertArrayNotHasKey($bank, $enabledBanks);

                $this->assertArrayNotHasKey($bank, $enabledBanks);
            }
        }
    }

    public function testBulkTerminalUpdateForBankAddMethod()
    {
        $url = '/terminals/banks/bulk';

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;

        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->fixtures->create('terminal:multiple_netbanking_terminals');
        $this->fixtures->terminal->setEnabledBanks($atomTermId);
        $this->fixtures->terminal->setEnabledBanks($ebsTermId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testBulkTerminalUpdateForBulkBankAddMethod()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->terminal->setEnabledBanks(Shared::ATOM_RAZORPAY_TERMINAL);

        $this->fixtures->terminal->setEnabledBanks(Shared::EBS_RAZORPAY_TERMINAL);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkTerminalUpdateBulkBankAddRemoveInvalidBank()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $beforeEnabledBanks = [
            Shared::ATOM_RAZORPAY_TERMINAL => (new Terminal\Repository)->getById(Shared::ATOM_RAZORPAY_TERMINAL)->getEnabledBanks(),
            Shared::EBS_RAZORPAY_TERMINAL  =>(new Terminal\Repository)->getById(Shared::EBS_RAZORPAY_TERMINAL)->getEnabledBanks(),
        ];

        foreach(['add', 'remove'] as $action)
        {
            $this->testData[__FUNCTION__]['request']['content']['action'] = $action;

            $this->ba->adminAuth();

            $this->startTest();
        }

        $afterEnabledBanks = [
            Shared::ATOM_RAZORPAY_TERMINAL => (new Terminal\Repository)->getById(Shared::ATOM_RAZORPAY_TERMINAL)->getEnabledBanks(),
            Shared::EBS_RAZORPAY_TERMINAL  =>(new Terminal\Repository)->getById(Shared::EBS_RAZORPAY_TERMINAL)->getEnabledBanks(),
        ];

        $this->assertEquals($beforeEnabledBanks, $afterEnabledBanks);
    }

    /*
     * Asserting that if both bank and banks are sent, then it should fail
     */
    public function testBulkTerminalUpdateForBankAndBanksShouldFail()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testBulkTerminalUpdateForUnsupportedBankAddMethod()
    {
        $url = '/terminals/banks/bulk';

        $atomTermId = Shared::ATOM_RAZORPAY_TERMINAL;

        $ebsTermId = Shared::EBS_RAZORPAY_TERMINAL;

        $this->fixtures->create('terminal:multiple_netbanking_terminals');
        $this->fixtures->terminal->setEnabledBanks($atomTermId);
        $this->fixtures->terminal->setEnabledBanks($ebsTermId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testBulkTerminalUpdateForInvalidBankCode()
    {
        $url = '/terminals/banks/bulk';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateTerminalsBulk()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'status'    =>  'pending',
            'gateway'   =>  'worldline',
            'enabled'   =>  false,
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'status'    =>  'activated',
            'gateway'   =>  'worldline',
        ]);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids'  =>  [
                $terminal['id'], $terminal2['id'], 'notexisttermid'
            ],
            'attributes'    =>  [
                'status'    =>  'activated',
                'enabled'   =>  true
            ]
        ];

        $this->startTest();

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal->getId(),
            true
        );

        $updatedTerminal2 = $this->getEntityById(
            'terminal',
            $terminal2->getId(),
            true
        );

        $this->assertEquals($updatedTerminal['status'], 'activated');
        $this->assertEquals($updatedTerminal['enabled'], true);
        $this->assertEquals($updatedTerminal2['status'], 'activated');
    }

    public function testTerminalsEnableBulk()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'status'    =>  'pending',
            'gateway'   =>  'worldline',
            'enabled'   => false
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'status'    =>  'activated',
            'gateway'   =>  'worldline',
            'enabled'   => false
        ]);

        $terminal3 = $this->fixtures->create('terminal', [
            'status'    =>  'deactivated',
            'gateway'   =>  'worldline',
            'enabled'   => false
        ]);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids'  =>  [
                $terminal['id'], $terminal2['id'],$terminal3['id'], 'notexisttermid'
            ],
        ];

        $this->testData[__FUNCTION__]['response']['content'] = [
            'failedIds'  =>  [
                $terminal['id'], 'notexisttermid'
            ],
        ];

        $this->startTest();

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal->getId(),
            true
        );

        $updatedTerminal2 = $this->getEntityById(
            'terminal',
            $terminal2->getId(),
            true
        );

        $updatedTerminal3 = $this->getEntityById(
            'terminal',
            $terminal3->getId(),
            true
        );

        $this->assertEquals($updatedTerminal['status'], 'pending');
        $this->assertEquals($updatedTerminal['enabled'], false);

        $this->assertEquals($updatedTerminal2['status'], 'activated');
        $this->assertEquals($updatedTerminal2['enabled'], true);

        $this->assertEquals($updatedTerminal3['status'], 'activated');
        $this->assertEquals($updatedTerminal3['enabled'], true);
    }

    public function testUpdateTerminalsBulkTryEnablingFailedTerminal()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'status'    =>  'pending',
            'gateway'   =>  'worldline',
            'enabled'   =>  false,
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'status'    =>  'activated',
            'gateway'   =>  'worldline',
        ]);

        // sending enabled true with status failed in request, should not be allowed
        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids'  =>  [
                $terminal['id'], $terminal2['id'], 'notexisttermid'
            ],
            'attributes'    =>  [
                'status'    =>  'failed',
                'enabled'   =>  true
            ]
        ];

        $this->startTest();

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal->getId(),
            true
        );

        $updatedTerminal2 = $this->getEntityById(
            'terminal',
            $terminal2->getId(),
            true
        );

        // nothing should have updated
        $this->assertEquals($updatedTerminal['status'], 'pending');
        $this->assertEquals($updatedTerminal['enabled'], false);
        $this->assertEquals($updatedTerminal2['status'], 'activated');
    }

    public function testUpdateTerminalsBulkTryStatusUpdateWithoutEnableField()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'status'    =>  'pending',
            'gateway'   =>  'worldline',
            'enabled'   =>  false,
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'status'    =>  'activated',
            'gateway'   =>  'worldline',
        ]);

        $this->testData[__FUNCTION__]['request']['content'] = [
            'terminal_ids'  =>  [
                $terminal['id'], $terminal2['id'], 'notexisttermid'
            ],
            'attributes'    =>  [
                'status'    =>  'activated',
            ]
        ];

        $this->startTest();

        $updatedTerminal = $this->getEntityById(
            'terminal',
            $terminal->getId(),
            true
        );

        $updatedTerminal2 = $this->getEntityById(
            'terminal',
            $terminal2->getId(),
            true
        );

        // nothing should have updated
        $this->assertEquals($updatedTerminal['status'], 'pending');
        $this->assertEquals($updatedTerminal['enabled'], false);
        $this->assertEquals($updatedTerminal2['status'], 'activated');
    }

    public function testQueryCacheforTerminals()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest($this->testData);

        Event::assertDispatched(KeyForgotten::class);

        $this->defaultAuthPayment();

        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertNotDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                $this->assertNotEquals($tag, 'terminal_10000000000000');
            }
            return false;
        });

        $this->defaultAuthPayment();

        Event::assertDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'terminal') === true)
                {
                    $this->assertEquals('terminal_10000000000000', $tag);
                }
            }

            return true;
        });
    }

    public function testCreateWalletPhonepeTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateWalletPhonepeTerminalWrongWallet()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateHdfcTerminalWithEnabledWallet()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateWalletPhonepeSwitchTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateWalletPaypalTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateNetbankingSibTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateNetbankingYesbTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateNetbankingIbkTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateBilldeskTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditBilledeskTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_access_code'       => 'random'
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals('random', $terminal['gateway_access_code']);
    }

    public function testCreateNetbankingCanaraTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        // Adding below assert to check if the org is being associated to terminal (via merchant) properly
        $this->assertEquals('100000razorpay', $terminal['org_id']);

    }

    public function testCreateWorldlineTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        // Adding below assert to check if the org is being associated to terminal (via merchant) properly
        $this->assertEquals('100000razorpay', $terminal['org_id']);
    }

    public function testCreateEzetapTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $resp = $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        // Adding below assert to check if the org is being associated to terminal (via merchant) properly
        $this->assertEquals('100000razorpay', $terminal['org_id']);
    }

    public function testAssignUpiMindgateVirtualVPATerminal()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignUpiMindgateVirtualVPATerminalWithoutConfig()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '100001Razorpay']);

        $url = '/merchants/'.$merchant->getKey().'/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateJuspayTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiJuspayQrExpectedTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $tid = $response['id'];

        $data = [
            'expected' => 0,
        ];

        $terminal = $this->editTerminal($tid, $data);

        $this->assertSame(false, $terminal['expected']);
    }

    public function testEditJuspayTerminal()
    {
        $terminal = $this->fixtures->create('terminal:upi_juspay_terminal');

        $tid = $terminal['id'];

        $data = [
            'category' => '1411',
            'vpa'      => 'some@asd'
        ];

        $this->editTerminal($tid, $data);
    }

    public function testCreateJuspayIntentTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiYesbankCollectTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateUpiYesbankIntentTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePayuUpiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateBilldeskOptimizerUpiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePayuWalletTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCashfreeUpiTerminal()
    {
        $url = '/merchants/10000000000000/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditUpiYesbankTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal', ['vpa' => 'abc@ybl']);

        $tid = $terminal['id'];

        $data = array('vpa' => 'xyz@ybl');

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals('xyz@ybl', $terminal->reload()->vpa);
    }

    public function testCreateCybersourceYesBTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals('yesb', $terminal['gateway_acquirer']);

        // Adding below assert to check if the org is being associated to terminal (via merchant) properly
        $this->assertEquals('100000razorpay', $terminal['org_id']);
    }

    public function testAssignTerminalWithNoAccountTypeAttribute($channel = 'bt_icici')
    {
        $merchant = $this->testAssignTerminalWithDifferentAccountTypeAttribute($channel);

        $url = '/merchants/' . $merchant->getKey() . '/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content']['gateway'] = $channel;
        $this->testData[__FUNCTION__]['request']['content']['gateway_merchant_id'] = '9999';

        $this->startTest();
    }

    public function testAssignTerminalWithNoAccountTypeAttributeForYesbank()
    {
        $this->testAssignTerminalWithNoAccountTypeAttribute('bt_yesbank');
    }

    public function testAssignTerminalWithDifferentAccountTypeAttributeForYesbank()
    {
        $this->testAssignTerminalWithDifferentAccountTypeAttribute('bt_yesbank');
    }

    public function testAssignTerminalWithDifferentAccountTypeAttribute($gateway = 'bt_icici')
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/' . $merchant->getKey() . '/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content']['gateway'] = $gateway;
        $this->testData[__FUNCTION__]['request']['content']['gateway_merchant_id'] = '2323';
        $this->testData[__FUNCTION__]['request']['content']['account_type'] = 'current';

        $this->testData[__FUNCTION__]['response']['content']['gateway'] = $gateway;
        $this->testData[__FUNCTION__]['response']['content']['gateway_merchant_id'] = '2323';
        $this->testData[__FUNCTION__]['response']['content']['account_type'] = 'current';

        $this->startTest();

        $url = '/merchants/' . $merchant->getKey() . '/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content']['gateway'] = $gateway;
        $this->testData[__FUNCTION__]['request']['content']['gateway_merchant_id'] = '9999';
        $this->testData[__FUNCTION__]['request']['content']['account_type'] = 'nodal';

        $this->testData[__FUNCTION__]['response']['content']['gateway'] = $gateway;
        $this->testData[__FUNCTION__]['response']['content']['gateway_merchant_id'] = '9999';
        $this->testData[__FUNCTION__]['response']['content']['account_type'] = 'nodal';

        $this->startTest();

        return $merchant;
    }

    public function testAssignTerminalWithDifferentAccountTypeAttributeForKotak()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/' . $merchant->getKey() . '/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['request']['content']['gateway'] = 'bt_kotak';
        $this->testData[__FUNCTION__]['request']['content']['gateway_merchant_id'] = '2323';
        $this->testData[__FUNCTION__]['request']['content']['account_type'] = 'current';

        $this->startTest();
    }

    public function testTerminalFetchByIdAppAuth()
    {
        $this->ba->terminalsAuth();

        $terminal   = $this->fixtures->create(
            'terminal');
        $id = $terminal->getId();

        $url = '/terminals/'.$id;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $res = $this->startTest();

        $publicId = "term_" . $id;

        $this->assertEquals($publicId, $res["id"]);
    }

    public function testTerminalFetchByIdAppAuthBadRequest()
    {
        $this->ba->terminalsAuth();

        $id = 'Asdfgh';

        $url = '/terminals/'. $id;

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignUpiIciciVirtualVPATerminal()
    {
        $this->startTest();
    }

    public function testAssignUpiIciciVirtualVPATerminalWithoutConfig()
    {
        $this->startTest();
    }

    public function testTerminalModePurchaseForAxisMigs()
    {
        $this->startTest();
    }

    public function testTerminalModeDualForAxisMigs()
    {
        $this->startTest();
    }

    // test for cron that migrates existing original mpans to vault (mpan tokenization)
    public function testTokenizeExistingTerminalMpans()
    {
        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5234567890123456',
            'visa_mpan'           => '4234567890123456',
            'rupay_mpan'          => '6234567890123456',
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5334567890123456',
            'visa_mpan'           => '4334567890123456',
            'rupay_mpan'          => '6334567890123456',
        ]);

        $this->ba->cronAuth();

        $res = $this->startTest();
        $this->assertEquals([$terminal->getId(), $terminal2->getId()], $res['tokenization_success_terminal_ids']);
        $this->assertEquals([], $res['tokenization_failed_terminal_ids']);

        $terminal->reload();
        $this->assertEquals(base64_encode('5234567890123456'), $terminal['mc_mpan']);
        $this->assertEquals(base64_encode('4234567890123456'), $terminal['visa_mpan']);
        $this->assertEquals(base64_encode('6234567890123456'), $terminal['rupay_mpan']);
    }

    // test for cron that migrates existing original mpans to vault (mpan tokenization)
    public function testTokenizeExistingTerminalMpansInputValidationFailure()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    // test for cron that migrates existing original mpans to vault (mpan tokenization)
    // tests that even if two terminals have same mpans, then also migration works fine. Although two activated terminala can't have same mpans due to duplicity validations in place but
    // a failed terminal can have the same mpans as that of an activated terminal and if activated terminal is picked up before failed one for tokenization,
    // then "A terminal for this gateway for this merchant already exists" is raised when we tokenize failed terminal, we are bypassing this duplicity check for this scenario
    public function testTokenizeExistingTerminalMpansSameFields()
    {

        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5234567890123456',
            'visa_mpan'           => '4234567890123456',
            'rupay_mpan'          => '6234567890123456',
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5234567890123456',
            'visa_mpan'           => '4234567890123456',
            'rupay_mpan'          => '6234567890123456',
        ]);


        $this->ba->cronAuth();

        $res = $this->startTest();
        $this->assertEquals([$terminal->getId(), $terminal2->getId()], $res['tokenization_success_terminal_ids']);
        $this->assertEquals([], $res['tokenization_failed_terminal_ids']);

        $terminal->reload();
        $this->assertEquals('NTIzNDU2Nzg5MDEyMzQ1Ng==', $terminal['mc_mpan']);
        $this->assertEquals('NDIzNDU2Nzg5MDEyMzQ1Ng==', $terminal['visa_mpan']);
        $this->assertEquals('NjIzNDU2Nzg5MDEyMzQ1Ng==', $terminal['rupay_mpan']);

        $terminal2->reload();
        $this->assertEquals('NTIzNDU2Nzg5MDEyMzQ1Ng==', $terminal2['mc_mpan']);
        $this->assertEquals('NDIzNDU2Nzg5MDEyMzQ1Ng==', $terminal2['visa_mpan']);
        $this->assertEquals('NjIzNDU2Nzg5MDEyMzQ1Ng==', $terminal2['rupay_mpan']);

    }
    // test tokenize existing mpan cron route should accept terminal_id
    public function testTokenizeExistingTerminalMpansWithTerminalIdInInput()
    {
        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5234567890123456',
            'visa_mpan'           => '4234567890123456',
            'rupay_mpan'          => '6234567890123456',
        ]);

        $terminal2 = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5334567890123456',
            'visa_mpan'           => '4334567890123456',
            'rupay_mpan'          => '6334567890123456',
        ]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__]['request']['content']['terminal_ids'] = [$terminal->getId()];

        $res = $this->startTest();
        $this->assertEquals([$terminal->getId()], $res['tokenization_success_terminal_ids']);
        $this->assertEquals([], $res['tokenization_failed_terminal_ids']);

        $terminal->reload();
        $this->assertEquals(base64_encode('5234567890123456'), $terminal['mc_mpan']);
        $this->assertEquals(base64_encode('4234567890123456'), $terminal['visa_mpan']);
        $this->assertEquals(base64_encode('6234567890123456'), $terminal['rupay_mpan']);

        $terminal2->reload();
        $this->assertEquals('5334567890123456', $terminal2['mc_mpan']);
        $this->assertEquals('4334567890123456', $terminal2['visa_mpan']);
        $this->assertEquals('6334567890123456', $terminal2['rupay_mpan']);
    }

    public function testTokenizeExistingMpansSingleMpanShouldAlsoGetTokenized()
    {
        $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(base64_encode("4234564890123456"), $terminal['visa_mpan']);
    }

    // tests that none of the mpan of a terminal should get tokenized even if one fails
    public function testTokenizeExistingTerminalMpansTransaction()
    {
        $successTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => '5234567890123456',
            'visa_mpan'           => '4234567890123456',
            'rupay_mpan'          => '6234567890123456',
        ]);

        $failureTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'status'              => 'activated',
            'mc_mpan'             => '5334567890123456',
            'visa_mpan'           => '4334567890123456',
            'rupay_mpan'          => '6334567890123456',
        ]);

        $cardVault = Mockery::mock('RZP\Services\CardVault');

        $this->app->instance('mpan.cardVault', $cardVault);

        $cardVault->shouldReceive('tokenize')
                ->with(Mockery::type('array'))
                ->andReturnUsing
                (function ($input)
                {
                    // fail tokenization for one mpan
                    if ($input['secret'] === '4334567890123456')
                    {
                        throw new Exception\ServerErrorException(
                            'Request timedout at card vault service',
                            ErrorCode::SERVER_ERROR);
                    }

                    $token = base64_encode($input['secret']);

                    return $token;
                });


        $this->ba->cronAuth();

        $res = $this->startTest();

        $this->assertEquals([$successTerminal['id']], $res['tokenization_success_terminal_ids']);
        $this->assertEquals([$failureTerminal['id']], $res['tokenization_failed_terminal_ids']);

        $successTerminal->reload();
        $this->assertEquals('NTIzNDU2Nzg5MDEyMzQ1Ng==', $successTerminal['mc_mpan']);
        $this->assertEquals('NDIzNDU2Nzg5MDEyMzQ1Ng==', $successTerminal['visa_mpan']);
        $this->assertEquals('NjIzNDU2Nzg5MDEyMzQ1Ng==', $successTerminal['rupay_mpan']);

        $failureTerminal->reload();
        // asserts that no mpan got tokenized
        $this->assertEquals('5334567890123456', $failureTerminal['mc_mpan']);
        $this->assertEquals('4334567890123456', $failureTerminal['visa_mpan']);
        $this->assertEquals('6334567890123456', $failureTerminal['rupay_mpan']);
    }

    public function testCreateTerminalMpansShouldBeTokenized()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(base64_encode("5122600116743268"), $terminal['mc_mpan']);
        $this->assertEquals(base64_encode("4604901116743090"), $terminal['visa_mpan']);
        $this->assertEquals(base64_encode("6100020116743712"), $terminal['rupay_mpan']);
    }

    public function testCreateTerminalMpansTokenizationFailure()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $cardVault = Mockery::mock('RZP\Services\CardVault');

        $this->app->instance('mpan.cardVault', $cardVault);

        $cardVault->shouldReceive('tokenize')
                ->with(Mockery::type('array'))
                ->andReturnUsing
                (function ($input)
                {
                    // fail tokenization for one mpan
                    if ($input['secret'] === '4604901116743090')
                    {
                        throw new Exception\ServerErrorException(
                            'Request timedout at card vault service',
                            ErrorCode::SERVER_ERROR);
                    }

                    $token = base64_encode($input['secret']);

                    return $token;
                });

       $this->startTest();
    }

    public function testAdminFetchTerminalShouldNotHaveOriginalMpans()
    {
        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'mc_mpan'             => base64_encode('5234567890123456'),
            'visa_mpan'           => base64_encode('4234567890123456'),
            'rupay_mpan'          => base64_encode('6234567890123456'),
        ]);

        $this->ba->adminAuth();

        $res = $this->startTest();

        $responseTerminal = array_filter($res['items'], function ($item) use ($terminal)
        {
            return ($item['id'] === $terminal->getPublicId());
        });

        // asserts that detokenized mpans are not shown even to admins
        $this->assertEquals($responseTerminal[0]['mpan']['mc_mpan'],   base64_encode('5234567890123456'));
        $this->assertEquals($responseTerminal[0]['mpan']['visa_mpan'],   base64_encode('4234567890123456'));
        $this->assertEquals($responseTerminal[0]['mpan']['rupay_mpan'],   base64_encode('6234567890123456'));
    }

    public function testSmsSyncSaveTerminalTestOtp()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testTerminalEncryption()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getDbLastEntity('terminal');

        $gatewayTerminalPasswordDb  = $terminal->getRawOriginal()['gateway_terminal_password'];

        $this->assertNotEquals($gatewayTerminalPasswordDb, 'umesh12345678'); // asserts that it got encrypted

        $decryptedGatewayTerminalPassword  = Crypt::decrypt($gatewayTerminalPasswordDb);

        $this->assertEquals($decryptedGatewayTerminalPassword, 'umesh12345678');
    }

    // password should get encrypted using axis org's key
    public function testTerminalEncryptionAxisOrg()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
        ->setConstructorArgs([$this->app])
        ->setMethods(['getTreatment'])
        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($actionId, $feature, $mode)
            {
                return 'on';
            }) );

        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = MerchantEntity::AXIS_ORG_ID; // axis orgId

        $this->fixtures->create('org', ['id' => $orgId]);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
        ]);

        $url = '/merchants/1cXSLlUU8V9sXl/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getDbLastEntity('terminal');

        $this->assertEquals($terminal['org_id'], MerchantEntity::AXIS_ORG_ID);

        $gatewayTerminalPasswordDb  = $terminal->getRawOriginal()['gateway_terminal_password'];

        $this->assertNotEquals($gatewayTerminalPasswordDb, 'umesh12345678'); // asserts that it got encrypted

        $decryptedGatewayTerminalPassword  = Crypt::decrypt($gatewayTerminalPasswordDb, true, $terminal);

        $this->assertEquals($decryptedGatewayTerminalPassword, 'umesh123456789');

        $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);
        $this->expectExceptionMessage('The MAC is invalid.');

        // should not get decrypted using default RZP key, should get The MAC is invalid. exception
        $decryptedGatewayTerminalPassword  = Crypt::decrypt($gatewayTerminalPasswordDb); // Crypt will try to decrypt using default key as we have not passed entity
    }

    public function testCreateWalletPayzappTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateWalletBajajTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditWalletBajajTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData['testCreateWalletBajajTerminal']['request']['url'] = $url;

        $this->startTest($this->testData['testCreateWalletBajajTerminal']);

        $terminal = $this->getLastEntity('terminal', true);

        $tid = $terminal['id'];

        $newTerminalMerchantId = '98981234'; //new merchant id

        $data = [
            'gateway_merchant_id'=> $newTerminalMerchantId
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals($newTerminalMerchantId, $terminal['gateway_merchant_id']);
    }

    public function testCreateWalletBoostTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertTrue(in_array(Wallet::BOOST, $terminal[Terminal\Entity::ENABLED_WALLETS]));
    }

    public function testCreateWalletMCashTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertTrue(in_array(Wallet::MCASH, $terminal[Terminal\Entity::ENABLED_WALLETS]));
    }

    public function testCreateWalletTouchNGoTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertTrue(in_array(Wallet::TOUCHNGO, $terminal[Terminal\Entity::ENABLED_WALLETS]));
    }

    public function testCreateWalletGrabPayTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertTrue(in_array(Wallet::GRABPAY, $terminal[Terminal\Entity::ENABLED_WALLETS]));
    }

    public function testFetchMerchantsInfoForIIR()
    {
        $now       = Carbon::now()->timestamp;
        $merchants = [];

        $merchants[0] = $this->fixtures->merchant->createMerchantWithDetails(
            Org::RZP_ORG,
            'testIIRMid1234',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1234',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => $now,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1234.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => 'biz type 1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 1',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
            ]);

        $this->fixtures->org->createAxisOrg();

        $merchants[1] = $this->fixtures->merchant->createMerchantWithDetails(
            Org::AXIS_ORG_ID,
            'testIIRMid1235',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => $now,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => 'biz type 2',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
            ]);

        $merchants[2] = $this->fixtures->merchant->createMerchantWithDetails(
            Org::AXIS_ORG_ID,
            'testIIRMid1236',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1256',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => $now,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1236.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => 'biz type 2',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
            ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testCreateTerminalRupaySiHub()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateEmerchantpayTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testAssignTerminalWhenTerminalExistForEmerchantPayGatewayButDifferentGatewayMerchantId()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('terminal', [
            'merchant_id'           => $merchant->getId(),
            'gateway'               => 'emerchantpay',
            'category'              => '2222',
            'gateway_merchant_id'   => '12345',
            'gateway_acquirer'      => 'razorpay',
            'gateway_terminal_id'   => 'empoli',
            'international'         => true,
            'app'                   => 1,
            'currency'              =>['EUR']
        ]);

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['content']['merchant_id'] =  $merchant->getId();

        $this->startTest();
    }

    public function testEditRupaySiHubTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_rupay_sihub_terminal');

        $tid = $terminal['id'];

        $data = [
            'gateway_access_code' => 'random'
        ];

        $this->editTerminal($tid, $data);

        $terminal = $this->getEntityById('terminal', $tid, true);

        $this->assertEquals('random', $terminal['gateway_access_code']);
    }

    public function testGetSalesforceDetailsForMerchantIDs() {
        $salesForceResponsePayload = [
            'random-MID-123' => [
                'owner_role' => 'KAM'
            ],
            'random-MID-124' => [
                'owner_role' => 'Sales'
            ]
        ];


        $this->salesforceMock->shouldReceive('getSalesforceDetailsForMerchantIDs')
            ->times(1)
            ->andReturnUsing(function() use ($salesForceResponsePayload){
                return $salesForceResponsePayload;
            });

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    protected function setUpSalesforceMock(): void
    {
        $this->salesforceMock = Mockery::mock('RZP\Services\SalesForceClient', $this->app)->makePartial();

        $this->salesforceMock->shouldAllowMockingProtectedMethods();

        $this->app['salesforce'] = $this->salesforceMock;
    }

    public function testCreateCheckoutDotComTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCheckoutDotComRecurringTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCheckoutDotComNonRecurringTerminal()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreateCheckoutDotComTerminalAllTypes()
    {
        $url = '/merchants/100000Razorpay/terminals';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditToCheckoutDotComRecurringTerminal()
    {
        $originalTerminal = $this->fixtures->create("terminal:checkout_dot_com_non_recurring_terminal");

        $url = '/terminals/'.$originalTerminal['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
        $editedTerminal = $this->getLastEntity('terminal', true);

        $this->assertEquals($editedTerminal['type'], ['non_recurring', 'recurring_3ds', 'recurring_non_3ds']);
    }

    // God mode editing skips terminal gateway specific validations written in Terminal/Validator.php
    public function testEditTerminalWithoutGodMode()
    {
        $originalTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
        ]);

        $url = '/terminals/'.$originalTerminal['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    // All fields are editable in God mode (gateway validations are skipped)
    public function testEditTerminalWithGodModeEdit()
    {
        $originalTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
        ]);

        $url = '/terminals/god_mode_edit/'.$originalTerminal['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $editedTerminal = $this->getLastEntity('terminal', true);

        $this->assertEquals($editedTerminal['gateway_merchant_id'], 'editedMid'); // asserts that gateway_merchant_id got edited
    }

    public function testGetTerminalEditableFields()
    {
        $this->startTest();
    }

    public function testEditTerminalWithGodModeEditWithOnlyDSFeature()
    {
        $this->fixtures->create('feature', [
            'entity_id' => '10000000000000',
            'name'   => 'only_ds',
            'entity_type' => 'merchant',
        ]);

        $originalTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'hitachi',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
        ]);

        $url = '/terminals/god_mode_edit/'.$originalTerminal['id'];

        $testData = $this->testData['testEditTerminalWithoutGodMode'];

        $testData['request']['url'] = $url;

        $this->startTest($testData);
    }

    public function testDisableTerminalWithOnlyDsWhenOnlyOneTerminal()
    {
        $this->fixtures->create('feature', [
            'entity_id' => '10000000000000',
            'name'   => 'only_ds',
            'entity_type' => 'merchant',
        ]);

        $originalTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
            'type'    => [
                'direct_settlement_with_refund' => '1'
            ],
        ]);

        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals/term_'.$originalTerminal['id']. '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->privateAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testDisableTerminalWithOnlyDsWhenMoreThanOneTerminal()
    {
        $this->fixtures->create('feature', [
            'entity_id' => '10000000000000',
            'name'   => 'only_ds',
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
            'type'    => [
                'direct_settlement_with_refund' => '1'
            ],
        ]);

        $originalTerminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
            'type'    => [
                'direct_settlement_with_refund' => '1'
            ],
        ]);

        $this->app['basicauth']->setPartnerMerchantId('10000000000000');

        $this->fixtures->merchant->addFeatures(FeatureConstants::TERMINAL_ONBOARDING);

        $url = '/terminals/term_'.$originalTerminal['id']. '/disable';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->privateAuth();

        $this->startTest($this->testData[__FUNCTION__]);
    }

    public function testUnassignTheOnlyNonDSTerminalOfMerchantWithOnlyDs()
    {
        $this->ba->adminAuth();

        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000001',
            'status'              => 'activated',
            'visa_mpan'           => '4234564890123456',
            'gateway_terminal_id' => 'tid12345',
            'type'    => [
                'direct_settlement_with_refund' => '1'
            ],
        ]);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('feature', [
            'entity_id' => '10000000000000',
            'name'   => 'only_ds',
            'entity_type' => 'merchant',
        ]);

        $url = '/terminals/' . $terminal['id'] . '/reassign';

        $requestContent = ['merchant_id' => $merchant['id']];

        $request = [
            'url'    => $url,
            'method' => 'PUT',
            'content' => $requestContent,
        ];

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->startTest($this->testData[__FUNCTION__]);

    }

    public function testCreatePayuSodexoTerminal()
    {
        $this->startTest();
    }

    public function testEditPayuSodexoTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'payu',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'card' => 1,
                'mode' => 3,
                'type'    => [
                    'non_recurring' => '1'
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'card' => "1",
            'type'    => [
                'sodexo' => '1',
                'non_recurring'=>'1'
            ],
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "1", $content['card']);

        $this->assertEquals(['non_recurring','sodexo'],$content['type']);
    }

    public function testCreateOptimizerRazorpayTerminal()
    {
        $this->startTest();
    }

    public function testEditOptimizerRazorpayTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'optimizer_razorpay',
                'gateway_merchant_id' => '250000002',
                'gateway_secure_secret' => "1231424",
                'card' => 1,
                'netbanking' => 1,
                'mode' => 2,
                'type'    => [
                    'optimizer'     => 1,
                    'non_recurring' => 1,
                    'direct_settlement_with_refund' => 1,
                ],
            ]);
        $tid = $terminal['id'];

        $data = [
            'upi' => 1
        ];

        $content = $this->editTerminal($tid, $data);

        $this->assertEquals( "1", $content['upi']);
        $this->assertEquals( "1", $content['netbanking']);
        $this->assertEquals( "1", $content['card']);
    }

    public function testUniversalValidRoutes()
    {
        $validRoutes = [
            'universal/bulkterminaldisable',
            'universal/terminal/fourteendigits',
        ];

        foreach ($validRoutes as $route) {
            $this->assertNull((new TerminalController())->validateUniversalAdminCall($route));
        }
    }

    public function testInvalidUniversalRoutes()
    {
        $invalidRoutes = [
            'universal/bulk/notFourteenDigits',
            'universal/moreThanTwentyFiveCharacters',
        ];

        foreach ($invalidRoutes as $route) {
            $this->expectException(\RZP\Exception\BadRequestException::class);
            $this->expectExceptionCode(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
            (new TerminalController())->validateUniversalAdminCall($route);
        }
    }
}
