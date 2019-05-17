<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class TransactionConcernTest extends TestCase
{
    use TransactionTrait;

    public function testRaiseConcern()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $helper->withSchemaValidated();

        $response = $helper->raiseConcern($transaction->getPublicId());
    }

    public function testRaiseConcernDuplicate()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $helper->raiseConcern($transaction->getPublicId());

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'The request is duplicate',
            ], $error);
        });

        $helper->raiseConcern($transaction->getPublicId());

        $transaction->concern->setStatus('closed')->saveOrFail();

        $helper->withSchemaValidated()->expectFailureInResponse(false);

        $helper->raiseConcern($transaction->getPublicId());
    }

    public function testConcernStatus()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $helper->raiseConcern($transaction->getPublicId());

        $helper->withSchemaValidated();

        $response = $helper->concernStatus($transaction->getPublicId());

        $this->assertSame('success', $response['response_code']);
        $this->assertSame('Beneficiary account has already been credited.', $response['response_description']);
    }

    public function testFetchAllConcerns()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $helper->raiseConcern($transaction->getPublicId());

        $helper->concernStatus($transaction->getPublicId());

        $helper->raiseConcern($transaction->getPublicId());

        //TODO: Fix json schema
        //$helper->withSchemaValidated();

        $response = $helper->fetchAllConcerns();

        $this->assertCount(2, $response['items']);
        $this->assertSame('pending', $response['items'][0]['response_code']);
        $this->assertSame('success', $response['items'][1]['response_code']);
    }
}
