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

        $transaction = $this->createFailedPayTransaction();

        $helper->withSchemaValidated();

        $response = $helper->raiseConcern($transaction->getPublicId());
    }

    public function testRaiseConcernDuplicate()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createFailedPayTransaction();

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

        $transaction = $this->createFailedPayTransaction();

        $helper->raiseConcern($transaction->getPublicId());

        $helper->withSchemaValidated();

        $response = $helper->concernStatus($transaction->getPublicId());

        $this->assertSame('success', $response['response_code']);
        $this->assertSame('Beneficiary account has already been credited.', $response['response_description']);
    }

    public function testFetchAllConcerns()
    {
        $helper = $this->getTransactionHelper();

        $ctxn = $this->createFailedPayTransaction();

        $helper->raiseConcern($ctxn->getPublicId());

        $helper->concernStatus($ctxn->getPublicId());

        $ctxn2 = $this->createFailedPayTransaction();

        $helper->raiseConcern($ctxn2->getPublicId());

        $helper->raiseConcern($ctxn->getPublicId());

        $ctxn2->deleteOrFail();
        $this->assertTrue($ctxn2->trashed());

        //TODO: Fix json schema
        //$helper->withSchemaValidated();

        $response = $helper->fetchAllConcerns([
            'expand' => ['transaction.payee', 'transaction.payer', 'transaction.upi']
        ]);

        $this->assertCount(3, $response['items']);

        $this->assertSame('pending', $response['items'][0]['response_code']);
        $this->assertSame($ctxn->upi->getRrn(), $response['items'][0]['transaction']['upi']['rrn']);
        $this->assertSame($ctxn->payer->getAddress(), $response['items'][0]['transaction']['payer']['address']);
        $this->assertSame($ctxn->payer->getAddress(), $response['items'][0]['transaction']['payer']['address']);

        $this->assertSame('pending', $response['items'][1]['response_code']);
        $this->assertSame($ctxn2->upi->getRrn(), $response['items'][1]['transaction']['upi']['rrn']);
        $this->assertSame($ctxn2->payer->getAddress(), $response['items'][1]['transaction']['payer']['address']);
        $this->assertSame($ctxn2->payer->getAddress(), $response['items'][1]['transaction']['payer']['address']);

        $this->assertSame('success', $response['items'][2]['response_code']);
        $this->assertSame($ctxn->upi->getRrn(), $response['items'][2]['transaction']['upi']['rrn']);
        $this->assertSame($ctxn->payer->getAddress(), $response['items'][2]['transaction']['payer']['address']);
        $this->assertSame($ctxn->payer->getAddress(), $response['items'][2]['transaction']['payer']['address']);
    }

    public function testRaiseConcernInvalidStatus()
    {
        $transaction = $this->createPayTransaction();

        $helper = $this->getTransactionHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'  => 'BAD_REQUEST_ERROR',
            ], $error);
        });

        $helper->raiseConcern($transaction->getPublicId());
    }

    public function testInternalRaiseConcern()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createFailedPayTransaction([], [
            'gateway_error_code'    => 'RM',
        ]);

        $response = $helper->raiseConcern($transaction->getPublicId());

        $this->assertArraySubset([
            'transaction_id'        => $transaction->getPublicId(),
            'status'                => 'closed',
            'response_code'         => 'failed',
            'response_description'  => 'Your transaction has failed due to invalid UPI PIN',
        ], $response);
    }

    public function testRaiseConcernCallback()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createFailedPayTransaction();

        $counter = 0;
        $udf = null;
        $udf2 = null;

        $this->mockActionContentFunction([
            'raise_concern' => function ($content) use (&$counter, & $udf, & $udf2)
            {
                if ($counter === 0)
                {
                    $udf = json_decode($content['udfParameters'], true);

                    $this->assertArrayHasKey('id', $udf);
                    $this->assertArrayHasKey('rid', $udf);
                    $this->assertArrayHasKey('handle', $udf);
                }
                else
                {
                    $udf2 = json_decode($content['udfParameters'], true);

                    $this->assertArrayHasKey('id', $udf2);
                    $this->assertArrayHasKey('rid', $udf2);
                    $this->assertArrayHasKey('handle', $udf2);
                }

                $counter++;
            }
        ]);

        $helper->raiseConcern($transaction->getPublicId());

        $this->assertSame('initiated', $transaction->concern->getStatus());
        $this->assertSame('pending', $transaction->concern->getResponseCode());

        $transaction2 = $this->createFailedPayTransaction();

        $helper->raiseConcern($transaction2->getPublicId());

        $this->assertSame('initiated', $transaction2->concern->getStatus());
        $this->assertSame('pending', $transaction2->concern->getResponseCode());

        $this->mockSdk()->setCallback('QUERIES', [
            'merchantChannelId'     => 'MERCHANTAPP',
            'merchantId'            => 'MERCHANT',
            'queries'               => [
                [
                    'gatewayReferenceId'         => $transaction->upi->getRrn(),
                    'gatewayResponseCode'        => '105',
                    'gatewayResponseMessage'     => 'Beneficiary account has already been credited.',
                    'gatewayTransactionId'       => $transaction->upi->getNetworkTransactionId(),
                    'merchantCustomerId'         => $transaction->customer->getId(),
                    'queryClosingTimestamp'      => '2019-11-25T00:00:00+05:30',
                    'queryComment'               => $transaction->concern->getComment(),
                    'queryReferenceId'           => $transaction->concern->getGatewayReferenceId(),
                    'udfParameters'              => json_encode($udf),
                ],
                [
                    'gatewayReferenceId'         => $transaction2->upi->getRrn(),
                    'gatewayResponseCode'        => '106',
                    'gatewayResponseMessage'     => 'Funds have been reversed to your bank account.',
                    'gatewayTransactionId'       => $transaction2->upi->getNetworkTransactionId(),
                    'merchantCustomerId'         => $transaction2->customer->getId(),
                    'queryClosingTimestamp'      => '2019-11-25T00:00:00+05:30',
                    'queryComment'               => $transaction2->concern->getComment(),
                    'queryReferenceId'           => $transaction2->concern->getGatewayReferenceId(),
                    'udfParameters'              => json_encode($udf2),
                ]
            ],
        ]);

        $helper->callback($this->gateway, $this->mockedSdk->callback());

        $transaction->concern->refresh();

        $this->assertArraySubset([
            'status'   => 'closed',
            'internal_status'   => 'closed',
            'response_code' => 'success',
            'response_description'  => 'Beneficiary account has already been credited.',
        ], $transaction->concern->toArray());

        $transaction2->concern->refresh();

        $this->assertArraySubset([
            'status'   => 'closed',
            'internal_status'   => 'closed',
            'response_code' => 'failed',
            'response_description'  => 'Funds have been reversed to your bank account.',
        ], $transaction2->concern->toArray());
    }

    public function testFailedConcernFetch()
    {
        $helper = $this->getTransactionHelper();

        $ctxn = $this->createFailedPayTransaction();

        $helper->raiseConcern($ctxn->getPublicId());

        $ctxn = $this->createFailedPayTransaction();

        $this->mockActionContentFunction([
            'raise_concern' => function(& $content)
            {
                $content['status'] = 'FAILURE';
                $content['responseCode'] = 'INVALID_DATA';
                $content['responseMessage'] = 'INVALID_DATA';
            }
        ]);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertSame($error['description'], 'Action could not be completed at bank');
        }, 502);

        $helper->raiseConcern($ctxn->getPublicId());

        $helper->expectFailureInResponse(false);

        $response = $helper->fetchAllConcerns([
            'expand' => ['transaction.payee', 'transaction.payer', 'transaction.upi']
        ]);

        $this->assertCount(1, $response['items']);
    }

    public function testRaiseConcernSoftFailure()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createFailedPayTransaction();

        $counter = 0;
        $udf = null;

        $this->mockActionContentFunction([
             'raise_concern' => function ($content) use (&$counter, & $udf)
             {
                 if ($counter === 0)
                 {
                     $udf = json_decode($content['udfParameters'], true);

                     $this->assertArrayHasKey('id', $udf);
                     $this->assertArrayHasKey('rid', $udf);
                     $this->assertArrayHasKey('handle', $udf);
                 }

                 $counter++;
             }
        ]);

        $helper->raiseConcern($transaction->getPublicId());

        // set invalid gateway id so that it throws logical exception.
        $transaction->concern->setGatewayReferenceId("QUERYGBhTmiBaQZA");

        $this->assertSame('initiated', $transaction->concern->getStatus());
        $this->assertSame('pending', $transaction->concern->getResponseCode());

        $this->mockSdk()->setCallback('QUERIES', [
            'merchantChannelId'     => 'MERCHANTAPP',
            'merchantId'            => 'MERCHANT',
            'queries'               => [
                [
                    'gatewayReferenceId'         => $transaction->upi->getRrn(),
                    'gatewayResponseCode'        => '105',
                    'gatewayResponseMessage'     => 'Beneficiary account has already been credited.',
                    'gatewayTransactionId'       => $transaction->upi->getNetworkTransactionId(),
                    'merchantCustomerId'         => $transaction->customer->getId(),
                    'queryClosingTimestamp'      => '2019-11-25T00:00:00+05:30',
                    'queryComment'               => $transaction->concern->getComment(),
                    'queryReferenceId'           => $transaction->concern->getGatewayReferenceId(),
                    'udfParameters'              => json_encode($udf),
                ]
            ],
        ]);

        // verify for gateway error code 500
        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset(
                [
                    'code'          => 'SERVER_ERROR',
                    'description'   => 'We are facing some trouble completing your request at the moment. Please try again shortly.'
                ],
            $error);
        }, 500);

        $helper->callback($this->gateway, $this->mockedSdk->callback());
    }
}
