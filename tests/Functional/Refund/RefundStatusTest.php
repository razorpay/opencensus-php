<?php

namespace RZP\Tests\Functional\Refund;

use RZP\Models\Pricing\Fee;
use RZP\Services\Scrooge;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RefundStatusTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const PROCESSING_DELAY            = 3600;
    const TIMESTAMP_FAILED_AT         = '2082738600';
    const TIMESTAMP_PROCESSED_AT      = '2082738599';
    const TIMESTAMP_SPEED_CHANGE_TIME = '2082738598';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundStatusTestData.php';

        parent::setUp();
    }

    protected function capturePaymentForMerchant($id, $amount, $merchantId, $currency = 'INR')
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount));

        if ($currency !== 'INR')
        {
            $request['content']['currency'] = $currency;
        }

        $this->ba->privateAuth('rzp_test_' . $merchantId);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['status'], 'captured');

        return $content;
    }

    public function testInstantRefunds()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->gateway = 'hdfc';

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->ba->privateAuth();

        $this->assertCreatedInstantRefundResponses(__FUNCTION__, $refund);

        $this->assertInitiatedInstantRefundResponses(__FUNCTION__, $refund);

        $this->assertProcessedInstantRefundResponses(__FUNCTION__, $refund);
    }

    public function testFlipkartRefunds()
    {
        $merchantId = 'BbaYzzPW541Aut';

        $this->fixtures->merchant->createAccount($merchantId);
        $this->fixtures->merchant->addFeatures('show_refund_public_status', $merchantId);

        $this->fixtures->on('live')->merchant->edit($merchantId, ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->edit($merchantId, ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $payment = $this->defaultAuthPayment();

        $this->fixtures->payment->edit(substr($payment['id'], 4), ['merchant_id' => $merchantId]);

        $payment = $this->capturePaymentForMerchant($payment['id'], $payment['amount'], $merchantId);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment(
            $payment['id'],
            $payment['amount'],
            [],
            [],
            false,
            [
                'key'    => 'rzp_test_'.$merchantId,
                'secret' => 'TheKeySecretForTests'
            ]
        );

        $refundEntity = $this->getDbEntityById('refund', substr($refund['id'], 5));

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertEquals($merchantId, $refundEntity['merchant_id']);

        $this->ba->privateAuth('rzp_test_' . $merchantId);

        $this->assertFlipkartRefundResponses(__FUNCTION__, $refund);
    }

    public function testFlipkartRefundsWithFeatureShowRefundPublicStatus()
    {
        $this->fixtures->merchant->addFeatures('show_refund_public_status');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->ba->privateAuth();

        $this->assertFlipkartRefundResponses(__FUNCTION__, $refund);
    }

    public function testSnapdealRefunds()
    {
        $merchantId = 'ByWbZS28NK9CeG';

        $this->fixtures->merchant->createAccount($merchantId);
        $this->fixtures->merchant->addFeatures('refund_pending_status', $merchantId);

        $this->fixtures->on('live')->merchant->edit($merchantId, ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->edit($merchantId, ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $payment = $this->defaultAuthPayment();

        $this->fixtures->payment->edit(substr($payment['id'], 4), ['merchant_id' => $merchantId]);

        $payment = $this->capturePaymentForMerchant($payment['id'], $payment['amount'], $merchantId);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment(
            $payment['id'],
            $payment['amount'],
            [],
            [],
            false,
            [
                'key'    => 'rzp_test_'.$merchantId,
                'secret' => 'TheKeySecretForTests'
            ]
        );

        $refundEntity = $this->getDbEntityById('refund', substr($refund['id'], 5));

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertEquals($merchantId, $refundEntity['merchant_id']);

        $this->ba->privateAuth('rzp_test_' . $merchantId);

        $this->assertSnapdealRefundResponses(__FUNCTION__, $refund);
    }

    public function testSnapdealRefundsWithRefundPendingStatusFeature()
    {
        $this->fixtures->merchant->addFeatures('refund_pending_status');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);

        $this->ba->privateAuth();

        $this->assertSnapdealRefundResponses(__FUNCTION__, $refund);
    }

    protected function updateRefundStatus($refund, $status)
    {
        $this->fixtures->refund->edit(substr($refund['id'], 3), [Refund\Entity::STATUS => $status]);
    }

    protected function updateRefundSpeedRequested($refund, $speedRequested)
    {
        $this->fixtures->refund->edit(substr($refund['id'], 3), [Refund\Entity::SPEED_REQUESTED => $speedRequested]);
    }

    protected function updateRefundSpeedProcessed($refund, $speedProcessed)
    {
        $this->fixtures->refund->edit(substr($refund['id'], 3), [Refund\Entity::SPEED_PROCESSED => $speedProcessed]);
    }

    protected function updateRefundSpeedDecisioned($refund, $speedDecisioned)
    {
        $this->fixtures->refund->edit(substr($refund['id'], 3), [Refund\Entity::SPEED_DECISIONED => $speedDecisioned]);
    }

    protected function assertRefundResponse($callee, $refund, $status, $speedRequested, $speedProcessed)
    {
        $expectedFieldsToBeAbsent = [];

        if (empty($status) === false)
        {
            $this->testData[$callee]['response']['content']['status'] = $status;
        }
        else
        {
            $expectedFieldsToBeAbsent[] = 'status';
        }

        if (empty($speedRequested) === false)
        {
            $this->testData[$callee]['response']['content']['speed_requested'] = $speedRequested;
        }
        else
        {
            $expectedFieldsToBeAbsent[] = 'speed_requested';
        }

        if (empty($speedProcessed) === false)
        {
            $this->testData[$callee]['response']['content']['speed_processed'] = $speedProcessed;
        }
        else
        {
            $expectedFieldsToBeAbsent[] = 'speed_processed';
        }

        $this->testData[$callee]['request']['url'] = '/refunds/' . $refund['id'];

        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        foreach ($expectedFieldsToBeAbsent as $fieldToBeAbsent)
        {
            $this->assertTrue(empty($response[$fieldToBeAbsent]));
        }
    }

    protected function assertCreatedInstantRefundResponses($callee, $refund)
    {
        // internal_status		speed_requested		speed_processed		speed_decisioned	speed_requested		speed_processed	status
        // created				optimum				normal				optimum				optimum				normal			processed
        // created				normal				normal				normal				normal				normal			processed
        // created				optimum				normal				normal				optimum				normal			processed
        // created				optimum				NA					optimum				optimum				instant			pending

        $data = [
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => null,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::INSTANT,
                'status'              => Refund\Status::PENDING,
                'scrooge_speed'       => Refund\Speed::INSTANT
            ]
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }

    protected function assertInitiatedInstantRefundResponses($callee, $refund)
    {
        // internal_status		speed_requested		speed_processed		speed_decisioned	speed_requested		speed_processed	status
        // initiated			optimum				normal				optimum				optimum				normal			processed
        // initiated			normal				normal				normal				normal				normal			processed
        // initiated			optimum				normal				normal				optimum				normal			processed
        // initiated			optimum				NA					optimum				optimum				instant			pending

        $data = [
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => null,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::INSTANT,
                'status'              => Refund\Status::PENDING,
                'scrooge_speed'       => Refund\Speed::INSTANT
            ]
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }

    protected function assertProcessedInstantRefundResponses($callee, $refund)
    {
        // internal_status		speed_requested		speed_processed		speed_decisioned	speed_requested		speed_processed	status
        // processed			optimum				normal				optimum				optimum				normal			processed
        // processed			normal				normal				normal				normal				normal			processed
        // processed			optimum				normal				normal				optimum				normal			processed
        // processed			optimum				instant				optimum				optimum				instant			processed

        $data = [
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
            ],
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::OPTIMUM,
                'db_speed_processed'  => Refund\Speed::INSTANT,
                'db_speed_decisioned' => Refund\Speed::OPTIMUM,
                'speed_requested'     => Refund\Speed::OPTIMUM,
                'speed_processed'     => Refund\Speed::INSTANT,
                'status'              => Refund\Status::PROCESSED,
            ]
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }

    protected function iterateOverDataAndAssertRefundResponse($callee, $refund, $data)
    {
        foreach ($data as $datum)
        {
            $this->updateRefundStatus($refund, $datum['db_status']);
            $this->updateRefundSpeedRequested($refund, $datum['db_speed_requested']);
            $this->updateRefundSpeedProcessed($refund, $datum['db_speed_processed']);
            $this->updateRefundSpeedDecisioned($refund, $datum['db_speed_decisioned']);

            $scroogeMock = $this->getMockBuilder(Scrooge::class)
                                ->setConstructorArgs([$this->app])
                                ->setMethods(['getPublicRefund'])
                                ->getMock();

            $this->app->instance('scrooge', $scroogeMock);

            if ((isset($datum['scrooge_status']) === false) and
                (isset($datum['scrooge_speed'])) === false)
            {
                //
                // Not calling scrooge - regular merchants / snapdeal
                //
                $this->app->scrooge->expects($this->never())
                                   ->method('getPublicRefund');
            }
            else if (isset($datum['scrooge_status']) === false)
            {
                //
                // Calling scrooge for speed - instant refunds merchants
                //
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'speed'  => $datum['scrooge_speed'],
                    ]
                ];

                $this->app->scrooge->expects($this->atLeastOnce())
                                   ->method('getPublicRefund')
                                   ->with($refund['id'], ['speed' => 1, 'status' => 0])
                                   ->willReturn($scroogeResponse);
            }
            else if (isset($datum['scrooge_speed']) === false)
            {
                //
                // Calling scrooge for status - Flipkart merchant
                //
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'status' => $datum['scrooge_status'],
                    ]
                ];

                $this->app->scrooge->expects($this->atLeastOnce())
                                   ->method('getPublicRefund')
                                   ->with($refund['id'], ['speed' => 0, 'status' => 1])
                                   ->willReturn($scroogeResponse);
            }
            else
            {
                //
                // Calling scrooge for speed and status - should be very rare - like in case of instant refunds on
                // Flipkart merchants
                //
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'status' => $datum['scrooge_status'],
                        'speed'  => $datum['scrooge_speed'],
                    ]
                ];

                $this->app->scrooge->expects($this->atLeastOnce())
                                   ->method('getPublicRefund')
                                   ->with($refund['id'], ['speed' => 1, 'status' => 1])
                                   ->willReturn($scroogeResponse);
            }

            $this->assertRefundResponse($callee, $refund, $datum['status'], $datum['speed_requested'], $datum['speed_processed']);
        }
    }

    protected function assertFlipkartRefundResponses($callee, $refund)
    {
        // status	    scrooge_status	public_status
        // created		pending		    pending
        // created		processed	    processed
        // created		failed		    failed
        // initiated	pending		    pending
        // initiated	processed	    processed
        // initiated 	failed		    failed
        // processed	NA			    processed
        // failed		pending		    pending
        // failed		processed	    processed
        // failed		failed		    failed
        // reversed	    NA			    failed

        $data = [
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::REVERSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL,
            ],
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }

    protected function assertSnapdealRefundResponses($callee, $refund)
    {
        // status	    public_status
        // created		pending
        // initiated	pending
        // processed	processed
        // failed		pending

        $data = [
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL
            ],
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => Refund\Speed::NORMAL,
                'speed_processed'     => Refund\Speed::NORMAL
            ],
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }

    public function testDisableInstantRefundsFeature()
    {
        $this->fixtures->merchant->addFeatures('disable_instant_refunds');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $data= [
            'speed'=>'optimum'
        ];

        try
        {
            $this->refundPayment($payment['id'],$payment['amount'],$data);
        }
        catch ( \Exception $ex)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_INSTANT_REFUND_NOT_SUPPORTED,$ex->getCode());
            return ;
        }

        $this->fail();
    }

    // These timestamps come in proxy route for refund get api
    public function testTimestampsOnGetRefundApi()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->gateway = 'hdfc';

        // Adding IMPS pricing as well to assert that the extra pricing rule is not affecting those refunds
        // without a mode decisioned
        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->mockScroogeToTestTimestampsOnGetRefundApi($refund);

        // public refund id
        $this->testData[__FUNCTION__]['request']['url'] = '/refunds/' . $refund['id'];

        $refund = $this->getDbLastEntity('refund');

        $this->assertDefaultMerchantTypeCases(__FUNCTION__, $refund);

        // Snapdeal like merchants
        $this->assertApiPublicStatusMerchantTypeCases(__FUNCTION__, $refund);

        // Flipkart like merchants
        $this->assertScroogePublicStatusMerchantTypeCases(__FUNCTION__, $refund);
    }

    protected function assertDefaultMerchantTypeCases($caller, $refund)
    {
        $merchantId = "100000000000X1";

        $this->fixtures->merchant->createAccount($merchantId, false);

        $this->fixtures->refund->edit($refund['id'], ['merchant_id' => $merchantId]);
        $this->fixtures->payment->edit($refund['payment_id'], ['merchant_id' => $merchantId]);

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user['id']);

        $data = [
            'instant_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => null,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::INSTANT,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => self::TIMESTAMP_PROCESSED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => self::TIMESTAMP_SPEED_CHANGE_TIME,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => self::TIMESTAMP_SPEED_CHANGE_TIME,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT],
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT],
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => $refund[Refund\Entity::CREATED_AT],
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => $refund[Refund\Entity::CREATED_AT],
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
        ];

        $this->helperExecuteTestTimestampsOnGetRefundApi($refund, $data, $caller);
    }

    protected function assertApiPublicStatusMerchantTypeCases($caller, $refund)
    {
        $merchantId = 'CBcPtPwFgpjdUp';

        $this->fixtures->merchant->createAccount($merchantId, false);
        $this->fixtures->merchant->addFeatures('refund_pending_status', $merchantId);

        $this->fixtures->refund->edit($refund['id'], ['merchant_id' => $merchantId]);
        $this->fixtures->payment->edit($refund['payment_id'], ['merchant_id' => $merchantId]);

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user['id']);

        $data = [
            'instant_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => null,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::INSTANT,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => self::TIMESTAMP_PROCESSED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => self::TIMESTAMP_PROCESSED_AT,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT         => null,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => self::TIMESTAMP_PROCESSED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT         => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => self::TIMESTAMP_PROCESSED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
        ];

        $this->helperExecuteTestTimestampsOnGetRefundApi($refund, $data, $caller);
    }

    protected function assertScroogePublicStatusMerchantTypeCases($caller, $refund)
    {
        $merchantId = '9hefgkvGhT18Q9';

        $this->fixtures->merchant->createAccount($merchantId, false);
        $this->fixtures->merchant->addFeatures('show_refund_public_status', $merchantId);

        $this->fixtures->refund->edit($refund['id'], ['merchant_id' => $merchantId]);
        $this->fixtures->payment->edit($refund['payment_id'], ['merchant_id' => $merchantId]);

        $reversalAttributes = [
            'entity_id'   => $refund->getId(),
            'entity_type' => 'refund',
            'amount'      => $refund->getAmount(),
            'merchant_id' => $merchantId,
            'fee'         => 0,
            'tax'         => 0,
            'created_at'  => self::TIMESTAMP_FAILED_AT,
            'updated_at'  => self::TIMESTAMP_FAILED_AT,
        ];

        $this->fixtures->reversal->createReversalWithoutTransaction($reversalAttributes);

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user['id']);

        $data = [
            'instant_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => null,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_processed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::INSTANT,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => self::TIMESTAMP_PROCESSED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processed_before_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processed_after_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT] + Refund\Constants::SCROOGE_PUBLIC_STATUS_TO_PROCESSED_TIME,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'instant_failed_normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT         => null,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => [
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processed_before_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processed_after_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT         => $refund[Refund\Entity::CREATED_AT] + Refund\Constants::SCROOGE_PUBLIC_STATUS_TO_PROCESSED_TIME,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'optimum_requested_normal_decisioned_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT         => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processing' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::CREATED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSING,
                    Refund\Entity::PROCESSED_AT => null,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processed_before_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => $refund[Refund\Entity::CREATED_AT] + self::PROCESSING_DELAY,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_processed_after_allowed_processing_delay' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::PROCESSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => self::TIMESTAMP_PROCESSED_AT,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::PROCESSED,
                    Refund\Entity::PROCESSED_AT => $refund[Refund\Entity::CREATED_AT] + Refund\Constants::SCROOGE_PUBLIC_STATUS_TO_PROCESSED_TIME,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                    Refund\Constants::FAILED_AT,
                ]
            ],
            'normal_failed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::REVERSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS       => Refund\Status::FAILED,
                    Refund\Entity::PROCESSED_AT => null,
                    Refund\Constants::FAILED_AT => self::TIMESTAMP_FAILED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                ]
            ],
            'optimum_requested_normal_decisioned_failed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::REVERSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::NORMAL,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::FAILED,
                    Refund\Entity::PROCESSED_AT         => null,
                    Refund\Constants::FAILED_AT         => self::TIMESTAMP_FAILED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                ]
            ],
            'instant_failed_normal_failed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::REVERSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => Refund\Speed::NORMAL,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::FAILED,
                    Refund\Entity::PROCESSED_AT         => null,
                    Refund\Constants::FAILED_AT         => self::TIMESTAMP_FAILED_AT,
                    Refund\Constants::SPEED_CHANGE_TIME => self::TIMESTAMP_SPEED_CHANGE_TIME,
                ],
                'unsets' => []
            ],
            'instant_failed' => [
                'db_updates' => [
                    Refund\Entity::STATUS           => Refund\Status::REVERSED,
                    Refund\Entity::SPEED_REQUESTED  => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_DECISIONED => Refund\Speed::OPTIMUM,
                    Refund\Entity::SPEED_PROCESSED  => null,
                    Refund\Entity::PROCESSED_AT     => null,
                ],
                'asserts' => [
                    Refund\Entity::STATUS               => Refund\Status::FAILED,
                    Refund\Entity::PROCESSED_AT         => null,
                    Refund\Constants::FAILED_AT         => self::TIMESTAMP_FAILED_AT,
                ],
                'unsets' => [
                    Refund\Constants::SPEED_CHANGE_TIME,
                ]
            ],
        ];

        $this->helperExecuteTestTimestampsOnGetRefundApi($refund, $data, $caller);
    }

    protected function helperExecuteTestTimestampsOnGetRefundApi($refund, $data, $caller)
    {
        foreach ($data as $z=>$subTest) {
            $this->assertArrayHasKey('db_updates', $subTest);
            $this->assertArrayHasKey('unsets', $subTest);
            $this->assertArrayHasKey('asserts', $subTest);

            foreach ($subTest['db_updates'] as $field=>$value)
            {
                $this->fixtures->refund->edit($refund['id'], [$field => $value]);
            }

            $response = $this->runRequestResponseFlow($this->testData[$caller]);

            foreach ($subTest['asserts'] as $key=>$value)
            {
                $this->assertEquals($value, $response[$key]);
            }

            foreach ($subTest['unsets'] as $unsetKeys)
            {
                $this->assertFalse(isset($response[$unsetKeys]));
            }
        }
    }

    protected function mockScroogeToTestTimestampsOnGetRefundApi($refund)
    {
        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getPublicRefund'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $scroogeResponse = [
            'code' => 200,
            'body' => [
                'speed_change_time'  => self::TIMESTAMP_SPEED_CHANGE_TIME,
            ]
        ];

        $this->app->scrooge->expects($this->any())
                           ->method('getPublicRefund')
                           ->with($refund['id'], ['speed_change_time' => 1])
                           ->willReturn($scroogeResponse);
    }
}
