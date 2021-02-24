<?php

namespace RZP\Tests\Functional\Dispute;

use Mail;
use Cache;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Dispute\Phase;
use RZP\Models\Dispute\Entity;
use RZP\Models\Dispute\Repository;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Dispute\EmailNotificationStatus;
use RZP\Models\Dispute\Reason\Network;
use RZP\Models\Dispute\Reason\Entity as DisputeReasonEntity;
use RZP\Services\FreshdeskTicketClient;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Dispute\File\Core as DisputeFileCore;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Dispute\File\Service as DisputeFileService;
use RZP\Models\Dispute\Customer\FreshdeskTicket\ReasonCode;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Subcategory;
use RZP\Mail\Dispute\BulkCreation as DisputeBulkCreationMail;
use RZP\Mail\Dispute\Admin\AcceptedAdmin as DisputeAcceptedForAdminMail;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Mail\Dispute\Admin\SubmittedAdmin as DisputeSubmittedForAdminMail;

class DisputeTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;

    protected $payment = null;

    protected $merchant = null;

    protected $repo = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testDisputeCreate()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(0, $dispute['amount_deducted']);

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

        $this->ba->adminProxyAuth();

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

    public function testDisputeCreateWithDeduct()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $transaction['type']);

        $this->assertEquals(100, $transaction['amount']);

        $this->assertEquals(100, $transaction['debit']);

        $this->assertEquals(0, $transaction['credit']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(100, $dispute['amount_deducted']);
    }

    public function testDisputeCreateWithDeductWithoutEnoughBalance()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->fixtures->refund->createFromPayment(['payment' => $payment]);

        $testData = $this->updateCreateTestData('pay_' . $payment->getId());

        $testData['request']['content']['amount'] = $payment->getAmount();

        $this->startTest($testData);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('refund', $transaction['type']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertNull($dispute);
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
        $data = $this->updateEditTestData(['deduct_at_onset' => 1, 'amount' => 1000000]);

        $eventTestDataKey = 'testDisputeWonEventPostDeductData';

        $this->expectWebhookEventWithContents('payment.dispute.won', $eventTestDataKey);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
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

        $this->assertEquals(false, $payment['disputed']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);
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
        $data = $this->updateEditTestData(['deduct_at_onset' => 1]);

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

    public function testDisputeLostPartiallyAccepted()
    {
        $this->markTestSkipped('Partial dispute are not supported');

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

    public function testDisputeLostPartiallyAcceptedForNoOnsetDeduct()
    {
        $this->markTestSkipped('Partial dispute are not supported');

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
        $this->markTestSkipped('Partial dispute are not supported');

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
        $this->markTestSkipped('Partial dispute are not supported');

        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 0;

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

        $this->checkDisputeFetchForMerchant($disputes, $content);
    }

    public function testDisputeFetchForAdmin()
    {
        $this->ba->adminAuth();

        $this->fixtures->times(2)->create('dispute');

        $testData = $this->updateFetchTestData();

        $this->runRequestResponseFlow($testData);
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

    public function testDisputeEditLostWithoutDeduction()
    {
        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeEditLostWithoutDeductionEventData';

        $this->expectWebhookEventWithContents('payment.dispute.lost', $eventTestDataKey);

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

    public function testPhaseBasedBulkCreateMails()
    {
        Mail::fake();

        $this->ba->cronAuth();

        $reason = $this->fixtures->create('dispute_reason', [
            'code'    => 'dummy_reason',
            'network' => Network::VISA,
        ]);

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
                ($mail->hasTo('test@razorpay.com')));
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

        $attributes = [
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
        $this->fixtures->create('dispute', $attributes);

        $testData = &$this->testData[__FUNCTION__];

        $this->startTest($testData);

        Mail::assertQueued(DisputeBulkCreationMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->rawAttachments);

            $this->assertArrayHasKey('data', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('name', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('options', $mail->rawAttachments[0]);

            $this->assertArrayHasKey('options', $mail->rawAttachments[0]);

            $this->assertInternalType('string', $mail->rawAttachments[0]['data']);

            $this->assertEquals('application/csv', $mail->rawAttachments[0]['options']['mime']);

            $attachmentFilePrefix = DisputeBulkCreationMail::BULK_DISPUTE_ATTACHMENT_FILE_NAME;

            $attachmentFileExtension = '.csv';

            $this->assertStringStartsWith($attachmentFilePrefix, $mail->rawAttachments[0]['name']);

            $this->assertStringEndsWith($attachmentFileExtension, $mail->rawAttachments[0]['name']);

            return ($mail->hasFrom('disputes@razorpay.com') and
                ($mail->hasTo('test@razorpay.com')));
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
        ];

        $fileData[] = $row;

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $this->startTest($testData);
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

    public function testFreshdeskWebhookPaymentFailedCase()
    {
        $payment = $this->fixtures->create('payment:failed');

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply'], ['postTicketReply'], true, true, false, false, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
    }

    public function testFreshdeskWebhookPaymentNotCapturedCase()
    {
        $payment = $this->fixtures->create('payment:authorized');

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
    }

    public function testFreshdeskWebhookPaymentFullyRefundedCase()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->refundPayment($payment->getPublicId());

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
    }

    public function testFreshdeskWebhookPaymentAlreadyDisputedCase()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->disputePayment($payment);

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
    }

    public function testFreshdeskWebhookMerchantDisabledCase()
    {
        $payment = $this->fixtures->create('payment:captured');

        // reduce funds
        $merchantBalance = $this->fetchBalance();
        $this->fixtures->edit('balance', $merchantBalance['id'], ['balance' => $payment->getAmount() - 1]);

        $this->freshdeskFlow(true, false, ['updateTicketV2', 'postTicketReply'], [], true, false, true, true, false, Subcategory::DISPUTE_A_PAYMENT_FD, $payment);
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

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply', 'fetchTicketById'], [], true, true, false, true, true, Subcategory::DISPUTE_A_PAYMENT_FD, $payment, $changeTicketGroupToCsExtraArgs);

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

        $this->freshdeskFlow(true, true, ['updateTicketV2', 'postTicketReply', 'fetchTicketById'], [], true, true, false, true, true, Subcategory::REPORT_FRAUD, $payment, $changeTicketGroupToCsExtraArgs, $reasonCode);

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

    // ---------------------------- helper methods-------------------------------

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
            $this->app['config']->set('applications.freshdesk.customer.dispute.automation_agent_id', $automationAgentId);
            $this->app['config']->set('applications.freshdesk.customer.dispute.automation_group_id', $automationGroupId);
        }

        if ($needCustomerSupportGroupConst)
        {
            $this->app['config']->set('applications.freshdesk.customer.dispute.customer_support_group_id', $customerSupportGroupId);
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
        $this->ba->adminProxyAuth();

        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

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
                                        $fileSize,
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
}
