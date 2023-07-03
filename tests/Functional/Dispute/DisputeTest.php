<?php

namespace RZP\Tests\Functional\Dispute;

use DB;
use Mail;
use Cache;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Dispute;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Models\Dispute\Phase;
use RZP\Models\Dispute\Entity;
use RZP\Models\Terminal\Category;
use RZP\Services\RazorXClient;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Dispute\BulkCreation;
use Functional\Dispute\DisputeTrait;
use RZP\Services\Mock\DisputesClient;
use RZP\Models\Dispute\Reason\Network;
use RZP\Exception\BadRequestException;
use RZP\Services\FreshdeskTicketClient;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Dispute\EmailNotificationStatus;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Dispute\File\Core as DisputeFileCore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Dispute\File\Service as DisputeFileService;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Models\Dispute\Customer\FreshdeskTicket\ReasonCode;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Subcategory;
use RZP\Mail\Dispute\BulkCreation as DisputeBulkCreationMail;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;
use RZP\Mail\Dispute\Admin\AcceptedAdmin as DisputeAcceptedForAdminMail;
use RZP\Mail\Dispute\Admin\SubmittedAdmin as DisputeSubmittedForAdminMail;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Constants\Entity as EntityConstant;

class DisputeTest extends TestCase
{
    use FreshdeskTrait;
    use DisputeTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected $druidMock;

    protected $salesforceMock;

    const SECONDS_IN_DAY = 24 * 60 * 60;

    protected $payment = null;

    protected $merchant = null;

    protected $repo = null;

    const TableVsEntityList = [
        Table::DISPUTE => EntityConstant::DISPUTE,
        Table::DISPUTE_REASON => EntityConstant::DISPUTE_REASON,
        Table::DISPUTE_EVIDENCE => EntityConstant::DISPUTE_EVIDENCE,
        Table::DISPUTE_EVIDENCE_DOCUMENT => EntityConstant::DISPUTE_EVIDENCE_DOCUMENT,
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeTestData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $this->setUpDruidMock();

        $this->setUpSalesforceMock();

        $this->setUpFreshdeskClientMock();

        $this->setUpDisputeClientMock();
    }

    protected function setUpDisputeClientMock()
    {
        $mockDisputeClient = new DisputesClient();

        $this->app->instance('disputes', $mockDisputeClient);
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testDisputeCreate()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'amount_deducted'   => 0,
            'internal_status'   => 'open',
        ], $dispute);

        $this->assertEqualsWithDelta($dispute['created_at'] + self::SECONDS_IN_DAY * 10, $dispute['internal_respond_by'], 5);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);
    }

    public function testDomesticDisputeCreateWithExcessGatewayAmount()
    {
        $payment = $this->fixtures->create('payment:captured', [
            'amount' => 500,
            'base_amount' => 500,
            'amount_authorized' => 500,
        ]);

        $testData = $this->updateCreateTestData($payment->getPublicId());

        $this->startTest($testData);
    }

    public function testInternationalDisputeCreateAudInr()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 1000, 'currency' => 'AUD', 'base_amount' => 10000]);

        $testData = $this->updateCreateTestData($payment->getPublicId());

        $testData['response']['content']['payment_id'] = $payment->getPublicId();

        $store = Cache::store();

        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        Cache::shouldReceive('store')
            ->withAnyArgs()
            ->andReturn($store);

        Cache::shouldReceive('get')
            ->times(2)
            ->with('currency:exchange_rates_INR')
            ->andReturnUsing(function ()
                {
                    return ['AUD' => 0.09];
                });

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(0, $dispute['amount_deducted']);
        $this->assertEquals(10000, $dispute['base_amount']);
        $this->assertEquals('INR', $dispute['base_currency']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);
    }

    public function testDisputeCreateWAmountAndGatewayAmount()
    {
        $testData = $this->updateCreateTestData();

        $this->startTest($testData);
    }

    public function testDisputeCreateWithInternalRespondBy()
    {
        $testData = $this->updateCreateTestData();

        $this->startTest($testData);
    }


    public function testLostInternationalDispute()
    {
        $payment = $this->fixtures->create('payment:captured', ['amount' => 1000, 'currency' => 'AUD', 'base_amount' => 10000, 'disputed' => 1]);

        $reason = $this->fixtures->create('dispute_reason');

        $attributes = [
            'amount'      => 1000,
            'currency'    => 'AUD',
            'base_amount' => 10000,
            'base_currency' => 'INR',
            'gateway_amount' => 10000,
            'gateway_currency' => 'INR',
            'merchant_id' => $payment->getMerchantId(),
            'payment_id' => $payment->getId(),
            'reason_id' => $reason['id'],
            'conversion_rate' => 100000,
        ];

        $dispute = $this->fixtures->dispute->create($attributes);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] ='/disputes/' . $dispute->getPublicId();

        $this->ba->adminProxyAuth($payment->getMerchantId(), 'rzp_test_' . $payment->getMerchantId());

        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $content = $this->runRequestResponseFlow($testData);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testData['request']['content']['status'], $content['status']);
        $this->assertEquals(1000, $dispute['amount']);
        $this->assertEquals($dispute['base_amount'], $dispute['amount_deducted']);
        $this->assertEquals(0,
            $dispute['amount_reversed']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustment['entity_id']);
        $this->assertEquals(-10000, $adjustment['amount']);
    }

    public function testDisputeCreateWithSkipEmail()
    {
        $testData = $this->updateCreateTestData();

        $this->startTest($testData);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(EmailNotificationStatus::DISABLED, $dispute[Entity::EMAIL_NOTIFICATION_STATUS]);
    }

    public function testDisputeCreatedWebhook()
    {
        $payment = $this->doAuthAndCapturePayment();

        $paymentId = $payment['id'];

        $testData = $this->updateCreateTestData($paymentId);

        $eventTestDataKey = 'testDisputeCreatedWebhookEventData';

        $this->expectWebhookEventWithContents('payment.dispute.created', $eventTestDataKey);

        $this->testData[$eventTestDataKey]['payload']['dispute']['entity']['payment_id'] = $paymentId;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testDisputeCreateWithDeductRefundRecoveryMethod()
    {
        $payment = $this->fixtures->create('payment', [
            'method' => 'netbanking',
        ]);

        $testData = $this->updateCreateTestData($payment->getPublicId());

        $this->startTest($testData);
    }

    public function testDisputeCreateWithDeductAdjustmentRecoveryMethod()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals([
            'disputed'          => true,
            'amount_refunded'   => 0,
            'status'            => 'captured',
        ], $payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $transaction['type']);

        $this->assertEquals(100, $transaction['amount']);

        $this->assertEquals(100, $transaction['debit']);

        $this->assertEquals(0, $transaction['credit']);

        $adjustment = $this->getDbLastEntity('adjustment');

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'status'                => 'open',
            'deduct_at_onset'       => true,
            'amount_deducted'       => 100,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => $adjustment['id'],
        ], $dispute);
    }

    public function assertDualWriteDisputeEntityById($table, $actualEntityDataSent, $action)
    {
        $expectedDataSent = [];

        if ($table === Table::DISPUTE_EVIDENCE_DOCUMENT)
        {
            foreach ($actualEntityDataSent["evidence_documents"] as $actualData)
            {
                $expectedDataSent = $this->getTrashedDbEntityById(self::TableVsEntityList[$table], $actualData['id'])
                                         ->toDualWriteArray();

                unset($actualData["type"]);

                $this->assertEquals($expectedDataSent, $actualData);
            }

            return;
        }

        if ($table === Table::DISPUTE_EVIDENCE)
        {
            $expectedDataSent = $this->getTrashedDbEntityById(self::TableVsEntityList[$table], $actualEntityDataSent['id'])
                                     ->toDualWriteArray();
        }
        else
        {
            $expectedDataSent = $this->getDbEntityById(self::TableVsEntityList[$table], $actualEntityDataSent['id'])
                                     ->toDualWriteArray();
        }

        $this->assertEquals($expectedDataSent, $actualEntityDataSent);
    }

    public function testDisputeCreateWithDeductAtOnsetMerchantValidationFailure()
    {
        $testData = $this->updateCreateTestData();

        $merchantId = $this->payment->merchant->getId();

        $this->setUpFixtures(['merchant_id' => $merchantId]);

        $this->fixtures->merchant->addFeatures(['exclude_deduct_dispute']);

        $this->startTest($testData);

        $this->fixtures->merchant->removeFeatures(['exclude_deduct_dispute']);

        $this->fixtures->edit('merchant', $merchantId, ['category' => '6211']);

        $testData['response']['content']['error']['description'] = 'Deduct At Onset Dispute can not be created for this Merchant Category';

        $this->startTest($testData);
    }

    public function testDisputeCreateWithDeductAtOnsetGovernmentMerchantValidationFailure()
    {
        $testData = $this->updateCreateTestData();

        $merchantId = $this->payment->merchant->getId();

        $this->setUpFixtures(['merchant_id' => $merchantId]);

        $this->fixtures->edit('merchant', $merchantId, ['category2' => Category::GOVERNMENT]);

        $this->startTest($testData);
    }

    public function testDisputeCreateWithDeductAdjustmentRecoveryMethodWithoutEnoughBalance()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->fixtures->refund->createFromPayment(['payment' => $payment]);

        $testData = $this->updateCreateTestData('pay_' . $payment->getId());

        $testData['request']['content']['amount'] = $payment->getAmount();

        $this->startTest($testData);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $transaction['type']);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertNotNull($adjustment);
        $this->assertEquals('processed', $adjustment['status']);
    }

    public function testDisputeCreateWithoutReason()
    {
        $testData = $this->updateCreateTestData();

        $testData['request']['content']['reason_id'] = null;

        $this->startTest($testData);
    }

    public function testDisputeCreateWithExtraFields()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateOnDisputedPayment()
    {
        $dispute = $this->fixtures->create('dispute');

        $this->updateCreateTestData('pay_'.$dispute['payment_id']);

        $this->startTest();
    }

    public function testDisputeCreateWithAmountGreaterThanPayment()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithAmountLessThanMin()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithInvalidPhase()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateCreateTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->runRequestResponseFlow($testData);

        $disputeChild = $this->getLastEntity('dispute', true);

        $this->assertEquals($disputeParent->getId(), $disputeChild['parent_id']);
    }

    public function testDisputeCreateWithDuplicateParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $this->fixtures->create('dispute', ['parent_id' => $disputeParent->getId()]);

        $testData = $this->updateCreateTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeCreateNonTransactionalPhaseDeductAtOnset()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithNonArrayMerchantEmail()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithInvalidMerchantEmail()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithWhitespaceMerchantEmail()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeEdit()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeEditWon()
    {
        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeWonEventData';

        $this->expectWebhookEventWithContents('payment.dispute.won', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditWonPostDeduct()
    {
        $data = $this->updateEditTestData(['deduct_at_onset' => 1, 'amount' => 1000000,
            'deduction_source_type' => 'adjustment', 'deduction_source_id' => 'randomAdjstId1']);

        $eventTestDataKey = 'testDisputeWonEventPostDeductData';

        $this->expectWebhookEventWithContents('payment.dispute.won', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals([
            'status' => 'captured',
            'disputed' => false,
            'amount_refunded' => 0,
        ], $payment);

        $this->assertEquals(false, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $dispute);
    }

    public function testDisputeEditClose()
    {
        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeClosedEventData';

        $this->expectWebhookEventWithContents('payment.dispute.closed', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $adj = $this->getLastEntity('adjustment', true);

        $this->assertNull($adj);
    }

    public function testDisputeEditDeductOnLost()
    {
        $data = $this->updateEditTestData();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $this->ba->adminProxyAuth();

        $eventTestDataKey = 'testDisputeLostEventData';

        $this->expectWebhookEventWithContents('payment.dispute.lost', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals([
            'disputed' => false,
            'status'   => 'refunded',
        ], $payment);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertNotNull($dispute['deduction_source_type']);

        $this->assertNotNull($dispute['deduction_source_id']);

        $this->assertEquals('lost_merchant_debited', $dispute['internal_status']);
    }

    public function testDisputeEditDeductForNoBalance()
    {
        $data = $this->updateEditTestData();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $this->ba->adminProxyAuth();

        $eventTestDataKey = 'testDisputeLostEventData';

        $this->expectWebhookEventWithContents('payment.dispute.lost', $eventTestDataKey);

        $this->fixtures->edit('balance', '10000000000000',
            [
                'balance'      => 0,
            ]);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArraySelectiveEquals([
            'disputed' => false,
            'status'   => 'refunded',
        ], $payment);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertNotNull($dispute['deduction_source_type']);

        $this->assertNotNull($dispute['deduction_source_id']);

        $this->assertEquals('lost_merchant_debited', $dispute['internal_status']);
    }

    public function testMerchantEditWhenDisputeUnderReview()
    {
        $attributes = [
            'status' => 'under_review'
        ];

        $data = $this->updateEditTestData($attributes);

        // Run as merchant
        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($data);
    }

    public function testMerchantEditAcceptAndSubmit()
    {
        $data = $this->updateEditTestData();

        // Run as merchant
        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($data);
    }

    public function testDisputeEditDoNotDeductOnLostIfDeducted()
    {
        $data = $this->updateEditTestData(['deduct_at_onset' => 1, 'amount' => 1000000,
                                           'deduction_source_type' => 'adjustment', 'deduction_source_id' => 'randomAdjstId1']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $this->ba->adminProxyAuth();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $this->assertEquals(false, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertNotNull($dispute['deduction_source_type']);

        $this->assertNotNull($dispute['deduction_source_id']);

        $this->assertEquals('lost_merchant_debited', $dispute['internal_status']);
    }

    public function testDisputeEditWithDeductAtOnsetToInternalStatusLostMerchantNotDebited()
    {
        $data = $this->updateEditTestData(['status'=> 'under_review',
                                           'deduct_at_onset' => 1,
                                           'amount' => 1000000,
                                           'deduction_source_type' => 'adjustment',
                                           'deduction_source_id' => 'randomAdjstId1']);

        $this->startTest($data);
    }

    public function testDisputeEditClosed()
    {
        $this->updateEditTestData(['status' => 'won']);

        $this->startTest();
    }

    public function testDisputeEditExtraInput()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeEditInvalidStatus()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeReversalWinLogic()
    {
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $oldMerchantBalance = $this->getEntityById('balance', $this->merchant['id'], true)['balance'];

        $this->ba->adminProxyAuth();

        $content = $this->runRequestResponseFlow($testdata);

        $adjustment = $this->getLastEntity('adjustment', true);

        $dispute = $this->getLastEntity('dispute', true);

        $txn = $this->getLastEntity('transaction', true);

        $newMerchantBalance = $this->getEntityById('balance', $dispute['merchant_id'], true)['balance'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($adjustment['amount'], $dispute['amount_reversed']);
        $this->assertEquals($input['amount'], ($newMerchantBalance - $oldMerchantBalance));
        $this->assertEquals('adjustment', $txn['type']);
    }

    public function testDisputeReversalLostLogic()
    {
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getLastPayment('payment', true);

        $this->assertArraySelectiveEquals([
            'disputed'        => false,
            'amount_refunded' => 10100,
            'refund_status'   => 'partial',
        ], $payment);

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals('adjustment', $txn['type']);
    }

    public function testDisputeEditForNoInitialParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();
        $testData['response']['content']['parent_id'] = $disputeParent->getId();

        $content = $this->runRequestResponseFlow($testData);

        $disputes = $this->getEntities('dispute', [], true);

        $this->checkRequestDisputeAttributes($content, $disputes);
        $this->assertEquals(2, $disputes['count']);
        $this->assertEquals($content['parent_id'], Entity::stripDefaultSign($disputes['items'][1]['id']));
    }

    public function testDisputeEditWithExistingParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeEditReplaceParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $disputeNewParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeNewParent->getId();
        $testData['response']['content']['parent_id'] = $disputeNewParent->getId();

        $content = $this->runRequestResponseFlow($testData);

        $disputes = $this->getEntities('dispute', [], true);

        $this->checkRequestDisputeAttributes($content, $disputes);
        $this->assertEquals(3, $disputes['count']);
        $this->assertEquals($content['parent_id'], Entity::stripDefaultSign($disputes['items'][1]['id']));
    }

    public function testDisputeEditReplaceParentWithAlreadyLinkedParent()
    {
        $disputeOtherParent = $this->fixtures->create('dispute');

        $this->fixtures->create('dispute', ['parent_id' => $disputeOtherParent->getId()]);

        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeOtherParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeLostPartiallyAcceptedAdjustmentRecoveryMethod()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals(($input['amount'] - $reqContent[Entity::ACCEPTED_AMOUNT]),
            $dispute['amount_reversed']);
        $this->assertEquals(2, $adjustments['count']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustments['items'][0]['entity_id']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustments['items'][1]['entity_id']);
        $this->assertEquals(($input['amount'] - $reqContent[Entity::ACCEPTED_AMOUNT]),
            $adjustments['items'][0]['amount']);
        $this->assertEquals(0 - $input['amount'], $adjustments['items'][1]['amount']);
    }

    public function testDisputeLostPartiallyAcceptedRefundRecoveryMethod()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];

        $testdata = $this->updateEditTestData($input);

        $this->assertNull($this->getDbLastEntity('refund'));

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 7000;


        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $refund = $this->getLastEntity('refund', true);


        $this->assertEquals($input['amount'], $dispute['amount']);

        $this->assertEquals($input['amount'], $dispute['amount_deducted']);

        $this->assertEquals($dispute['payment_id'], $refund['payment_id']);

        $this->assertEquals(7000, $refund['amount']);
    }

    public function testDisputeLostPartiallyAcceptedForNoOnsetDeduct()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($reqContent['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($reqContent[Entity::ACCEPTED_AMOUNT],
            $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals(1, $adjustments['count']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']),
            $adjustments['items'][0]['entity_id']);
        $this->assertEquals((0 - $reqContent[Entity::ACCEPTED_AMOUNT]),
            $adjustments['items'][0]['amount']);
    }

    public function testDisputeLostPartiallyAcceptedWithInvalidAcceptedAmount()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 20000;

        $this->startTest($testdata);
    }

    public function testDisputeLostPartiallyAcceptedWithZeroAcceptedAmount()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 0;

        $this->startTest($testdata);
    }
    public function testDisputeLostPartiallyAcceptedWithNonInrPayment()
    {
        $createInput  = [
            'amount'                => 10000,
            'deduct_at_onset'       => 1,
        ];

        $testdata = $this->updateEditTestData($createInput);

        $dispute = $this->getDbLastEntity('dispute');

        $payment = $dispute->payment;

        $this->fixtures->edit('payment', $payment->getId(), [
            'currency' => 'USD',
        ]);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 100;

        $this->startTest($testdata);
    }

    public function testNonTransactionalDisputeInvalidClose()
    {
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
            'phase'                 => Phase::FRAUD,
        ];
        $testdata = $this->updateEditTestData($input);

        $this->startTest($testdata);
    }

    public function testDisputeFetchProxyAuth()
    {
        $this->ba->proxyAuth();

        $dispute = $this->fixtures->create('dispute', ['id' => '1000000dispute', 'deduct_at_onset' => 1]);

        $testData = $this->updateFetchTestData();

        $content = $this->runRequestResponseFlow($testData);

        foreach (['deduction_source_type',
                  'deduction_source_id',
                  'internal_status',
                  'internal_respond_by',
                 ] as $keysNotVisibleToMerchant)
        {
            $this->assertArrayNotHasKey($keysNotVisibleToMerchant, $content);
        }

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->checkDisputeFetchProxyAuth($dispute, $adjustment, $content);
    }

    public function testDisputeFetchForMerchant()
    {
        $this->ba->proxyAuth();

        $disputes = $this->fixtures->times(2)->create('dispute');

        $testData = $this->updateFetchTestData();

        $content = $this->runRequestResponseFlow($testData);

        $this->checkDisputeFetchForMerchant($disputes, $content);

        $this->ba->privateAuth();

        $content = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('reason', $content['items'][0]);

        $this->assertArrayNotHasKey('lifecycle', $content['items'][0]);

        $this->checkDisputeFetchForMerchant($disputes, $content);
    }

    public function testDisputeFetchForAdmin()
    {
        $this->ba->adminAuth();

        $this->fixtures->times(2)->create('dispute');

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchLifecycleForAdmin()
    {
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $paymentId = $this->fixtures->create('payment:captured')->getPublicId();

        $this->ba->adminAuth();

        $disputeId = $this->makeRequestAndGetContent([
            'url'       => "/payments/{$paymentId}/disputes",
            'method'    => 'post',
            'content'   => [
                'gateway_dispute_id'   => '4342frf34r',
                'raised_on'            => '946684800',
                'expires_on'           => '1912162918',
                'amount'               => 100,
                'deduct_at_onset'      => 0,
                'phase'                => 'chargeback',
                'reason_id'            => $reason['id'],
            ],
        ])['id'];

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        // now edit the dispute and make it lost
        $this->makeRequestAndGetContent([
           'url'        => '/disputes/' . $disputeId,
           'method'     => 'post',
           'content'    => [
                'status' => 'lost',
           ],
        ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $disputeId;

        $this->ba->adminAuth();

        $lifecycle = $this->startTest()['lifecycle'];

        foreach ($lifecycle as $entry)
        {
            $this->assertArrayHasKey('created_at', $entry);
        }

    }

    public function testDisputeFetchForAdminInternalStatusParam()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['internal_status' => 'open']);

        $this->fixtures->create('dispute', ['internal_status' => 'closed']);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchForAdminInternalRespondByParam()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['internal_respond_by' => 1600000000]);

        $this->fixtures->create('dispute', ['internal_respond_by' => 1500000000]);


        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchForAdminDeductionReversalAtParam()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['deduction_reversal_at' => 1600000000, 'status' => 'under_review']);

        $this->fixtures->create('dispute', ['deduction_reversal_at' => 1500000000, 'status' => 'under_review']);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchForAdminInternalRespondByPrioritize()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['internal_respond_by' => 1600000000, 'created_at' => 1400000000]);

        $this->fixtures->create('dispute', ['internal_respond_by' => 1500000000, 'created_at' => 1400000000]);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchForAdminGatewayFilter()
    {
        $this->ba->adminAuth();

        $paymentId1 = $this->fixtures->create('payment:captured', ['gateway' => 'sharp'])->getId();

        $this->fixtures->create('dispute', ['payment_id' => $paymentId1, 'amount' => 5000]);

        $paymentId2 = $this->fixtures->create('payment:captured', ['gateway' => 'hdfc'])->getId();

        $this->fixtures->create('dispute', ['payment_id' => $paymentId2, 'amount' => 6000]);

        $paymentId3 = $this->fixtures->create('payment:captured', ['gateway' => 'sharp'])->getId();

        $this->fixtures->create('dispute', ['payment_id' => $paymentId3, 'amount' => 5000]);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    /**
     * Creating a endpoint in proxy auth to fetch only the count of disputes which match a particular query
     * Query params allowed are same as /disputes in proxy/private auth
     * Reason: This endpoint will be called on merchant-dashboard on transaction page load as a notification
     * we want to avoid sending data for every such load to reduce bandwidth usage on merchant devices
     */
    public function testDisputeFetchCountProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->fixtures->times(3)->create('dispute');

        $response = $this->startTest();

        $this->assertArrayNotHasKey('items', $response);
    }

    public function testDisputeFetchForAdminRestricted()
    {
        $this->fixtures->edit('org', '100000razorpay', ['type' => 'restricted']);

        $this->ba->adminAuth();

        $this->fixtures->create('dispute');

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    protected function checkDisputeFetchProxyAuth(DisputeEntity $dispute, $adjustment, array $content)
    {
        $this->assertEquals($dispute->getId(), Entity::stripDefaultSign($content['id']));
        $this->assertEquals(1000000, $dispute['amount']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustment['entity_id']);
        $this->assertEquals(-1000000, $adjustment['amount']);
    }

    protected function checkDisputeFetchForMerchant(array $disputes, array $content)
    {
        $this->assertEquals(2, $content['count']);
        $this->assertEquals($disputes[0]->getId(), Entity::stripDefaultSign($content['items'][1]['id']));
        $this->assertEquals($disputes[1]->getId(), Entity::stripDefaultSign($content['items'][0]['id']));
        $this->assertEquals($disputes[0]->payment->getPublicId(), $content['items'][1]['payment_id']);
        $this->assertEquals($disputes[1]->payment->getPublicId(), $content['items'][0]['payment_id']);
    }

    public function testFetchMerchantDetails()
    {
        $this->ba->privateAuth();

        $testData = $this->updateDetailsFetchTestData(['expires_on' => 12345678]);

        $this->startTest($testData);
    }

    /**
     * This test first uploads without submitting, verifies details
     * then submits and verifies further details related to submit
     * like mail triggers.
     */
    public function testEditDisputeFileUploadSaveForLater()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $testData = $this->updateUploadDocumentData();

        $this->runRequestResponseFlow($testData);

        $testData = $this->updateUploadDocumentData([], 'testEditDisputeFileUploadSaveForLaterAfterSave');

        Mail::assertNotQueued(DisputeSubmittedForAdminMail::class);

        $testData['request']['content'][DisputeEntity::SUBMIT] = true;

        $this->runRequestResponseFlow($testData);

        Mail::assertQueued(DisputeSubmittedForAdminMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['dashboard_hostname']);

            $this->assertNotEmpty($mailData['payment']);

            $this->assertNotEmpty($mailData['dispute']);

            $this->assertTrue($mailable->hasFrom('disputes@razorpay.com'));

            return true;
        });
    }

    public function testEditDisputeMerchantAcceptDispute()
    {
        Mail::fake();

        // Input params while creating
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 0,
        ];

        $testdata = $this->updateEditTestData($input);

        $this->ba->proxyAuth();

        $this->startTest($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(10100, $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals(0, $dispute['deduct_at_onset']);

        Mail::assertQueued(DisputeAcceptedForAdminMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['dashboard_hostname']);

            $this->assertNotEmpty($mailData['payment']);

            $this->assertNotEmpty($mailData['dispute']);

            $this->assertTrue($mailable->hasFrom('disputes@razorpay.com'));

            return true;
        });
    }

    public function testEditDisputeMerchantAcceptDisputeForNonTransactional()
    {
        Mail::fake();

        // Input params while creating
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 0,
            'phase'                 => 'fraud',
        ];

        $testdata = $this->updateEditTestData($input);

        $this->ba->proxyAuth();

        $this->startTest($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(0, $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals(0, $dispute['deduct_at_onset']);

        Mail::assertQueued(DisputeAcceptedForAdminMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['dashboard_hostname']);

            $this->assertNotEmpty($mailData['payment']);

            $this->assertNotEmpty($mailData['dispute']);

            $this->assertTrue($mailable->hasFrom('disputes@razorpay.com'));

            return true;
        });
    }

    public function testDisputeFileInvalidDelete()
    {
        $dispute = $this->fixtures->create('dispute', ['status' => 'closed']);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId() . '/files/file_123456';

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testFetchFiles()
    {
        $this->fixtures->create('dispute', ['id' => '1000000dispute']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testDisputeEditInternalRespondBy()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    /**
     * https://razorpay.slack.com/archives/C027FDDSZ0F/p1645077672501209?thread_ts=1645076375.052999&cid=C027FDDSZ0F
     */
    public function testDisputeEditInvalidInternalStatusValues()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    /**
     * https://razorpay.slack.com/archives/C027FDDSZ0F/p1645077672501209?thread_ts=1645076375.052999&cid=C027FDDSZ0F
     */
    public function testDisputeEditInvalidStatusValues()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeEditDeductionSourceTypeAndId()
    {
        $this->updateEditTestData(['status' => 'lost', 'internal_status' => 'lost_merchant_not_debited']);

        $merchantEntity = (new Merchant\Repository)->findOrFail('10000000000000');

        $this->app['basicauth']->setModeAndDbConnection('test');

        $adjustment = (new Adjustment\Core)->createAdjustment([
            'amount'        => 1000,
            'currency'      => 'INR',
            'description'   => 'test description',
        ], $merchantEntity);

        $this->testData[__FUNCTION__]['request']['content']['deduction_source_id'] = $adjustment->getId();
        $this->testData[__FUNCTION__]['response']['content']['deduction_source_id'] = $adjustment->getId();


        $this->startTest();
    }

    /**
     * @dataProvider disputeEditDeductionSourceTypeAndIdValidationFailuresDataProvider
     */
    public function testDisputeEditDeductionSourceTypeAndIdValidationFailures($disputeAttributes, $editInput, $expectedError, $expectedException = [])
    {
        $this->updateEditTestData($disputeAttributes);

        $this->startTest([
            'request'       => ['content' => $editInput,],
            'response'      => ['content' => ['error' => $expectedError], 'status_code' => 400],
            'exception'     => $expectedException,
        ]);
    }

    public function disputeEditDeductionSourceTypeAndIdValidationFailuresDataProvider(): array
    {
        return [
            'invalid internal_status'           => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'deduction_source_type' => 'refund',
                    'deduction_source_id'   => 'randomRefundId',
                    'internal_status'       => 'lost_merchant_not_debited',
                    'skip_deduction'        => true,
                ],
                'error'              => [
                    'description' => 'deduction_source_type/deduction_source_id can be set
        only when internal_status is "lost_merchant_not_debited"',
                ],
            ],
            'field_missing'           => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'deduction_source_type' => 'refund',
                ],
                'error'              => [
                    'description' => 'The deduction source id field is required when deduction source type is present.',
                ],
            ],

            'invalid deduction source id' => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'deduction_source_type' => 'refund',
                    'deduction_source_id'   => 'randomRefundId',
                    'internal_status'       => 'lost_merchant_debited',
                    'skip_deduction'        => true,
                ],
                'error'              => [
                    'description' => 'The id provided does not exist',
                ],
                'expected_exception' => [
                    'class'               => BadRequestException::class,
                    'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'skip deduction evaluates to false' => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'deduction_source_type' => 'refund',
                    'deduction_source_id'   => 'randomRefundId',
                    'internal_status'       => 'lost_merchant_debited',
                ],
                'error'              => [
                    'description' => 'skip_deduction should be true if overriding deduction_source_id
        and deduction_source_type',
                ],
            ],
            'invalid internal status for recovery method' => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'status'          => 'under_review',
                    'internal_status' => 'open',
                    'recovery_method' => 'refund',
                ],
                'error'              => [
                    'description' => 'Recovery Method not supported for given Internal Status',
                ],
            ],
            'no recovery method for internal status' => [
                'dispute_attributes' => [],
                'edit_input'         => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_debited',
                ],
                'error'              => [
                    'description' => 'Recovery Method is required for given Internal Status',
                ],
            ],
            'recovery method for skip deduction' => [
                'dispute_attributes' => ['status' => 'lost', 'internal_status' => 'lost_merchant_not_debited'],
                'edit_input'         => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_debited',
                    'skip_deduction' => '1',
                    'recovery_method' => 'adjustment'
                ],
                'error'              => [
                    'description' => 'Recovery Method is not supported with Skip Deduction',
                ],
            ],
        ];
    }

    public function testDisputeEditWithStatusAndInternalStatusValidCombinations()
    {
        $this->updateEditTestData();

        $disputeID = $this->getDbLastEntity('dispute')->getId();

        $testcases = $this->getTestCasesForDisputeEditWithStatusInternalStatusValidCombinations();

        foreach ($testcases as $testcase)
        {
            $seedData = $testcase['seed'];

            $this->fixtures->edit('dispute', $disputeID, $seedData);

            $this->testData[__FUNCTION__]['request']['content'] = $testcase['request'];;

            $this->testData[__FUNCTION__]['response']['content'] = $testcase['response'];

            if (isset($testcase['exception']) === true)
            {
                $this->testData[__FUNCTION__]['response']['status_code'] = 400;
                $this->testData[__FUNCTION__]['exception'] = $testcase['exception'];
            }

            $this->startTest();

            if (isset($testcase['exception']) === true)
            {
                $this->testData[__FUNCTION__]['response']['status_code'] = 200;
                $this->testData[__FUNCTION__]['exception'] = null;
            }
        }
    }

    public function testDisputeEditWithStatusAndInternalStatusInvalidCombinations()
    {
        $this->mockRazorxTreatment('on');

        $this->updateEditTestData();

        $disputeID = $this->getDbLastEntity('dispute')->getId();

        $testcases = $this->getTestCasesForDisputeEditWithStatusInternalStatusInvalidCombinations();

        $errorFormatString = "'internal_status' of dispute cannot move from '%s' to '%s'";

        foreach ($testcases as $testcase)
        {
            $seedData = $testcase['seed'];

            $this->fixtures->edit('dispute', $disputeID, $seedData);

            $this->testData[__FUNCTION__]['request']['content'] = $testcase['request'];;

            $description = sprintf($errorFormatString, $testcase['seed']['internal_status'], $testcase['request']['internal_status']);

            $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $description;

            $this->startTest();
        }
    }

    public function testDisputeEditLostWithoutDeduction()
    {
        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeEditLostWithoutDeductionEventData';

        $this->expectWebhookEventWithContents('payment.dispute.lost', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    /**
     * If deduction type is not specified, then it has to be via adjustment
     */
    public function testDisputeEditLostWithDeductionWithDeductionTypeNotSpecified()
    {
        $data = $this->updateEditTestData();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditLostWithDeductionViaAdjustment()
    {
        $data = $this->updateEditTestData();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditLostWithDeductionViaRefund()
    {
        $data = $this->updateEditTestData();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testPhaseBasedBulkCreateDisputes()
    {
        $fileData = $this->getBulkDisputeUploadedFileData();

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $testData['request']['url'] = '/disputes/bulk-create';

        $this->startTest($testData);

        $fileRowByPaymentIdMap = [];

        foreach ($fileData as $fileRow)
        {
            $fileRowByPaymentIdMap[$fileRow['payment_id']] = $fileRow;
        }

        $disputes = $this->getEntities('dispute', [], true)['items'];

        $this->assertCount(count($fileData), $disputes);

        foreach ($disputes as $disputeEntityItem)
        {
            $fileRow = $fileRowByPaymentIdMap[$disputeEntityItem['payment_id']];

            $this->assertEquals($fileRow['amount'], $disputeEntityItem['amount']);

            $this->assertEquals($fileRow['gateway_dispute_id'], $disputeEntityItem['gateway_dispute_id']);

            $this->assertEquals($fileRow['gateway_dispute_status'], $disputeEntityItem['status']);

            $this->assertEquals($fileRow['reason_code'], $disputeEntityItem['reason_code']);

            $this->assertEquals($fileRow['phase'], $disputeEntityItem['phase']);

            if ($fileRow['skip_email'] === 'N')
            {
                $this->assertEquals(EmailNotificationStatus::SCHEDULED, $disputeEntityItem[Entity::EMAIL_NOTIFICATION_STATUS]);
            }
            else if ($fileRow['skip_email'] === 'Y')
            {
                $this->assertEquals(EmailNotificationStatus::DISABLED, $disputeEntityItem[Entity::EMAIL_NOTIFICATION_STATUS]);
            }
        }
    }


    public function testBulkCreateDisputesWithoutReasonCode()
    {
        $fileData = $this->getBulkDisputeUploadedFileDataWithoutReasonCode();

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $this->ba->disputesServiceAuth();

        $this->startTest($testData);

        $fileRowByPaymentIdMap = [];

        foreach ($fileData as $fileRow)
        {
            $fileRowByPaymentIdMap[$fileRow['payment_id']] = $fileRow;
        }

        $disputes = $this->getEntities('dispute', [], true)['items'];

        $this->assertCount(count($fileData), $disputes);

        foreach ($disputes as $disputeEntityItem)
        {
            $fileRow = $fileRowByPaymentIdMap[$disputeEntityItem['payment_id']];

            $this->assertEquals($fileRow['gateway_amount'], $disputeEntityItem['amount']);

            $this->assertEquals($fileRow['gateway_dispute_id'], $disputeEntityItem['gateway_dispute_id']);

            $this->assertEquals($fileRow['gateway_dispute_status'], $disputeEntityItem['status']);

            $this->assertEquals($fileRow['phase'], $disputeEntityItem['phase']);

            if ($fileRow['skip_email'] === 'N')
            {
                $this->assertEquals(EmailNotificationStatus::SCHEDULED, $disputeEntityItem[Entity::EMAIL_NOTIFICATION_STATUS]);
            }
            else if ($fileRow['skip_email'] === 'Y')
            {
                $this->assertEquals(EmailNotificationStatus::DISABLED, $disputeEntityItem[Entity::EMAIL_NOTIFICATION_STATUS]);
            }
        }
    }

    public function testPhaseBasedBulkCreateMails()
    {
        Mail::fake();

        $this->ba->cronAuth();

        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $this->mockSalesforceRequestforSalesPOC('10000000000000', "sales.poc@gmail.com", 2);

        $attributes1 = [
            'payment_id'                => $this->fixtures->create('payment:captured')->getId(),
            'gateway_dispute_id'        => 'Dispute100001',
            'gateway_dispute_status'    => 'open',
            'reason_id'                 => $reason['id'],
            'phase'                     => Phase::CHARGEBACK,
            'raised_on'                 => (strtotime('-1 month', strtotime('now'))),
            'expires_on'                => (strtotime('+1 month', strtotime('now'))),
            'amount'                    => 10000,
            'email_notification_status' => EmailNotificationStatus::SCHEDULED,
        ];
        $dispute1 = $this->fixtures->create('dispute', $attributes1);

        $attributes2 = [
            'payment_id'                => $this->fixtures->create('payment:captured')->getId(),
            'gateway_dispute_id'        => 'Dispute100001',
            'gateway_dispute_status'    => 'open',
            'reason_id'                 => $reason['id'],
            'phase'                     => Phase::ARBITRATION,
            'raised_on'                 => (strtotime('-1 month', strtotime('now'))),
            'expires_on'                => (strtotime('+1 month', strtotime('now'))),
            'amount'                    => 10000,
            'email_notification_status' => EmailNotificationStatus::SCHEDULED,
        ];
        $dispute2 = $this->fixtures->create('dispute', $attributes2);

        $attributesNotToBeEmailed = [
            'payment_id'                => $this->fixtures->create('payment:captured')->getId(),
            'gateway_dispute_id'        => 'Dispute100001',
            'gateway_dispute_status'    => 'open',
            'reason_id'                 => $reason['id'],
            'phase'                     => Phase::CHARGEBACK,
            'raised_on'                 => (strtotime('-1 month', strtotime('now'))),
            'expires_on'                => (strtotime('+1 month', strtotime('now'))),
            'amount'                    => 10000,
            'email_notification_status' => EmailNotificationStatus::DISABLED,
        ];
        $this->fixtures->create('dispute', $attributesNotToBeEmailed);

        $testData = &$this->testData[__FUNCTION__];

        $this->startTest($testData);

        $disputeByPaymentIdMap = [];

        $totalPhasePayments = [];

        foreach ([$dispute1, $dispute2] as $dispute)
        {
            $disputeByPaymentIdMap['pay_' . $dispute['payment_id']] = $dispute;

            $phase = $dispute['phase'];

            if (isset($totalPhasePayments[$phase]) === false)
            {
                $totalPhasePayments[$phase] = 0;
            }

            $totalPhasePayments[$phase]++;
        }

        $expectedData = [
            'dispute_payment_map' => $disputeByPaymentIdMap,
            'total_payments'      => $totalPhasePayments,
        ];

        Mail::assertQueued(DisputeBulkCreationMail::class, function ($mail) use ($expectedData)
        {
            $mailData = $mail->viewData;

            $this->assertArrayHasKey('merchant', $mail->viewData);

            $this->assertArrayHasKey('disputesDataTable', $mail->viewData);

            $this->assertTrue(Phase::exists($mailData['phase']));

            $this->assertEquals($expectedData['total_payments'][$mailData['phase']], $mailData['totalPayments']);

            foreach ($mailData['disputesDataTable'] as $mailDisputeRow)
            {
                $dispute = $expectedData['dispute_payment_map'][$mailDisputeRow['payment_id']];

                $this->assertEquals($dispute['gateway_dispute_id'], $mailDisputeRow['case_id']);

                $this->assertEquals($dispute['phase'], $mailDisputeRow['phase']);
            }

            return ($mail->hasFrom('disputes@razorpay.com') and
                ($mail->hasTo('test@razorpay.com')) and ($mail->hasCC("sales.poc@gmail.com")));
        });

        $actualEmailStatus = $this->getEntityById('dispute', 'disp_' .$dispute1[Entity::ID], true)[Entity::EMAIL_NOTIFICATION_STATUS];
        $this->assertEquals(EmailNotificationStatus::NOTIFIED, $actualEmailStatus);

        $actualEmailStatus = $this->getEntityById('dispute', 'disp_' .$dispute2[Entity::ID], true)[Entity::EMAIL_NOTIFICATION_STATUS];
        $this->assertEquals(EmailNotificationStatus::NOTIFIED, $actualEmailStatus);
    }

    public function testBulkDisputeCreateMailAttachment()
    {
        Mail::fake();

        $this->ba->cronAuth();

        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $payment = $this->fixtures->create('payment:captured');

        $attributes = [
            'payment_id'                => $payment->getId(),
            'gateway_dispute_id'        => 'Dispute100001',
            'gateway_dispute_status'    => 'open',
            'reason_id'                 => $reason['id'],
            'phase'                     => Phase::CHARGEBACK,
            'raised_on'                 => (strtotime('-1 month', strtotime('now'))),
            'expires_on'                => (strtotime('+1 month', strtotime('now'))),
            'amount'                    => 10000,
            'email_notification_status' => EmailNotificationStatus::SCHEDULED,
        ];
        $this->fixtures->create('dispute', $attributes);

        $testData = &$this->testData[__FUNCTION__];

        $this->mockSalesforceRequestforSalesPOC($payment->getMerchantId(), "sales.poc@gmail.com");

        $this->startTest($testData);

        Mail::assertQueued(DisputeBulkCreationMail::class, function ($mail)
        {
            $merchantId = $mail->viewData['merchant']['id'];

            $merchantName = $mail->viewData['merchant']['name'];

            $currentDate = Carbon::now(Timezone::IST)->format('d/m/Y');

            $expectedMailSubject = sprintf('Razorpay | Service Chargeback Alert - %s [%s] | %s',
                $merchantName, $merchantId, $currentDate);

            $this->assertEquals($expectedMailSubject, $mail->subject);

            $this->assertNotEmpty($mail->rawAttachments);

            $this->assertArrayHasKey('data', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('name', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('options', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('options', $mail->rawAttachments[0]);

            $this->assertIsString($mail->rawAttachments[0]['data']);

            $this->assertEquals('application/csv', $mail->rawAttachments[0]['options']['mime']);

            $attachmentFilePrefix = DisputeBulkCreationMail::BULK_DISPUTE_ATTACHMENT_FILE_NAME;

            $attachmentFileExtension = '.csv';

            $this->assertStringStartsWith($attachmentFilePrefix, $mail->rawAttachments[0]['name']);

            $this->assertStringEndsWith($attachmentFileExtension, $mail->rawAttachments[0]['name']);

            return ($mail->hasFrom('disputes@razorpay.com') and
                ($mail->hasTo('test@razorpay.com')) and ($mail->hasCC("sales.poc@gmail.com")));
        });
    }

    public function testBulkDisputeNewFormat()
    {
        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $fileData = [];

        $row = [
            'payment_id'             => $payment = $this->fixtures->create('payment:captured', ['amount' => 1000, 'currency' => 'USD', 'base_amount' => 10000])->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100001',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::CHARGEBACK,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 100,
            'gateway_currency'       => 'USD',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'deduct_at_onset'        => 'Y',
        ];

        $fileData[] = $row;

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $this->startTest($testData);
    }



    public function testBulkDisputeEdit()
    {
        $disputeForRefund = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => true,
        ]);

        $disputeForAdjustment = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => true,
        ]);


        $fileData = [
            [
                'id'                                 => $disputeForRefund->getId(),
                'gateway_dispute_status'             => 'open',
                'skip_deduction'                     => 'Y',
                'comments'                           => 'test comment',
                'status'                             => 'under_review',
                'internal_status'                    => 'represented',
                'deduction_reversal_delay_in_days'   => 50,
                'recovery_method'                    => null,
            ],

            [
                'id'                                 => $disputeForAdjustment->getId(),
                'gateway_dispute_status'             => 'open',
                'skip_deduction'                     => 'Y',
                'comments'                           => 'test comment',
                'status'                             => 'under_review',
                'internal_status'                    => 'represented',
                'deduction_reversal_delay_in_days'   => 50,
                'recovery_method'                    => null,
            ],
        ];

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $this->startTest($testData);

        $disputeArrayForRefund     = $this->getEntityById('dispute', $disputeForRefund->getId(), true);
        $disputeArrayForAdjustment = $this->getEntityById('dispute', $disputeForAdjustment->getId(), true);

        $this->assertArraySelectiveEquals([
            'internal_status' => 'represented',
            'status'          => 'under_review',
            ], $disputeArrayForRefund);

        $this->assertArraySelectiveEquals([
            'internal_status' => 'represented',
            'status'          => 'under_review',
            ], $disputeArrayForAdjustment);

        $this->assertNotNull($disputeArrayForRefund['deduction_reversal_at']);
        $this->assertNotNull($disputeArrayForAdjustment['deduction_reversal_at']);

        $this->assertNotEquals(1300000000, $disputeArrayForRefund['internal_respond_by']);
        $this->assertNotEquals(1300000000, $disputeArrayForAdjustment['internal_respond_by']);
    }

    public function testBulkLostDisputeEdit()
    {
        $disputeForRefund = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => false,
        ]);

        $disputeForAdjustment = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => false,
        ]);

        $fileData = [
              [
                  'id'                               => $disputeForRefund->getId(),
                  'gateway_dispute_status'           => 'open',
                  'skip_deduction'                   => 'N',
                  'comments'                         => 'test comment',
                  'status'                           => 'lost',
                  'internal_status'                  => 'lost_merchant_debited',
                  'deduction_reversal_delay_in_days' => null,
                  'recovery_method'                  => 'refund',
              ],

              [
                  'id'                               => $disputeForAdjustment->getId(),
                  'gateway_dispute_status'           => 'open',
                  'skip_deduction'                   => 'N',
                  'comments'                         => 'test comment',
                  'status'                           => 'lost',
                  'internal_status'                  => 'lost_merchant_debited',
                  'deduction_reversal_delay_in_days' => null,
                  'recovery_method'                  => 'adjustment',
              ],
        ];

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $this->startTest($testData);

        $disputeArrayForRefund     = $this->getEntityById('dispute', $disputeForRefund->getId(), true);
        $disputeArrayForAdjustment = $this->getEntityById('dispute', $disputeForAdjustment->getId(), true);

        $this->assertArraySelectiveEquals([
            'internal_status'       => 'lost_merchant_debited',
            'status'                => 'lost',
            'deduction_source_type' => 'refund',
        ], $disputeArrayForRefund);

        $this->assertArraySelectiveEquals([
            'internal_status'       => 'lost_merchant_debited',
            'status'                => 'lost',
            'deduction_source_type' => 'adjustment',
        ], $disputeArrayForAdjustment);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($disputeArrayForRefund['payment_id'], $refund['payment_id']);
        $this->assertEquals($disputeArrayForRefund['amount'], $refund['amount']);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertEquals($disputeForAdjustment->getId(), $adjustment['entity_id']);
    }

    public function testBulkDisputeEditValidationFailure()
    {
        $id1 = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => true,
        ])->getId();

        $id2 = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => false,
        ])->getId();

        $id3 = $this->fixtures->create('dispute', [
            'status'              => 'open',
            'internal_status'     => 'open',
            'deduct_at_onset'     => false,
        ])->getId();

        $fileData = [
            [
                'id'                               => $id1,
                'gateway_dispute_status'           => 'open',
                'skip_deduction'                   => 'N',
                'comments'                         => 'test comment',
                'status'                           => 'lost',
                'internal_status'                  => 'open',
                'deduction_reversal_delay_in_days' => 50,
                'recovery_method'                  => 'refund',
            ],

            [
                'id'                               => $id2,
                'gateway_dispute_status'           => 'open',
                'skip_deduction'                   => 'N',
                'comments'                         => 'test comment',
                'status'                           => 'lost',
                'internal_status'                  => 'lost_merchant_debited',
                'deduction_reversal_delay_in_days' => null,
                'recovery_method'                  => null,
            ],

            [
                'id'                               => $id3,
                'gateway_dispute_status'           => 'open',
                'skip_deduction'                   => 'N',
                'comments'                         => 'test comment',
                'status'                           => 'lost',
                'internal_status'                  => 'lost_merchant_debited',
                'deduction_reversal_delay_in_days' => null,
                'recovery_method'                  => 'refund',
            ],
        ];

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $disputeArray1Before = $this->getEntityById('dispute', $id1, true);
        $disputeArray2Before = $this->getEntityById('dispute', $id2, true);

        $this->startTest($testData);

        $disputeArray1After = $this->getEntityById('dispute', $id1, true);
        $disputeArray2After = $this->getEntityById('dispute', $id2, true);

        $this->assertEquals($disputeArray1Before, $disputeArray1After);
        $this->assertEquals($disputeArray2Before, $disputeArray2After);

        $disputeArray3 = $this->getEntityById('dispute', $id3, true);

        $this->assertArraySelectiveEquals([
            'internal_status'       => 'lost_merchant_debited',
            'status'                => 'lost',
            'deduction_source_type' => 'refund',
            ], $disputeArray3);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($disputeArray3['payment_id'], $refund['payment_id']);
        $this->assertEquals($disputeArray3['amount'], $refund['amount']);
    }

    public function testDisputeReasonFetch()
    {
        $this->ba->expressAuth();

        $disputeReason = $this->fixtures->create('dispute_reason');

        $testData = $this->updateFetchTestData();
        $testData['request']['url'] .= '/' . $disputeReason->getId();

        $content = $this->runRequestResponseFlow($testData);

        $this->assertEquals($disputeReason->getId(), $content['id']);
    }

    public function testRearchDisputeCreate()
    {
        $this->enablePgRouterConfig();

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' => 'GfnS1Fj048VHo2',
            'type' => 'payment',
            'merchant_id' => '10000000000000',
            'amount' => 50000,
            'fee' => 1000,
            'mdr' => 1000,
            'tax' => 0,
            'pricing_rule_id' => NULL,
            'debit' => 0,
            'credit' => 49000,
            'currency' => 'INR',
            'balance' => 2025400,
            'gateway_amount' => NULL,
            'gateway_fee' => 0,
            'gateway_service_tax' => 0,
            'api_fee' => 0,
            'gratis' => FALSE,
            'fee_credits' => 0,
            'escrow_balance' => 0,
            'channel' => 'axis',
            'fee_bearer' => 'platform',
            'fee_model' => 'prepaid',
            'credit_type' => 'default',
            'on_hold' => FALSE,
            'settled' => FALSE,
            'settled_at' => 1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' => '10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' => TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' => 1614262078,
            'updated_at' => 1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
            'merchant_id' => '10000000000000',
            'name' => 'Harshil',
            'expiry_month' => 12,
            'expiry_year' => 2024,
            'iin' => '401200',
            'last4' => '3335',
            'length' => '16',
            'network' => 'Visa',
            'type' => 'credit',
            'sub_type' => 'consumer',
            'category' => 'STANDARD',
            'issuer' => 'HDFC',
            'international' => FALSE,
            'emi' => TRUE,
            'vault' => 'rzpvault',
            'vault_token' => 'NDAxMjAwMTAzODQ0MzMzNQ==',
            'global_fingerprint' => '==QNzMzM0QDOzATMwAjMxADN',
            'trivia' => NULL,
            'country' => 'IN',
            'global_card_id' => NULL,
            'created_at' => 1614256967,
            'updated_at' => 1614256967,
        ]);

        // sd($card->getId());

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();

        $this->app->instance('pg_router', $pgService);

        $this->disputed = false;

        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' => 'GfnS1Fj048VHo2',
                        'merchant_id' => '10000000000000',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'base_amount' => 50000,
                        'method' => 'card',
                        'status' => 'captured',
                        'two_factor_auth' => 'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' => FALSE,
                        'amount_authorized' => 50000,
                        'amount_refunded' => 0,
                        'base_amount_refunded' => 0,
                        'amount_transferred' => 0,
                        'amount_paidout' => 0,
                        'refund_status' => NULL,
                        'description' => 'description',
                        'card_id' => $card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' => FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => [
                            'merchant_order_id' => 'id',
                        ],
                        'transaction_id' => $transaction->getId(),
                        'authorized_at' => 1614253879,
                        'auto_captured' => FALSE,
                        'captured_at' => 1614253880,
                        'gateway' => 'hdfc',
                        'terminal_id' => '1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' => 0,
                        'signed' => FALSE,
                        'verified' => NULL,
                        'gateway_captured' => TRUE,
                        'verify_bucket' => 0,
                        'verify_at' => 1614253880,
                        'callback_url' => NULL,
                        'fee' => 1000,
                        'mdr' => 1000,
                        'tax' => 0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' => FALSE,
                        'save' => FALSE,
                        'late_authorized' => FALSE,
                        'convert_currency' => NULL,
                        'disputed' => FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' => 'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' => 1614253879,
                        'updated_at' => 1614253880,
                        'captured' => TRUE,
                        'reference2' => '12343123',
                        'entity' => 'payment',
                        'fee_bearer' => 'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' => FALSE,
                        'gateway_amount' => 50000,
                        'gateway_currency' => 'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' => FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $transaction, $paymentData) {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    $this->assertEquals($data['disputed'], true);
                    return [];
                }

            });

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $transaction, $paymentData) {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    $this->assertEquals($data['disputed'], true);
                    return [];
                }

            });

        $testData = $this->updateCreateTestData("GfnS1Fj048VHo2");

        $testData['response']['content']['payment_id'] = "pay_GfnS1Fj048VHo2";

        $this->startTest($testData);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(0, $dispute['amount_deducted']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);
    }

    public function testFreshdeskWebhookPaymentFailedCase()
    {
        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $payment = $this->fixtures->create('payment:failed');

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply'], ['postTicketReply'], true, true, false, false, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookPaymentNotCapturedCase()
    {
        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $payment = $this->fixtures->create('payment:authorized');

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookPaymentFullyRefundedCase()
    {
        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $payment = $this->fixtures->create('payment:captured');

        $this->refundPayment($payment->getPublicId());

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookPaymentAlreadyDisputedCase()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->disputePayment($payment);

        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookMerchantDisabledCase()
    {
        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $payment = $this->fixtures->create('payment:captured');

        $merchantActivated = $payment->merchant->isActivated();

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => false]);

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => $merchantActivated]);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookDisputeCreationForPaymentsOlderThanSixMonths()
    {
        $existingDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $payment = $this->fixtures->create('payment:captured');

        $this->fixtures->edit('payment', $payment->getId(), ['created_at' => $payment->getCreatedAt() - FreshdeskConstants::MAX_ALLOWED_DISPUTE_CREATION_WINDOW_IN_SECS - 1]);

        $automationAgentId = 234;
        $changeTicketGroupToCsExtraArgs = [
            'status'       => FreshdeskConstants::FD_TICKET_STATUS_PENDING,
            'responder_id' => $automationAgentId,
            'tags'         => [
                FreshdeskConstants::FD_TAGS_AUTOMATED_DISPUTE_FLOW,
                FreshdeskConstants::FD_TAGS_PENDING_WITH_DISPUTES,
                FreshdeskConstants::FD_TAGS_PAYMENT_OLDER_THAN_SIX_MONTHS,
            ],
        ];

        $merchantActivated = $payment->merchant->isActivated();

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => true]);

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply', 'fetchTicketById'], ['postTicketReply'], true, true, false, false, true, Subcategory::DISPUTE_A_PAYMENT_FD, $payment, $changeTicketGroupToCsExtraArgs);

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => $merchantActivated]);

        $updatedDisputeCount = $this->getEntities('dispute', [], true)['count'];

        $this->assertEquals($existingDisputeCount, $updatedDisputeCount);
    }

    public function testFreshdeskWebhookCreateDisputeCase()
    {
        $payment = $this->fixtures->create('payment:captured');

        $automationAgentId = 234;
        $changeTicketGroupToCsExtraArgs = [
            'status'       => FreshdeskConstants::FD_TICKET_STATUS_PENDING_WITH_THIRD_PARTY,
            'responder_id' => $automationAgentId,
            'tags'         => [
                FreshdeskConstants::FD_TAGS_AUTOMATED_DISPUTE_FLOW,
                FreshdeskConstants::FD_TAGS_DISPUTE_CREATED,
                FreshdeskConstants::FD_TAGS_PENDING_WITH_DISPUTES
            ],
        ];

        $merchantActivated = $payment->merchant->isActivated();

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => true]);

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply', 'fetchTicketById'], [], true, true, false, true, true, Subcategory::DISPUTE_A_PAYMENT_FD, $payment, $changeTicketGroupToCsExtraArgs);

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => $merchantActivated]);

        $reasonCode = 'goods_service_not_provided';
        $dispute = $this->getLastEntity('dispute', true);
        $this->assertEquals($payment->getPublicId(), $dispute['payment_id']);
        $this->assertEquals($reasonCode, $dispute['reason_code']);
        $this->assertEquals(ReasonCode::REASON_CODE_MAP[Subcategory::DISPUTE_A_PAYMENT][$reasonCode][Entity::PHASE], $dispute['phase']);
    }

    public function testFreshdeskWebhookReportFraud()
    {
        $payment = $this->fixtures->create('payment:captured');

        $automationAgentId = 234;
        $changeTicketGroupToCsExtraArgs = [
            'status'       => FreshdeskConstants::FD_TICKET_STATUS_PENDING_WITH_THIRD_PARTY,
            'responder_id' => $automationAgentId,
            'tags'         => [
                FreshdeskConstants::FD_TAGS_AUTOMATED_DISPUTE_FLOW,
                FreshdeskConstants::FD_TAGS_DISPUTE_CREATED,
                FreshdeskConstants::FD_TAGS_PENDING_WITH_DISPUTES
            ],
        ];

        $reasonCode = 'potential_fraud';
        $this->fixtures->create('dispute_reason', [
            'network'      => 'RZP',
            'code'         => $reasonCode,
            'gateway_code' => 'RZP03',
        ]);

        $merchantActivated = $payment->merchant->isActivated();

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => true]);

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply', 'fetchTicketById'], [], true, true, false, true, true, Subcategory::REPORT_FRAUD, $payment, $changeTicketGroupToCsExtraArgs, $reasonCode);

        $this->fixtures->edit('merchant', $payment['merchant_id'], ['activated' => $merchantActivated]);

        $reasonCode = 'potential_fraud';
        $dispute = $this->getLastEntity('dispute', true);
        $this->assertEquals($payment->getPublicId(), $dispute['payment_id']);
        $this->assertEquals($reasonCode, $dispute['reason_code']);
        $this->assertEquals(ReasonCode::REASON_CODE_MAP[Subcategory::REPORT_FRAUD][$reasonCode][Entity::PHASE], $dispute['phase']);
    }

    public function testFreshdeskWebhookPaymentNotExists()
    {
        $payment = new Payment\Entity();
        $payment->setId('random10000000');

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply'], ['postTicketReply'], true, true, false, false, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
    }

    public function testFreshdeskWebhookReasonCodeNotValidForSubcategory()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->freshdeskFlow(false, false, ['updateTicketV2', 'postTicketReply'], ['updateTicketV2', 'postTicketReply'], false, false, false, false, false, Subcategory::REPORT_FRAUD_FD, $payment);
    }


    /**
     * @dataProvider functionTestBulkDisputeCreateMailProvider
     */
    public function testBulkDisputeCreateMail($features, $disputeCreateInput, $expectedMailView, $expectedMailViewData = [], $mobileSignupTest)
    {
        $this->fixtures->merchant->addFeatures($features);

        $this->runTestBulkDisputeCreateMailSubject($disputeCreateInput, $expectedMailView, $expectedMailViewData, $mobileSignupTest);

    }

    public function functionTestBulkDisputeCreateMailProvider()
    {
        return [
            'dispute_presentment_and_no_deduct_at_onset' => [
                'features'                 => [],
                'dispute_create_input'     => [],
                'expected_mail_view'       => 'bulk_creation_dispute_presentment_enabled',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => false],
                'mobile_signup_test'       => false,
            ],
            'no_dispute_presentment_and_no_deduct_at_onset' => [
                'features'                 => ['exclude_disp_presentment'],
                'dispute_create_input'     => [],
                'expected_mail_view'       => 'bulk_creation',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => false],
                'mobile_signup_test'       => false,
            ],
            'dispute_presentment_and_deduct_at_onset' => [
                'features'                 => [],
                'dispute_create_input'     => ['deduct_at_onset' => true],
                'expected_mail_view'       => 'bulk_creation_dispute_presentment_enabled',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => true],
                'mobile_signup_test'       => false,
            ],
            'no_dispute_presentment_deduct_at_onset' => [
                'features'                 => ['exclude_disp_presentment'],
                'dispute_create_input'     => ['deduct_at_onset' => true],
                'expected_mail_view'       => 'bulk_creation',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => true],
                'mobile_signup_test'       => false,
            ],
            'no_dispute_presentment_and_no_deduct_at_onset_mobile_signup' => [
                'features'                 => ['exclude_disp_presentment'],
                'dispute_create_input'     => [],
                'expected_mail_view'       => 'bulk_creation',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => false],
                'mobile_signup_test'       => true,
            ],
            'dispute_presentment_and_deduct_at_onset_mobile_signup' => [
                'features'                 => [],
                'dispute_create_input'     => ['deduct_at_onset' => true],
                'expected_mail_view'       => 'bulk_creation_dispute_presentment_enabled',
                'expected_mail_view_data'  => ['hasDeductAtOnset' => true],
                'mobile_signup_test'       => true,
            ],
        ];
    }

    // ---------------------------- helper methods-------------------------------

    protected function runTestBulkDisputeCreateMailSubject($disputeCreateInput, $expectedMailView, $expectedMailViewData = [], $mobileSignupTest = false)
    {
        Mail::fake();

        $this->mockSalesforceRequestforSalesPOC('10000000000000', "sales.poc@gmail.com", $mobileSignupTest === true ? 4 : 5);

        $this->ba->cronAuth();

        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $disputePhases = [
          Phase::CHARGEBACK,
          Phase::PRE_ARBITRATION,
          Phase::ARBITRATION,
          Phase::RETRIEVAL,
          Phase::FRAUD,
        ];

        if ($mobileSignupTest === true)
        {
            $this->fixtures->create('merchant_detail', [
                'merchant_id'    => '10000000000000',
                'contact_mobile' => '9991119991',
            ]);

            $this->fixtures->edit('merchant', '10000000000000', [
                'signup_via_email' => 0,
            ]);

            $this->fixtures->create('merchant_email', [
                'type'  => 'chargeback',
                'email' => null,
            ]);

            $expectedContent = [
                'type'          =>  'Question',
                'group_id'        => 82000327895,
                'tags'          =>  ['bulk_dispute_email'],
                'priority'        => 1,
                'phone'           => '+919991119991',
                'custom_fields'   => [
                    'cf_ticket_queue'               => 'Merchant',
                    'cf_category'                   => 'Chargebacks',
                    'cf_subcategory'                => 'Service Chargeback',
                    'cf_product'                    => 'Payment Gateway',
                    'cf_created_by'                 =>  'agent',
                    'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                    'cf_merchant_id'                => '10000000000000',
                    'cf_merchant_activation_status' => 'undefined',
                ],
            ];

            $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                        $expectedContent,
                                                        [
                                                            'id'        => '1234',
                                                            'priority'  => 1,
                                                            'fr_due_by' => 'today',
                                                        ]);
        }

        foreach ($disputePhases as $disputePhase)
        {
            $attributes = [
                'payment_id'                => $this->fixtures->create('payment:captured')->getId(),
                'gateway_dispute_id'        => 'Dispute100001',
                'gateway_dispute_status'    => 'open',
                'reason_id'                 => $reason['id'],
                'phase'                     => $disputePhase,
                'raised_on'                 => (strtotime('-1 month', strtotime('now'))),
                'expires_on'                => (strtotime('+1 month', strtotime('now'))),
                'amount'                    => 10000,
                'email_notification_status' => EmailNotificationStatus::SCHEDULED,
            ];

            $attributes = array_merge($attributes, $disputeCreateInput);

            $this->fixtures->create('dispute', $attributes);

            $testData = &$this->testData['testBulkDisputeCreateMailSubject'];

            $this->startTest($testData);

            if ($mobileSignupTest === false)
            {
                Mail::assertQueued(DisputeBulkCreationMail::class, function ($mail) use ($expectedMailView)
                {
                    $this->assertEquals($this->getBulkDisputeMailExpectedSubject($mail), $mail->subject);

                    $this->assertEquals('emails.dispute.' . $expectedMailView, $mail->view);

                    return true;
                });
            }
        }

    }
    protected function freshdeskFlow(
        bool $needAutomationGroupConst,
        bool $needCustomerSupportGroupConst,
        array $fdClientMockMethods,
        array $expectNoFdCallList,
        bool $needAssignAutomationAgentToTicketCall,
        bool $needChangeTicketGroupToCustomerSupportCall,
        bool $needCloseTicketCall,
        bool $needReplyToTicketCall,
        bool $needFetchTicketCall,
        string $subcategory,
        Payment\Entity $payment,
        array $changeTicketGroupToCsExtraArgs = null,
        string $reasonCode = null)
    {
        $ticketId = 123;
        $automationAgentId = 234;
        $automationGroupId = 345;
        $customerSupportGroupId = 456;

        if ($needAutomationGroupConst)
        {
            $this->app['config']->set('applications.freshdesk.customer.dispute.rzpind.automation_agent_id', $automationAgentId);
            $this->app['config']->set('applications.freshdesk.customer.dispute.rzpind.automation_group_id', $automationGroupId);
        }

        if ($needCustomerSupportGroupConst)
        {
            $this->app['config']->set('applications.freshdesk.customer.dispute.rzpind.customer_support_group_id', $customerSupportGroupId);
        }

        $this->enableRazorXTreatmentForFreshdeskWebhookDisputeAutomation();

        $this->enableFreshdeskMock($fdClientMockMethods);

        foreach ($expectNoFdCallList as $noCallMethod)
        {
            $this->expectNoFreshdeskCall($noCallMethod);
        }

        $updateTicketCallArgs = [];

        $postReplyCallArgs = [];

        $fetchTicketCallArgs = [];

        if ($needAssignAutomationAgentToTicketCall)
        {
            $assignAutomationAgentToTicketCallArgs = [$ticketId, [
                'group_id' => $automationGroupId,
                'responder_id' => $automationAgentId
            ]];

            $updateTicketCallArgs []= $assignAutomationAgentToTicketCallArgs;
        }

        if ($needChangeTicketGroupToCustomerSupportCall)
        {
            $changeTicketGroupToCustomerSupportCallArgs = [$ticketId, [
                'group_id' => $customerSupportGroupId,
                'responder_id' => null
            ]];

            if (empty($changeTicketGroupToCsExtraArgs) === false)
            {
                $changeTicketGroupToCustomerSupportCallArgs[1] = array_merge($changeTicketGroupToCustomerSupportCallArgs[1], $changeTicketGroupToCsExtraArgs);
            }

            $updateTicketCallArgs []= $changeTicketGroupToCustomerSupportCallArgs;
        }

        if ($needCloseTicketCall)
        {
            // Mock closeTicket call
            $closeTicketCallArgs = [$ticketId, [
                'status'       => FreshdeskConstants::FD_TICKET_STATUS_CLOSED,
                'group_id'     => $automationGroupId,
                'responder_id' => $automationAgentId,
            ]];

            $updateTicketCallArgs []= $closeTicketCallArgs;
        }

        if ($needReplyToTicketCall)
        {
            $replyToTicketCallArgs = [$ticketId];

            $postReplyCallArgs []= $replyToTicketCallArgs;
        }

        if ($needFetchTicketCall)
        {
            $fetchTicketCallArg = [$ticketId];

            $fetchTicketCallArgs []= $fetchTicketCallArg;
        }

        if (count($updateTicketCallArgs) > 0)
        {
            $this->expectFreshdeskCall('updateTicketV2', $updateTicketCallArgs);
        }

        if (count($postReplyCallArgs) > 0)
        {
            $this->expectFreshdeskCall('postTicketReply', $postReplyCallArgs);
        }

        if (count($fetchTicketCallArgs) > 0)
        {
            $this->expectFreshdeskCall('fetchTicketById', $fetchTicketCallArgs);
        }

        if (isset($reasonCode) === false)
        {
            $reasonCode = 'goods_service_not_provided';
            $this->fixtures->create('dispute_reason', [
                'network'      => 'RZP',
                'code'         => $reasonCode,
                'gateway_code' => 'RZP01',
            ]);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];
        $testData = &$this->testData[$name];

        $testData['request']['content']['freshdesk_webhook']['ticket_cf_transaction_id'] = $payment->getPublicId();
        $testData['request']['content']['freshdesk_webhook']['ticket_cf_razorpay_payment_id'] = $payment->getPublicId();
        $testData['request']['content']['freshdesk_webhook']['ticket_cf_requestor_subcategory'] = $subcategory;
        $testData['request']['content']['freshdesk_webhook']['ticket_cf_requester_item'] = $reasonCode;

        $this->ba->freshdeskWebhookAuth();

        $this->runRequestResponseFlow($testData);
    }

    protected function updateCreateTestData(string $paymentId = null): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        if (isset($paymentId) === false)
        {
            $this->payment = $this->fixtures->create('payment:captured');

            $paymentId = $this->payment->getPublicId();
        }

        $reason = $this->fixtures->create('dispute_reason');

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/payments/' . $paymentId . '/disputes';

        $testData['request']['content']['reason_id'] = $reason['id'];

        return $testData;
    }

    protected function updateEditTestData(array $attributes = []): array
    {
        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $this->ba->adminProxyAuth($this->merchant->getId(), 'rzp_test_' . $this->merchant->getId());

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }

    protected function updateFetchTestData(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = &$this->testData[$name];

        return $testData;
    }

    protected function updateDetailsFetchTestData(array $attributes = []): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }

    protected function checkRequestDisputeAttributes(array $content, array $disputes)
    {
        $this->assertEquals($content['id'], $disputes['items'][0]['id']);
        $this->assertEquals($content['parent_id'], $disputes['items'][0]['parent_id']);
        $this->assertEquals($content['payment_id'], $disputes['items'][0]['payment_id']);
    }

    protected function updateUploadDocumentData(array $attributes = [], string $testDataKey = null): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $testDataKey ?? $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        $testData['request']['content'][DisputeFileCore::FILES][0][DisputeFileCore::FILE] = $this->getTestFile(0);
        $testData['request']['content'][DisputeFileCore::FILES][1][DisputeFileCore::FILE] = $this->getTestFile(1);

        return $testData;
    }

    protected function createUploadedFile(string $filePath, string $mimeType = null, int $fileSize = -1)
    {
        $this->assertFileExists($filePath);

        $mimeType = $mimeType ?: 'image/png';

        $fileSize = ($fileSize === -1) ? filesize($filePath) : $fileSize;

        $uploadedFile = new UploadedFile(
                                        $filePath,
                                        $filePath,
                                        $mimeType,
                                        null,
                                        true);

        return $uploadedFile;
    }

    protected function getTestFile(int $num)
    {
        $name = 'a' . $num . '.png';

        $originalFile = $this->createUploadedFile('tests/Functional/Storage/a.png');

        copy($originalFile, 'tests/Functional/Storage/' . $name);

        return $this->createUploadedFile('tests/Functional/Storage/' . $name);
    }

    protected function getBulkDisputeUploadedFileData()
    {
        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $fileData = [];

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100001',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::CHARGEBACK,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 10000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100002',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::PRE_ARBITRATION,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 20000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100003',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::ARBITRATION,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 30000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100004',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::RETRIEVAL,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 40000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100005',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::FRAUD,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 50000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100006',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'reason_code'            => $reason['code'],
            'phase'                  => Phase::CHARGEBACK,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'amount'                 => 60000,
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        return $fileData;
    }

    protected function getBulkDisputeUploadedFileDataWithoutReasonCode()
    {
        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

        $fileData = [];

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100001',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::CHARGEBACK,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 10000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100002',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::PRE_ARBITRATION,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 20000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100003',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::ARBITRATION,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 30000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100004',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::RETRIEVAL,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 40000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100005',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::FRAUD,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 50000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        $row = [
            'payment_id'             => $this->fixtures->create('payment:captured')->getPublicId(),
            'gateway_dispute_id'     => 'Dispute100006',
            'gateway_dispute_status' => 'open',
            'network_code'           => $reason['network'] . '-' . $reason['gateway_code'],
            'phase'                  => Phase::CHARGEBACK,
            'raised_on'              => date('d/m/Y', (strtotime('-1 month', strtotime('now')))),
            'expires_on'             => date('d/m/Y', (strtotime('+1 month', strtotime('now')))),
            'gateway_amount'         => 60000,
            'gateway_currency'       => 'INR',
            'skip_email'             => 'N',
            'internal_respond_by'    => date('d/m/Y', (strtotime('+10 day', strtotime('now')))),
            'deduct_at_onset'        => 'N',
        ];

        $fileData[] = $row;

        return $fileData;
    }

    protected function getBulkDisputeUploadedXLSXFileFromFileData($fileData)
    {
        $inputExcelFile = (new DisputeFileService)->createExcelFile(
            $fileData,
            'bulk_dispute_test_input',
            'files/dispute/test'
        );

        $uploadedFile = $this->createUploadedFile($inputExcelFile);

        return $uploadedFile;
    }

    protected function enableRazorXTreatmentForFreshdeskWebhookDisputeAutomation()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('automate');
    }

    protected function enableFreshdeskMock(array $methods)
    {
        $freshdeskClientMock = $this->getMockBuilder(FreshdeskTicketClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods($methods)
            ->getMock();

        $this->app->instance('freshdesk_client', $freshdeskClientMock);
    }

    protected function expectFreshdeskCall(string $method, array $args)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $fdClient */
        $fdClient = $this->app['freshdesk_client'];

        $fdClient
            ->expects($this->exactly(count($args)))
            ->method($method)
            ->withConsecutive(...$args)
            ->willReturn([]);
    }

    protected function expectNoFreshdeskCall(string $method)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $fdClient */
        $fdClient = $this->app['freshdesk_client'];

        $fdClient
            ->expects($this->never())
            ->method($method);
    }

    private function getBulkDisputeMailExpectedSubject($mail)
    {
        $phase = $mail->viewData['phase'];

        $isFraud = false;

        if (isset($mail->viewData['isFraud'])) {
            $isFraud = $mail->viewData['isFraud'];
        }

        $merchantId = $mail->viewData['merchant']['id'];

        $merchantName = $mail->viewData['merchant']['name'];

        $currentDate = Carbon::now(Timezone::IST)->format('d/m/Y');

        switch($phase)
        {
            case Phase::CHARGEBACK:
                if ($isFraud === false) {
                    return sprintf('Razorpay | Service Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
                }
                return sprintf('Razorpay | Fraud Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::RETRIEVAL:
                return sprintf('Razorpay | Retrieval Request Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::PRE_ARBITRATION:
                return sprintf('Razorpay | Pre-Arbitration Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::ARBITRATION:
                return sprintf('Razorpay | Arbritration Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::FRAUD:
                return sprintf('Razorpay | Fraud Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
        }
    }


    protected function getTestCasesForDisputeEditWithStatusInternalStatusValidCombinations() : array
    {
        return [
            [
                'seed'     => [
                    'status' => 'open',
                ],
                'request'  => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'response' => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
            ],
            [
                'seed'     => [
                    'status' => 'open',
                ],
                'request'  => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'response' => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
            ],
            [
                'seed'     => [
                    'status' => 'open',
                ],
                'request'  => [
                    'internal_status' => 'contested',
                ],
                'response' => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'response' => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'internal_status' => 'represented',
                ],
                'response' => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_debited',
                    'recovery_method' => 'refund',
                ],
                'response' => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_debited',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_debited',
                ],
                'response' => [
                    'error' => [
                        'code'        => "BAD_REQUEST_ERROR",
                        'description' => "Recovery Method is required for given Internal Status",
                        'reason'      => "input_validation_failed",
                    ],
                ],
                'exception' => [
                    'class'               => BadRequestValidationFailureException::class,
                    'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                    'message'             => 'Recovery Method is required for given Internal Status',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'under_review',
                    'internal_status' => 'open',
                    'recovery_method' => 'adjustment',
                ],
                'response' => [
                    'error' => [
                        'code'        => "BAD_REQUEST_ERROR",
                        'description' => "Recovery Method not supported for given Internal Status",
                        'reason'      => "input_validation_failed",
                    ],
                ],
                'exception' => [
                    'class'               => BadRequestValidationFailureException::class,
                    'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                    'message'             => 'Recovery Method not supported for given Internal Status',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'lost',
                    'skip_deduction'  => true,
                ],
                'response' => [
                    'status'          => 'lost',
                    'internal_status' => 'lost_merchant_not_debited',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'internal_status' => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'open',
                    'internal_status' => 'open',
                ],
                'response' => [
                    'status'          => 'open',
                    'internal_status' => 'open',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'status'          => 'open',
                ],
                'response' => [
                    'status'          => 'open',
                    'internal_status' => 'open',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request'  => [
                    'internal_status' => 'open',
                ],
                'response' => [
                    'status'          => 'open',
                    'internal_status' => 'open',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'status'          => 'won',
                    'internal_status' => 'won',
                ],
                'response' => [
                    'status'          => 'won',
                    'internal_status' => 'won',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'internal_status' => 'won',
                ],
                'response' => [
                    'status'          => 'won',
                    'internal_status' => 'won',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'status'          => 'won',
                ],
                'response' => [
                    'status'          => 'won',
                    'internal_status' => 'won',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'internal_status' => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
            [
                'seed'     => [
                    'status'          => 'under_review',
                    'internal_status' => 'represented',
                ],
                'request'  => [
                    'status'          => 'closed',
                ],
                'response' => [
                    'status'          => 'closed',
                    'internal_status' => 'closed',
                ],
            ],
        ];
    }

    protected function getTestCasesForDisputeEditWithStatusInternalStatusInvalidCombinations() : array
    {
        return [
            [
                'seed'    => [
                    'status' => 'open',
                    'internal_status' => 'open',
                ],
                'request' => [
                    'status'          => 'won',
                    'internal_status' => 'won',
                ],
            ],
            [
                'seed'    => [
                    'status'          => 'under_review',
                    'internal_status' => 'contested',
                ],
                'request' => [
                    'internal_status' => 'won',
                ],
            ],
        ];
    }
    public function testDisputeFetchForAdminGatewayDisputeNetwork()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['gateway_dispute_id' => '4342frf34r']);

        $this->fixtures->create('dispute', ['gateway_dispute_id' => 'DISPUTE1348184']);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchForAdminGatewayDisputeCustomer()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('dispute', ['gateway_dispute_id' => '4342frf34r']);

        $this->fixtures->create('dispute', ['gateway_dispute_id' => 'DISPUTE1348184']);

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
    }

    public function testDisputeFetchDeductionReversalSetFilter()
    {
        $this->fixtures->create('dispute', ['deduction_reversal_at' => time(), 'status' => 'under_review']);

        $this->fixtures->create('dispute', ['deduction_reversal_at' => null, 'status' => 'under_review']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testDisputeFetchDeductAtOnsetFilter()
    {
        $this->fixtures->create('dispute', ['deduct_at_onset' => true,]);

        $this->fixtures->create('dispute', ['deduct_at_onset' => false,]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPaymentIdNotFound()
    {
        $this->markTestSkipped('Feature not in use');

        $this->testData[__FUNCTION__]['request']['content'][0]['txn_date'] = date("d/m/Y");

        $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                [null, []]);

        $this->mockDruidRequest(['query' => "select authorization_rrn, authorization_payment_id, payments_merchant_id  from druid.payments_fact where authorization_rrn in ('7411075126003315629502')"],
                                [null, []]);

        $this->mockSalesforceRequest([], []);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testMerchantContestsDeductAtOnsetDisputeAndWins()
    {
        [$payment, $dispute] = $this->setupForDisputePresentmentWithDeductAtOnsetScenarios(['status' => 'won']);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
            'status'                => 'won',
            'internal_status'       => 'won',
            'amount_reversed'       => 1000000,
            'deduction_reversal_at' => null,
        ], $dispute);


        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 0,
            'base_amount_refunded' => 0,
            'disputed'             => false,
            'refund_status'        => null,
        ], $payment);
    }

    public function testMerchantContestsDeductAtOnsetDisputeWithScheduledDeductionReversal()
    {
        $tPlusSixty = time() + 60 * 86400;

        [$payment, $dispute] = $this->setupForDisputePresentmentWithDeductAtOnsetScenarios([
            'internal_status'       => 'represented',
            'deduction_reversal_at' => $tPlusSixty,
        ]);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => 'randomAdjId123',
            'status'                => 'under_review',
            'internal_status'       => 'represented',
            'amount_reversed'       => 0,
            'deduction_reversal_at' => $tPlusSixty,
        ], $dispute);


        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 0,
            'base_amount_refunded' => 0,
            'disputed'             => true,
            'refund_status'        => null,
        ], $payment);
    }

    /**
     *  While marking a dispute internal_status to `represented` for deduct at onset dispute,
     * if no default schedule is specified, then a schedule of t+45 is to be created.
     */
    public function testMerchantContestsDeductAtOnsetDisputeWithDefaultScheduleNotSpecified()
    {
        $tPlusFortyFive = time() + 45 * 86400;

        [$payment, $dispute] = $this->setupForDisputePresentmentWithDeductAtOnsetScenarios([
            'internal_status'       => 'represented', // not specifiying deduction_reversal_at in this testcase
        ]);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => 'randomAdjId123',
            'status'                => 'under_review',
            'internal_status'       => 'represented',
            'amount_reversed'       => 0,
        ], $dispute);


        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 0,
            'base_amount_refunded' => 0,
            'disputed'             => true,
            'refund_status'        => null,
        ], $payment);

        $this->assertGreaterThanOrEqual($tPlusFortyFive, $dispute['deduction_reversal_at']);
    }

    public function testMerchantContestsDeductAtOnsetDisputeWithScheduledDeductionReversalOverride()
    {
        $tPlusSixty = time() + 60 * 86400;

        [$payment, $dispute] = $this->setupForDisputePresentmentWithDeductAtOnsetScenarios([
            'internal_status'       => 'represented',
            'deduction_reversal_at' => $tPlusSixty,
        ]);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => 'randomAdjId123',
            'status'                => 'under_review',
            'internal_status'       => 'represented',
            'amount_reversed'       => 0,
            'deduction_reversal_at' => $tPlusSixty,
        ], $dispute);

        $this->performAdminActionOnDispute(['status' => 'won']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
            'status'                => 'won',
            'internal_status'       => 'won',
            'amount_reversed'       => 1000000,
            'deduction_reversal_at' => null,
        ], $dispute);
    }

    /**
     * @dataProvider functionDeductionReversalInputValidationProvider
     */
    public function testDeductionReversalInputValidation($disputeInput)
    {
        $dispute = new Dispute\Entity();

        $dispute->fill($disputeInput);

        try
        {
            $dispute->edit(['deduction_reversal_at' => time()]);

            $this->fail('expected exception:');
        }
        catch (BadRequestValidationFailureException $exception)
        {
            $this->app['trace']->traceException($exception);
        }
    }

    public function functionDeductionReversalInputValidationProvider()
    {
        return [
            'cannot set deduction_reversal_at when internal_status is not represented' => [
                'disputeInput' =>  [
                    'status'=>'won'
                ],
            ],
            'cannot set deduction_reversal_at without deduct at onset' => [
                'disputeInput' => [
                    'internal_status' => 'represented',
                    'deduct_at_onset' => 0,
                ],
            ],
        ];
    }

    public function testDeductionReversalCron()
    {
        $this->ba->cronAuth();

        $disputeToBeReversed = $this->fixtures->create('dispute', [
           'deduct_at_onset'            => 1,
           'deduction_reversal_at'      => time() - 500,
           'internal_status'            => 'represented',
           'status'                     => 'under_review',
           'deduction_source_type'      => 'adjustment',
           'deduction_source_id'        => 'rndAdjstmentId'
        ]);

        //dont reverse this because deduct at onset is false
        $this->fixtures->create('dispute', [
            'deduct_at_onset'            => 0,
            'deduction_reversal_at'      => time() - 500,
            'internal_status'            => 'represented',
            'status'                     => 'under_review',
            'deduction_source_type'      => 'adjustment',
            'deduction_source_id'        => 'rndAdjstmentId'
        ]);

        //dont reverse this because adjustment type is refund
        $this->fixtures->create('dispute', [
            'deduct_at_onset'            => 1,
            'deduction_reversal_at'      => time() - 500,
            'internal_status'            => 'represented',
            'status'                     => 'under_review',
            'deduction_source_type'      => 'refund',
            'deduction_source_id'        => 'randomRefundId'
        ]);

        //dont reverse this because wrong internal status
        $this->fixtures->create('dispute', [
            'deduct_at_onset'            => 1,
            'deduction_reversal_at'      => time() - 500,
            'internal_status'            => 'won',
            'status'                     => 'won',
            'deduction_source_type'      => 'refund',
            'deduction_source_id'        => 'randomRefundId'
        ]);

        $this->fixtures->create('dispute', [
            'deduct_at_onset'            => 1,
            'deduction_reversal_at'      => time() - 500,
            'internal_status'            => 'lost',
            'status'                     => 'lost',
            'deduction_source_type'      => 'refund',
            'deduction_source_id'        => 'randomRefundId'
        ]);

        $response = $this->startTest();

        $this->assertEquals([
           [
               'id'             => $disputeToBeReversed->getId(),
               'success'        => true,
           ],
        ], $response);

        $disputeToBeReversed->reload();

        $this->assertArraySelectiveEquals([
            'internal_status'            => 'won',
            'status'                     => 'won',
            'deduction_source_type'      => null,
            'deduction_source_id'        => null
        ], $disputeToBeReversed->toArray());

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals([
            'entity_id'         => $disputeToBeReversed->getId(),
            'entity_type'       => 'dispute',
            'description'       => 'Credit to reverse a previous dispute debit',
        ], $adjustment);

    }

    /*
   * https://docs.google.com/spreadsheets/d/1cUe13Fw5yif4C54T1Y3t0h-Bb8Dpwdp1Wq71129a7IY/edit#gid=641351782
   */
    protected function setupForDisputePresentmentWithDeductAtOnsetScenarios($adminDisputeEditInput): array
    {
        $this->addPermissionToBaAdmin('edit_dispute');

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->setUpForDisputePresentmentDeductAtOnsetTest();

        $this->contestDispute();

        $this->performAdminActionOnDispute($adminDisputeEditInput);

        [$payment, $dispute] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd'
        );

        return array($payment, $dispute);
    }

    protected function setUpForDisputePresentmentDeductAtOnsetTest()
    {
        $this->setUpForInitiateDraftEvidenceTest([
            'deduct_at_onset'       => true,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => 'randomAdjId123',
        ], 'payment:captured', [
                'amount_refunded'      => 0,
                'base_amount_refunded' => 0,
                'refund_status'        => null,
            ]
        );
    }


    public function testChargebackSuccess()
    {
        $this->markTestSkipped('Feature not in use');
        $past = Carbon::create(2021, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->fixtures->create('dispute_reason', [
            'id'                  => str_random(14),
            'network'             => 'RZP',
            'gateway_code'        => 'RZP00',
            'gateway_description' => '',
            'code'                => 'card_holder_not_recognised',
            'description'         => '',
        ]);

        $this->fixtures->edit('merchant','10000000000000',['name' => 'enim']);

        $this->fixtures->create('merchant_detail', [
            DetailEntity::MERCHANT_ID   => '10000000000000',
        ]);

        $testCases = [
            [
                'network' => 'Visa'
            ],
            [
                'network' => 'Mastercard'
            ],
            [
                'network' => 'RuPay',
            ]
        ];

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['request']['content'][0]['network'] = $testCase['network'];

            $paymentAttributes = [
                'merchant_id' => '10000000000000',
            ];

            $payment           = $this->fixtures->create('payment:captured', $paymentAttributes);

            $this->fixtures->create('dispute_reason', [
                'id'                  => str_random(14),
                'network'             => $testCase['network'],
                'gateway_code'        => '10.3',
                'gateway_description' => '',
                'code'                => 'this_is_the_code',
                'description'         => '',
            ]);

            $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                    [null, [['payments_reference1' => '741107512600331562950201', 'payments_id' => $payment['id'], 'payments_merchant_id' => '10000000000000']]]);

            $this->mockSalesforceRequest(['10000000000000'], ['10000000000000' => 'TempName']);

            $this->ba->batchAppAuth();

            $response = $this->startTest();

            $dispute = $this->getLastEntity('dispute', true);

            $this->assertEquals('pay_' . $payment['id'], $dispute['payment_id']);

            $this->assertEquals($payment['id'], $response['items'][0]['Payment Id']);
        }
    }

    public function testChargebackSuccessAndFailure()
    {
        $this->markTestSkipped('Feature not in use');
        $past = Carbon::create(2021, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->fixtures->create('dispute_reason', [
            'id'                  => str_random(14),
            'network'             => 'RZP',
            'gateway_code'        => 'RZP00',
            'gateway_description' => '',
            'code'                => 'card_holder_not_recognised',
            'description'         => '',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['name' => 'enim']);

        $this->fixtures->create('merchant_detail', [
            DetailEntity::MERCHANT_ID => '10000000000000',
        ]);

        $paymentAttributes = [
            'merchant_id' => '10000000000000',
        ];

        $payment = $this->fixtures->create('payment:captured', $paymentAttributes);

        $this->fixtures->create('dispute_reason', [
            'id'                  => str_random(14),
            'network'             => 'Visa',
            'gateway_code'        => '10.3',
            'gateway_description' => '',
            'code'                => 'this_is_the_code',
            'description'         => '',
        ]);

        $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                [null, [['payments_reference1' => '741107512600331562950201', 'payments_id' => $payment['id'], 'payments_merchant_id' => '10000000000000']]]);

        $this->mockSalesforceRequest(['10000000000000'], ['10000000000000' => 'TempName']);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals('pay_' . $payment['id'], $dispute['payment_id']);

        $this->assertEquals($payment['id'], $response['items'][0]['Payment Id']);
    }

    public function testDisputeTypeGoodFaith()
    {
        $this->markTestSkipped('Feature not in use');
        $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                [null, [['payments_reference1' => '741107512600331562950201', 'payments_id' => '123', 'payments_merchant_id' => '123']]]);

        $this->mockSalesforceRequest(['123'], ['123' => 'TempName']);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testTxnDateBefore120NotProcessed()
    {
        $this->markTestSkipped('Feature not in use');
        $paymentAttributes = [
            'merchant_id' => '10000000000000',
        ];

        $payment  = $this->fixtures->create('payment:captured', $paymentAttributes);

        $this->testData[__FUNCTION__]['request'] = $this->testData['testDisputeTypeGoodFaith']['request'];

        $this->testData[__FUNCTION__]['request']['content'][0]['dispute_type'] = 'ARB';

        $this->testData[__FUNCTION__]['response'] = $this->testData['testDisputeTypeGoodFaith']['response'];

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['error']['description'] = 'transaction older that 120 days';

        $past = Carbon::create(2022, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                [null, [['payments_reference1' => '741107512600331562950201', 'payments_id' => $payment['id'], 'payments_merchant_id' => '10000000000000']]]);

        $this->mockSalesforceRequest(['10000000000000'], ['10000000000000' => 'TempName']);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testPaymentNotInCaptured()
    {
        $this->markTestSkipped('Feature not in use');
        $this->testData[__FUNCTION__]['request'] = $this->testData['testDisputeTypeGoodFaith']['request'];

        $this->testData[__FUNCTION__]['request']['content'][0]['txn_date'] = '01/02/2022';

        $this->testData[__FUNCTION__]['request']['content'][0]['dispute_type'] = 'ARB';

        $this->testData[__FUNCTION__]['response'] = $this->testData['testDisputeTypeGoodFaith']['response'];

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['error']['description'] = 'payment not in captured state';

        $past = Carbon::create(2022, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $payment = $this->fixtures->create('payment:authorized', [
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->mockDruidRequest(['query' => "select payments_reference1, payments_id, payments_merchant_id  from druid.payments_fact  where payments_reference1 in ('741107512600331562950201')"],
                                [null, [['payments_reference1' => '741107512600331562950201', 'payments_id' => $payment['id'], 'payments_merchant_id' => '10000000000000']]]);

        $this->mockSalesforceRequest(['10000000000000'], ['10000000000000' => 'TempName']);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    /*
     * create a DAO on partial refunded payment, mark the DAO as win
     */
    public function testDeductAtOnsetRefundedAmountForWin()
    {
        // testDisputeReversalLostLogic create a partial Deduct at onset dispute of 10100 and mark that as lost
        $this->testDisputeReversalLostLogic();

        $payment = $this->getLastPayment('payment', true);
        $this->assertArraySelectiveEquals([
                                              'disputed'        => false,
                                              'amount_refunded' => 10100,
                                              'refund_status'   => 'partial',
                                              'amount'  =>1000000,
                                          ], $payment);

        $input = [
            'amount'                => 5000,
            'deduct_at_onset'       => 1,
            'payment_id'            => substr($payment['id'], 4)
        ];

        $testdata = $this->updateEditTestData($input);

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $txn = $this->getLastEntity('transaction', true);

        $payment = $this->getEntityById('payment', $payment['id'], true);


        //no change in refund amount of the payment
        $this->assertArraySelectiveEquals([
                                              'disputed'        => false,
                                              'amount_refunded' => 10100,
                                              'refund_status'   => 'partial',
                                              'amount'  =>1000000,
                                          ], $payment);



        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals(5000, $dispute['amount_deducted']);
        $this->assertEquals(5000, $dispute['amount_reversed']);
        $this->assertEquals('adjustment', $txn['type']);
    }

    protected function mockSalesforceRequest($expectedMerchantIds, $expectedResponse): void
    {
        $this->salesforceMock->shouldReceive('getSalesForceTeamNameForMerchantID')
                             ->times(1)
                             ->with(Mockery::on(function($actualMerchantIds) use ($expectedMerchantIds) {
                                 return $this->validateMethodAndContentForChargebackAutomation($actualMerchantIds, 'POST', $expectedMerchantIds);
                             }))
                             ->andReturnUsing(function() use ($expectedResponse) {
                                 return $expectedResponse;
                             });

    }

    protected function mockDruidRequest($expectedContent, $response): void
    {
        $this->druidMock->shouldReceive('getDataFromDruid')
                        ->times(1)
                        ->with(Mockery::on(function($request) use ($expectedContent) {
                            return $this->validateMethodAndContentForChargebackAutomation($request, 'POST', $expectedContent);
                        }))
                        ->andReturnUsing(function() use ($response) {
                            return $response;
                        });

    }

    protected function validateMethodAndContentForChargebackAutomation($actualContent, $expectedMethod, $expectedContent): bool
    {
        if ($expectedMethod != 'POST')
        {
            return false;
        }
        foreach ($expectedContent as $key => $value)
        {
            if (isset($actualContent[$key]) === false)
            {
                return false;
            }

            if ($expectedContent[$key] !== $actualContent[$key])
            {
                return false;
            }
        }

        return true;
    }

    protected function setUpDruidMock(): void
    {
        $this->druidMock = Mockery::mock('RZP\Services\DruidService')->makePartial();

        $this->druidMock->shouldAllowMockingProtectedMethods();

        $this->app['druid.service'] = $this->druidMock;
    }

    protected function setUpSalesforceMock(): void
    {
        $this->salesforceMock = Mockery::mock('RZP\Services\SalesForceClient', $this->app)->makePartial();

        $this->salesforceMock->shouldAllowMockingProtectedMethods();

        $this->app['salesforce'] = $this->salesforceMock;

    }

    protected function mockSalesforceRequestforSalesPOC($expectedMerchantId, $expectedResponse,$times=1): void
    {
        $this->salesforceMock->shouldReceive('getSalesPOCForMerchantID')
                             ->times($times)
                             ->with(Mockery::on(function($actualMerchantId) use ($expectedMerchantId) {
                                 return $actualMerchantId == $expectedMerchantId;
                             }))
                             ->andReturnUsing(function() use ($expectedResponse) {
                                 return $expectedResponse;
                             });

    }
}
