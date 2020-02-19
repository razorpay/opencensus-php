<?php

namespace RZP\Tests\Functional\Refund;

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

    /**
     * @var Terminal
     */

    protected $sharedTerminal;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundStatusTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->ba->privateAuth();
    }

    // flipkart - from scrooge
    // snapdeal - real status
    // card_transfer_refund - speed_requested, speed_processed

    // card
    // upi
    // wallet

    public function createUpiPayment()
    {
        $this->gateway = Gateway::UPI_MINDGATE;

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);

        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertNotNull($upiEntity['npci_reference_id']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);
        $this->assertNotNull($payment['acquirer_data']['upi_transaction_id']);

        $this->assertEquals($payment['reference16'], $upiEntity['npci_reference_id']);
        $this->assertNotNull($upiEntity['gateway_payment_id']);
        $this->assertEquals($payment['reference1'],$upiEntity['gateway_payment_id']);
        $this->assertSame('00', $upiEntity['status_code']);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment['amount']);

        return $payment;
    }

    public function testInstantRefundSuccessful()
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

        $this->assertCreatedInstantRefundResponses(__FUNCTION__, $refund);

        $this->assertInitiatedInstantRefundResponses(__FUNCTION__, $refund);

        $this->assertProcessedInstantRefundResponses(__FUNCTION__, $refund);
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

        if (empty($status) === false)
        {
            $this->testData[$callee]['response']['content']['speed_requested'] = $speedRequested;
        }
        else
        {
            $expectedFieldsToBeAbsent[] = 'speed_requested';
        }

        if (empty($status) === false)
        {
            $this->testData[$callee]['response']['content']['speed_processed'] = $speedProcessed;
        }
        else
        {
            $expectedFieldsToBeAbsent[] = 'speed_processed';
        }

        $this->testData[$callee]['request']['url'] = '/refunds/' . $refund['id'];

        $this->ba->privateAuth();

        $response = $this->runRequestResponseFlow($this->testData[$callee]);

        foreach ($expectedFieldsToBeAbsent as $fieldToBeAbsent)
        {
            $this->assertTrue(empty($response[$fieldToBeAbsent]));
        }
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
                $this->app->scrooge->expects($this->never())
                                   ->method('getPublicRefund');
            }
            else if (isset($datum['scrooge_status']) === false)
            {
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'speed'  => $datum['scrooge_speed'],
                    ]
                ];

                $this->app->scrooge->method('getPublicRefund')
                                   ->with($refund['id'], ['speed' => 1, 'status' => 0])
                                   ->willReturn($scroogeResponse);
            }
            else if (isset($datum['scrooge_speed']) === false)
            {
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'status' => $datum['scrooge_status'],
                    ]
                ];

                $this->app->scrooge->method('getPublicRefund')
                                   ->willReturn($scroogeResponse);
            }
            else
            {
                $scroogeResponse = [
                    'code' => 200,
                    'body' => [
                        'status' => $datum['scrooge_status'],
                        'speed'  => $datum['scrooge_speed'],
                    ]
                ];

                $this->app->scrooge->method('getPublicRefund')
                                   ->willReturn($scroogeResponse);
            }

            $this->assertRefundResponse($callee, $refund, $datum['status'], $datum['speed_requested'], $datum['speed_processed']);
        }
    }
}
