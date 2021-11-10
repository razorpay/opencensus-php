<?php

namespace Functional\Dispute;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

trait DisputeTrait
{
    use WorkflowTrait;
    use PaymentTrait;

    protected function setUpForUpdateDraftEvidenceTest(): void
    {
        $this->setUpForInitiateDraftEvidenceTest();

        $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_0123456789abcd/contest',
            'method'  => 'PATCH',
            'content' => [
                'amount'             => 1000,
                'summary'            => 'sample contest summary',
                'shipping_proof'     => ['doc_shippingProfId'],
                'billing_proof'      => ['doc_billingProfId1'], //these fileids are hardcoded as valid files in ufh mock
                'cancellation_proof' => ['doc_cancelProofId1'],
                'others'             => [
                    [
                        'type'         => 'custom_proof_type_1',
                        'document_ids' => ['file_customType1Id1'],
                    ],
                    [
                        'type'         => 'custom_proof_type_2',
                        'document_ids' => ['file_customType2Id1'],
                    ],
                ],
                'action'             => 'draft',
            ],
        ]);
    }

    protected function setUpFixtures(array $disputeAttributes = [], string $paymentResource = 'payment:captured',
                                     array $paymentAttributes = []): void
    {
        $this->setUpPaymentFixtures($paymentResource, $paymentAttributes);

        $this->setUpDisputeFixtures($disputeAttributes);
    }

    protected function setUpPaymentFixtures($paymentResource, $paymentAttributes = [])
    {
        $defaultPaymentAttributes = [
            'id' => 'randomPayId123',
        ];

        $paymentAttributes = array_merge($defaultPaymentAttributes, $paymentAttributes);

        $this->fixtures->create($paymentResource, $paymentAttributes);
    }

    protected function setUpDisputeFixtures($disputeAttributes = [])
    {
        $defaultDisputeAttributes = [
            'id'               => '0123456789abcd',
            'payment_id'       => 'randomPayId123',
            'reason_code'      => 'chargeback',
            'created_at'       => 1600000000,
            'expires_on'       => time() + 10000,
            'base_amount'      => 1000000,
            'base_currency'    => 'INR',
            'amount'           => 1000000,
            'currency'         => 'INR',
            'gateway_amount'   => 1000000,
            'gateway_currency' => 'INR',
        ];

        $disputeAttributes = array_merge($defaultDisputeAttributes, $disputeAttributes);

        $this->fixtures->create('dispute', $disputeAttributes);
    }

    protected function setUpForInitiateDraftEvidenceTest(array $disputeAttributes = [],
                                                         string $paymentResource = 'payment:captured',
                                                         array $paymentAttributes = []): void
    {
        $this->setUpFixtures($disputeAttributes, $paymentResource, $paymentAttributes);

        $this->fixtures->merchant->addFeatures(['dispute_presentment']);

        $this->ba->privateAuth();
    }

    protected function contestDispute($disputeId = 'disp_0123456789abcd', $input = [])
    {
        $this->ba->privateAuth();

        $defaultInput = [
            'action'         => 'submit',
            'amount'         => 1000,
            'summary'        => 'sample contest summary',
            'shipping_proof' => ['doc_shippingProfId'],
        ];

        $finalInput = array_merge($defaultInput, $input);

        return $this->makeRequestAndGetContent([
            'url'     => "/disputes/{$disputeId}/contest",
            'method'  => 'PATCH',
            'content' => $finalInput,
        ]);
    }

    /**
     * @param ...$params
     * in the format arg1=entityName arg2=entityId
     * A helper function to avoid repeated code to fetch different types of entitites before/after test scenario
     */
    protected function getEntitiesByTypeAndIdMultiple(...$params)
    {
        $result = [];

        for ($i = 0; $i < count($params); $i += 2)
        {
            $entityType = $params[$i];

            $entityId = $params[$i + 1];

            if ($entityId === null)
            {
                $entity = $this->getLastEntity($entityType, true);
            }
            else
            {
                $entity = $this->getEntityById($entityType, $entityId, true);
            }
            array_push($result, $entity);
        }
        return $result;
    }

    protected function performAdminActionOnDispute(array $input)
    {
        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        return $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_0123456789abcd',
            'method'  => 'POST',
            'content' => $input,
        ]);
    }

}