<?php


namespace Functional\Dispute;

use DB;
use Queue;
use Config;
use RZP\Models\Dispute;
use RZP\Models\User\Role;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Mail;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Mail\Dispute\Admin\DisputePresentmentRiskOpsReview;

class DisputePresentmentTest extends TestCase
{
    use DisputeTrait;
    use TestsWebhookEvents;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputePresentmentTestData.php';

        parent::setUp();

        $this->addPermissionToBaAdmin('edit_dispute');

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->app['config']->set('services.disputes.mock', true);
    }


    public function testGetDisputeDocumentTypesMetadata()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidence()
    {
        $beforeEvidenceDocumentCount = DB::Table('dispute_evidence_document')->count();

        $this->setUpForInitiateDraftEvidenceTest();

        $response = $this->startTest();

        $expectedOthersEvidence = [
            [
                'type'         => 'custom_proof_type_1',
                'document_ids' => ['doc_customType1Id2', 'doc_customType1Id1'],
            ],
            [
                'type'         => 'custom_proof_type_2',
                'document_ids' => ['doc_customType2Id1'],

            ],
        ];

        $this->assertArrayHasKey('others', $response['evidence']);
        $actualOthersEvidence = $response['evidence']['others'];

        usort($actualOthersEvidence, function ($a, $b)
        {
            return strcmp($a['type'], $b['type']);
        });

        $this->assertEquals($expectedOthersEvidence, $actualOthersEvidence);


        $afterEvidenceDocumentCount = DB::Table('dispute_evidence_document')->count();

        $this->assertArrayHasKey('submitted_at', $response['evidence']);
        $this->assertGreaterThanOrEqual($response['evidence']['submitted_at'], time());

        $this->assertEquals($beforeEvidenceDocumentCount + 6, $afterEvidenceDocumentCount);


        //Ensure lifecycle attribute is not present when request is made in private. reason: its not part of the public facing API contract
        $this->assertArrayNotHasKey('lifecycle', $response);
    }

    /**
     * Not asserting anything specific in this test.
     * Intent of test is to assert that required routes are reachable from merchant-dashboard
     */
    public function testDisputePresentmentInProxyAuth()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->ba->proxyAuth();

        $responses[] = $features = $this->makeRequestAndGetContent([
            'url'    => '/merchants/me/features',
            'method' => 'get',
        ]);

        $responses[] = $this->makeRequestAndGetContent([
            'url'    => '/disputes/documents/types',
            'method' => 'get',
        ]);

        $responses[] = $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'         => 1000,
                'summary'        => 'sample contest summary',
                'shipping_proof' => ['doc_shippingProfId'],
                'action'         => 'draft',
            ],
        ]);

        $responses[] = $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_0123456789abcd/accept',
            'method'  => 'POST',
            'content' => [

            ],
        ]);

        foreach ($responses as $response)
        {
            $this->assertArrayNotHasKey('error', $response);
        }
    }

    private function mockRazorx($variant = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function($mid, $feature, $mode) use($variant) {
                return $variant;
            }));
    }

    /**
     * Ensure that only those actions performed in private auth are displayed to merchant in proxy auth
     * Only those fields should be displayed in diff which are public fields in the dispute entity
     * In this test, we perform action via private auth and fetch dispute in proxy auth
     */
    public function testDisputeLifecycleAttributesInProxyAuthActionPerformedInPrivateAuth()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        //action is performed in private auth
        $this->acceptDispute('disp_0123456789abcd');

        //retrieval in proxyAuth
        $this->ba->proxyAuth();

        $this->mockRazorx('off');

        $lifecycle = $this->startTest()['lifecycle'];

        //if same request was from admin dashboard, there would be 2 entries
        $this->assertEquals(1, count($lifecycle));
    }

    public function testAcceptDeductAtOnsetDispute()
    {
        Mail::fake();

        $this->setUpForInitiateDraftEvidenceTest(['deduct_at_onset' => true, 'gateway_dispute_id' => 'DISPUTE123'],'payment:captured', ['method' => "card" ]);

        $this->acceptDispute('disp_0123456789abcd');

        [$disputeAfter] = $this->getEntitiesByTypeAndIdMultiple(
            'dispute', '0123456789abcd'
        );

        $this->assertArraySelectiveEquals([
            'internal_status' => 'lost_merchant_debited',
            'status'          => 'lost',
            ], $disputeAfter);

        Mail::assertNotQueued(DisputePresentmentRiskOpsReview::class);
    }

    protected function acceptDispute(string $disputeId)
    {
        return $this->makeRequestAndGetContent([
            'url'     => "/disputes/{$disputeId}/accept",
            'method'  => 'POST',
            'content' => [

            ],
        ]);
    }

    /**
     * Ensure that only those actions performed in proxy/private auth are displayed to merchant in proxy auth
     * Only those fields should be displayed in diff which are public fields in the dispute entity
     * In this test, we perform action via proxy auth and fetch dispute in proxy auth
     */
    public function testDisputeLifecycleAttributesInProxyAuthActionPerformedInProxyAuth()
    {
        $this->mockRazorx('off');

        $this->setUpForInitiateDraftEvidenceTest();

        $this->ba->proxyAuth();

        //action is performed in proxy auth
        $this->acceptDispute('disp_0123456789abcd');

        //retrieval in proxyAuth
        $lifecycle = $this->startTest()['lifecycle'];

        //if same request was from admin dashboard, there would be 2 entries
        $this->assertEquals(1, count($lifecycle));

        $entry = $lifecycle[0];

        $this->assertArrayNotHasKey('internal_status', $entry['change']['new']);
        $this->assertArrayNotHasKey('internal_status', $entry['change']['old']);
    }

    /**
     * Only the following user roles should be able to accept/contest dispute in proxy auth from merchant dashboard
     * Owner
     * Admin
     * Manager
     * Operations
     * Finance
     *
     * This test asserts that other roles fail if they try to contest/accept dispute from proxy auth
     */
    public function testDisputeContestInProxyAuthBlockedRoles()
    {
        $blockedRoles = array_values(array_diff(Role::ALL_ROLES, ['owner', 'admin', 'manager', 'operations', 'finance']));

        $this->setUpForInitiateDraftEvidenceTest();

        foreach ($blockedRoles as $blockedRole)
        {
            $user = $this->fixtures->create('user');

            DB::table('merchant_users')
                ->insert([
                    'merchant_id' => '10000000000000',
                    'user_id'     => $user->getId(),
                    'product'     => 'primary',
                    'role'        => $blockedRole,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ]);

            $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());


            $contestResponse = $this->makeRequestAndGetContent([
                'url'     => '/disputes/disp_0123456789abcd/contest',
                'method'  => 'PATCH',
                'content' => [
                ],
            ]);

            $acceptResponse = $this->makeRequestAndGetContent([
                'url'     => '/disputes/disp_0123456789abcd/accept',
                'method'  => 'POST',
                'content' => [
                ],
            ]);

            $this->assertEquals('Authentication failed', $contestResponse['error']['description']);

            $this->assertEquals('Authentication failed', $acceptResponse['error']['description']);
        }
    }

    public function testInitiateDraftEvidenceNoAmountProvided()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    /**
     * When no action is specified by merchant, default action to be considered is "draft" action
     */
    public function testInitiateDraftNoActionProvided()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidenceNoProofSubmitted()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidenceOnlyActionSubmitted()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();

    }

    public function testInitiateDraftEvidenceInvalidProofSubmitted()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidenceInvalidContestAmount()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        // contest amount greater than dispute amount
        $this->startTest();


        // contest amount cannot be -1
        $this->testData[__FUNCTION__]['request']['content']['amount'] = -1;

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = 'Minimum transaction amount allowed is Re. 1';

        $this->startTest();
    }

    public function testInitiateDraftEvidenceInvalidDisputeStatus()
    {
        $errorDescriptionFormat = $this->testData[__FUNCTION__]['response']['content']['error']['description'];

        $this->setUpForInitiateDraftEvidenceTest();

        foreach (['under_review', 'lost', 'won', 'closed'] as $status)
        {
            $this->fixtures->edit('dispute', '0123456789abcd', [
                'status' => $status,
            ]);

            $this->testData[__FUNCTION__]['response']['content']['error']['description'] = sprintf($errorDescriptionFormat, $status);

            $this->startTest();
        }
    }

    public function testInitiateDraftEvidenceInvalidAction()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    /**
     * For scenario when merchant submits invalid document id/document id belonging to other merchants etc
     */
    public function testInitiateDraftEvidenceInvalidDocumentId()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidenceInvalidDocumentTypeSubmittedAsEvidence()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testInitiateDraftEvidenceDisputeDoesntBelongToMerchant()
    {
        $merchantId = $this->fixtures->create('merchant')['id'];

        $this->setUpFixtures(['merchant_id' => $merchantId]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * In this testcase. merchant has already submitted proof of billing_profo and shipping_profo
     * We update this evidence amount, summary, update billing_proof and add new proof for explanation_letter
     * Existing proof updates are in place
     */
    public function testUpdateDraftEvidence()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $beforeDocumentsCount = DB::table("dispute_evidence_document")->count();

        $this->startTest();

        $afterDocumentsCount = DB::table("dispute_evidence_document")->count();

        $this->assertEquals($beforeDocumentsCount + 7, $afterDocumentsCount);
    }

    /**
     * In this testcase, the request doesnt contain a new amount OR a  new summary
     * assert that existing contest amount, contest summary are considered
     * (instead of the entire dispute amount)
     */
    public function testUpdateDraftEvidenceOnlyProofUpdated()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->startTest();
    }

    public function testUpdateDraftEvidenceNullifyProof()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->startTest();
    }

    public function testUpdateDraftEvidenceLeadingToNoProofSubmitted()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->startTest();
    }

    public function testContestDispute()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->expectDisputeWebhook('under_review');

        $this->startTest();
    }

    protected function expectDisputeWebhook(string $event)
    {
        $testCase = $this->getName();

        $this->expectWebhookEventWithContents("payment.dispute.{$event}", "{$testCase}EventData");
    }

    public function testContestDisputePartialAmount()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testContestDisputeWithoutAmountProvided()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testContestDisputeWithoutEvidenceSubmittedShouldFail()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->startTest();
    }

    public function testContestDisputeInvalidDisputeStatus()
    {
        $errorDescriptionFormat = $this->testData[__FUNCTION__]['response']['content']['error']['description'];

        $this->setUpForInitiateDraftEvidenceTest();

        foreach (['under_review', 'lost', 'won', 'closed'] as $status)
        {
            $this->fixtures->edit('dispute', '0123456789abcd', [
                'status' => $status,
            ]);

            $this->testData[__FUNCTION__]['response']['content']['error']['description'] = sprintf($errorDescriptionFormat, $status);

            $this->startTest();
        }
    }

    public function testDisputeReopenedFromUnderReviewWebhook()
    {
        $this->setUpFixtures(['status' => 'under_review']);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->expectDisputeWebhook('action_required');

        $this->startTest();
    }

    public function testGetDisputeByIDWithoutFeatureEnabled()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->fixtures->merchant->addFeatures(['exclude_disp_presentment']);

        $response = $this->startTest();

        $this->assertArrayNotHasKey('evidence', $response);
    }

    public function testGetDisputeByIdWithFeatureEnabledAndNoEvidence()
    {
        $this->testData[__FUNCTION__] = $this->testData['testGetDisputeByIDWithoutFeatureEnabled'];

        $this->setUpForInitiateDraftEvidenceTest();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('evidence', $response);
    }

    public function testAcceptDisputeWithoutFeatureEnabled()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->fixtures->merchant->addFeatures(['exclude_disp_presentment']);

        $this->startTest();
    }

    public function testAcceptDisputeBeyondRespondByDateShouldFail()
    {
        $this->setUpForInitiateDraftEvidenceTest([
            'expires_on' => time() - 10000,
        ]);

        $this->startTest();
    }

    public function testContestDisputeBeyondRespondByDateShouldFail()
    {
        $this->setUpForInitiateDraftEvidenceTest([
            'expires_on' => time() - 10000,
        ]);

        $this->startTest();
    }

    public function testAcceptDispute()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->expectDisputeWebhook('lost');

        $this->startTest();
    }

    public function testAcceptDisputeShouldPurgeExistingEvidence()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $currentEvidence = $this->makeRequestAndGetContent([
            'url'    => '/disputes/disp_0123456789abcd/',
            'method' => 'GET',
        ])['evidence'];

        $this->assertEquals(1000, $currentEvidence['amount']);

        $this->assertEquals('sample contest summary', $currentEvidence['summary']);

        $this->testData[__FUNCTION__] = $this->testData['testAcceptDispute'];

        $this->startTest();
    }

    /**
     * Any customer dispute should result in refund as the recovery method
     * refer: https://docs.google.com/spreadsheets/d/1Uh_s0rm3PO9GOdiNVo6xRWdaG13wsD6W_YwJMZ_4OEE/edit?ts=60f15177#gid=0
     */
    public function testAcceptDisputeGetRecoveryAmountMethodForCustomerDispute()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $disputeCore = (new Dispute\Core);

        $disputeEntity = (new Dispute\Repository)->findOrFail('0123456789abcd');

        $testcases = [
            [
                'payment_edit_input' => ['method' => 'unknown_method'],
            ],
            [
                'payment_edit_input' => ['method' => 'card'],
            ],
            [
                'payment_edit_input' => ['method' => 'upi', 'gateway' => 'upi_axis'],
            ],
            [
                'payment_edit_input' => ['method' => 'wallet', 'gateway' => 'wallet_mpesa'],
            ],
            [
                'payment_edit_input' => ['method' => 'netbanking', 'gateway' => 'netbanking_sbi'],//example netbanking gateway that needs to be sent to risk for review
            ],
        ];

        foreach ($testcases as $testcase)
        {
            $this->fixtures->payment->edit('randomPayId123', $testcase['payment_edit_input']);

            $this->fixtures->dispute->edit('0123456789abcd', ['gateway_dispute_id' => 'DISPUTE123']);

            $disputeEntity->refresh();

            $actualRecoveryOption = $disputeCore->getRecoveryMethodForDisputeAccept($disputeEntity);

            $this->assertEquals('refund', $actualRecoveryOption, 'Test recovery option for ' . json_encode($testcase));
        }
    }

    /**
     * refer: https://docs.google.com/spreadsheets/d/1Uh_s0rm3PO9GOdiNVo6xRWdaG13wsD6W_YwJMZ_4OEE/edit?ts=60f15177#gid=0
     */
    public function testAcceptDisputeGetRecoveryAmountMethodForBankDispute()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $disputeCore = (new Dispute\Core);

        $disputeEntity = (new Dispute\Repository)->findOrFail('0123456789abcd');

        $testcases = [
            [
                'payment_edit_input'       => ['method' => 'unknown_method'],
                'expected_recovery_method' => 'risk_ops_review',
            ],
            [
                'payment_edit_input'       => ['method' => 'card'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'netbanking_hdfc'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'netbanking_yesb'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'netbanking_axis'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'atom'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'billdesk'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'netbanking_icici'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'netbanking', 'gateway' => 'netbanking_sbi'],//example netbanking gateway that needs to be sent to risk for review
                'expected_recovery_method' => 'risk_ops_review',
            ],
            [
                'payment_edit_input'       => ['method' => 'upi', 'gateway' => 'upi_axis'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'upi', 'gateway' => 'upi_icici'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'upi', 'gateway' => 'upi_sbi'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'upi', 'gateway' => 'upi_yesbank'],
                'expected_recovery_method' => 'risk_ops_review',
            ],
            [
                'payment_edit_input'       => ['method' => 'upi', 'gateway' => 'upi_mindgate'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_olamoney'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_phonepe'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'mobikwik'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_payzapp'],
                'expected_recovery_method' => 'adjustment',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'bajajfinserv'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_freecharge'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_jiomoney'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'bajajfinserv'],
                'expected_recovery_method' => 'refund',
            ],
            [
                'payment_edit_input'       => ['method' => 'wallet', 'gateway' => 'wallet_mpesa'],
                'expected_recovery_method' => 'risk_ops_review',
            ],
        ];

        foreach ($testcases as $testcase)
        {
            $this->fixtures->payment->edit('randomPayId123', $testcase['payment_edit_input']);

            $expectedRecoveryOption = $testcase['expected_recovery_method'];

            $disputeEntity->refresh();

            $actualRecoveryOption = $disputeCore->getRecoveryMethodForDisputeAccept($disputeEntity);


            $this->assertEquals($expectedRecoveryOption, $actualRecoveryOption, 'Test recovery option for ' . json_encode($testcase));
        }
    }

    public function testAcceptDisputeRecoverViaAdjustment()
    {
        $this->setUpForInitiateDraftEvidenceTest(); // payment is of card type -> recovery via adjustment

        $this->expectDisputeWebhook('lost');

        [$paymentBefore, $disputeBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd'
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $adjustment, $transaction] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'adjustment', null,
            'transaction', null
        );

        $this->assertArraySelectiveEquals([
            'amount_refunded' => 0,
        ], $paymentBefore);

        $this->assertArraySelectiveEquals([
            'amount_refunded' => 1000000,
        ], $paymentAfter);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'status'                => 'lost',
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => Adjustment\Entity::verifyIdAndSilentlyStripSign($adjustment['id']),
            'internal_status'       => 'lost_merchant_debited',
        ], $disputeAfter);


        $this->assertArraySelectiveEquals([
            'merchant_id'    => '10000000000000',
            'entity_type'    => 'dispute',
            'entity_id'      => '0123456789abcd',
            'amount'         => -1000000,
            'currency'       => 'INR',
            'description'    => 'Debit disputed amount V2',
            'transaction_id' => Transaction\Entity::verifyIdAndSilentlyStripSign($transaction['id']),
        ], $adjustment);


        $this->assertArraySelectiveEquals([
            'amount'    => 1000000,
            'entity_id' => 'adj_' . $adjustment['id'],
        ], $transaction);
    }

    public function testAcceptDisputeNonINRRecoveryViaAdjustment()
    {
        $this->setUpForInitiateDraftEvidenceTest([
            'amount'        => 10,
            'base_amount'   => 100,
        ], 'payment:captured', [
            'amount'        => 10,
            'base_amount'   => 100,
            'international' => true,
        ]);



        [$paymentBefore, $disputeBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd'
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $adjustment, $transaction] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'adjustment', null,
            'transaction', null
        );

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 100,
            'status'                => 'lost',
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => Adjustment\Entity::verifyIdAndSilentlyStripSign($adjustment['id']),
            'internal_status'       => 'lost_merchant_debited',
        ], $disputeAfter);


        $this->assertArraySelectiveEquals([
            'merchant_id'    => '10000000000000',
            'entity_type'    => 'dispute',
            'entity_id'      => '0123456789abcd',
            'amount'         => -100,
            'currency'       => 'INR',
            'description'    => 'Debit disputed amount V2',
            'transaction_id' => Transaction\Entity::verifyIdAndSilentlyStripSign($transaction['id']),
        ], $adjustment);


        $this->assertArraySelectiveEquals([
            'amount'    => 100,
            'entity_id' => 'adj_' . $adjustment['id'],
        ], $transaction);

    }


    public function testAcceptDisputeRecoveryViaAdjustmentFail()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->fixtures->edit('balance', '10000000000000', [
            'balance' => 200, // reduce balance to below dispute amount
        ]);

        $this->acceptDispute('disp_0123456789abcd');

        // even though adjustment creation failed, dispute should be moved into lost state
        // this is the product requirement
        $disputeAfter = $this->getEntityById('dispute', '0123456789abcd', true);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertNotNull($adjustment);
        $this->assertEquals('processed', $adjustment['status']);
        $this->assertEquals($adjustment['id'], "adj_" . $disputeAfter['deduction_source_id']);
    }

    public function testAcceptDisputeRecoveryViaRefundWithNoPreExistingRefunds()
    {
        Mail::fake();

        $this->expectDisputeWebhook('lost');

        $this->setUpForInitiateDraftEvidenceTest([], 'payment:netbanking_captured', ['gateway' => 'netbanking_hdfc']);

        [$paymentBefore, $disputeBefore, $refundBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $refundAfter] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );


        $this->assertNull($refundBefore);

        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 1000000,
            'currency'   => 'INR',
            'notes'      => [
                'reason' => 'disp_0123456789abcd',
            ],
        ], $refundAfter);

        $this->assertArraySelectiveEquals([
            'amount_refunded' => 0,
            'refund_status'   => null,
            'disputed'        => true,
        ], $paymentBefore);

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 1000000,
            'base_amount_refunded' => 1000000,
            'refund_status'        => 'full',
            'disputed'             => false,
        ], $paymentAfter);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 1000000,
            'status'                => 'lost',
            'deduction_source_type' => 'refund',
            'deduction_source_id'   => Refund\Entity::verifyIdAndSilentlyStripSign($refundAfter['id']),
            'internal_status'       => 'lost_merchant_debited',
        ], $disputeAfter);

        Mail::assertNotQueued(DisputePresentmentRiskOpsReview::class);
    }

    public function testAcceptDisputeRecoveryViaRefundWithDisputeAmountGreaterThanUnrefundedAmount()
    {
        Mail::fake();

        $this->setUpForAcceptDisputeViaRefundTest(300000, 800000);

        [$paymentBefore, $disputeBefore, $refundBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $refundAfter] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        //--------assertions
        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 300000,
        ], $refundBefore);

        // we dont want a refund to be created when dispute amount > unrefunded amount
        // this is a product/ops requirement. therefore asserting no new refund entity got created
        $this->assertEquals($refundBefore, $refundAfter);

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 300000,
            'base_amount_refunded' => 300000,
            'refund_status'        => 'partial',
            'disputed'             => true,
        ], $paymentBefore);

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 300000,
            'base_amount_refunded' => 300000,
            'refund_status'        => 'partial',
            'disputed'             => false,
        ], $paymentAfter);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'lost',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
            'internal_status'       => 'lost_merchant_not_debited',
        ], $disputeAfter);


        Mail::assertQueued(DisputePresentmentRiskOpsReview::class, function (DisputePresentmentRiskOpsReview $mail)
        {
            $this->assertEquals('adjustment or refund creation failed', $mail->viewData['reason_for_review']);

            $this->assertEquals('Cannot create refund for dispute accept because dispute amount is greater than unrefunded amount',
                $mail->viewData['review_message']);

            return true;
        });
    }

    protected function setUpForAcceptDisputeViaRefundTest($refundAmount, $disputeAmount): void
    {
        $this->setUpPaymentFixtures('payment:netbanking_captured', ['gateway' => 'netbanking_hdfc']);

        $this->refundPayment('pay_randomPayId123', $refundAmount);

        $this->setUpDisputeFixtures(['amount' => $disputeAmount, 'base_amount' => $disputeAmount]);
    }

    public function testAcceptDisputeRecoveryViaRefundWithDisputeAmountLesserThanUnrefundedAmount()
    {
        Mail::fake();

        $this->setUpForAcceptDisputeViaRefundTest(300000, 600000);


        [$paymentBefore, $disputeBefore, $refundBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $refundAfter] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 300000,
            'base_amount_refunded' => 300000,
            'refund_status'        => 'partial',
            'disputed'             => true,
        ], $paymentBefore);

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 900000,
            'base_amount_refunded' => 900000,
            'refund_status'        => 'partial',
            'disputed'             => false,
        ], $paymentAfter);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 600000,
            'status'                => 'lost',
            'deduction_source_type' => 'refund',
            'deduction_source_id'   => Refund\Entity::verifyIdAndSilentlyStripSign($refundAfter['id']),
            'internal_status'       => 'lost_merchant_debited',
        ], $disputeAfter);

        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 300000,
        ], $refundBefore);

        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 600000,
            'currency'   => 'INR',
        ], $refundAfter);

        Mail::assertNotQueued(DisputePresentmentRiskOpsReview::class);
    }

    public function testAcceptDisputeRecoveryViaRefundWithDisputeAmountEqualToUnrefundedAmount()
    {
        Mail::fake();

        $this->setUpForAcceptDisputeViaRefundTest(300000, 700000);


        [$paymentBefore, $disputeBefore, $refundBefore] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->ba->privateAuth();

        $this->acceptDispute('disp_0123456789abcd');

        [$paymentAfter, $disputeAfter, $refundAfter] = $this->getEntitiesByTypeAndIdMultiple(
            'payment', 'randomPayId123',
            'dispute', '0123456789abcd',
            'refund', null
        );

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 300000,
            'base_amount_refunded' => 300000,
            'refund_status'        => 'partial',
            'disputed'             => true,
        ], $paymentBefore);

        $this->assertArraySelectiveEquals([
            'amount_refunded'      => 1000000,
            'base_amount_refunded' => 1000000,
            'refund_status'        => 'full',
            'disputed'             => false,
        ], $paymentAfter);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 0,
            'status'                => 'open',
            'deduction_source_type' => null,
            'deduction_source_id'   => null,
        ], $disputeBefore);

        $this->assertArraySelectiveEquals([
            'amount_deducted'       => 700000,
            'status'                => 'lost',
            'deduction_source_type' => 'refund',
            'deduction_source_id'   => Refund\Entity::verifyIdAndSilentlyStripSign($refundAfter['id']),
            'internal_status'       => 'lost_merchant_debited',
        ], $disputeAfter);

        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 300000,
        ], $refundBefore);

        $this->assertArraySelectiveEquals([
            'payment_id' => 'pay_randomPayId123',
            'amount'     => 700000,
            'currency'   => 'INR',
        ], $refundAfter);

        Mail::assertNotQueued(DisputePresentmentRiskOpsReview::class);

    }

    public function testAcceptDisputeRecoveryViaRiskOpsReview()
    {
        Mail::fake();

        $this->setUpForInitiateDraftEvidenceTest([], 'payment:netbanking_captured'); // goes via unmapped gateway

        $this->acceptDispute('disp_0123456789abcd');

        Mail::assertQueued(DisputePresentmentRiskOpsReview::class, function (DisputePresentmentRiskOpsReview $mail)
        {
            $this->assertEquals('behavior not specified for payment+gateway combination', $mail->viewData['reason_for_review']);

            return true;
        });
    }

    public function testAdminFetchPresentmentEntities()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        [$disputeEvidence, $disputeEvidenceDocument] = $this->getEntitiesByTypeAndIdMultiple('dispute_evidence', null, 'dispute_evidence_document', null);

        $this->assertArrayKeysExist($disputeEvidence, ['id', 'summary', 'amount', 'currency', 'rejection_reason', 'source', 'created_at', 'updated_at', 'submitted_at', 'admin', 'dispute_id']);

        $this->assertArrayKeysExist($disputeEvidenceDocument, ['id', 'dispute_id', 'type', 'custom_type', 'document_id', 'created_at', 'updated_at', 'admin', 'entity']);
    }
}
