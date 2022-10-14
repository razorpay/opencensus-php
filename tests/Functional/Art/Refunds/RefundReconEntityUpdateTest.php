<?php

namespace RZP\Tests\Functional\Art\Refunds;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Jobs\ArtReconProcess;
use RZP\Services\Mock\Scrooge;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class RefundReconEntityUpdateTest extends TestCase
{
    use DbEntityFetchTrait;
    use PaymentTrait;
    use ReconTrait;

    protected $payment;

    protected $sharedTerminal;

    protected $jobData;

    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);

        $this->app['rzp.mode'] = 'test';
    }

    protected function testRefundEntityUpdate(string $gateway = null)
    {
        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefundRecon'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $refundEntity = $this->getDbLastEntity('refund');

        $this->app['scrooge']->method('initiateRefundRecon')
            ->will($this->returnCallback(
                function ($input) use ($refundEntity) {
                    $refunds[] = [
                        'arn'                => '786355626788',
                        'gateway_keys'       => [],
                        'reconciled_at'      => 1549108187,
                        'refund_id'          => $refundEntity['id'],
                        'status'             => 'processed',
                        'gateway_settled_at' => 1549108189,
                    ];

                    $response = [
                        'body' => [
                            'response'              => [
                                'batch_id'                => $input['batch_id'],
                                'chunk_number'            => $input['chunk_number'],
                                'refunds'                 => $refunds,
                                'should_force_update_arn' => false,
                                'source'                  => 'art',
                            ]
                        ]
                    ];

                    return $response;
                }
            ));

        $this->job->handle();

        $transactionId = $refundEntity['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

         $this->assertNotEmpty($transaction['reconciled_at']);

         $this->assertEquals('1549108187', $transaction['reconciled_at']);

         $this->assertEquals('mis', $transaction['reconciled_type']);

         $updatedRefundEntity = $this->getLastEntity('refund',true);

         $this->assertEquals('processed',$updatedRefundEntity['status']);
    }

    /**
     * Testcase for refund entity update through ART
     */
    public function testUpiIciciRefundEntityUpdate()
    {
        $reconJobData = $this->buildRefundReconData();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $upiEntityPayment = $this->getNewUpiEntity('10000000000000', 'upi_icici');

        $paymentId = $upiEntityPayment['payment_id'];

        $this->createUpiRefund($paymentId, 50000, 'upi_icici');

        $refundEntity = $this->getLastEntity('refund',true);

        $reconJobData['upi'][] = [
            'refund_id'         => substr($refundEntity['id'],5),
            'npci_reference_id' => '23456674322',
            'npci_txn_id'       => '23413455674',
        ];

        $reconJobData['gateway'] = 'upi_icici';

        $this->job = $this->mockArtReconProcess($reconJobData);

        $this->testRefundEntityUpdate('upi_icici');

        $upiEntity = $this->getDbLastEntity('upi');

        $this->assertNotEquals($upiEntity['npci_reference_id'],'23456674322') ;

        $this->assertNotEquals($upiEntity['npci_txn_id'],'23413455674') ;

        $reconJobData['upi'][0]['npci_reference_id'] = '';
        $reconJobData['upi'][0]['npci_txn_id'] = '';

        $this->job = $this->mockArtReconProcess($reconJobData);

        $this->testRefundEntityUpdate('upi_icici');

        $upiEntity = $this->getDbLastEntity('upi');

        $this->assertNotEquals($upiEntity['npci_reference_id'],'23456674322') ;

        $this->assertNotEquals($upiEntity['npci_txn_id'],'23413455674') ;
    }

    public function testUpiIciciInvalidRefundEntityUpdate()
    {
        $reconJobData = $this->buildRefundReconData();

        unset($reconJobData['refunds']);

        $reconJobData['gateway'] = 'upi_icici';

        $this->job = $this->mockArtReconProcess($reconJobData);

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['initiateRefundRecon'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $refundEntity = $this->getDbLastEntity('refund');

        $this->app['scrooge']->method('initiateRefundRecon')
            ->will($this->returnCallback(
                function ($input) use ($refundEntity) {
                    $refunds[] = [
                        'arn'                => '786355626788',
                        'gateway_keys'       => [
                            'arn'           => '12345678910',
                            'recon_batch_id'=> $input['batch_id']
                        ],
                        'reconciled_at'      => 1549108187,
                        'refund_id'          => $refundEntity['id'],
                        'status'             => 'processed',
                        'gateway_settled_at' => 1549108189,
                    ];

                    $response = [
                        'body' => [
                            'response'              => [
                                'batch_id'                => $input['batch_id'],
                                'chunk_number'            => $input['chunk_number'],
                                'refunds'                 => $refunds,
                                'should_force_update_arn' => false,
                                'source'                  => 'art',
                            ]
                        ]
                    ];

                    return $response;
                }
            ));

        $this->job->handle();

        $this->assertEquals($this->job->attempts(),1);

    }

    public function testUpiSbiRefundEntityUpdate()
    {
        $reconJobData = $this->buildRefundReconData();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $upiEntityPayment = $this->getNewUpiEntity('10000000000000', 'upi_sbi');

        $paymentId = $upiEntityPayment['payment_id'];

        $this->refundAuthorizedPayment($paymentId);

        $reconJobData['gateway'] = 'upi_sbi';

        $this->job = $this->mockArtReconProcess($reconJobData);

        $this->testRefundEntityUpdate('upi_sbi');
    }

    public function testNetbankingSbiRefundEntityUpdate()
    {
        $reconJobData = $this->buildRefundReconData();

        $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $paymentId = $this->doAuthPayment($payment);

        $paymentId = substr($paymentId['razorpay_payment_id'], 4);

        $this->refundAuthorizedPayment($paymentId);

        $refund = $this->getDbLastEntityToArray('refund');

        $reconJobData['gateway'] = 'netbanking_sbi';

        $reconJobData['refunds'][0]['payment_id'] = $paymentId;

        $reconJobData['refunds'][0]['gateway_keys'] = ["gateway_status" => "success", "sequence_no" => 1];

        $this->job = $this->mockArtReconProcess($reconJobData);

        $this->testRefundEntityUpdate('netbanking_sbi');
    }

    public function testNetbankingSbiFailedRefundEntityUpdate()
    {
        $reconJobData = $this->buildRefundReconData();

        $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $paymentId = $this->doAuthPayment($payment);

        $paymentId = substr($paymentId['razorpay_payment_id'], 4);

        $this->refundAuthorizedPayment($paymentId);

        $refund = $this->getDbLastEntityToArray('refund');

        $reconJobData['gateway'] = 'netbanking_sbi';

        $reconJobData['refunds'][0]['payment_id'] = $paymentId;

        $reconJobData['refunds'][0]['gateway_keys'] = ["gateway_status" => "failed", "sequence_no" => 1];

        $this->job = $this->mockArtReconProcess($reconJobData);

        $this->testRefundEntityUpdate('netbanking_sbi');
    }

    private function createUpiRefund(string $paymentId, string $amount, string $gateway)
    {
        $this->refundAuthorizedPayment($paymentId);

        $refundEntity = $this->getDbLastEntity('refund');

        $this->fixtures->create(
            'upi',
            [
                'payment_id'        => $paymentId,
                'refund_id'         => PublicEntity::stripDefaultSign($refundEntity['id']),
                'action'            => Payment\Action::REFUND,
                'npci_reference_id' => '23413455675',
                'npci_txn_id'       => '23413455672',
            ]);
    }

    private function mockArtReconProcess(array $data)
    {
        $job = \Mockery::mock(ArtReconProcess::class, [$data])->makePartial();

        $job->shouldAllowMockingProtectedMethods();

        return $job;
    }

    private function buildRefundReconData()
    {
        return [
            'mode'                     => 'test',
            'should_force_update_arn'  => false,
            'source'                   => 'art',
            'art_request_id'           => '112234134566',
            'refunds'                  => [
                [
                    'payment_id'        => 'JvGbfizowfftSf',
                    'refund_id'         => 'JvGbfizowfftSE',
                    'status'            => 'processed',
                    'gateway_keys'      => [],
                    'arn'               => '786355621232',
                    'gateway_settled_at'=> null,
                    'reconciled_at'     => null,
                ]
            ],
        ];
    }

    protected function getNewUpiEntity(string $merchantId, string $gateway, $mockServer = null)
    {
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $payment = $this->getDefaultUpiPaymentArray($gateway);

        $payment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->gateway = $gateway;

        $content = ($mockServer ? $mockServer : $this->mockServer())->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $gatewayPayment = $this->getDbLastEntityToArray('upi');

        return $gatewayPayment;
    }
}
