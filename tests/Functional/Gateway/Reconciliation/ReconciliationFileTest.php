<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation;

use App;
use Queue;
use Mockery;
use RZP\Jobs;
use Carbon\Carbon;
use RZP\Models\Batch;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Gateway;
use RZP\Services\Mock\Scrooge;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Refund;
use RZP\Jobs\CardsPaymentRecon;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\PublicEntity;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\Base\Constants;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Services\Mock\CardPaymentService;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Exception\GatewayRequestException;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Gateway\Card\Fss\Entity as CardFssEntity;
use RZP\Gateway\Worldline\Entity as WorldlineEntity;
use RZP\Tests\Functional\Gateway\Reconciliation\TestTraits;
use RZP\Reconciliator\Base\SubReconciliator\ManualReconciliate;
use RZP\Reconciliator\Base\SubReconciliator\PaymentReconciliate;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;
use RZP\Reconciliator\Axis\SubReconciliator\RefundReconciliate as AxisRefundRecon;
use RZP\Reconciliator\HDFC\SubReconciliator\RefundReconciliate as HdfcRefundRecon;
use RZP\Reconciliator\HDFC\SubReconciliator\PaymentReconciliate as HDFCPaymentRecon;
use RZP\Reconciliator\Axis\SubReconciliator\PaymentReconciliate as AxisPaymentRecon;
use RZP\Reconciliator\FirstData\SubReconciliator\PaymentReconciliate as FDPaymentRecon;
use RZP\Reconciliator\Hitachi\SubReconciliator\RefundReconciliate as HitachiRefundRecon;
use RZP\Reconciliator\VirtualAccRbl\SubReconciliator\PaymentReconciliate as VirtualAccRbl;
use RZP\Reconciliator\BillDesk\SubReconciliator\RefundReconciliate as BilldeskRefundRecon;
use RZP\Reconciliator\Hitachi\SubReconciliator\PaymentReconciliate as HitachiPaymentRecon;
use RZP\Reconciliator\Fulcrum\SubReconciliator\PaymentReconciliate as FulcrumPaymentRecon;
use RZP\Reconciliator\VasAxis\SubReconciliator\PaymentReconciliate as VasAxisPaymentRecon;
use RZP\Reconciliator\BillDesk\SubReconciliator\PaymentReconciliate as BilldeskPaymentRecon;
use RZP\Reconciliator\VirtualAccIcici\SubReconciliator\ReconciliationFields as VirtualAccIcici;
use RZP\Reconciliator\Freecharge\SubReconciliator\PaymentReconciliate as FreechargePaymentRecon;
use RZP\Reconciliator\VirtualAccYesBank\SubReconciliator\PaymentReconciliate as VirtualAccYesBank;
use RZP\Reconciliator\checkout_dot_com\SubReconciliator\ReconciliationFields as CheckoutDotComPaymentRecon;

class ReconciliationFileTest extends TestCase
{
    use BatchTestTrait;
    use VirtualAccountTrait;
    use TestTraits\EbsReconTestTrait;

    protected $payment;
    protected $recurringPayment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ReconciliationFileTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->recurringPayment = $this->getDefaultRecurringPaymentArray();

        $this->mockCardVault();

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testFirstDataReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');



        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);
        $entries[] = $this->overrideFirstDataPayment($payment1);

        $payment2 = $this->getNewPaymentEntity(true, false);
        $this->assertNull($payment2['reference1']);
        $this->assertNull($payment2['reference2']);
        $entries[] = $this->overrideFirstDataPayment($payment2);

        $payment3 = $this->getNewPaymentEntity(true, false);
        $this->assertNull($payment3['reference1']);
        $this->assertNull($payment3['reference2']);
        $entries[] = $this->overrideFirstDataPayment($payment3);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedPayment2 = $this->getDbEntityById('payment' ,$payment2['id']);
        $this->assertEquals($entries[1][FDPaymentRecon::COLUMN_ARN], $updatedPayment2['reference1']);
        $this->assertEquals($entries[1][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment2['reference2']);
        $this->assertTrue($updatedPayment2['gateway_captured']);

        $updatedPayment3 = $this->getDbEntityById('payment' ,$payment3['id']);
        $this->assertEquals($entries[2][FDPaymentRecon::COLUMN_ARN], $updatedPayment3['reference1']);
        $this->assertEquals($entries[2][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment3['reference2']);
        $this->assertTrue($updatedPayment3['gateway_captured']);

        $updatedTransaction1 = $this->getDbEntityById('transaction', $updatedPayment1['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $updatedTransaction2 = $this->getDbEntityById('transaction', $updatedPayment2['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($updatedTransaction2['reconciled_at']);

        $updatedTransaction3 = $this->getDbEntityById('transaction', $updatedPayment3['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($updatedTransaction3['reconciled_at']);

        // Check the status of processed batch.
        $this->assertBatchStatus(Status::PROCESSED);

        // Overriding Entity to test force update
        unset($entries[1], $entries[2]);

        $entries[0][FDPaymentRecon::COLUMN_ARN]         = 'force_updated_arn';
        $entries[0][FDPaymentRecon::COLUMN_AUTH_CODE]   = 'force_updated_auth_code';

        $file = $this->writeToExcelFile($entries, 'first_data');

        $this->runForFiles([$file], 'FirstData', ['payment_arn', 'payment_auth_code']);

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);

        // Check the status of processed batch.
        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFirstdataReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $refund1 = $this->refundPayment($payment1['id']);

        $payment1['gateway_transaction_id'] = '00123';
        $this->assertNull($refund1['acquirer_data']['arn']);

        $entries[] = $this->overrideFirstDataRefund($payment1);

        // Recurring authorised payment
        $payment2 = $this->getNewPaymentEntity(false, true);
        $refund2 = $this->refundPayment($payment2['id']);
        $payment2['gateway_transaction_id'] = '456';
        $this->assertNull($refund2['acquirer_data']['arn']);

        $entries[] = $this->overrideFirstDataRefund($payment2);

        $file = $this->writeToExcelFile($entries, 'first_data');

        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($entries[0]['ft_no'], '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment1['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund1['id'])
                    ],
                    $entries[1]['ft_no'] => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment2['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund2['id'])
                    ]
                ]
            ]
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

        $cpsResponse = [
            'body' => [
                strtoupper(PublicEntity::stripDefaultSign($payment1['id'])) => [
                    'authorization' => [
                        'payment_id' => $payment1['id']
                    ]
                ],
                strtoupper(PublicEntity::stripDefaultSign($payment2['id'])) => [
                    'authorization' => [
                        'payment_id' => $payment2['id']
                    ]
                ],
            ]
        ];

        $cpsMock = $this->getMockBuilder(CardPaymentService::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['fetchPaymentIdFromCapsPIDs'])
                        ->getMock();

        $this->app->instance('card.payments', $cpsMock);

        $this->app['card.payments']->method('fetchPaymentIdFromCapsPIDs')->willReturn($cpsResponse);

        $this->runForFiles([$file], 'FirstData');

        $updatedTransaction1 = $this->getDbEntity(
            'transaction',
            [
                'type'      => 'refund',
                'entity_id' => PublicEntity::stripDefaultSign($refund1['id'])
            ])->toArray();

        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $updatedTransaction2 = $this->getDbEntity(
        'transaction',
        [
            'type'      => 'refund',
            'entity_id' => PublicEntity::stripDefaultSign($refund2['id'])
        ])->toArray();

        $this->assertNotNull($updatedTransaction2['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFirstdataCombinedReconFileViaBatchServiceRoute()
    {
        $this->markTestSkipped('Skipping this right now as it fails intermittently and affects other deverlopers. Will have to fix this soon.');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $this->assertNull($payment1['reference1']);

        $refund1 = $this->refundPayment($payment1['id']);
        $this->assertNull($refund1['acquirer_data']['arn']);

        // Recurring authorised payment 2
        $payment2 = $this->getNewPaymentEntity(false, true);
        $this->assertNull($payment2['reference1']);

        $refund2 = $this->refundPayment($payment2['id']);
        $this->assertNull($refund2['acquirer_data']['arn']);

        $this->payment['amount'] = 500000;
        $payment3 = $this->getNewPaymentEntity(false, true);

        // Add these 2 payments and 2 refunds in entries array
        $entries[] = $this->overrideFirstDataPayment($payment1);
        $entries[] = $this->overrideFirstDataRefund($payment1);
        $entries[] = $this->overrideFirstDataPayment($payment2);
        $entries[] = $this->overrideFirstDataRefund($payment2);
        $entries[] = $this->overrideFirstDataPayment($payment3, ['transaction_amt' => 5000]);

        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($entries[1]['ft_no'], '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment1['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund1['id'])
                    ],
                    $entries[3]['ft_no'] => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment2['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund2['id'])
                    ]
                ]
            ]
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'FirstData';
            $entry[Constants::SUB_TYPE]         = 'combined';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        // Assert that payments got reconciled
        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedPayment2 = $this->getDbEntityById('payment' ,$payment2['id']);
        $this->assertEquals($entries[2][FDPaymentRecon::COLUMN_ARN], $updatedPayment2['reference1']);
        $this->assertTrue($updatedPayment2['gateway_captured']);

        $updatedPayment3 = $this->getDbEntityById('payment' ,$payment3['id']);
        $this->assertEquals($entries[4][FDPaymentRecon::COLUMN_ARN], $updatedPayment3['reference1']);
        $this->assertTrue($updatedPayment3['gateway_captured']);

        $paymentTransaction1 = $this->getDbEntityById('transaction', $updatedPayment1['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($paymentTransaction1['reconciled_at']);
        $this->assertEquals(0, $updatedPayment1['gateway_service_tax']);

        $paymentTransaction2 = $this->getDbEntityById('transaction', $updatedPayment2['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($paymentTransaction2['reconciled_at']);
        $this->assertEquals(0, $updatedPayment2['gateway_service_tax']);

        $paymentTransaction3 = $this->getDbEntityById('transaction', $updatedPayment3['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($paymentTransaction3['reconciled_at']);
        // Commission amount is 50, so gst is 9
        $this->assertEquals(900, $paymentTransaction3['gateway_service_tax']);

        // Assert that refunds got reconciled
        $refundTransaction1 = $this->getDbEntity(
            'transaction',
            [
                'type'      => 'refund',
                'entity_id' => PublicEntity::stripDefaultSign($refund1['id'])
            ])->toArray();

        $this->assertNotNull($refundTransaction1['reconciled_at']);

        $refundTransaction2 = $this->getDbEntity(
            'transaction',
            [
                'type'      => 'refund',
                'entity_id' => PublicEntity::stripDefaultSign($refund2['id'])
            ])->toArray();

        $this->assertNotNull($refundTransaction2['reconciled_at']);
    }

    public function testFirstDataReconNonInrPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment = $this->getNewPaymentEntity(true, false);
        $this->assertNull($payment['reference1']);
        $this->assertNull($payment['reference2']);

        $this->fixtures->edit('payment',
            $payment['id'],
            [
                'base_amount'       => 7000,
                'amount'            => 100,
                'convert_currency'  => 0,
                'currency'          => 'USD',
            ]);

        $entries[] = $this->overrideFirstDataNonInrPayment($payment);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment['reference2']);

        $this->assertTrue($updatedPayment['gateway_captured']);

        $updatedTransaction = $this->getDbLastEntity('transaction');
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertBatchStatus();
    }
    public function testFirstDataForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');



        $payment = $this->getNewPaymentEntity(true, false);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideFirstDataPayment($payment);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testFirstDataForceAuthorizeFailedCpsPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment = $this->getNewPaymentEntity(true,true);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'                => 'failed',
                'cps_route'             => 2,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideFirstDataPayment($payment);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData', [], ['pay_'. $payment['id']]);

        $updatedPayment2 = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('authorized', $updatedPayment2['status']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * A failed payment should be force authorised only if
     * payment amount and currency match.
     */
    public function testAmountMisMatchForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getNewPaymentEntity(true, false);

        // change the amount  and set status to 'failed'
        $this->fixtures->payment->edit($payment['id'],
            [
                'amount'                => $payment['amount'] * 2,
                'base_amount'           => $payment['base_amount'] * 2,
                'status'                => PaymentStatus::FAILED,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideFirstDataPayment($payment);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $updatedPayment['status']);
    }

    public function testHdfcFssReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssCaptureFailureReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new GatewayRequestException('Timed out');
            }

            return $content;
        }, 'hdfc');

        $this->makeRequestAndCatchException(
            function ()
            {
                $this->doAuthAndCapturePayment();
            });

        $gatewayPayment = $this->getDbLastEntityToArray('hdfc');

        $entries[] = $this->overrideHdfcPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment = $this->getDbLastPayment();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment['reference2']);
        $this->assertTrue($updatedPayment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }


    public function testHdfcReconPaymentFileWithMetaDataDelete()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

// Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::DELETE_CARD_METADATA_AFTER_RECONCILIATION)
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $callable = function($route, $method, $input) {
            $response = [
                'error'   => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token']       = 'pay_44f3d176b38b4cd2a588f243e3ff7b20';
                    $response['fingerprint'] = null;
                    $response['scheme']      = '0';
                    break;

                case 'cards/metadata/fetch':
                    $response['token']        = $input['token'];
                    $response['iin']          = '411111';
                    $response['expiry_month'] = '08';
                    $response['expiry_year']  = '2025';
                    $response['name']         = 'chirag';
                    break;

                case 'cards/metadata':
                    self::assertArrayKeysExist($input, [
                        Entity::TOKEN,
                        Entity::NAME,
                        Entity::EXPIRY_YEAR,
                        Entity::EXPIRY_MONTH,
                        Entity::IIN
                    ]);
                    break;

                case 'delete/token':
                    self::assertEquals('pay_44f3d176b38b4cd2a588f243e3ff7b20', $input['token']);
                    break;
            }

            return $response;
        };

        $app = App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable)->times(6);


        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new GatewayRequestException('Timed out');
            }

            return $content;
        }, 'hdfc');

        $this->makeRequestAndCatchException(
            function ()
            {
                $this->doAuthAndCapturePayment();
            });

        $gatewayPayment = $this->getDbLastEntityToArray('hdfc');

        $entries[] = $this->overrideHdfcPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment = $this->getDbLastPayment();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment['reference2']);
        $this->assertTrue($updatedPayment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcCyberSourceTerminalIdentify()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $row = $this->overrideHdfcPayment($gatewayPayment1,[],'cybersource');

        //
        // Set below field so that if the terminal not identified as cybersource then
        // in the next if condition it will get identified as BharatQR and thus this test
        // should fail as payment could not get identified correctly.
        //
        $row['card_type'] = 'BHARAT QR';

        $entries[] = $row;

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $updatedTransaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAxisMigsForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getNewPaymentEntity(true, false);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status' => 'failed',
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
                'verify_bucket' => 0,
                'verified' => null
            ]);

        $gatewayPayment = $this->getDbLastEntityToArray('axis_migs');

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment, [], 'migs');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement', 'Sale');
        $this->runForFiles([$file], 'Axis', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testAxisMigsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment = $this->getNewPaymentEntity(true, false);
        $gatewayPayment = $this->getDbLastEntityToArray('axis_migs');

        $this->assertNull($payment['reference1']);
        $this->assertNull($payment['reference2']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment,[],'migs');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment = $this->getDbEntityById('payment' ,$payment['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment['reference1']);
        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment['reference2']);
        $this->assertTrue($updatedPayment['gateway_captured']);

        $updatedTransaction = $this->getDbEntityById('transaction', $updatedPayment['transaction_id'])->toArrayAdmin();
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAxisMigsReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $refund = $this->getNewRefundEntity(true);
        $gatewayRefund = $this->getDbLastEntityToArray('axis_migs');

        $this->assertNull($refund['acquirer_data']['arn']);

        $entries[] = $this->overrideAxisRefund($gatewayRefund);

        $this->fixtures->edit('axis_migs',
            $gatewayRefund['id'],
            [
                'vpc_ReceiptNo' => $entries[0]['rrn_no'],
            ]);

        $file = $this->writeToExcelFile($entries, 'axis','files/settlement','refund');

        $this->runForFiles([$file], 'Axis');

        $updatedTransaction = $this->getDbEntityById('transaction', $refund['transaction_id'])->toArrayAdmin();

        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCardFssReconCombinedFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        // Specifying the gateway because it is set to HDFC by default if left null
        $this->gateway = 'card_fss';

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        $payment_transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($payment_transaction['reconciled_at']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $paymentData = $this->overrideCardFssPayment($gatewayPayment);

        $paymentHeader = array_keys($paymentData);

        $blank_rows = array_combine($paymentHeader, array_fill(0,count($paymentHeader), null));

        // Refund this newly created payment, and populate refund data
        $this->refundPayment('pay_' . $gatewayPayment['payment_id']);

        $refund_transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($refund_transaction['reconciled_at']);

        $gatewayRefund = $this->getLastEntity('card_fss', true);

        $refundData = $this->overrideCardFssRefund($gatewayRefund, $gatewayPayment);

        $refundHeader = array_keys($refundData);

        //
        // add one payment data row, followed by two blank rows
        //
        $entries[] = $paymentData;
        $entries[] = $blank_rows;
        $entries[] = $blank_rows;

        // add refund header and refund data row
        $entries[] = array_combine($paymentHeader, array_pad($refundHeader, count($paymentHeader), null));
        $entries[] = array_combine($paymentHeader, array_pad($refundData, count($paymentHeader), null));

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement');

        $this->runForFiles([$file], 'CardFssHdfc');

        // ======== verify refund reconciliation ========
        $updatedTransaction = $this->getDbEntityById('transaction', $refund_transaction['id']);

        $updatedRefund = $this->getDbEntityById('refund', $refund_transaction['entity_id']);

        // here 'Reference Tran Id' of refund header is mapped to payment header column 'MSF Amount'
        // in the test excel file, so we are using 'MSF Amount' in next line.
        $this->assertEquals($entries[4]['MSF Amount'], $updatedRefund['reference1']);

        $this->assertNotNull($updatedTransaction['reconciled_at']);

        // ======== verify payment reconciliation =======
        $updatedPayment = $this->getDbEntityById('payment', $response['id']);

        $this->assertEquals($entries[0]['RRN'], $updatedPayment['reference1']);
        $this->assertEquals($entries[0]['Auth/Approval Code'], $updatedPayment['reference2']);

        $updatedTransaction = $this->getDbEntityById('transaction', $payment_transaction['id']);

        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $updatedGatewayPayment = $this->getLastEntity('card_fss', true);

        // Here in next line we wanted to check the refund column 'Aggregator Transaction ID', but
        // this is mapped to 'transaction category' column of payment header in test excel file
        $this->assertEquals($entries[4]['transaction category'], $updatedGatewayPayment['tranid']);

        $this->assertBatchStatus();
    }

    public function testYesBankReconCombinedFile()
    {
        $payment = $this->fixtures->create('payment:captured', [
            'amount'    => 100
        ]);

        $card = $this->fixtures->create('card', []);

        $payment->card()->associate($card);

        $payment->saveOrFail();

        $refund = $this->fixtures->create('refund:from_payment', ['payment' => $payment]);

        $entries[] = $this->overrideYesBankPayment($payment->toArrayAdmin());

        $entries[] = $this->overrideYesBankRefund($refund['id']);

        $file = $this->writeToExcelFile($entries, 'fss');

        $this->runForFiles([$file], 'YesBank');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($transaction['reconciled_type']);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['success_count']);

        $this->assertEquals('reconciliation', $batch['type']);

        $this->assertEquals('YesBank', $batch['gateway']);

        $this->assertBatchStatus();
    }

    public function testCardFssReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $entries[] = $this->overrideCardFssPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'payment');

        $this->runForFiles([$file], 'CardFssHdfc');

        $updatedPayment = $this->getDbEntityById('payment', $response['id']);

        $this->assertEquals($entries[0]['RRN'], $updatedPayment['reference1']);
        $this->assertEquals($entries[0]['Auth/Approval Code'], $updatedPayment['reference2']);

        $updatedTransaction = $this->getLastEntity('transaction', true);
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $updatedGatewayPayment = $this->getLastEntity('card_fss', true);

        $this->assertEquals($entries[0]['payment gateway transaction id'], $updatedGatewayPayment['tranid']);

        $this->assertBatchStatus();
    }

    public function testCardFssHdfcReconCpsPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $entries[] = $this->overrideCardFssPayment($gatewayPayment);

        $this->fixtures->payment->edit($paymentEntity['id'], ['cps_route' => 2]);

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'payment');

        $this->runForFiles([$file], 'CardFssHdfc');

        $updatedPayment = $this->getDbEntityById('payment', $response['id']);

        $this->assertEquals($entries[0]['RRN'], $updatedPayment['reference1']);
        $this->assertEquals($entries[0]['Auth/Approval Code'], $updatedPayment['reference2']);

        $updatedTransaction = $this->getLastEntity('transaction', true);
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $updatedGatewayPayment = $this->getLastEntity('card_fss', true);

        $this->assertEquals($entries[0]['payment gateway transaction id'], $updatedGatewayPayment['tranid']);

        $this->assertBatchStatus();
    }

    public function testCardFssReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        // Specifying the gateway because it is set to HDFC by default if left null
        $this->gateway = 'card_fss';

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $this->refundPayment('pay_' . $gatewayPayment['payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $this->assertNull($transaction['reconciled_type']);

        $gatewayRefund = $this->getLastEntity('card_fss', true);

        $entries[] = $this->overrideCardFssRefund($gatewayRefund, $gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'refund', 'xlsx');

        $this->runForFiles([$file], 'CardFssHdfc');

        $updatedTransaction = $this->getLastEntity('transaction', true);

        $updatedRefund = $this->getLastEntity('refund', true);

        $this->assertEquals($entries[0]['Reference Tran Id'], $updatedRefund['reference1']);
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCardFssForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment1 =$this->doAuthAndGetPayment($payment);

        $this->fixtures->payment->edit($payment1['id'],
            [
                'status' => 'failed',
                'error_code' => 'BAD_REQUEST_ERROR',
            ]);

        $gatewayPayment = $this->getDbLastEntityToArray('card_fss');

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideCardFssPayment($gatewayPayment, [], 'card_fss');

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'payment');

        $this->runForFiles([$file], 'CardFssHdfc', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testVirtualAccYesBankReconFile()
    {
        $this->fixtures->terminal->createBankAccountTerminal();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        // Intentionally changing the IFSC to validate IFSC is not updated from recon file anymore.
        $payment = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PROCESSED);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankTransfer['payer_ifsc']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankAccount['ifsc_code']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);
        $this->assertNotNull($transaction['reconciled_type']);

        // Beneficiary name should be overridden by the one in the file.
        $this->assertEquals($entries[0]['rmtr_full_name'], $bankAccount['beneficiary_name']);
    }

    // MIS row with trans_status as 'PENDING CREDIT'
    public function testVirtualAccYesBankPendingCreditReconFile()
    {
        $this->fixtures->terminal->createBankAccountTerminal();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        // Intentionally changing the IFSC to validate IFSC is not updated from recon file anymore.
        $payment = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        // Change the row trans_status to Pending Credit
        $entries[0][VirtualAccYesBank::COLUMN_TRANS_STATUS]  = 'PENDING CREDIT';

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PROCESSED);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankTransfer['payer_ifsc']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankAccount['ifsc_code']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);
        $this->assertNotNull($transaction['reconciled_type']);

        // Beneficiary name should be overridden by the one in the file.
        $this->assertEquals($entries[0]['rmtr_full_name'], $bankAccount['beneficiary_name']);
    }

    public function testVirtualAccYesBankReconFileWithWrongValues()
    {
        $this->fixtures->terminal->createBankAccountTerminal();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        $payment = $this->payVirtualAccount($account['id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        // With wrong amount, batch should be marked partially processed and reconciled at should not be present.
        $entries[0]['amount'] = 1000;

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $utr = $entries[0]['transaction_ref_no'];

        // If UTR is not present, batch should be marked partially processed and reconciled at should not be present.
        $entries[0]['transaction_ref_no'] = strtoupper(random_alphanum_string(22));

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        // With correct values of utr, batch should be marked processed with reconciled at timestamp.
        $entries[0]['transaction_ref_no'] = $utr;

        $entries[0]['amount'] = 100;

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);
    }

    /**
     * Creates 3 payments, 1 of IMPS, 1 UPI, 1 NEFT and reconcile them.
     * Getting UTR from recon row is different for these 3 cases.
     */
    public function testVirtualAccRblReconFile()
    {
        $this->fixtures->terminal->createBankAccountTerminal();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        // Intentionally changing the IFSC to validate IFSC is not updated from recon file anymore.
        $payment1 = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);

        //create 3 payments
        $paymentEntity1 = $this->getLastEntity('payment', true);
        $transaction1 = $this->getDbEntityById('transaction', $paymentEntity1['transaction_id']);
        $this->assertNull($transaction1['reconciled_at']);
        $this->assertNull($transaction1['reconciled_type']);

        $payment2 = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);
        $paymentEntity2 = $this->getLastEntity('payment', true);
        $transaction2 = $this->getDbEntityById('transaction', $paymentEntity2['transaction_id']);
        $this->assertNull($transaction2['reconciled_at']);
        $this->assertNull($transaction2['reconciled_type']);

        $payment3 = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);
        $paymentEntity3 = $this->getLastEntity('payment', true);
        $transaction3 = $this->getDbEntityById('transaction', $paymentEntity3['transaction_id']);
        $this->assertNull($transaction3['reconciled_at']);
        $this->assertNull($transaction3['reconciled_type']);

        $entries[] = $this->overrideVirtualAccRblPayment($account, $payment1, 'IMPS');
        $entries[] = $this->overrideVirtualAccRblPayment($account, $payment2, 'UPI');
        $entries[] = $this->overrideVirtualAccRblPayment($account, $payment3, 'NEFT');

        $file = $this->writeToExcelFile($entries, 'virtualAccRbl', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccRbl');

        $this->assertBatchStatus(Status::PROCESSED);

        $updatedTransaction1 = $this->getDbEntityById('transaction', $paymentEntity1['transaction_id']);
        $updatedTransaction2 = $this->getDbEntityById('transaction', $paymentEntity2['transaction_id']);
        $updatedTransaction3 = $this->getDbEntityById('transaction', $paymentEntity3['transaction_id']);

        $this->assertNotNull($updatedTransaction1['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_type']);

        $this->assertNotNull($updatedTransaction2['reconciled_at']);
        $this->assertNotNull($updatedTransaction2['reconciled_type']);

        $this->assertNotNull($updatedTransaction3['reconciled_at']);
        $this->assertNotNull($updatedTransaction3['reconciled_type']);

        // Check further assertions only for last payment
        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertNotEquals($entries[2]['sender_ifsc'], $bankTransfer['payer_ifsc']);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $this->assertNotEquals($entries[2]['sender_ifsc'], $bankAccount['ifsc_code']);

        // Beneficiary name should be overridden by the one in the file.
        $this->assertEquals($entries[2]['sender_acct_name'], $bankAccount['beneficiary_name']);
    }

    public function testAxisCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment1['reference2'], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    //
    // Tests recon and gateway data update for cybersource payment
    // which is being routed through Cards Payment Service (CPS).
    //
    public function testAxisCyberSourceCpsReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);

        // Make cps route = 2
        $this->fixtures->payment->edit($payment1['id'], ['cps_route' => 2]);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment1['reference2'], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    //
    // Tests whether recon batch is getting created and the
    // batch job is getting queued in the desired queue.
    //
    public function testAxisCyberSourceReconQueueAndBatchStatus()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');

        Queue::fake();

        $this->runForFiles([$file], 'Axis');

        Queue::assertPushedOn(env('AWS_RECON_BATCH_QUEUE'), Jobs\Batch::class);

        $this->assertBatchStatus(Status::CREATED);
    }

    public function testAxisCyberSourceReconPaymentModifiedFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        //
        // Changing the 'ref' in cybersource entities, as we are dealing with 3 payments here.
        // If we don't change then the same 'ref' will be used to get the payment...and that
        // will result in all these MIS rows getting the same payment_id from gateway_payment
        //
        $gatewayPayment1['ref'] = $gatewayPayment1['ref'] . '1';
        $this->fixtures->edit('cybersource', $gatewayPayment1['id'], ['ref' => $gatewayPayment1['ref']]);

        $this->assertNull($payment1['reference1']);

        $row1 = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        // Payment_id field not set in row
        $row1['merchant_trans_ref'] = '';

        // Payment2
        $payment2 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment2 = $this->getDbLastEntityToArray('cybersource');

        $gatewayPayment2['ref'] = $gatewayPayment2['ref'] . '2';
        $this->fixtures->edit('cybersource', $gatewayPayment2['id'], ['ref' =>  $gatewayPayment2['ref']]);

        $this->assertNull($payment2['reference1']);

        $row2 = $this->overrideAxisPayment($gatewayPayment2,[],'cybersource');

        // Payment_id field is set to valid payment_id
        $row2['merchant_trans_ref'] = $gatewayPayment2['payment_id'];

        // Payment3 : negative test
        $payment3 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment3 = $this->getDbLastEntityToArray('cybersource');

        $gatewayPayment3['ref'] = $gatewayPayment3['ref'] . '3';

        //
        // Here, intentionally change the cybersource ref by appending '00' so
        // that for this row, we won't not find the cybersource entity, which will
        // result in payment absent error.
        //
        $this->fixtures->edit('cybersource', $gatewayPayment3['id'], ['ref' => $gatewayPayment3['ref'] . '00']);

        $this->assertNull($payment3['reference1']);

        $row3 = $this->overrideAxisPayment($gatewayPayment3,[],'cybersource');

        // Payment_id field is set to random string
        $row3['merchant_trans_ref'] = 'Xyz1234';

        $entries[] = $row1;
        $entries[] = $row2;
        $entries[] = $row3;

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);
        $updatedPayment2 = $this->getDbEntityById('payment' ,$payment2['id']);
        $updatedPayment3 = $this->getDbEntityById('payment' ,$payment3['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[1][AxisPaymentRecon::COLUMN_ARN], $updatedPayment2['reference1']);
        $this->assertNull($updatedPayment3['reference1']);

        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment1['reference2'], $updatedPayment1['reference2']);
        $this->assertEquals($payment2['reference2'], $updatedPayment2['reference2']);
        $this->assertEquals($payment3['reference2'], $updatedPayment3['reference2']);

        $this->assertTrue($updatedPayment1['gateway_captured']);
        $this->assertTrue($updatedPayment2['gateway_captured']);
        $this->assertTrue($updatedPayment3['gateway_captured']);

        $updatedTransaction1 = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $updatedPayment2['transaction_id'], true);
        $updatedTransaction3 = $this->getEntityById('transaction', $updatedPayment3['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction1['reconciled_at']);
        $this->assertNotNull($updatedTransaction2['reconciled_at']);
        $this->assertNull($updatedTransaction3['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(2, $batch['success_count']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testHdfcFssReconRefundFile()
    {
        $this->gateway = 'hdfc';

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        // Recurring authorised payment
        $refund1 = $this->getNewRefundEntity(true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($refund1['reference1']);

        $entries[] = $this->overrideHdfcRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedRefund1['reference1']);

        // Test for force update ARN
        $entries[0][HDFCPaymentRecon::COLUMN_ARN] .= str_random(2);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC', ['refund_arn']);

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedRefund1['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcCybersourceReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'cybersource';

        $refund = $this->getNewRefundEntity(true);
        $gatewayRefund = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($refund[Refund\Entity::REFERENCE1]);

        $entries[] = $this->overrideHdfcCybersourceOnusRefund($gatewayRefund);

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund = $this->getDbEntityById('refund', $refund['id'])->toArrayAdmin();

        $this->assertEquals($gatewayRefund['ref'], $updatedRefund[Refund\Entity::REFERENCE1]);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAtomReconPaymentFile()
    {
        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');

        $this->fixtures->create('terminal:shared_atom_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $gatewayPayment = $this->getLastEntity('atom', true);

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $entries[] = $this->overrideAtomPayment($gatewayPayment);

        $file = $this->writeToCsvFile($entries, 'settlementReport');

        $this->runForFiles([$file], 'Atom');

        $updatedTransaction = $this->getLastEntity('transaction', true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAtomReconExtraCommaPaymentFile()
    {
        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');

        $this->fixtures->create('terminal:shared_atom_terminal');

        $payment1 = $this->getDefaultNetbankingPaymentArray();

        $payment1 = $this->doAuthAndCapturePayment($payment1);

        $transaction1 = $this->getLastEntity('transaction', true);

        $gatewayPayment1 = $this->getLastEntity('atom', true);

        //Reconciled at should be null
        $this->assertNull($transaction1['reconciled_at']);

        $this->assertEquals($payment1['id'], $transaction1['entity_id']);

        $entry1 = $this->overrideAtomPayment($gatewayPayment1);

        $this->rightShiftRowValuesForAtom($entry1);

        $entries[] = $entry1;

        // make another payment
        $payment2 = $this->getDefaultNetbankingPaymentArray();

        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $transaction2 = $this->getLastEntity('transaction', true);

        $gatewayPayment2 = $this->getLastEntity('atom', true);

        //Reconciled at should be null
        $this->assertNull($transaction2['reconciled_at']);

        $this->assertEquals($payment2['id'], $transaction2['entity_id']);

        $entry2 = $this->overrideAtomPayment($gatewayPayment2);

        $entries[] = $entry2;

        $file = $this->writeToCsvFile($entries, 'settlementReport');

        $this->runForFiles([$file], 'Atom');

        $updatedTransaction1 = $this->getEntityById('transaction', $transaction1['id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $transaction2['id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction1['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_type']);
        $this->assertNotNull($updatedTransaction1['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction1['gateway_fee']);
        $this->assertNotNull($updatedTransaction1['gateway_service_tax']);

        $this->assertNotNull($updatedTransaction2['reconciled_at']);
        $this->assertNotNull($updatedTransaction2['reconciled_type']);
        $this->assertNotNull($updatedTransaction2['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction2['gateway_fee']);
        $this->assertNotNull($updatedTransaction2['gateway_service_tax']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(2, $batch['success_count']);
        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAtomCombinedUniqueEntityRecon()
    {
        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');

        $this->fixtures->create('terminal:shared_atom_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getLastEntity('atom', true);

        $paymentData = $this->overrideAtomPayment($gatewayPayment);

        $refund1 = $this->refundPayment($payment['id'], '10000');

        $refund2 = $this->refundPayment($payment['id'], '20000');

        $refund3 = $this->refundPayment($payment['id'], '20000');

        $refundData1 = $this->overrideAtomRefund($gatewayPayment, $refund1['amount']/100);

        $refundData2 = $this->overrideAtomRefund($gatewayPayment, $refund2['amount']/100);

        $refundData3 = $this->overrideAtomRefund($gatewayPayment, $refund3['amount']/100);

        $entries[] = $paymentData;

        $entries[] = $refundData1;

        $entries[] = $refundData2;

        $entries[] = $refundData3;

        $file = $this->writeToCsvFile($entries, 'settlementReport');

        $this->runForFiles([$file], 'Atom');

        $payment = $this->getEntityById('payment', PublicEntity::stripDefaultSign($payment['id']), true);
        $refundEntity1 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund1['id']), true);
        $refundEntity2 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund2['id']), true);
        $refundEntity3 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund3['id']), true);

        $paymentTransaction  = $this->getEntityById('transaction', $payment['transaction_id'], true);
        $updatedTransaction1 = $this->getEntityById('transaction', $refundEntity1['transaction_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $refundEntity2['transaction_id'], true);
        $updatedTransaction3 = $this->getEntityById('transaction', $refundEntity3['transaction_id'], true);

        $this->assertNotNull($refundEntity1['reference1']);
        $this->assertNull($refundEntity2['reference1']);
        $this->assertNull($refundEntity3['reference1']);

        //
        // One payment and one refund row get reconciled, other 2 refunds
        // remain unreconciled, as we could not identify the refund uniquely.
        //
        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $this->assertNull($updatedTransaction2['reconciled_at']);
        $this->assertNull($updatedTransaction3['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(4, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(2, $batch['failure_count']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testAtomCombinedFileReconViaBatchServiceRoute()
    {
        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');

        $this->fixtures->create('terminal:shared_atom_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getLastEntity('atom', true);

        $paymentData = $this->overrideAtomPayment($gatewayPayment, 'atom_v2');

        $refund1 = $this->refundPayment($payment['id'], '10000');

        $refund2 = $this->refundPayment($payment['id'], '20000');

        $refund3 = $this->refundPayment($payment['id'], '20000');

        $refundData1 = $this->overrideAtomRefund($gatewayPayment, $refund1['amount']/100, 'atom_v2');

        $refundData2 = $this->overrideAtomRefund($gatewayPayment, $refund2['amount']/100, 'atom_v2');

        $refundData3 = $this->overrideAtomRefund($gatewayPayment, $refund3['amount']/100, 'atom_v2');

        $entries[] = $paymentData;

        $entries[] = $refundData1;

        $entries[] = $refundData2;

        $entries[] = $refundData3;

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'Atom';
            $entry[Constants::SUB_TYPE]         = 'combined';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        $payment = $this->getEntityById('payment', PublicEntity::stripDefaultSign($payment['id']), true);
        $refundEntity1 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund1['id']), true);
        $refundEntity2 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund2['id']), true);
        $refundEntity3 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund3['id']), true);

        $paymentTransaction  = $this->getEntityById('transaction', $payment['transaction_id'], true);
        $updatedTransaction1 = $this->getEntityById('transaction', $refundEntity1['transaction_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $refundEntity2['transaction_id'], true);
        $updatedTransaction3 = $this->getEntityById('transaction', $refundEntity3['transaction_id'], true);

        $this->assertNotNull($refundEntity1['reference1']);
        $this->assertNull($refundEntity2['reference1']);
        $this->assertNull($refundEntity3['reference1']);

        //
        // One payment and one refund row get reconciled, other 2 refunds
        // remain unreconciled, as we could not identify the refund uniquely.
        //
        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $this->assertNull($updatedTransaction2['reconciled_at']);
        $this->assertNull($updatedTransaction3['reconciled_at']);
    }

    //For success case of Bill desk reconciliation
    public function testBillDeskReconRefundFileFailure()
    {
        $this->markTestSkipped();

        $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);
        $gatewayRefund = $this->getLastEntity('billdesk', true);

        $refundEntity = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $transaction = $this->getEntityById('transaction', $refundEntity['transaction_id'], true);

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideBilldeskRefund($gatewayRefund);

        $file = $this->writeToCsvFile($entries, 'billdesk_refund');

        $this->runForFiles([$file], 'BillDesk');

        $updatedRefund1 = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $updatedTransaction = $this->getEntityById('transaction', $updatedRefund1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testBillDeskRefundReconFile()
    {
        $this->markTestSkipped();

        $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);
        $gatewayRefund = $this->getLastEntity('billdesk', true);

        $refundEntity = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $transaction = $this->getEntityById('transaction', $refundEntity['transaction_id'], true);

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideBilldeskRefund($gatewayRefund);

        // Recurring authorised payment 2
        $payment2 = $this->getDefaultNetbankingPaymentArray();

        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $refund2 = $this->refundPayment($payment2['id']);
        $gatewayRefund2 = $this->getLastEntity('billdesk', true);

        $refundEntity2 = $this->getEntityById('refund', $gatewayRefund2['refund_id'], true);
        $transaction2 = $this->getEntityById('transaction', $refundEntity2['transaction_id'], true);

        //Reconciled at should be null
        $this->assertNull($transaction2['reconciled_at']);
        $this->assertNull($transaction2['reconciled_type']);

        $entries[] = $this->overrideBilldeskRefund($gatewayRefund2);

        $file = $this->writeToCsvFile($entries, 'billdesk_refund');

        // Intentionally, Here we have just one refund in scrooge mock response,
        // Thus, for 2nd refund, we will fetch refund ID from billdesk table, in old way.
        // This is done to test back ward compatibility.
        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($entries[0]['refund_id'], '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund['id'])
                    ],
                ]
            ]
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

        $this->runForFiles([$file], 'BillDesk');

        // Assert Refund 1
        $updatedRefund = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $updatedTransaction = $this->getEntityById('transaction', $updatedRefund['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);

        // Assert Refund 2
        $updatedRefund2 = $this->getEntityById('refund', $gatewayRefund2['refund_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $updatedRefund2['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedRefund['reference1']);
        $this->assertNotNull($updatedRefund2['reference1']);
        $this->assertNotNull($updatedTransaction2['reconciled_at']);
        $this->assertNotNull($updatedTransaction2['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testBillDeskReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        $transaction = $paymentEntity->transaction;

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideBillDeskPayment(['payment_id' => $paymentEntity->getId(), 'TxnAmount' => $paymentEntity->getAmount()/100]);

        $file = $this->writeToCsvFile($entries, 'billdesk_success');

        $this->runForFiles([$file], 'BillDesk');

        $updatedTransaction = $paymentEntity->transaction->reload();

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $this->assertBatchStatus();
    }

    /**
     * Test for success and failure count of a processed batch
     */
    public function testAxisMigsBatchProcessTest(bool $addFeature = true, bool $mockMandate=true)
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        if ($addFeature === true) $this->fixtures->merchant->addFeatures('charge_at_will');

        if ($mockMandate === true) {

        }

        // Recurring authorised payments
        $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('axis_migs');

        $this->getNewPaymentEntity(true, false);
        $gatewayPayment2 = $this->getDbLastEntityToArray('axis_migs');

        $this->getNewPaymentEntity(true, false);
        $gatewayPayment3 = $this->getDbLastEntityToArray('axis_migs');

        $entries['Sale'][0] = $this->overrideAxisPayment($gatewayPayment1, [], 'migs');
        $entries['Sale'][1] = $this->overrideAxisPayment($gatewayPayment2, [], 'migs');

        // Passing payment id in refund also for a failure case
        $entries['Refund'][2] = $this->overrideAxisPayment($gatewayPayment3, [], 'migs');

        $file = $this->writeToExcelFile($entries, 'axis_razorpayadd', 'files/settlement', ['Sale', 'Refund'], 'xls');

        $this->runForFiles([$file], 'Axis');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);
        $this->assertEquals(3, $batch['processed_count']);

        // One failure, status will be partially_processed
        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    /**
     * Tests the flow of HDFC recon after Axis recon.
     * Aim is to test flow where recon setting sheet name runs first and then
     * and recon having sheet indices.
     * This tests if selectedSheets gets reset after parsing finished.
     */
    public function testLaravelExcelReaderSheetNamesReset()
    {
        // Run Axis migs test
        $this->testAxisMigsBatchProcessTest();

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        // Recurring authorised payment
        $payment3 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment3 = $this->getDbLastEntityToArray('hdfc');

        $entries2[] = $this->overrideHdfcPayment($gatewayPayment3);

        $file = $this->writeToExcelFile($entries2, 'fss', 'files/settlement', ['Sheet 1'], 'xlsx');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment2 = $this->getDbEntityById('payment', $payment3['id']);

        $this->assertEquals($entries2[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment2['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * Tests the flow of Axis recon after First Data recon
     * Aim is to test flow where recon setting sheet indices runs first and then
     * and recon having sheet names.
     * This tests if selectedSheetIndices gets reset to empty after parsing finished.
     */
    public function testLaravelExcelReaderSheetNamesResetReverse()
    {
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');



        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);

        $entries[] = $this->overrideFirstDataPayment($payment1);

        $file = $this->writeToExcelFile($entries, 'first_data', 'files/settlement', ['Sheet 1'], 'xlsx');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment1 = $this->getDbEntityById('payment', $payment1['id']);

        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        // Run Axis migs test
        $this->testAxisMigsBatchProcessTest(false, false);
    }

    public function testFreechargeReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_freecharge_terminal');

        $gatewayPayment1 = $this->getNewWalletEntity('10000000000000', 'freecharge');

        $wallet = $this->getLastEntity('wallet', true);

        $entries = $this->overrideFreechargePayment($wallet);

        $file = $this->writeToCsvFile($entries, 'freecharge');

        $this->runForFiles([$file], 'Freecharge');

        $updatedPayment1 = $this->getEntityById('payment', $wallet['payment_id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
        $this->assertEquals($updatedTransaction['gateway_service_tax'],200);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /*
     * Helpers
     */

    private function getNewPaymentEntity(bool $recurring = false, bool $captured = false)
    {
        $payment = $recurring ? $this->recurringPayment : $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        if ($recurring === true)
        {
            $paymentArray = $paymentEntity->toArrayPublic();
            unset($payment['card']);
            $payment['token'] = $paymentArray['token_id'];

            $response = $this->doS2SRecurringPayment($payment);

            $paymentId = $response['razorpay_payment_id'];

            $this->testData[__FUNCTION__]['request']['url'] = sprintf('/reminders/send/test/payment/card_auto_recurring/%s',
                substr($paymentId, 4));

            $this->ba->reminderAppAuth();

            $this->startTest();

            $paymentEntity = $this->getDbLastPayment();
        }

        if ($captured === true)
        {
            $paymentArray = $paymentEntity->toArrayPublic();
            $this->capturePayment($paymentArray['id'], $paymentArray['amount']);

            $paymentEntity = $this->getDbLastPayment();
        }

        return $this->getEntityById('payment', $paymentEntity->getId(), true);
    }

    private function getNewRefundEntity($captured = false)
    {
        $payment = $this->getNewPaymentEntity(false, $captured);

        $this->gateway = $payment['gateway'];

        $this->refundPayment($payment['id']);

        return $this->getDbLastRefund()->toArrayAdmin();
    }

    private function doCredPayment()
    {
        $this->gateway = 'cred';
        $this->payment = $this->getDefaultCredPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $this->payment
        ];

        $this->ba->publicAuth();
        $this->makeRequestAndGetContent($request);
        $payment = $this->getLastPayment('payment', 'true');
        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);
        $this->makeS2SCallbackAndGetContent($content, 'cred');
        $this->capturePayment($payment['id'], $payment['amount']);

        return $payment;
    }

    private function overrideFirstDataPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['first_data'];

        $facade[FDPaymentRecon::COLUMN_RZP_ENTITY_ID] = strtoupper(PublicEntity::stripDefaultSign($payment['id']));
        $facade[FDPaymentRecon::COLUMN_AUTH_CODE]       = random_integer(6);
        $facade[FDPaymentRecon::COLUMN_ARN]             = str_random(24);

        return array_merge($facade, $forceOverride);
    }

    private function overrideFirstDataNonInrPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['first_data'];

        $facade[FDPaymentRecon::COLUMN_RZP_ENTITY_ID]                  = strtoupper(PublicEntity::stripDefaultSign($payment['id']));
        $facade[FDPaymentRecon::COLUMN_AUTH_CODE]                      = random_integer(6);
        $facade[FDPaymentRecon::COLUMN_ARN]                            = str_random(24);
        $facade[FDPaymentRecon::COLUMN_CURRENCY]                       = 'USD';
        $facade[FDPaymentRecon::COLUMN_PAYMENT_AMOUNT]                 = 70;
        $facade[FDPaymentRecon::COLUMN_INTERNATIONAL_PAYMENT_AMOUNT]   = 1;

        return array_merge($facade, $forceOverride);
    }

    private function overrideHdfcPayment(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->testData['facades']['hdfc'];

        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = $payment['payment_id'];
        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE]  = "'" . random_integer(6);
        $facade[HDFCPaymentRecon::COLUMN_ARN]        = "'" . str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[HDFCPaymentRecon::COLUMN_TERMINAL_NUMBER] = "'89050258";
        }

        return $facade;
    }

    private function overrideYesBankPayment(array $payment)
    {
        $facade = $this->testData['facades']['yes_bank'];

        $facade['MTX ID'] = substr($payment['id'], 4, 14);

        return $facade;
    }

    private function overrideYesBankRefund($refundId)
    {
        $facade = $this->testData['facades']['yes_bank'];

        $facade['MTX ID'] = $refundId;

        $facade['TRANSACTION TYPE'] = 'REFUND';

        return $facade;
    }

    private function overrideAxisPayment(array $payment, array $forceOverride = [], $gateway = 'migs')
    {
        $facade = $this->testData['facades']['axis'];

        $facade[AxisPaymentRecon::COLUMN_PAYMENT_ID[0]] = $payment['payment_id'];
        $facade[AxisPaymentRecon::COLUMN_AUTH_CODE]     = random_integer(6);
        $facade[AxisPaymentRecon::COLUMN_ARN]           = str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[AxisPaymentRecon::COLUMN_MID] = 'RAZORPAYCYBS';
            $facade[AxisPaymentRecon::COLUMN_ORDER_ID] = $payment['ref'];
        }

        return $facade;
    }

    private function overrideAxisRefund(array $refund, array $forceOverride = [], $gateway = 'migs')
    {
        $facade = $this->testData['facades']['axis'];

        $facade[AxisRefundRecon::COLUMN_PAYMENT_ID[0]] = $refund['refund_id'];
        $facade[AxisRefundRecon::COLUMN_ARN]           = str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[AxisRefundRecon::COLUMN_MID] = 'RAZORPAYCYBS';
            $facade[AxisRefundRecon::COLUMN_ORDER_ID] = $refund['ref'];
        }

        return $facade;
    }

    private function overrideFreechargePayment(array $payment, array $forceOverride = [], $gateway = 'freecharge')
    {
        $facade = $this->testData['facades']['freecharge'];

        $facade[0][FreechargePaymentRecon::COLUMN_PAYMENT_ID] = 'pay_' . $payment['payment_id'];

        return $facade;
    }

    private function overrideVirtualAccYesBankPayment($account, $payment)
    {
        $facade = $this->testData['facades']['virtual_yes_bank'];

        $facade[VirtualAccYesBank::COLUMN_UTR]           = $payment['transaction_id'];
        $facade[VirtualAccYesBank::COLUMN_PAYEE_ACCOUNT] = $account['receivers'][0]['account_number'];

        return $facade;
    }

    private function overrideVirtualAccRblPayment($account, $payment, $mode = 'NEFT')
    {
        $facade = $this->testData['facades']['virtual_acc_rbl'];

        // For IMPS or UPI txn, UTR is not present in UTR_number column,
        // rather it is mentioned in RRN number column.
        // e.g. IMPS 001234567811 FROM SK ENTERPRISES
        // or
        // UPI/006752404360/PAYMENT FROM PHONEPE/8199080070@Y
        if ($mode === 'IMPS')
        {
            $facade[VirtualAccRbl::COLUMN_TRANSACTION_TYPE] = 'IMPS';
            $facade[VirtualAccRbl::COLUMN_RRN_NUMBER]       = 'IMPS ' . $payment['transaction_id'] . ' FROM ENTERPRISES';
        }
        else if ($mode === 'UPI')
        {
            $facade[VirtualAccRbl::COLUMN_TRANSACTION_TYPE] = 'IMPS';
            $facade[VirtualAccRbl::COLUMN_RRN_NUMBER]       = 'UPI/' . $payment['transaction_id'] . '/PAYMENT FROM PHONEPE/8199080070@Y';
        }
        else
        {
            // For NEFT/RTGS, utr directly present in the column
            $facade[VirtualAccRbl::COLUMN_TRANSACTION_TYPE] = 'N';
            $facade[VirtualAccRbl::COLUMN_UTR]              = $payment['transaction_id'];
        }

        $facade[VirtualAccRbl::COLUMN_PAYEE_ACCOUNT] = $account['receivers'][0]['account_number'];

        return $facade;
    }

    private function overrideHdfcRefund(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($payment, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = $payment['refund_id'];
        $facade[HdfcRefundRecon::COLUMN_GATEWAY_TRANSACTION_ID] = $payment['gateway_transaction_id'];

        return $facade;
    }

    private function overrideFirstDataRefund(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideFirstDataPayment($payment, $forceOverride);

        $facade['transaction_type'] = 'REFUND (CREDIT)';

        $facade['ft_no'] = $payment['gateway_transaction_id'] ?? str_random(12);

        return $facade;
    }

    private function overrideCardFssPayment($gatewayPayment)
    {
        $facade = $this->testData['facades']['card_fss_payment'];

        $facade['payment gateway payment transaction id'] = $gatewayPayment[CardFssEntity::GATEWAY_PAYMENT_ID];
        $facade['transaction amount']                     = number_format($gatewayPayment[CardFssEntity::AMOUNT] / 100, 2, '.', '');
        $facade['merchant track id']                      = $gatewayPayment[CardFssEntity::PAYMENT_ID];
        $facade['RRN']                                    = $gatewayPayment[CardFssEntity::REF];
        $facade['Auth/Approval Code']                     = $gatewayPayment[CardFssEntity::AUTH];
        $facade['payment gateway transaction id']         = $gatewayPayment[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['MSF Amount']                             = $facade['transaction amount'] * 0.009 * (-1);
        $facade['GST On MSF']                             = $facade['MSF Amount'] / 5.6;
        $facade['settlement amount']                      = $facade['transaction amount'] - $facade['MSF Amount'] - $facade['GST On MSF'];
        $facade['Action Code']                            = 'Random String';

        return $facade;
    }

    private function overrideCardFssRefund($gatewayRefund, $gatewayPayment)
    {
        $facade = $this->testData['facades']['card_fss_refund'];

        $facade['Aggregator Transaction ID']       = $gatewayRefund[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['Transaction Date']                = Carbon::createFromTimestamp($gatewayPayment[CardFssEntity::CREATED_AT], Timezone::IST)->format('d/m/Y h:i:s');
        $facade['Action Code']                     = 'Credit';
        $facade['transaction_amount']              = number_format($gatewayPayment[CardFssEntity::AMOUNT] / 100, 2, '.', '');
        $facade['Merchant Track Id']               = $gatewayRefund[CardFssEntity::REFUND_ID];
        $facade['Original Transaction Id']         = $gatewayPayment[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['aggregator_request_sent_time']    = $facade['Transaction Date'];
        $facade['merchant_response_sent_time']     = $facade['Transaction Date'];
        $facade['Reference Tran Id']               = $gatewayRefund[CardFssEntity::REF];
        $facade['Transaction Type']                = 'Random';
        $facade['Merchant Track Id']               .='1';

        return $facade;
    }

    private function overrideHdfcOnusRefund(array $refund, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($refund, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HdfcRefundRecon::COLUMN_ARN]       = "'(Onus transaction)";
        $facade[HdfcRefundRecon::COLUMN_REFUND_ID] = $refund['refund_id'];
        $facade[HdfcRefundRecon::COLUMN_GATEWAY_TRANSACTION_ID] = $refund['gateway_transaction_id'];

        return $facade;
    }

    private function overrideHdfcCybersourceOnusRefund(array $refund, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->testData['facades']['hdfc_cybersource'];

        $facade[HdfcRefundRecon::COLUMN_REFUND_ID]   = $refund['refund_id'];
        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE]  = "'" . random_integer(6);

        return $facade;
    }

    private function overrideHdfcNonInrPayment(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->testData['facades']['hdfc_non_inr'];

        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = $payment['payment_id'];

        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE]  = "'" . random_integer(6);

        $facade[HDFCPaymentRecon::COLUMN_ARN]        = "'" . str_random(24);

        return $facade;
    }

    private function overrideBilldeskRefund(array $refund)
    {
        $facade = $this->testData['facades']['billdesk_refund'];

        $facade[BilldeskRefundRecon::COLUMN_REFUND_ID]  = $refund['RefundId'];
        $facade[BilldeskRefundRecon::COLUMN_PAYMENT_ID] = $refund['payment_id'];
        $facade['ref_2']                                = $refund['payment_id'];

        return $facade;
    }

    private function overrideBillDeskPayment(array $gatewayPayment)
    {
        $facade = $this->testData['facades']['billdesk_payment'];

        $facade[BilldeskPaymentRecon::COLUMN_PAYMENT_ID]     = $gatewayPayment['payment_id'];
        $facade[BilldeskPaymentRecon::COLUMN_PAYMENT_AMOUNT] = $gatewayPayment['TxnAmount'];

        return $facade;
    }

    private function overrideAtomPayment(array $gatewayPayment, string $format = 'atom')
    {
        $facade = $this->testData['facades'][$format];

        $facade['Atom Txn Id']           = $gatewayPayment['gateway_payment_id'];
        $facade['Merchant Txn Id']       = $gatewayPayment['payment_id'];
        $facade['Bank Ref No']           = $gatewayPayment['bank_payment_id'];
        $facade['Gross Txn Amount']      = $gatewayPayment['amount'] / 100;
        $facade['Txn Charges']           = (float) $facade['Gross Txn Amount'] * 1.1;
        $facade['GST (18%)']             = (float) $facade['Gross Txn Amount'] * 0.002;
        $facade['Bank / Card Name']      = $gatewayPayment['bank_name'];
        $facade['Net Amount to be Paid'] = $facade['GST (18%)'] + $facade['Txn Charges'];
        $facade['Settlement Date']       = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)->format('d-M-Y h:i:s');
        $facade['Txn Date']              = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)->format('d-M-Y h:i:s');
        $facade['Refund Status']         = '';

        return $facade;
    }

    private function overrideAtomRefund(array $gatewayPayment, $refundAmount, $format = 'atom')
    {
        $facade = $this->overrideAtomPayment($gatewayPayment, $format);

        $facade['Txn State']        = 'Full Refund';
        $facade['Gross Txn Amount'] = $refundAmount;

        return $facade;
    }

    private function overrideWorldlineBqrPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['worldline_bqr_payment'];
        $facade[VasAxisPaymentRecon::COLUMN_PAYMENT_AMOUNT] = $payment[WorldlineEntity::TXN_AMOUNT];
        $facade[VasAxisPaymentRecon::COLUMN_AUTH_CODE]      = $payment[WorldlineEntity::AUTH_CODE];
        $facade[VasAxisPaymentRecon::COLUMN_RRN]            = $payment[WorldlineEntity::REF_NO];
        $facade[VasAxisPaymentRecon::COLUMN_GATEWAY_AMOUNT] = intval($payment[WorldlineEntity::TXN_AMOUNT] * 0.8);
        $facade[VasAxisPaymentRecon::COLUMN_GATEWAY_UTR]    = str_random(8);

        return array_merge($facade, $forceOverride);
    }

    private function overrideHitachiPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['hitachi'];
        $facade[HitachiPaymentRecon::COLUMN_PAYMENT_ID]     = $payment['payment_id'];
        $facade[HitachiPaymentRecon::COLUMN_PAYMENT_AMOUNT] = intval($payment['amount'] / 100);
        $facade[HitachiPaymentRecon::COLUMN_AUTH_CODE]      = $payment['pAuthID'];
        $facade[HitachiPaymentRecon::COLUMN_ARN]            = str_random(24);
        $facade[HitachiPaymentRecon::COLUMN_CURRENCY_CODE]  = '356';

        return array_merge($facade, $forceOverride);
    }

    private function overrideFulcrumPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['fulcrum'];
        $facade[FulcrumPaymentRecon::COLUMN_PAYMENT_ID]     = '00';
        $facade[FulcrumPaymentRecon::COLUMN_PAYMENT_AMOUNT] = intval($payment['amount'] / 100);
        $facade[FulcrumPaymentRecon::COLUMN_AUTH_CODE]      = $payment['pAuthID'];
        $facade[FulcrumPaymentRecon::COLUMN_ARN]            = str_random(24);
        $facade[FulcrumPaymentRecon::COLUMN_CURRENCY_CODE]  = '356';

        return array_merge($facade, $forceOverride);
    }

    // Needed for Batch service recon flow test, where we get raw
    // row data, with un-normalized headers
    private function overrideHitachiPaymentUnnormalized(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['hitachi_un_normalized'];
        $facade['Transaction type']         = '00';
        $facade['Invoice Number']           = $payment['payment_id'];
        $facade['Amount (In Paise)']        = intval($payment['amount'] / 100);
        $facade['Auth ID']                  = $payment['pAuthID'];
        $facade['ARN']                      = str_random(24);
        $facade['Tran Currency Code']       = '356';

        return array_merge($facade, $forceOverride);
    }

    private function overrideHitachiPaymentManualReconFile(array $payment)
    {
        $row = [
            ManualReconciliate::RECON_TYPE      => Reconciliate::PAYMENT,
            ManualReconciliate::RECON_ID        => $payment['payment_id'],
            ManualReconciliate::AMOUNT          => intval($payment['amount'] / 100),
            Reconciliate::GATEWAY_FEE           => intval($payment['amount'] / 1000),
            Reconciliate::GATEWAY_SERVICE_TAX   => intval($payment['amount'] / 2000),
            Reconciliate::ARN                   => '1234567890',
        ];

        return $row;
    }

    private function overrideHitachiRefund(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideHitachiPayment($payment, $forceOverride);

        $facade['message_type'] = '0220';
        $facade['transaction_type'] = '20';
        $facade[HitachiRefundRecon::COLUMN_REFUND_ID] = $payment['refund_id'];

        return $facade;
    }

    private function overrideFulcrumRefund(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideFulcrumPayment($payment, $forceOverride);

        $facade['message_type'] = '0220';
        $facade['transaction_type'] = '20';

        return array_merge($facade, $forceOverride);
    }

    private function overrideHitachiRefundUnnormalized(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideHitachiPaymentUnnormalized($payment, $forceOverride);

        $facade['Message type']     = '0220';
        $facade['Transaction type'] = '20';
        $facade['Invoice Number']   = $payment['refund_id'];

        return $facade;
    }

    private function overrideOlamoneyPayment(array $payment)
    {
        $facade = $this->testData['facades']['olamoney'];

        $facade['Unique Bill Id'] = $payment['payment_id'];

        return $facade;
    }

    private function overrideOlamoneyRefund(array $refund)
    {
        $facade = $this->testData['facades']['olamoney'];

        $facade['Unique Bill Id'] = str_replace('rfnd_','',$refund['refund_id']);

        $facade['Transaction Type'] = 'refund';

        return $facade;
    }

    private function overrideMobikwikPayment(array $payment)
    {
        $facade = $this->testData['facades']['mobikwik'];

        $paymentId = str_replace('pay_', '', $payment['id']);

        $facade['OrderID'] = '"""'. $paymentId;

        return $facade;
    }

    private function overrideMobikwikRefund(array $payment, $refundAmount)
    {
        $facade = $this->overrideMobikwikPayment($payment);

        $facade['Status']       = 'Refund adjusted';

        $facade['RefundAmount'] = $refundAmount;

        return $facade;
    }

    private function overrideVirtualAccIciciPayment($account, $payment)
    {
        $facade = $this->testData['facades']['virtual_acc_icici'];

        $facade[VirtualAccIcici::UTR] = $payment['transaction_id'];

        $facade[VirtualAccIcici::VAN] = $account['receivers'][0]['account_number'];

        return $facade;
    }

    protected function runForFiles(array $files,
                                   string $gateway,
                                   array $forceUpdate = [],
                                   array $forceAuthorizePayments = [],
                                   $manualFile = false,
                                   $response = null)
    {
        $this->ba->h2hAuth();

        $testData = $this->testData['reconciliate'];

        $testData['request']['content']['gateway'] = $gateway;
        $testData['request']['content']['attachment-count'] = count($files);

        foreach ($files as $index => $file)
        {
            $testData['request']['files']['attachment-' . ($index + 1)] = $this->createUploadedFile($file);
        }

        if ($manualFile === true)
        {
            $testData['request']['content'][Base::MANUAL_RECON_FILE] = 1;
        }

        if (empty($forceUpdate) === false)
        {
            foreach ($forceUpdate as $forceUpdateColumn)
            {
                $testData['request']['content']['force_update'][] = $forceUpdateColumn;
            }
        }

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $testData['request']['content'][Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        $this->runRequestResponseFlow($testData);
    }

    public function runWithData($entries)
    {
        $this->ba->batchAuth();

        $testData = $this->testData['bulk_reconcile_via_batch_service'];

        $testData['request']['content']= $entries;

        $this->runRequestResponseFlow($testData);
    }

    public function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            basename($url),
            $mime,
            null,
            true);
    }

    private function getNewWalletEntity($merchantId, $wallet)
    {
        $this->fixtures->merchant->enableWallet($merchantId, $wallet);

        $payment = $this->getDefaultWalletPaymentArray($wallet);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getDbLastEntityPublic('payment');

        return $gatewayPayment;
    }

    public function testHitachiReconViaBatchServiceRoute()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment1 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $entries[] = $this->overrideHitachiPaymentUnnormalized($gatewayPayment1, ['Auth ID' => $payment1['reference2']]);

        $payment2 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment2 = $this->getLastEntity('hitachi', true);

        $entries[] = $this->overrideHitachiPaymentUnnormalized($gatewayPayment2, ['Auth ID' => $payment2['reference2']]);

        $refund1 = $this->getNewRefundEntity(true);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hitachi');

        $this->assertNull($refund1['reference1']);

        $entries[] = $this->overrideHitachiRefundUnnormalized($gatewayPayment1);

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'Hitachi';
            $entry[Constants::SUB_TYPE]         = 'combined';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        $updatedPayment1 = $this->getEntityById('payment', $payment1['id'], true);

        $this->assertEquals($entries[0]['ARN'], $updatedPayment1['reference1']);

        $this->assertEquals($entries[0]['Auth ID'], $updatedPayment1['reference2']);

        $updatedTransaction1 = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $updatedPayment2 = $this->getEntityById('payment', $payment2['id'], true);

        $this->assertEquals($entries[1]['ARN'], $updatedPayment2['reference1']);

        $this->assertEquals($entries[1]['Auth ID'], $updatedPayment2['reference2']);

        $updatedTransaction2 = $this->getEntityById('transaction', $updatedPayment2['transaction_id'], true);

        $this->assertNotNull($updatedTransaction2['reconciled_at']);

        $this->assertTrue($updatedPayment2['gateway_captured']);

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[2]['ARN'], $updatedRefund1['reference1']);

        $updatedTransaction3 = $this->getEntityById('transaction', $updatedRefund1['transaction_id'], true);

        $this->assertNotNull($updatedTransaction3['reconciled_at']);
    }

    public function testHitachiForceAuthFailedPaymentViaBatchService()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment['reference1']);

        $row = $this->overrideHitachiPayment($gatewayPayment1, ['auth_id' => $payment['reference2']]);

        // set the payment status to 'failed' and try to reconcile it with force authorize
        $this->fixtures->edit('payment', $gatewayPayment1['payment_id'], ['status' => Payment\Status::FAILED]);

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($updatedPayment['status'], Payment\Status::FAILED);

        // add extra column to indicate we want to force auth this payment
        $row[PaymentReconciliate::RZP_FORCE_AUTH_PAYMENT] = 1;

        $entries[] = $row;

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'Hitachi';
            $entry[Constants::SUB_TYPE]         = 'combined';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        $updatedPayment2 = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment2['reference2']);

        $this->assertTrue($updatedPayment2['gateway_captured']);

        $this->assertEquals('authorized', $updatedPayment2['status']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(function ($mid, $feature, $mode)
                          {
                              if ($feature === Constants::BATCH_SERVICE_RECONCILIATION_MIGRATION)
                              {
                                  return 'on';
                              }
                              return 'off';
                          }));
    }

    public function testFulcrumPaymentRecon()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn']);

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment1 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->fixtures->edit('payment', $gatewayPayment1['payment_id'],
            [
                'gateway'   => 'fulcrum',
                'cps_route' => 2
            ]);

        $entries[] = $this->overrideFulcrumPayment($gatewayPayment1, ['auth_id' => $payment1['reference2']]);

        $file = $this->writeToExcelFile($entries, 'Fulcrum');

        $cpsResponse = [
                $entries[0][FulcrumPaymentRecon::COLUMN_RRN] => [
                    'authorization' => [
                        'payment_id' => $gatewayPayment1['payment_id']
                    ]
                ]
        ];

        $cpsMock = $this->getMockBuilder(CardPaymentService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['fetchPaymentIdFromCapsPIDs'])
            ->getMock();

        $this->app->instance('card.payments', $cpsMock);

        $this->app['card.payments']->method('fetchPaymentIdFromCapsPIDs')->willReturn($cpsResponse);

        $this->runForFiles([$file], 'Fulcrum');

        $updatedPayment1 = $this->getEntityById('payment', $payment1['id'], true);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertEquals($entries[0][FulcrumPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FulcrumPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFulcrumRefundRecon()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn']);

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $refund1 = $this->getNewRefundEntity(true);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hitachi');

        $this->assertNull($refund1['reference1']);

        $this->fixtures->edit('payment', $gatewayPayment1['payment_id'],
            [
                'gateway'   => 'fulcrum',
                'cps_route' => 2
            ]);

        $entries[] = $this->overrideFulcrumRefund($gatewayPayment1,
            ['invoice_number' => PublicEntity::stripDefaultSign($refund1['id'])]);

        $file = $this->writeToExcelFile($entries, 'Fulcrum');

        $cpsResponse = [
            $entries[0][FulcrumPaymentRecon::COLUMN_RRN] => [
                'authorization' => [
                    'payment_id' => $gatewayPayment1['payment_id']
                ]
            ]
        ];

        $cpsMock = $this->getMockBuilder(CardPaymentService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['fetchPaymentIdFromCapsPIDs'])
            ->getMock();

        $this->app->instance('card.payments', $cpsMock);

        $this->app['card.payments']->method('fetchPaymentIdFromCapsPIDs')->willReturn($cpsResponse);

        $this->runForFiles([$file], 'Fulcrum');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][FulcrumPaymentRecon::COLUMN_ARN], $updatedRefund1['reference1']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn']);

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment1 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideHitachiPayment($gatewayPayment1, ['auth_id' => $payment1['reference2']]);

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $updatedPayment1 = $this->getEntityById('payment', $payment1['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    // Test that recon batch create request sent to batch service and
    // batch is getting created on batch service side.
    public function testHitachiReconFileForwardToBatchService()
    {
        $this->mockRazorX();

        $entries[] = $this->testData['facades']['hitachi'];

        $response = $this->testData['batch_service_response'];

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi', [], [], false, $response);
    }

    // Tests Hitachi international payment recon
    public function testHitachiReconNonInrPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn',
        ]);
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment = $this->getNewPaymentEntity(false,true);

        // Making the payment entity's amount as half (suppose USD to INR rate was 50%)
        $usdAmount = $payment['amount']/2;

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        // Make it international, convert currency as false
        $this->fixtures->edit(
            'payment',
            $gatewayPayment['payment_id'],
            [
                'international'     => true,
                'convert_currency'  => false,
                'currency'          => 'USD',
                'amount'            => $usdAmount,
            ]);

        $this->assertNull($payment['reference1']);

        $row = $this->overrideHitachiPayment($gatewayPayment, ['auth_id' => $payment['reference2']]);

        $row[HitachiPaymentRecon::COLUMN_PAYMENT_AMOUNT] /= 2;
        $row[HitachiPaymentRecon::COLUMN_CURRENCY_CODE] = '840';

        $entries[] = $row;

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $updatedPayment['reference1']);
        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment['reference2']);

        $this->assertTrue($updatedPayment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiUnexpectedPaymentCreateViaRecon()
    {
        $this->markTestSkipped();
        // Using Live because by default mode is live (when gateway != sharp
        // Refer :  function determineAndSetModeForQr()
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:vpa_shared_terminal_icici');

        $reconRow = $this->testData['facades']['hitachi_unexpected_payment_create'];

        $this->fixtures->on('live')->create('terminal', [
            'gateway_merchant_id'   => $reconRow['merchant_id'],
            'gateway'               => 'hitachi',
            'gateway_acquirer'      => 'ratn',
        ]);

        $this->fixtures->edit(
            'merchant',
            '10000000000000',
            [
                'activated'         => 1,
                'live'              => 1,
                'pricing_plan_id'   => '1hDYlICobzOCYt'
            ]);

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $bharatQr = $this->getDbLastEntity('bharat_qr', 'live');

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals($payment['id'], $bharatQr['payment_id']);

        $this->assertEquals($reconRow['retr_ref_nr'], $bharatQr['provider_reference_id']);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $payment['reference1']);
        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $payment['reference2']);

        $transactionEntity = $this->getDbLastEntity('transaction', 'live');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertTrue($payment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiForceAuthorizeFailedPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn'
        ]);
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment['reference1']);

        $entries[] = $this->overrideHitachiPayment($gatewayPayment1, ['auth_id' => $payment['reference2']]);

        $file = $this->writeToExcelFile($entries, 'hitachi');

        // set the payment status to 'failed' and try to reconcile it with force authorize
        $this->fixtures->edit('payment', $gatewayPayment1['payment_id'], ['status' => Payment\Status::FAILED]);

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($updatedPayment['status'], Payment\Status::FAILED);

        $this->runForFiles([$file], 'Hitachi', [], [$payment['id']]);

        $updatedPayment2 = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment2['reference2']);

        $this->assertTrue($updatedPayment2['gateway_captured']);

        $this->assertEquals('authorized', $updatedPayment2['status']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    // For payments being routed via Card Payment Service
    public function testHitachiForceAuthorizeFailedCpsPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'gateway_acquirer' => 'ratn'
        ]);
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment = $this->getNewPaymentEntity(false,true);

        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment['reference1']);

        $entries[] = $this->overrideHitachiPayment($gatewayPayment, ['auth_id' => $payment['reference2']]);

        $file = $this->writeToExcelFile($entries, 'hitachi');

        // set the payment cps_route to 2 and status to 'failed' and
        // try to reconcile it with force authorize
        $this->fixtures->edit(
            'payment',
            $gatewayPayment['payment_id'],
            [
                'status'    => Payment\Status::FAILED,
                'cps_route' => 2,
            ]);

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($updatedPayment['status'], Payment\Status::FAILED);

        $this->runForFiles([$file], 'Hitachi', [], [$payment['id']]);

        $updatedPayment2 = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment2['reference2']);

        $this->assertTrue($updatedPayment2['gateway_captured']);

        $this->assertEquals('authorized', $updatedPayment2['status']);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $payment['reference2']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testWorldlineReconPaymentFile()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:bharat_qr_worldline_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will', 'virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->payViaBharatQr($qrCode['id'], 'worldline');

        $payment = $this->getDbLastEntity('payment');

        $this->assertNull($payment['reference1']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $gatewayPayment = $this->getLastEntity('worldline', true);

        $entries[] = $this->overrideWorldlineBqrPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'vas_axis', 'files/settlement', 'Settle Detail');

        // VasAxis is the recon gateway for this
        $this->runForFiles([$file], 'VasAxis');

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][VasAxisPaymentRecon::COLUMN_RRN], $updatedPayment['reference1']);
        $this->assertEquals($entries[0][VasAxisPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment['reference2']);

        $this->assertTrue($updatedPayment['gateway_captured']);

        $updatedGatewayPayment = $this->getLastEntity('worldline', true);

        $this->assertEquals($entries[0][VasAxisPaymentRecon::COLUMN_AUTH_CODE], $updatedGatewayPayment['auth_code']);
        $this->assertEquals($entries[0][VasAxisPaymentRecon::COLUMN_GATEWAY_UTR], $updatedGatewayPayment['gateway_utr']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertEquals(($entries[0][VasAxisPaymentRecon::COLUMN_PAYMENT_AMOUNT] * 100), $transactionEntity['gateway_amount']);
        $this->assertNotNull($transactionEntity['gateway_settled_at']);
        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiBharatQrRecon()
    {
        $this->markTestSkipped();
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->payViaBharatQr($qrCode['id'], 'hitachi');

        $payment = $this->getDbLastEntity('payment');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $entries[] = $this->testData['facades']['hitachi'];

        $entries[0][HitachiPaymentRecon::COLUMN_TERMINAL_NUMBER] = '38R00450';

        $entries[0][HitachiPaymentRecon::COLUMN_PAYMENT_AMOUNT] = $payment['amount'] / 100;

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    //
    // Test for manually prepared recon file.
    // Here we have taken Hitachi payment to test card payment recon
    //
    public function testHitachiManualReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment1 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideHitachiPaymentManualReconFile($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi', [], [], true);

        $updatedPayment1 = $this->getEntityById('payment', $payment1['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiReconRefundFile()
    {
        $this->gateway = 'hitachi';

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['pStatus'] = 'Error';
            }
        });

        $refund1 = $this->getNewRefundEntity(true);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hitachi');

        $this->assertNull($refund1['reference1']);

        $entries[] = $this->overrideHitachiRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'hitachi');
        $this->runForFiles([$file], 'Hitachi');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HitachiRefundRecon::COLUMN_ARN], $updatedRefund1['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssOnusTransactionRecon()
    {
        $this->gateway = 'hdfc';

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->getNewRefundEntity(true);
        $gatewayRefund = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($refund[Refund\Entity::REFERENCE1]);

        $entries[] = $this->overrideHdfcOnusRefund($gatewayRefund);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund = $this->getDbEntityById('refund', $refund['id'])->toArrayAdmin();

        $updatedGatewayRefund = $this->getDbLastEntityToArray('hdfc');

        $this->assertEquals($gatewayRefund['ref'], $updatedRefund[Refund\Entity::REFERENCE1]);

        $this->assertEquals($updatedRefund[Refund\Entity::REFERENCE1], $updatedGatewayRefund['arn_no']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssReconNonInrPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getDefaultPaymentArray();

        $payment1 = $this->doAuthAndCapturePayment($payment);

        $this->fixtures->edit('payment',
            $payment1['id'],
            [
                'convert_currency' => false,
            ]);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $entries[] = $this->overrideHdfcNonInrPayment($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment1['reference2']);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus();
    }

    /**
     * Test for failed reconciliation batch. Retrying will mark it processed.
     */
    public function testFailedReconBatchRetry()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getNewPaymentEntity(false, true);

        $entries[] = $this->overrideFirstDataPayment($payment);

        // Creating batch with failed status.
        $this->fixtures->create('batch:recon_with_failed_status', $entries);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as failed.
        $this->assertBatchStatus(Status::FAILED);

        // Retrying failed batch.
        $this->retryFailedBatch('batch_' . $batch['id']);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as 'Processed' and counts.
        $this->assertEquals(Status::PROCESSED, $batch['status']);
        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(1, $batch['processed_count']);
    }

    /**
     * Test for reconciliation batch which are stuck in created state, having processing = true.
     * Retrying will be allowed for such batches only if updated_at is older than the specified
     * time gap (i.e. currently set at 2 hours).
     */
    public function testInProcessingReconBatchRetry()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getNewPaymentEntity(false, true);

        $entries[] = $this->overrideFirstDataPayment($payment);

        // Creating batch with created status with processing as true.
        $this->fixtures->create('batch:recon_with_created_status_and_processing_true', $entries);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as created, and processing  = true.
        $this->assertBatchStatus(Status::CREATED);
        $this->assertEquals(1, $batch['processing']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(0, $batch['processed_count']);

        // Retrying In Processing batch.
        $this->retryFailedBatch('batch_' . $batch['id']);

        $batch = $this->getDbLastEntityToArray('batch');

        // Assert that the batch was not retried.
        $this->assertEquals(Status::CREATED, $batch['status']);
        $this->assertEquals(1, $batch['processing']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(0, $batch['processed_count']);

        // Now change the updated_at of batch to 2 hours older and then retry
        $timeGap = Batch\Type::$retryInProcessingBatchTypes['reconciliation'];

        $olderUpdatedAt = $batch['updated_at'] - $timeGap;

        $this->fixtures->edit('batch', $batch['id'], ['updated_at' => $olderUpdatedAt]);

        // Retrying In Processing batch, this time it will be allowed for retry.
        $this->retryFailedBatch('batch_' . $batch['id']);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as 'Processed' and counts.
        $this->assertEquals(Status::PROCESSED, $batch['status']);
        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(1, $batch['processed_count']);
    }

    public function testOlamoneyReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_olamoney_terminal', ['type' => ['non_recurring' => '1', 'ivr' => '1']]);

        $this->getNewWalletEntity('10000000000000', 'olamoney');

        $wallet = $this->getLastEntity('wallet', true);

        $entries[] = $this->overrideOlamoneyPayment($wallet);

        $file = $this->writeToCsvFile($entries, 'olamoney');

        $this->runForFiles([$file], 'Olamoney');

        $updatedPayment1 = $this->getEntityById('payment', $wallet['payment_id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertNotNull($updatedTransaction['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testOlamoneyReconRefundFile()
    {
        $this->fixtures->create('terminal:shared_olamoney_terminal', ['type' => ['non_recurring' => '1', 'ivr' => '1']]);

        $this->fixtures->merchant->enableWallet('10000000000000', 'olamoney');

        $payment = $this->getDefaultWalletPaymentArray('olamoney');

        $this->doAuthPayment($payment);

        $wallet = $this->getLastEntity('wallet', true);

        $this->gateway = 'wallet_olamoney';

        $this->mockServerContentFunction(function (& $content)
        {
            $content['status'] = 'error';

            return $content;
        });

        $this->refundAuthorizedPayment('pay_' . $wallet['payment_id']);

        $walletRefund = $this->getLastEntity('wallet', true);

        $entries[] = $this->overrideOlamoneyRefund($walletRefund);

        $file = $this->writeToCsvFile($entries, 'olamoney');

        $this->runForFiles([$file], 'Olamoney');

        $updatedRefund = $this->getDbEntityById('refund', $walletRefund['refund_id']);

        $updatedTransaction = $this->getDbEntityById('transaction', $updatedRefund['transaction_id']);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssReconBatchSummaryCount()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');



        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $row1 = $this->overrideHdfcPayment($gatewayPayment1);

        // payment2 : Exception case
        $payment2 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment2 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($payment2['reference1']);
        $this->assertNull($payment2['reference2']);

        $row2 = $this->overrideHdfcPayment($gatewayPayment2);
        // set the payment ID to some random 14 char string
        $row2[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = 'Abcde12345ABCD';

        // Payment3
        $payment3 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment3 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($payment3['reference1']);
        $this->assertNull($payment3['reference2']);

        $row3 = $this->overrideHdfcPayment($gatewayPayment3);

        $entries[] = $row1;
        $entries[] = $row2;
        $entries[] = $row3;

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $updatedTransaction1 = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        // Check failure count, Here exception should have occurred at 2nd row
        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['processed_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testMobikwikReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $gatewayPayment1 = $this->getNewWalletEntity('10000000000000', 'mobikwik');

        $entries[] = $this->overrideMobikwikPayment($gatewayPayment1);

        $file = $this->writeToCsvFile($entries, 'mobikwik');

        $this->runForFiles([$file], 'Mobikwik');

        $updatedPayment1 = $this->getEntityById('payment', $gatewayPayment1['id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testMobikwikCombinedUniqueEntityRecon()
    {
        $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $gatewayPayment = $this->getNewWalletEntity('10000000000000', 'mobikwik');

        $paymentData = $this->overrideMobikwikPayment($gatewayPayment);

        $refund1 = $this->refundPayment($gatewayPayment['id'], '10000');

        $refund2 = $this->refundPayment($gatewayPayment['id'], '20000');

        $refund3 = $this->refundPayment($gatewayPayment['id'], '20000');

        $refundData1 = $this->overrideMobikwikRefund($gatewayPayment, $refund1['amount']/100);

        $refundData2 = $this->overrideMobikwikRefund($gatewayPayment, $refund2['amount']/100);

        $refundData3 = $this->overrideMobikwikRefund($gatewayPayment, $refund3['amount']/100);

        $entries[] = $paymentData;

        $entries[] = $refundData1;

        $entries[] = $refundData2;

        $entries[] = $refundData3;

        $file = $this->writeToCsvFile($entries, 'mobikwik');

        $this->runForFiles([$file], 'Mobikwik');

        $updatedPayment = $this->getEntityById('payment', $gatewayPayment['id'], true);
        $refundEntity1 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund1['id']), true);
        $refundEntity2 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund2['id']), true);
        $refundEntity3 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund3['id']), true);

        $paymentTransaction  = $this->getEntityById('transaction', $updatedPayment['transaction_id'], true);
        $updatedTransaction1 = $this->getEntityById('transaction', $refundEntity1['transaction_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $refundEntity2['transaction_id'], true);
        $updatedTransaction3 = $this->getEntityById('transaction', $refundEntity3['transaction_id'], true);

        //
        // One payment and one refund row get reconciled, other 2 refunds
        // remain unreconciled, as we could not identify the refund uniquely.
        //
        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $this->assertNull($updatedTransaction2['reconciled_at']);
        $this->assertNull($updatedTransaction3['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(4, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(2, $batch['failure_count']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testHdfcBharatQrReconPayment()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $response = $this->payViaBharatQr($qrCode, 'isg');

        $entries[] = $this->testData['facades']['hdfc'];

        $entries[0]['merchant_trackid'] = $qrCode->getId();

        $entries[0]['card_type'] = 'BHARAT QR';

        $entries[0]['tran_id']  = "'" . $response['TXN_ID'];

        $entries[0]['domestic_amt'] = '1.00';

        $file = $this->writeToExcelFile($entries, 'HDFC-MPR');

        $this->runForFiles([$file], 'HDFC');

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcBharatQrReconForUnexpectedPayment()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $response = $this->payViaBharatQr($qrCode, 'isg');

        $entries[] = $this->testData['facades']['hdfc'];

        $entries[0]['merchant_trackid'] = 'random';

        $entries[0]['card_type'] = 'BHARAT QR';

        $entries[0]['tran_id']  = "'random";

        $entries[0]['domestic_amt'] = '1.00';

        $file = $this->writeToExcelFile($entries, 'HDFC-MPR');

        $this->runForFiles([$file], 'HDFC');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFssBobPaymentReconFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment1 = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment1['id'], ['ref' => null]);

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobRecon($gatewayPayment1, $gatewayPayment1['payment_id']);

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $response = $this->runForFiles([$file], 'CardFssBob');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);
        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        //Test that gateway entity value is updated from response
        $updatedGatewayEnity = $this->getDbLastEntityToArray('card_fss');

        $this->assertEquals('30-07-2018', $updatedGatewayEnity['postdate']);

        //Test We update payment reference2 from recon
        $paymentEnity = $this->getDbLastEntity('payment');
        $this->assertNotNull($paymentEnity['reference2']);
        $this->assertNotNull($paymentEnity['reference1']);

        $gatewayFee = Helper::getIntegerFormattedAmount(abs($entries[0]['MSF Amount']));
        $gst = Helper::getIntegerFormattedAmount(abs($entries[0]['GST']));

        // Test that the gateway fee and tax sum is as expected
        $this->assertEquals( $gatewayFee + $gst, $transactionEntity->getGatewayFee());

        $this->assertEquals($gst, $transactionEntity->getGatewayServiceTax());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFssSbiCombinedReconFile()
    {
        $this->fixtures->terminal->createSharedFssTerminal([], 'sbin');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment1 = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment1['id'], ['ref' => null]);

        $paymentArray = $this->getDbLastEntityToArray('payment');

        // Add first payment to recon file
        $entries[] = $this->overrideFssSbiRecon($paymentArray);

        $this->refundPayment($payment['id']);

        $refund1 = $this->getDbLastEntityToArray('refund');

        // Add refund for the first payment
        $entries[] = $this->overrideFssSbiRecon($refund1, 'Refund');

        // Make a second payment
        $payment = $this->getNewPaymentEntity(false, true);

        $this->refundPayment($payment['id']);

        $refund2 = $this->getDbLastEntityToArray('refund');

        // For the second refund, set an invalid amount to verify that the recon mismatch happens
        $entries[] = $this->overrideFssSbiRecon($refund2, 'Refund', true);

        $file = $this->writeToCsvFile($entries, 'IPAYMIS_MID_Date');

        $this->runForFiles([$file], 'CardFssSbi');

        $transactionEntity = $this->getDbEntity(
            'transaction',
            [
                'type' => 'payment',
                'entity_id' => $gatewayPayment1['payment_id']
            ])->toArray();

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);
        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertEquals(2, $transactionEntity['gateway_fee']);
        $this->assertEquals(1, $transactionEntity['gateway_service_tax']);

        // Test we update payment reference2 from recon for the first payment
        $paymentEntity = $this->getDbEntity('payment', ['id' => $gatewayPayment1['payment_id']])->first();
        $this->assertNotNull($paymentEntity['reference2']);

        $transactionEntity = $this->getDbEntity('transaction', ['type' => 'refund', 'entity_id' => $refund1['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $transactionEntity = $this->getDbEntity(
            'transaction',
            [
                'type' => 'refund',
                'entity_id' => $refund2['id']
            ]);

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testFssBobNewFormatPaymentReconFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment1 = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment1['id'], ['ref' => null]);

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobReconNewFormat($gatewayPayment1, $gatewayPayment1['payment_id']);

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $response = $this->runForFiles([$file], 'CardFssBob');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);
        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        //Test that gateway entity value is updated from response
        $updatedGatewayEnity = $this->getDbLastEntityToArray('card_fss');

        $this->assertEquals('2018-07-30', $updatedGatewayEnity['postdate']);

        //Test We update payment reference2 from recon
        $paymentEnity = $this->getDbLastEntity('payment');
        $this->assertNotNull($paymentEnity['reference2']);
        $this->assertNotNull($paymentEnity['reference1']);

        $gatewayFee = Helper::getIntegerFormattedAmount(abs($entries[0]['msfamount']));
        $gst = Helper::getIntegerFormattedAmount(abs($entries[0]['gst']));

        // Test that the gateway fee and tax sum is as expected
        $this->assertEquals( $gatewayFee + $gst, $transactionEntity->getGatewayFee());

        $this->assertEquals($gst, $transactionEntity->getGatewayServiceTax());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCardFssBobNewFormatPaymentReconViaBatchService()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment1 = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment1['id'], ['ref' => null]);

        $entries[] = $this->overrideFssBobReconNewFormat($gatewayPayment1, $gatewayPayment1['payment_id']);

        // add metadata info to each row
        foreach ($entries as $key => $entry)
        {
            $entry[Constants::GATEWAY]          = 'CardFssBob';
            $entry[Constants::SUB_TYPE]         = 'combined';
            $entry[Constants::SOURCE]           = 'manual';
            $entry[Constants::SHEET_NAME]       = 'sheet0';
            $entry[Constants::IDEMPOTENT_ID]    = 'batch_' . str_random(14);

            $entries[$key] = $entry;
        }

        $this->runWithData($entries);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);
        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        //Test that gateway entity value is updated from response
        $updatedGatewayEnity = $this->getDbLastEntityToArray('card_fss');

        $this->assertEquals('2018-07-30', $updatedGatewayEnity['postdate']);

        //Test We update payment reference2 from recon
        $paymentEnity = $this->getDbLastEntity('payment');
        $this->assertNotNull($paymentEnity['reference2']);
        $this->assertNotNull($paymentEnity['reference1']);

        $gatewayFee = Helper::getIntegerFormattedAmount(abs($entries[0]['msfamount']));
        $gst = Helper::getIntegerFormattedAmount(abs($entries[0]['gst']));

        // Test that the gateway fee and tax sum is as expected
        $this->assertEquals( $gatewayFee + $gst, $transactionEntity->getGatewayFee());

        $this->assertEquals($gst, $transactionEntity->getGatewayServiceTax());
    }

    public function testFssBobNewFormatRefundReconFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getDbLastEntity('refund');

        $gatewayRefund = $this->getDbLastEntityToArray('card_fss');

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobReconNewFormat($gatewayRefund, $refund['id'], 'Refund');

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $this->runForFiles([$file], 'CardFssBob');

        $refund = $this->getDbLastEntity('refund');

        $this->assertEquals($entries[0]['arn'], $refund['reference1']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);

        $gatewayRefund = $this->getDbLastEntityToArray('card_fss');

        $this->assertBatchStatus(Status::PROCESSED);
    }

    //
    // Tests recon and gateway data update for cardfssbob payment
    // which is being routed through Cards Payment Service (CPS).
    //
    public function testCardFssBobCpsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment['id'], ['ref' => null]);

        // Make cps route = 2
        $this->fixtures->payment->edit($payment['id'], ['cps_route' => 2]);

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobRecon($gatewayPayment, $gatewayPayment['payment_id']);

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $this->runForFiles([$file], 'CardFssBob');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        //Test We update payment reference2 from recon
        $paymentEnity = $this->getDbLastEntity('payment');
        $this->assertNotNull($paymentEnity['reference2']);
        $this->assertNotNull($paymentEnity['reference1']);

        $reconRrn = trim(str_replace("'", '', $entries[0]['Retrieval Reference Number'] ?? null));

        $this->assertEquals($reconRrn, $paymentEnity['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testMpgsIcicFDCpsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        // Make cps route = 2
        $this->fixtures->payment->edit($payment['id'], ['cps_route' => 2, 'gateway' => Gateway::MPGS]);

        $entries[] = $this->overrideFirstDataPayment($payment);
        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testMpgsIcicFDCpsReconRefundFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);


        // Make cps route = 2
        $this->fixtures->payment->edit($payment['id'], ['cps_route' => 2, 'gateway' => Gateway::MPGS]);

        $refund = $this->refundPayment($payment['id']);

        $this->assertNull($refund['acquirer_data']['arn']);


        $entries[] = $this->overrideFirstDataRefund($payment);
        $file = $this->writeToExcelFile($entries, 'first_data');

        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($entries[0]['ft_no'], '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($payment['id']),
                        'refund_id'      => PublicEntity::stripDefaultSign($refund['id'])
                    ]
                ]
            ]
        ];
        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
            ->getMock();
        $this->app->instance('scrooge', $scroogeMock);
        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

        $this->runForFiles([$file], 'FirstData');

        $updatedTransaction1 = $this->getDbEntity(
            'transaction',
            [
                'type'      => 'refund',
                'entity_id' => PublicEntity::stripDefaultSign($refund['id'])
            ])->toArray();

        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testIsgCpsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment = $this->getDbLastEntityToArray('card_fss');

        // Make cps route = 2
        $this->fixtures->payment->edit($payment['id'], ['cps_route' => 2]);
        $this->fixtures->edit('card_fss', $gatewayPayment['id'], ['ref' => null]);
        $this->gateway = 'isg';

        $entries[] = $this->overrideIsgRecon($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'Purchase_MPR');

        $response = $this->runForFiles([$file], 'Isg');
        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testIsgCpsRefundReconFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getDbLastEntity('refund');

        $gatewayRefund = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->payment->edit($payment['id'], ['cps_route' => 2]);
        $this->gateway = 'isg';

        $entries[] = $this->overrideIsgRecon($gatewayRefund, 'refund', $refund['id']);

        $file = $this->writeToExcelFile($entries, 'Refund_MPR');

        $response = $this->runForFiles([$file], 'Isg');
        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcIsgBharatQrReconRefund()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->payViaBharatQr($qrCode, 'isg');

        $payment = $this->getDbLastEntity('payment');

        $refund = $this->refundPayment('pay_' . $payment['id']);

        $entries[] = $this->testData['facades']['hdfc'];

        $entries[0]['merchant_trackid'] = 'razorrfnd' . substr($refund['id'], 5);

        $entries[0]['rec_fmt'] = 'CVD';

        $entries[0]['domestic_amt'] = '1.00';

        $entries[0]['card_type'] = 'BHARAT QR';

        $file = $this->writeToExcelFile($entries, 'HDFC-MPR');

        $this->runForFiles([$file], 'HDFC');

        $refundEntity = $this->getEntityById('refund', $refund['id'], true);

        $transaction = $this->getEntityById('transaction', $refundEntity['transaction_id'], true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFssBobRefundRecon()
    {
        $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getDbLastEntity('refund');

        $gatewayRefund = $this->getDbLastEntityToArray('card_fss');

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobRecon($gatewayRefund, $refund['id'], 'Refund');

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $this->runForFiles([$file], 'CardFssBob');

        $refund = $this->getDbLastEntity('refund');

        $this->assertEquals('175309', $refund['reference1']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAmexPaymentRecon()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal', [
            'gateway_acquirer' => 'amex',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $this->getNewPaymentEntity(false, true);

        $gatewayPayment = $this->getDbLastEntityToArray('amex');

        $paymentData = $this->overrideAmexPayment($gatewayPayment);

        $entries[] = $paymentData;

        $entries[0]['Settlement date'] = '3-10-2018';

        $file = $this->writeToExcelFile($entries, 'Submission_details10032018_023644' , 'files/settlement',
                                        ['Sheet 1'], 'xlsx');

        $this->runForFiles([$file], 'Amex');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['settled_at']);

        $this->assertBatchStatus(Status::PROCESSED);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(1, $batch['total_count']);
    }

    public function testAmexPaymentReconFailureCount()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal', [
            'gateway_acquirer' => 'amex',
        ]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $this->getNewPaymentEntity(false, true);

        $gatewayPayment = $this->getDbLastEntityToArray('amex');

        $paymentData = $this->overrideAmexPayment($gatewayPayment);

        //changing payment amount to fail the recon

        $paymentData['Charge amount'] = '1.00';

        $entries[] = $paymentData;

        $file = $this->writeToExcelFile($entries, 'Submission_details10032018_023644' , 'files/settlement',
            ['Sheet 1'], 'xlsx');

        $this->runForFiles([$file], 'Amex');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);

        $this->assertEquals(1, $batch['failure_count']);
    }

    /**
     * Allowing recon for refunds which can be
     * uniquely identified by it's paymentId and
     * amount as we are not getting refund ID
     * or any unique identifier for refund in the MIS file.
     */
    public function testAmexCombinedUniqueEntityRecon()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $payment = $this->getNewPaymentEntity(false, true);

        $gatewayPayment = $this->getDbLastEntityToArray('amex');

        $paymentData = $this->overrideAmexPayment($gatewayPayment);

        $refund1 = $this->refundPayment($payment['id'], '10000');

        $refund2 = $this->refundPayment($payment['id'], '20000');

        $refund3 = $this->refundPayment($payment['id'], '20000');

        $refundData1 = $this->overrideAmexRefund($gatewayPayment, $refund1['amount']);

        $refundData2 = $this->overrideAmexRefund($gatewayPayment, $refund2['amount']);

        $refundData3 = $this->overrideAmexRefund($gatewayPayment, $refund3['amount']);

        $entries[] = $paymentData;

        $entries[] = $refundData1;

        $entries[] = $refundData2;

        $entries[] = $refundData3;

        $file = $this->writeToExcelFile($entries, 'Submission_details10032018_023644' , 'files/settlement',
                                        ['Sheet 1'], 'xlsx');

        $this->runForFiles([$file], 'Amex');

        $payment = $this->getEntityById('payment', PublicEntity::stripDefaultSign($payment['id']), true);
        $refundEntity1 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund1['id']), true);
        $refundEntity2 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund2['id']), true);
        $refundEntity3 = $this->getEntityById('refund', PublicEntity::stripDefaultSign($refund3['id']), true);

        $paymentTransaction  = $this->getEntityById('transaction', $payment['transaction_id'], true);
        $updatedTransaction1 = $this->getEntityById('transaction', $refundEntity1['transaction_id'], true);
        $updatedTransaction2 = $this->getEntityById('transaction', $refundEntity2['transaction_id'], true);
        $updatedTransaction3 = $this->getEntityById('transaction', $refundEntity3['transaction_id'], true);

        //
        // One payment and one refund row get reconciled, other 2 refunds
        // remain unreconciled, as we could not identify the refund uniquely.
        //
        $this->assertNotNull($paymentTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction1['reconciled_at']);

        $this->assertNull($updatedTransaction2['reconciled_at']);
        $this->assertNull($updatedTransaction3['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');


        $this->assertEquals(4, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(2, $batch['failure_count']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testOnHoldToggle()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('payment_onhold');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->fixtures->edit('payment', $payment['id'], [
            'on_hold' => true
        ]);

        $this->fixtures->edit('transaction', $payment['transaction_id'], [
            'on_hold' => true
        ]);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($payment['id'], 'pay_' . $paymentEntity->getId());

        $this->assertTrue($paymentEntity->getOnHold());

        $this->assertTrue($paymentEntity->transaction->getOnHold());

        $entries[] = $this->overrideFirstDataPayment($payment);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment = $this->getDbEntityById('payment' ,$payment['id']);

        $this->assertFalse($updatedPayment->getOnHold());

        $this->assertFalse($updatedPayment->transaction->getOnHold());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    // Tests Hitachi DCC payment recon
    public function testHitachiReconDCCPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');
        $this->fixtures->merchant->addFeatures(['dcc']);

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'Visa',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);

        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'iin' => $iin->getIin()],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $this->ba->privateAuth();
        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);
        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        // Payment with DCC Currency - USD
        $this->payment['dcc_currency'] = $cardCurrency;
        $this->payment['currency_request_id'] = $currencyRequestId;

        $payment = $this->getNewPaymentEntity(false,true);
        $gatewayPayment = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment['reference1']);
        $row = $this->overrideHitachiPayment($gatewayPayment,
                                [
                                    'auth_id' => $payment['reference2'],
                                    HitachiPaymentRecon::COLUMN_CURRENCY_CODE => Currency::ISO_NUMERIC_CODES[$gatewayPayment['currency']]
                                ]);
        $entries[] = $row;

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $updatedPayment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $updatedPayment['reference1']);
        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment['reference2']);

        $this->assertTrue($updatedPayment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCredPaymentReconciliation()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:direct_cred_terminal');
        $this->fixtures->merchant->enableApp('10000000000000', 'cred');

        $this->doCredPayment();

        $gatewayPayment = $this->getLastEntity('cred', true);

        $entries[] = $this->overrideCredPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'cred');

        $this->runForFiles([$file], 'Cred');
        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);
        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testVirtualAccIciciReconFile()
    {
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        $payment = $this->payVirtualAccount($account['id']);

        $paymentEntity = $this->getLastEntity('payment', true);
        $transaction = $this->getDbEntityById('transaction', $paymentEntity['transaction_id']);
        $this->assertNull($transaction['reconciled_at']);
        $this->assertNull($transaction['reconciled_type']);

        $entries[] = $this->overrideVirtualAccIciciPayment($account, $payment);

        $file = $this->writeToExcelFile($entries, 'virtualAccIcici', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccIcici');

        $this->assertBatchStatus(Status::PROCESSED);

        $updatedTransaction = $this->getDbEntityById('transaction', $paymentEntity['transaction_id']);

        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['reconciled_type']);
    }

    //
    // Tests recon and gateway data update for cardfssbob payment
    // which is being routed through Cards Payment Service (CPS).
    //
    public function testCardFssBobRearchReconPaymentFile()
    {
        $this->enablePgRouterConfig();

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' =>'GfnS1Fj048VHo2',
            'type' =>'payment',
            'merchant_id' =>'10000000000000',
            'amount' =>50000,
            'fee' =>1000,
            'mdr' =>1000,
            'tax' =>0,
            'pricing_rule_id' => NULL,
            'debit' =>0,
            'credit' =>49000,
            'currency' =>'INR',
            'balance' =>2025400,
            'gateway_amount' => NULL,
            'gateway_fee' =>0,
            'gateway_service_tax' =>0,
            'api_fee' =>0,
            'gratis' =>FALSE,
            'fee_credits' =>0,
            'escrow_balance' =>0,
            'channel' =>'axis',
            'fee_bearer' =>'platform',
            'fee_model' =>'prepaid',
            'credit_type' =>'default',
            'on_hold' =>FALSE,
            'settled' =>FALSE,
            'settled_at' =>1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' =>'10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' =>TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' =>1614262078,
            'updated_at' =>1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id' =>'10000000000000',
                'name' =>'Harshil',
                'expiry_month' =>12,
                'expiry_year' =>2024,
                'iin' =>'401200',
                'last4' =>'3335',
                'length' =>'16',
                'network' =>'Visa',
                'type' =>'credit',
                'sub_type' =>'consumer',
                'category' =>'STANDARD',
                'issuer' =>'HDFC',
                'international' =>FALSE,
                'emi' =>TRUE,
                'vault' =>'rzpvault',
                'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
                'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
                'trivia' => NULL,
                'country' =>'IN',
                'global_card_id' => NULL,
                'created_at' =>1614256967,
                'updated_at' =>1614256967,
        ]);

        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [

                        'id' =>'GfnS1Fj048VHo2',
                        'merchant_id' =>'10000000000000',
                        'amount' =>50000,
                        'currency' =>'INR',
                        'base_amount' =>50000,
                        'method' =>'card',
                        'status' =>'captured',
                        'two_factor_auth' =>'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' =>FALSE,
                        'amount_authorized' =>50000,
                        'amount_refunded' =>0,
                        'base_amount_refunded' =>0,
                        'amount_transferred' =>0,
                        'amount_paidout' =>0,
                        'refund_status' => NULL,
                        'description' =>'description',
                        'card_id' =>$card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' =>FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' =>'a@b.com',
                        'contact' =>'+919918899029',
                        'notes' =>[
                            'merchant_order_id' =>'id',
                        ],
                        'transaction_id' => $transaction->getId(),
                        'authorized_at' =>1614253879,
                        'auto_captured' =>FALSE,
                        'captured_at' =>1614253880,
                        'gateway' =>'hdfc',
                        'terminal_id' =>'1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' =>2,
                        'signed' =>FALSE,
                        'verified' => NULL,
                        'gateway_captured' =>TRUE,
                        'verify_bucket' =>0,
                        'verify_at' =>1614253880,
                        'callback_url' => NULL,
                        'fee' =>1000,
                        'mdr' =>1000,
                        'tax' =>0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' =>FALSE,
                        'save' =>FALSE,
                        'late_authorized' =>FALSE,
                        'convert_currency' => NULL,
                        'disputed' =>FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' =>'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' =>1614253879,
                        'updated_at' =>1614253880,
                        'captured' =>TRUE,
                        'reference2' => '12343123',
                        'entity' =>'payment',
                        'fee_bearer' =>'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' =>FALSE,
                        'gateway_amount' =>50000,
                        'gateway_currency' =>'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' =>FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    $this->paymentRearchRefernce1 = $data['reference1'];
                    return [];
                }

            });

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    $this->paymentRearchRefernce1 = $data['reference1'];
                    return [];
                }

            });

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement' => ' To Settlement', '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $gatewayPayment = [
            'amount' => 50000,
            'auth'   => 16142538
        ];

        $entries[] = $this->overrideFssBobRecon($gatewayPayment, 'GfnS1Fj048VHo2');

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $this->runForFiles([$file], 'CardFssBob');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        $reconRrn = trim(str_replace("'", '', $entries[0]['Retrieval Reference Number'] ?? null));

        $this->assertEquals($reconRrn, $this->paymentRearchRefernce1);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    private function overrideIsgRecon($gatewayInput, $reconType = 'payment', $refundId = '')
    {
        if ($reconType === 'payment') {
            $entries = $this->testData['facades']['testIsgPaymentRecon'];
        }
        else {
            $entries = $this->testData['facades']['testIsgRefundRecon'];
        }

        $entries['ORDER_ID'] = $gatewayInput['payment_id'];
        $entries['APP_CODE'] = $gatewayInput['auth'];


        if ($reconType === 'payment') {

            $entries['FINAL_AMOUNT'] = number_format($gatewayInput['amount'] / 100, 2);

        }
        else {
            $entries['REFUND_CANCEL_ID'] = $refundId;

            $entries['AMOUNT'] = number_format($gatewayInput['amount'] / 100, 2);

        }
        return $entries;
    }

    private function overrideFssBobRecon(array $gatewayPayment, string $entityId, $transactionType = 'Purchase')
    {
        $facade = $this->testData['facades']['testFssBobRecon'];

        $facade['Transaction Amount'] = number_format($gatewayPayment['amount'] / 100, 2);

        $facade['Settlement Amount']  = $facade['Transaction Amount'] / 100;

        $facade['Auth/Approval Code'] = $gatewayPayment['auth'];

        $facade['Merchant Track ID']  = "''". $entityId;

        $facade['Transaction Type']   =  $transactionType;

        return $facade;
    }

    private function overrideFssSbiRecon(array $entity, $transactionType = 'Purchase', $passInvalidAmount = false)
    {
        $facade = $this->testData['facades']['testFssSbiRecon'];

        $amt = ($passInvalidAmount === true) ? ($entity['amount'] + 1) : $entity['amount'];

        $facade['TXN_AMT']                  = number_format($amt / 100, 2);

        $facade['TRANSACTION_TYPE']         = $transactionType;

        // Payment/refund id
        $facade['MERCHANT_TXNNO']           = $entity['id'];

        // Auth code
        $facade['APPROVE_CODE']             = '13234';

        // RRN
        $facade['TXN_REF']                  = '01231232131';

        $facade['VAT_AMT']                  = '0.01';
        $facade['MDR']                      = '0.02';
        $facade['MTS_MSF_FIXFEE']           = '0.00';
        $facade['MTS_TOTL_CSF_AMT']         = '0.00';

        if ($transactionType === 'Refund')
        {
            // Payment id
            $facade['PRCHS_ MERCHANT_TXNNO']    = $entity['payment_id'];
            $facade['MERCHANT_TXNNO']          .= '1';
        }

        return $facade;
    }

    private function overrideFssBobReconNewFormat(array $gatewayPayment, string $entityId, $transactionType = 'Purchase')
    {
        $facade = $this->testData['facades']['testFssBobNewFormatRecon'];

        $facade['transactionamount'] = number_format($gatewayPayment['amount'] / 100, 2);

        $facade['settlementamount']  = $facade['transactionamount'];

        $facade['authapprovalcode']  = $gatewayPayment['auth'];

        $facade['merchanttrackid']   = "''". $entityId;

        $facade['transactiontype']   =  $transactionType;

        if ($transactionType === 'Refund')
        {
            // refund id
            $facade['merchanttrackid']    .='1';
        }

        return $facade;
    }

    private function overrideAmexPayment(array $gatewayPayment)
    {
        $facade = $this->testData['facades']['testAmexPaymentRecon'][0];

        $facade['Charge reference number'] = $gatewayPayment['vpc_ShopTransactionNo'];

        $facade['Rental agreement number'] = $gatewayPayment['vpc_ShopTransactionNo'];

        $facade['Merchant Account Number'] = 'razorpay amex';

        return $facade;
    }

    private function overrideAmexRefund(array $gatewayPayment, $amount)
    {
        $facade = $this->overrideAmexPayment($gatewayPayment);

        $facade['Charge amount'] = strval(-1 * $amount/100);

        return $facade;
    }

    private function overrideCredPayment(array $gatewayPayment)
    {
        $facade = $this->testData['facades']['cred'];

        $facade['P1 Transaction Id'] = $gatewayPayment['payment_id'];

        return $facade;
    }

    /**
     * This function is specifically for testAtomReconExtraCommaPaymentFile()
     * @param $row  Here we are changing the row intentionally to mock a row in MIS file
     * which has comma in the merchant_name and it causes the row values to shift right.
     */
    protected function rightShiftRowValuesForAtom(&$row)
    {
        $row['Merchant Name'] = 'Bangalore';

        $columns = array_keys($row);

        $values = array_values($row);

        array_unshift($values, 'RAZORPAY SOFTWARE PVT LTD');

        array_pop($values);

        $row = array_combine_pad($columns, $values);
    }

    /**
     * Assert the status of batch processed.
     *
     * @param string $status
     */
    protected function assertBatchStatus(string $status = Status::PROCESSED)
    {
        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], $status);
    }

    private function overrideCheckoutDotComPayment(array $payment)
    {
        $facade = $this->testData['facades']['checkout_dot_com'];

        $facade[CheckoutDotComPaymentRecon::REFERENCE] = $payment['id'];

        $facade[CheckoutDotComPaymentRecon::PROCESSING_CURRENCY_AMOUNT] = intval($payment['amount'] / 100);

        return $facade;
    }

    public function testCheckoutDotComReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

       $terminal = $this->fixtures->create('terminal:checkout_dot_com_terminal');

        $payment = $this->getDefaultPaymentArray();

        $attributes = [
            'terminal_id'       => $terminal->getId(),
            'method'            => Payment\Method::CARD,
            'amount'            => $payment['amount'],
            'base_amount'       => $payment['amount'],
            'amount_authorized' => $payment['amount'],
            'status'            => 'captured',
            'gateway'           => 'checkout_dot_com'
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id'   => $payment->getId(),
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $payment1 = $this->getDbLastEntityToArray('payment');

        $entries[] = $this->overrideCheckoutDotComPayment($payment1);

        $file = $this->writeToExcelFile($entries, 'checkout_dot_com');

        $this->runForFiles([$file], 'checkout_dot_com');

        $this->assertBatchStatus();
    }
}
