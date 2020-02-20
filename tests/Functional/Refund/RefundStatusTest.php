<?php

namespace RZP\Tests\Functional\Refund;

use RZP\Models\Pricing\Fee;
use RZP\Services\Scrooge;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Refund;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RefundStatusTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
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

        $this->fixtures->merchant->addFeatures('card_transfer_refund');

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

    public function testSnapdealRefunds()
    {
        $merchantId = 'ByWbZS28NK9CeG';

        $this->fixtures->merchant->createAccount($merchantId);

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
                'speed_requested'     => '',
                'speed_processed'     => ''
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::CREATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PENDING
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::PROCESSED
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => '',
                'speed_processed'     => '',
                'scrooge_status'      => Refund\Status::FAILED
            ],
            [
                'db_status'           => Refund\Status::REVERSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::FAILED,
                'speed_requested'     => '',
                'speed_processed'     => '',
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
                'speed_requested'     => '',
                'speed_processed'     => ''
            ],
            [
                'db_status'           => Refund\Status::INITIATED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => '',
                'speed_processed'     => ''
            ],
            [
                'db_status'           => Refund\Status::FAILED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PENDING,
                'speed_requested'     => '',
                'speed_processed'     => ''
            ],
            [
                'db_status'           => Refund\Status::PROCESSED,
                'db_speed_requested'  => Refund\Speed::NORMAL,
                'db_speed_processed'  => Refund\Speed::NORMAL,
                'db_speed_decisioned' => Refund\Speed::NORMAL,
                'status'              => Refund\Status::PROCESSED,
                'speed_requested'     => '',
                'speed_processed'     => '',
            ],
        ];

        $this->iterateOverDataAndAssertRefundResponse($callee, $refund, $data);
    }
}
