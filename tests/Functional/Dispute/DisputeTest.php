<?php

namespace RZP\Tests\Functional\Dispute;

use Mail;
use Illuminate\Http\UploadedFile;

use RZP\Models\Dispute\Phase;
use RZP\Models\Dispute\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Dispute\Reason\Network;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Dispute\File\Core as DisputeFileCore;
use RZP\Mail\Dispute\Creation as DisputeCreationMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Dispute\File\Service as DisputeFileService;
use RZP\Mail\Dispute\BulkCreation as DisputeBulkCreationMail;
use RZP\Mail\Dispute\Admin\AcceptedAdmin as DisputeAcceptedForAdminMail;
use RZP\Mail\Dispute\Admin\SubmittedAdmin as DisputeSubmittedForAdminMail;

class DisputeTest extends TestCase
{
    use WebhookTrait;
    use PaymentTrait;
    use MocksDnsTrait;

    protected $payment = null;

    protected $merchant = null;

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

    public function testDisputeCreateMerchantMail()
    {
        Mail::fake();

        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->startTest($testData);

        Mail::assertQueued(DisputeCreationMail::class, function ($mail) use ($testData)
        {
            $this->stringContains(
                $testData['response']['content']['payment_id'],
                $mail->subject
            );

            $this->stringContains(
                $testData['response']['content']['payment_id'],
                $mail->viewData
            );

            $this->assertArrayHasKey('dispute', $mail->viewData);

            $this->assertArrayHasKey('merchant', $mail->viewData);

            return ($mail->hasFrom('disputes@razorpay.com') and
                ($mail->hasTo('test@razorpay.com')));
        });
    }

    public function testDisputeCreateWithoutMerchantEmail()
    {
        Mail::fake();

        $testData = $this->updateCreateTestData();

        $this->startTest($testData);

        Mail::assertNotSent(DisputeCreationMail::class);
    }

    /**
     * @group dns-sensitive
     */
    public function testDisputeCreatedWebhook()
    {
        $this->setupMockDns();

        $this->createWebhook(['events' => ['payment.dispute.created' => '1']]);

        $payment = $this->doAuthAndCapturePayment();

        $paymentId = $payment['id'];

        $testData = $this->updateCreateTestData($paymentId);

        $eventTestDataKey = 'testDisputeCreatedWebhookEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

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
        $this->createWebhook(['events' => ['payment.dispute.won' => '1', 'payment.dispute.lost' => '1']]);

        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeWonEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditWonPostDeduct()
    {
        $this->createWebhook(['events' => ['payment.dispute.won' => '1', 'payment.dispute.lost' => '1']]);

        $data = $this->updateEditTestData(['deduct_at_onset' => 1, 'amount' => 1000000]);

        $eventTestDataKey = 'testDisputeWonEventPostDeductData';

        $this->setInfernoExpectations([$eventTestDataKey]);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditClose()
    {
        $this->createWebhook(['events' => ['payment.dispute.won' => '1', 'payment.dispute.closed' => '1']]);

        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeClosedEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

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
        $this->createWebhook(['events' => ['payment.dispute.created' => '1', 'payment.dispute.lost' => '1']]);

        $data = $this->updateEditTestData();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $this->ba->adminProxyAuth();

        $eventTestDataKey = 'testDisputeLostEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

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
        $this->createWebhook(['events' => ['payment.dispute.created' => '1', 'payment.dispute.lost' => '1']]);

        $data = $this->updateEditTestData();

        $eventTestDataKey = 'testDisputeEditLostWithoutDeductionEventData';

        $this->setInfernoExpectations([$eventTestDataKey]);

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

        $disputes = $this->getEntities('dispute')['items'];

        $this->assertCount(count($fileData), $disputes);

        foreach ($disputes as $disputeEntityItem)
        {
            $fileRow = $fileRowByPaymentIdMap[$disputeEntityItem['payment_id']];

            $this->assertEquals($fileRow['amount'], $disputeEntityItem['amount']);

            $this->assertEquals($fileRow['gateway_dispute_id'], $disputeEntityItem['gateway_dispute_id']);

            $this->assertEquals($fileRow['gateway_dispute_status'], $disputeEntityItem['status']);

            $this->assertEquals($fileRow['reason_code'], $disputeEntityItem['reason_code']);

            $this->assertEquals($fileRow['phase'], $disputeEntityItem['phase']);
        }
    }

    public function testPhaseBasedBulkCreateMails()
    {
        Mail::fake();

        $fileData = $this->getBulkDisputeUploadedFileData();

        $uploadedFile = $this->getBulkDisputeUploadedXLSXFileFromFileData($fileData);

        $testData['request']['files'][DisputeFileCore::FILE] = $uploadedFile;

        $testData['request']['url'] = '/disputes/bulk-create';

        $this->startTest($testData);

        $fileRowByPaymentIdMap = [];

        $totalPhaseAmounts = [];

        foreach ($fileData as $fileRow)
        {
            $fileRowByPaymentIdMap[$fileRow['payment_id']] = $fileRow;

            $phase = $fileRow['phase'];

            $amount = $fileRow['amount'];

            if (isset($totalPhaseAmounts[$phase]) === false)
            {
                $totalPhaseAmounts[$phase] = 0;
            }

            $totalPhaseAmounts[$phase] += $amount;
        }

        $expectedData = [
            'file_row_map' => $fileRowByPaymentIdMap,
            'total_amount' => $totalPhaseAmounts,
        ];

        Mail::assertQueued(DisputeBulkCreationMail::class, function ($mail) use ($expectedData)
        {
            $mailData = $mail->viewData;

            $this->assertTrue(Phase::exists($mailData['phase']));

            $this->assertEquals($expectedData['total_amount'][$mailData['phase']], $mailData['totalAmount']);

            foreach ($mailData['disputesDataTable'] as $disputeRow)
            {
                $fileRow = $expectedData['file_row_map'][$disputeRow['payment_id']];

                $this->assertEquals($fileRow['gateway_dispute_id'], $disputeRow['case_id']);

                $this->assertEquals($fileRow['phase'], $disputeRow['phase']);
            }

            return ($mail->hasFrom('disputes@razorpay.com') and
                ($mail->hasTo('test@razorpay.com')));
        });
    }

    // ---------------------------- helper methods-------------------------------

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
            'contact'                => null,
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
            'contact'                => null,
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
            'contact'                => null,
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
            'contact'                => null,
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
            'contact'                => null,
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
            'contact'                => null,
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
}
