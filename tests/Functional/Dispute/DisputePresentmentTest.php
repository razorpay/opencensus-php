<?php


namespace Functional\Dispute;

use DB;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class DisputePresentmentTest extends TestCase
{

    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputePresentmentTestData.php';

        parent::setUp();
    }

    protected function setUpForInitiateDraftEvidenceTest(): void
    {
        $this->setupDisputeFixture();

        $this->fixtures->merchant->addFeatures(['dispute_presentment']);

        $this->ba->privateAuth();
    }

    protected function setupDisputeFixture(array $attributes = []): void
    {
        $paymentId = 'randomPayId123';

        $this->fixtures->create('payment:captured', [
            'id' => $paymentId,
        ]);

        $defaultAttributes = [
            'id'          => '0123456789abcd',
            'payment_id'  => $paymentId,
            'reason_code' => 'chargeback',
            'created_at'  => 1600000000,
            'expires_on'  => 1610000000,
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        $this->fixtures->create('dispute', $attributes);
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
                'document_ids' => ['doc_1cXSLlUU8V9sXl', 'doc_1cXSLlUU8V9sXm'],
            ],
            [
                'type'         => 'custom_proof_type_2',
                'document_ids' => ['doc_1cXSLlUU8V9sXm'],
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


        // contest amount cannot be 0
        $this->testData[__FUNCTION__]['request']['content']['amount'] = 0;

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

    public function testInitiateDraftEvidenceDisputeDoesntBelongToMerchant()
    {
        $merchantId = $this->fixtures->create('merchant')['id'];

        $this->setupDisputeFixture(['merchant_id' => $merchantId]);

        $this->fixtures->merchant->addFeatures(['dispute_presentment']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    protected function setUpForUpdateDraftEvidenceTest(): void
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'             => 1000,
                'summary'            => 'sample contest summary',
                'shipping_proof'     => ['doc_1cXSLlUU8V9sXl'],
                'billing_proof'      => ['doc_1cXSLlUU8V9sXm'], //these fileids are hardcoded as valid files in ufh mock
                'cancellation_proof' => ['doc_1cXSLlUU8V9sXl'],
                'others'             => [
                    [
                        'type'         => 'custom_proof_type_1',
                        'document_ids' => ['doc_1cXSLlUU8V9sXl'],
                    ],
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['doc_1cXSLlUU8V9sXm'],
                    ],
                ],
                'action'             => 'draft',
            ],
        ]);
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

    public function testUpdateDraftEvidenceLeadingToNoProofSubmittedShouldFail()
    {
        $this->setUpForUpdateDraftEvidenceTest();

        $this->startTest();
    }


    public function testGetDisputeByIDWithoutFeatureEnabled()
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->fixtures->merchant->removeFeatures(['dispute_presentment']);

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

}