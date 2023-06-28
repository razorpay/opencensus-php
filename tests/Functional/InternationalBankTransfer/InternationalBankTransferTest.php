<?php

namespace RZP\Tests\Functional\InternationalBankTransfer;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Payment\B2bUploadInvoice;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Payment\Entity as Payment;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class InternationalBankTransferTest extends TestCase
{
    use AttemptTrait;
    use CreatesInvoice;
    use FileHandlerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;
    use AttemptReconcileTrait;

    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/InternationalBankTransferTestData.php';

        parent::setUp();


        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $slackMock = Mockery::mock();

        $slackMock->shouldReceive('queue');

        $this->app->instance('slack', $slackMock);
    }

    public function testCreateAccountForCurrencyCloud()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_intl_bank_transfer',$merchantDetail['merchant_id']);

        $this->fixtures->merchant->enableInternational($merchantDetail['merchant_id']);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 1;
        $request['content']['va_currency'] = "USD";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(1,$content);

        $mii = $this->getLastEntity('merchant_international_integrations',true);

        $this->assertEquals($merchantDetail['merchant_id'],$mii['merchant_id']);

        $this->assertEquals("currency_cloud",$mii['integration_entity']);

        $this->assertNotNull($mii['integration_key']);

        $this->assertNotNull($mii['reference_id']);

        $this->assertNotNull($mii['bank_account']);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 0;
        $request['content']['va_currency'] = "swift";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);
        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);

    }

    public function testCreateAccountForCurrencyCloudPricingPlan()
    {
        $merchant = $this->fixtures->merchant->create(["id"=>'10000merchant1']);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $merchant['id']]);
        $merchantDetail = $this->fixtures->merchant_detail->create(['merchant_id' => $merchant['id']]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_intl_bank_transfer',$merchantDetail['merchant_id']);

        $this->fixtures->pricing->createPricingPlanWithoutMethods("dummyId",["intl_bank_transfer"]);

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan('dummyId', $merchantDetail['merchant_id']);

        $this->fixtures->merchant->enableInternational($merchantDetail['merchant_id']);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 1;
        $request['content']['va_currency'] = "USD";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->sendRequest($request);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 0;
        $request['content']['va_currency'] = "swift";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->sendRequest($request);

        $this->ba->adminAuth();
        $pricing_plan = $this->fetchMerchantPricingPlan($merchantDetail['merchant_id']);

        $pricing_rule = [];
        foreach ($pricing_plan['rules'] as $rule) {
            if($rule['payment_method'] === 'intl_bank_transfer') {
                $pricing_rule[] = $rule;
            }
        }

        $this->assertCount(2,$pricing_rule);
    }

    public function testCreateAccountForCurrencyCloudPricingPlanMultipleMerchant()
    {
        $planId = "dummyId2";
        $merchant = $this->fixtures->merchant->create(["id"=>'10000merchant2']);
        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant['id'],
            'contact_mobile' => '1234567890',
            'contact_email' => 'user1@email.com',
        ]);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $merchant['id']]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_intl_bank_transfer',$merchantDetail['merchant_id']);

        $this->fixtures->pricing->createPricingPlanWithoutMethods($planId,["intl_bank_transfer"]);

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan($planId, $merchant['id']);

        $this->fixtures->merchant->enableInternational($merchantDetail['merchant_id']);

        //merchant_2
        $merchant2 = $this->fixtures->merchant->create(["id"=>'10000merchant3']);
        $merchantDetail2 = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchant2['id'],
            'contact_mobile' => '1234567891',
            'contact_email' => 'user2@email.com',
        ]);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $merchant2['id']]);

        $this->fixtures->user->createUserForMerchant($merchantDetail2['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_intl_bank_transfer',$merchantDetail2['merchant_id']);

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan($planId, $merchant2['id']);

        //

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 1;
        $request['content']['va_currency'] = "USD";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->sendRequest($request);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['content']['accept_b2b_tnc'] = 0;
        $request['content']['va_currency'] = "swift";

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->sendRequest($request);

        $this->ba->adminAuth();
        $pricing_plan = $this->fetchMerchantPricingPlan($merchantDetail['merchant_id']);

        $pricing_rule = [];
        foreach ($pricing_plan['rules'] as $rule) {
            if($rule['payment_method'] === 'intl_bank_transfer') {
                $pricing_rule[] = $rule;
            }
        }

        $this->assertCount(2,$pricing_rule);
        $this->assertNotEquals($planId,$pricing_plan['id']);

        $this->ba->adminAuth();
        $pricing_plan = $this->fetchMerchantPricingPlan($merchantDetail2['merchant_id']);
        $pricing_rule = [];
        foreach ($pricing_plan['rules'] as $rule) {
            if($rule['payment_method'] === 'intl_bank_transfer') {
                $pricing_rule[] = $rule;
            }
        }
        $this->assertEquals($planId,$pricing_plan['id']);
        $this->assertCount(0,$pricing_rule);
    }

    public function testFailCreateAccountForCurrencyCloud()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->enableInternational($merchantDetail['merchant_id']);

        $this->mockMozartResponseForCurrencyCloud();

        $this->testData[__FUNCTION__];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->startTest();
    }

    public function testCashManagerTransactionNotificationForCurrencyCloud()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        // Test to increase txn limit for B2B intl_bank_transfer payments
        // Higher limit is now Rs 8.5L base amount
        // https://razorpay.slack.com/archives/C024U3B04LD/p1681131023230559
        $this->mockMozartResponseForCurrencyCloud(85000);

        $this->ba->directAuth();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $paymentEntity = $this->getLastPayment('payment', 'true');

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals('ach',$paymentEntity['wallet']);
        $this->assertEquals(83300000,$paymentEntity['base_amount']);
        $this->assertEquals(8500000,$paymentEntity['amount']);
        $this->assertEquals('IF-20230609-GFOTB9',$paymentEntity['reference1']);

        $this->testSendNotificationForB2B($paymentEntity);
    }

    public function testCashManagerTransactionNotificationForCurrencyCloudWithHeaderInInput()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals('ach',$paymentEntity['wallet']);
        $this->assertEquals(294000,$paymentEntity['base_amount']);
        $this->assertEquals(30000,$paymentEntity['amount']);
        $this->assertEquals('IF-20230609-GFOTB9',$paymentEntity['reference1']);
    }

    public function testCashManagerSWIFTTransactionFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockSWIFTPaymentWebhookCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals('swift',$paymentEntity['wallet']);
        $this->assertEquals(294000,$paymentEntity['base_amount']);
        $this->assertEquals(30000,$paymentEntity['amount']);
        $this->assertEquals('IF-20230609-GFOTB9',$paymentEntity['reference1']);
    }

    public function testCashManagerTransactionNotificationForCurrencyCloudWithDifferentMcc()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $mccMarkdownPercent = 2;
        $this->fixtures->merchant->addMccMarkdownPaymentConfig($mccMarkdownPercent,$merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];

        $response = $this->sendRequest($request);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals(294000,$paymentEntity['base_amount']);
        $this->assertEquals(30000,$paymentEntity['amount']);
        $this->assertEquals('IF-20230609-GFOTB9',$paymentEntity['reference1']);
    }

    public function testCashManagerTransactionNotificationForCurrencyCloudWithDifferentMethodSpecificMcc()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $mccMarkdownPercent = 2;
        $config = [
            'mcc_markdown_percentage' => "10",
            'intl_bank_transfer_ach_mcc_markdown_percentage' => "20",
            'intl_bank_transfer_swift_mcc_markdown_percentage' => "30",
        ];
        $this->fixtures->merchant->addMccMarkdownPaymentConfig($mccMarkdownPercent,$merchantDetail['merchant_id'],$config);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];

        $response = $this->sendRequest($request);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals(240000,$paymentEntity['base_amount']);
        $this->assertEquals(30000,$paymentEntity['amount']);
        $this->assertEquals('IF-20230609-GFOTB9',$paymentEntity['reference1']);
    }

    public function testTransferCompletedNotificationACHFromCurrencyCloud()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->edit($merchantDetail['merchant_id'], ['live' => true, 'activated' => 1]);

        $this->fixtures->pricing->create([
            'plan_id'        => 'IntbnkTrnsfrId',
            'payment_method' => 'intl_bank_transfer',
            'payment_network' => 'ach',
            'feature'             => 'payment',
            'percent_rate'    => 300,
        ]);

        $this->merchantAssignPricingPlan('IntbnkTrnsfrId', $merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $this->ba->directAuth();

        $firstRequest = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];
        $firstResponse = $this->makeRequestAndGetContent($firstRequest);

        $paymentEntity = $this->getLastPayment('payment',true);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'] , $merchantUser['id']);

        $request = [
            'url'    => '/payment/'.$paymentEntity['public_id'].'/update_b2b_invoice_details',
            'method' => 'patch',
            'content' => [
                'document_id' => "doc_1234567890"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['b2b_invoice_updated']);

        $this->fixtures->payment->edit($paymentEntity['id'], ['reference16' => 'e68301d3-5b04-4c1d-8f8b-13a9b8437040']);

        $this->fixtures->merchant->addFeatures('enable_settlement_for_b2b', $merchantDetail['merchant_id']);

        $this->testSendNotificationForB2B($paymentEntity);

        $secondRequest = $this->testData[__FUNCTION__]['request'];

        Payment::verifyIdAndStripSign($paymentEntity['id']);

        $secondRequest['content']['reason'] = "Sub Account Transfer to House; " . $paymentEntity['id'];

        $this->collectAddress($paymentEntity, $merchantUser['id']);

        $this->ba->directAuth();

        $secondResponse = $this->makeRequestAndGetContent($secondRequest);

        $updatedPaymentEntity = $this->getLastPayment('payment',true);

        $this->assertEquals($updatedPaymentEntity['status'],'captured');
    }

    public function testTransferCompletedNotificationSWIFTFromCurrencyCloud()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->edit($merchantDetail['merchant_id'], ['live' => true, 'activated' => 1]);

        $this->fixtures->pricing->create([
            'plan_id'        => 'IntbnkTrnsfrId',
            'payment_method' => 'intl_bank_transfer',
            'payment_network' => 'ach',
            'feature'             => 'payment',
            'percent_rate'    => 300,
        ]);

        $this->fixtures->pricing->create([
            'plan_id'        => 'IntbnkTrnsfrId',
            'payment_method' => 'intl_bank_transfer',
            'payment_network' => 'swift',
            'feature'             => 'payment',
            'percent_rate'    => 500,
        ]);

        $this->merchantAssignPricingPlan('IntbnkTrnsfrId', $merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockSWIFTPaymentWebhookCurrencyCloud();

        $this->ba->directAuth();

        $firstRequest = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];
        $firstResponse = $this->makeRequestAndGetContent($firstRequest);

        $paymentEntity = $this->getLastPayment('payment',true);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'] , $merchantUser['id']);

        $request = [
            'url'    => '/payment/'.$paymentEntity['public_id'].'/update_b2b_invoice_details',
            'method' => 'patch',
            'content' => [
                'document_id' => "doc_1234567890"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['b2b_invoice_updated']);

        $this->fixtures->payment->edit($paymentEntity['id'], ['reference16' => 'e68301d3-5b04-4c1d-8f8b-13a9b8437040']);

        $this->fixtures->merchant->addFeatures('enable_settlement_for_b2b', $merchantDetail['merchant_id']);

        $this->testSendNotificationForB2B($paymentEntity);

        $this->mockMozartResponseForCurrencyCloud();

        $secondRequest = $this->testData[__FUNCTION__]['request'];

        Payment::verifyIdAndStripSign($paymentEntity['id']);

        $secondRequest['content']['reason'] = "Sub Account Transfer to House; " . $paymentEntity['id'];

        $this->collectAddress($paymentEntity, $merchantUser['id']);

        $this->ba->directAuth();

        $secondResponse = $this->makeRequestAndGetContent($secondRequest);

        $updatedPaymentEntity = $this->getLastPayment('payment',true);

        $this->assertEquals($updatedPaymentEntity['status'],'captured');
    }

    public function testCaptureCronForB2BPayments()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $firstRequest = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];
        $firstResponse = $this->sendRequest($firstRequest);

        $paymentEntity = $this->getLastPayment(true);

        $this->fixtures->edit('payment',$paymentEntity['id'],[
            'reference2' => 'doc_10000011111112'
        ]);

        $this->fixtures->merchant->addFeatures('enable_settlement_for_b2b',$merchantDetail['merchant_id']);

        $this->collectAddress($paymentEntity, $merchantUser['id']);

        $this->ba->cronAuth();
        $secondRequest = $this->testData[__FUNCTION__]['request'];
        $secondResponse = $this->sendRequest($secondRequest);

        $updatedPaymentEntity = $this->getLastPayment(true);

        $this->assertNotNull($updatedPaymentEntity['reference16']);
    }

    public function testSettlementCronForB2BPayments()
    {
        $this->ba->cronAuth();
        $this->mockMozartResponseForCurrencyCloud();
        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);
        $this->assertResponseOk($response);
    }

    // test for collecting customer billing address
    // https://razorpay.slack.com/archives/C024U3B04LD/p1682496775025409?thread_ts=1681996740.555379&cid=C024U3B04LD
    public function testAddressCollection()
    {
        $payment = $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'status' => 'authorized', 'method' => 'intl_bank_transfer', 'gateway' => 'currency_cloud']);

        $response = $this->collectAddress($payment);
    }

    public function testGetBalanceDetailsForMerchantVA()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_global_account',$merchantDetail['merchant_id']);
        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'reference_id' => '67df28b4-766a-405d-b6ad-2972fd50be18',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals($content['currency'], "USD");
        $this->assertNotNull($content['amount']);
        $this->assertNotNull($content['account_id']);

    }

    public function testCreateBeneficiaryForMerchantInCC()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->merchant->addFeatures('enable_global_account',$merchantDetail['merchant_id']);
        $this->fixtures->merchant->addFeatures('enable_b2b_export',$merchantDetail['merchant_id']);
        $internationalIntegrationRequest =  $this->testData['createInternationalIntegration']['request'];
        $internationalIntegrationRequest['content'] = [
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'reference_id' => '67df28b4-766a-405d-b6ad-2972fd50be18',
            'notes' => [],
        ];
        $this->ba->adminAuth();
        $response = $this->sendRequest($internationalIntegrationRequest);
        $content = $this->getJsonContentFromResponse($response);

        $this->mockMozartResponseForCurrencyCloud();

        $this->ba->adminAuth();

        $request = $this->testData[__FUNCTION__]['request'];
        $request['url'] = "/merchant/".$merchantDetail['merchant_id']."/international/virtual_accounts/beneficiary";
        $response = $this->sendRequest($request);
        $content = $this->getJsonContentFromResponse($response);

        $this->assertNotNull($content['id']);
        $this->assertNotNull($content['account_number']);
        $this->assertNotNull($content['name']);

        $mii = $this->getLastEntity('merchant_international_integrations',true);

        $this->assertNotNull($mii['notes']['beneficiary_id']);
        $this->assertEquals($content['id'],$mii['notes']['beneficiary_id']);
    }

    protected function mockMozartResponseForCurrencyCloud($amount = 0)
    {
        $mozartServiceMock = $this->getMockBuilder(\RZP\Services\Mock\Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $defaultAmount = 300;

        $amount = ($amount > 0) ? $amount : $defaultAmount;

        $mozartServiceMock->method('sendMozartRequest')
            ->will($this->returnCallback(
                function ($namespace,$gateway,$action,$data,$version) use ($amount)
                {
                    if($action == 'account_create')
                    {
                        return [
                            'data' =>[
                                'account_id'    => "66f51c98-1ef8-4e48-97de-aac0353ba2b4",
                                'account_status'=> "enabled",
                                'contact_id'    => "67df28b4-766a-405d-b6ad-2972fd50be18",
                                'contact_status'=> "enabled",
                                'status'        => "account_creation_successful",
                            ]
                        ];
                    }
                    elseif ($action == 'get_funding_account')
                    {
                        return [
                            'data' => [
                                "funding_accounts" => [
                                    [
                                        "id" => "1c3a920b-87fc-4c61-8b47-ce924a348215",
                                        "account_id" => "0c96a02f-996c-4856-8364-d7041849bf4d",
                                        "account_number" => "0332452785",
                                        "account_number_type" => "account_number",
                                        "account_holder_name" => "Sid Pvt Limited",
                                        "bank_name" => "Community Federal Savings Bank",
                                        "bank_address" => "810 Seventh Avenue, New York, NY 10019, US",
                                        "bank_country" => "US",
                                        "currency" => $data['currency'],
                                        "payment_type" => "regular",
                                        "routing_code" => "026073880",
                                        "routing_code_type" => "wire_routing_number",
                                        "created_at" => "2022-08-23T11:44:03+00:00",
                                        "updated_at" => "2022-08-23T11:44:03+00:00"
                                    ]
                                ]
                            ]
                        ];
                    }
                    elseif ($action == 'get_sender_detail')
                    {
                        return [
                            'data' => [
                                'id'                      => "e68301d3-5b04-4c1d-8f8b-13a9b8437040",
                                'amount'                  => $amount,
                                'currency'                => "USD",
                                'additional_information'  => "USTRD-0001",
                                'value_date'              => "2018-07-04T00:00:00+00:00",
                                'sender'                  => "David Jenkins; 31 High Street, Brighton, East Sussex, BN1 2NW;GB;1111111111;;00000000",
                                'receiving_account_number'=> null,
                                "receiving_account_iban"  => null,
                                "created_at"              => "2018-07-04T14:57:38+00:00",
                                "updated_at"              => "2018-07-04T14:57:39+00:00",
                                "status"                  => "successful"
                            ]
                        ];
                    }
                    elseif ($action == 'create_transfer')
                    {
                        return [
                            'data' => [
                                'id' => 'e68301d3-5b04-4c1d-8f8b-13a9b8437040',
                                'amount' => $data['amount'],
                                'currency' => $data['currency']
                            ]
                        ];
                    }
                    elseif ($action == 'get_balance')
                    {
                        return [
                            'data' => [
                                'id'     => 'e68301d3-5b04-4c1d-8f8b-13a9b8437040',
                                'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                                'currency' => $data['currency'],
                                'amount' => "100.00"
                            ]
                        ];
                    }
                    elseif ($action == 'payment_create')
                    {
                        return [
                            'data' => [
                                'currency' => $data['currency'],
                                'amount'   => $data['amount'],
                            ]
                        ];
                    }
                    elseif ($action == 'create_beneficiary')
                    {
                        return [
                            'data' => [
                                'id' => '9f658618-db68-4d30-84b5-04239e335dc7',
                                'status' => 'successfull',
                                'account_number' => '1234123',
                                'bic_swift' => 'ICICINBBCTS',
                                'bank_account_holder_name' => 'Razorpay',
                                'name' => 'Razorpay'
                            ]
                        ];
                    }
                    elseif ($action == 'get_beneficiary')
                    {
                        return [
                            'data' => [
                                'account_number' => '1234123',
                                'bank_account_holder_name' => 'Razorpay Stage',
                                'bank_address' => ["NO.302,GROUND FLOOR,7TH CROSS,DOMLUR LAYOUT,BANGALORE - 560071"],
                                'bank_country'=> 'IN',
                                'bank_name'=> 'ICICI Bank',
                                'bic_swift'=> 'ICICINBBCTS',
                                'currency'=> 'USD',
                                'id'=> 'c5423ced-048c-4b63-9c83-f91b8d991e99',
                                'name'=> 'Razopay Payments',
                                'status' => 'successful',
                            ]
                        ];
                    }
                    elseif ($action == 'get_payments')
                    {
                        return [
                            'data' => [
                                'payments' => [
                                    [
                                        'amount' => '100.00',
                                        'beneficiary_id' => '54973a5b-3189-4a25-9596-3c0150705633',
                                        'currency'=> 'USD',
                                        'id' => 'd652109b-6b0a-4692-9cef-0062ca2cbba5',
                                        'payment_date' => '2023-03-13',
                                        'reason' => 'For Settling Money from RZP House account to Merchants',
                                        'status' => 'ready_to_send',
                                    ],
                                    [
                                        'amount' => '100.00',
                                        'beneficiary_id' => '54973a5b-3189-4a25-9596-3c0150705633',
                                        'currency'=> 'USD',
                                        'id' => 'd652109b-6b0a-4692-9cef-0062ca2cbba5',
                                        'payment_date' => '2023-03-13',
                                        'reason' => 'For Settling Money from RZP House account to Merchants',
                                        'status' => 'ready_to_send',
                                    ]
                                ]
                            ]
                        ];
                    }
                }
            ));

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function mockSWIFTPaymentWebhookCurrencyCloud()
    {
        $mozartServiceMock = $this->getMockBuilder(\RZP\Services\Mock\Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
            ->will($this->returnCallback(
                function ($namespace,$gateway,$action,$data,$version)
                {
                    if ($action == 'get_sender_detail')
                    {
                        return [
                            'data' => [
                                'id'                      => "e68301d3-5b04-4c1d-8f8b-13a9b8437040",
                                'amount'                  => "300",
                                'currency'                => "USD",
                                'additional_information'  => "USTRD-0001",
                                'value_date'              => "2018-07-04T00:00:00+00:00",
                                'sender'                  => "David Jenkins; 31 High Street, Brighton, East Sussex, BN1 2NW;GB;1111111111;;00000000",
                                'receiving_account_number'=> null,
                                "receiving_account_iban"  => "GB99OXPH94665099600083",
                                "created_at"              => "2018-07-04T14:57:38+00:00",
                                "updated_at"              => "2018-07-04T14:57:39+00:00",
                                "status"                  => "successful"
                            ]
                        ];
                    }
                }
            ));
        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function collectAddress($payment, $merchantUserId = null)
    {
        $this->fixtures->merchant->addFeatures('enable_intl_bank_transfer', $payment['merchant_id']);

        $addressPayload = [
            'url' => '/v1/b2b-exports/' . $payment['public_id'] . '/address',
            'method' => 'put',
            'content' => [
                'name'      => 'business_name',
                'zipcode'   => '201301',
                'line1'     => 'address_line1',
                'city'      => 'city',
                'country'   => 'us',
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' . $payment['merchant_id'], $merchantUserId);

        $response = $this->makeRequestAndGetContent($addressPayload);

        $this->assertNotNull($response);
        $this->assertEquals($response['entity_type'], 'payment');
        $this->assertEquals($response['entity_id'], preg_replace('/^pay_/', '', $payment['id']));
        $this->assertEquals($response['name'], $addressPayload['content']['name']);
        $this->assertEquals($response['zipcode'], $addressPayload['content']['zipcode']);
        $this->assertEquals($response['line1'], $addressPayload['content']['line1']);
        $this->assertEquals($response['city'], $addressPayload['content']['city']);
        $this->assertEquals($response['country'], $addressPayload['content']['country']);

        return $response;
    }

    protected function testSendNotificationForB2B($payment)
    {
        Mail::fake();

        $notificationPayload = [
            'url' => '/v1/b2b-exports/notification',
            'method' => 'post',
            'content' => [
                'limit' => 1,
                'offset' => 0,
                'payment_ids' => [preg_replace('/^pay_/', '', $payment['id'])],
                'include_merchants' => [$payment['merchant_id']],
                'exclude_merchants' => ['HgcHwOUQYViVHE'],
            ]
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($notificationPayload);

        $this->assertNotNull($response);
        $this->assertEquals($response['email_reports']['upload_invoice']['total_payments'], 1);

        Mail::assertQueued(B2bUploadInvoice::class, function ($mail) use ($payment)
        {
            $expectedId = preg_replace('/^pay_/', '', $payment['id']);
            $actualId = preg_replace('/^pay_/', '', $mail->viewData['payment']['id']);

            $this->assertEquals($expectedId, $actualId);

            return true;
        });
    }

    // Test to increase txn limit for B2B intl_bank_transfer payments
    // Higher limit is now Rs 8.5L base amount
    // https://razorpay.slack.com/archives/C024U3B04LD/p1681131023230559
    public function testCashManagerTransactionNotificationForCurrencyCloudWithMaxPaymentAllowed()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        // Suggestive amount. Test conversion is factor of 10.
        $this->mockMozartResponseForCurrencyCloud(85001);

        $request = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];

        $this->makeRequestAndCatchException(function() use ($request)
            {
                $this->sendRequest($request);
            }, BadRequestException::class, 'Payment failed');
    }


    public function testDirectCaptureFailureForCurrencyCloud()
    {

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_international_integrations',[
            'merchant_id' => $merchantDetail['merchant_id'],
            'integration_entity' => 'currency_cloud',
            'integration_key' => '15b78101-0142-44a1-9758-8f7262429e9b',
            'notes' => [],
        ]);

        $this->mockMozartResponseForCurrencyCloud();

        $this->ba->directAuth();

        $request = $this->testData['testCashManagerTransactionNotificationForCurrencyCloud']['request'];

        $response = $this->makeRequestAndGetContent($request);

        $paymentEntity = $this->getLastPayment('payment', 'true');

        $this->assertEquals('authorized',$paymentEntity['status']);
        $this->assertEquals('currency_cloud',$paymentEntity['gateway']);
        $this->assertEquals('intl_bank_transfer',$paymentEntity['method']);
        $this->assertEquals('ach',$paymentEntity['wallet']);
        $this->assertEquals(294000,$paymentEntity['base_amount']);
        $this->assertEquals(30000,$paymentEntity['amount']);

        $testData = $this->testData[__FUNCTION__];
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $testData['request']['url'] = '/payments/' . $paymentEntity['id'] . '/capture';
        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $response = $this->sendRequest($testData['request']);
            var_dump($response);
        }, BadRequestException::class, 'No approved preauth transaction was found.');
    }

    // Stop capture reminder emails being sent to B2B export merchants
    // for intl_bank_transfer payments.
    // Slack: https://razorpay.slack.com/archives/C024U3B04LD/p1685525446278749?thread_ts=1685432424.957589&cid=C024U3B04LD
    public function testCaptureReminderMailNotSent()
    {
        Mail::fake();

        $payment = $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'status' => 'authorized', 'method' => 'intl_bank_transfer', 'gateway' => 'currency_cloud']);

        $createdAt = Carbon::today(Timezone::IST)->subDays(1)->timestamp;

        $payment = $this->fixtures->edit('payment', $payment->getId(),
            ['authorized_at' => $createdAt, 'created_at' => $createdAt]);

        $cronPayload = [
            'url' => '/v1/payments/all/reminder',
            'method' => 'get',
            'content' => []
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($cronPayload);

        $this->assertNotNull($response);

        $this->assertEquals(0, $response['initial']['counts']['payments']);
        $this->assertEquals(0, $response['initial']['counts']['merchants']);
        $this->assertEquals(0, $response['initial']['counts']['failures']);

        $this->assertEquals(0, $response['final']['counts']['payments']);
        $this->assertEquals(0, $response['final']['counts']['merchants']);
        $this->assertEquals(0, $response['final']['counts']['failures']);

        Mail::assertNotSent(AuthorizedPaymentsReminder::class);
    }
}
