<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiJusPay;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Entity;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiJusPayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    /**
     * @var array
     */
    private $payment;

    private $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = Payment\Gateway::UPI_JUSPAY;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);

        $this->terminal = $this->fixtures->create('terminal:upi_juspay_terminal');

        $this->ba->publicAuth();
    }

    public function testUpiJuspayPaymentRecon()
    {
        $payment = $this->createDependentEntitiesForPayment(500000);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'upi_sett_bajaj.csv', 'text/plain');

        $this->reconcile($uploadedFile, 'UpiJuspay');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiJuspay',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->paymentReconAsserts($payment);
    }

    public function testUpiJuspayRefundRecon()
    {
        $refund = $this->createDependentEntitiesForRefund(500000);

        $this->mockReconContentFunction(function (& $content) use ($refund)
        {
            if ($content['REFUNDID'] === $refund['id'])
            {
                $content = [];
            }
        });

        $fileContents = $this->generateReconFile(
            [
                'gateway' => $this->gateway,
                'type'    => 'refund',
            ]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'upi_refund_bajaj.csv', 'text/plain');

        $this->reconcile($uploadedFile, 'UpiJuspay');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiJuspay',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->refundReconAsserts($refund);
    }

    public function testUnexpectedPaymentCreation()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**
     * Tests the duplicate unexpected payment creation
     * for recon edge cases invalid paymentId, rrn mismatch ,Multiple RRN.
     * Amount mismatch case is handled in seperate testcase
     */
    public function testUnexpectedPaymentCreateForAmountMismatch()
    {

        $payment = $this->createDependentEntitiesForPayment(500000);

        $upi = $this->getDbLastUpi();

        $upi->setGateway(Payment\Gateway::UPI_JUSPAY);



        $this->fixtures->edit('upi', $upi['id'], ['vpa' => 'unexpectedpayment@abfspay']);

        $content = $this->buildUnexpectedPaymentRequest();

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], ['gateway' => 'upi_juspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        $content['upi']['merchant_reference'] = $upiEntity['payment_id'];

        $content['upi']['vpa'] = $upiEntity['vpa'];

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals($upi['npci_reference_id'], $content['upi']['npci_reference_id']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**
     * Test unexpected payment request mandatory validation
     */
    public function testUnexpectedPaymentValidationFailure()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);
        unset($content['terminal']['gateway_merchant_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);
        },Exception\BadRequestValidationFailureException::class);
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     */
    public function testDuplicateUnexpectedPayment()
    {
        $payment = $this->createDependentEntitiesForPayment(500000);


        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], ['vpa' => 'unexpectedpayment@abfspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['gateway' => 'upi_juspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        $content = $this->buildUnexpectedPaymentRequest();

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $content['upi']['merchant_reference'] = $upiEntity['payment_id'];

        $content['upi']['vpa'] = $upiEntity['vpa'];

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Duplicate Unexpected payment with same amount');
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     * for amount mismatch cases
     */
    public function testDuplicateUnexpectedPaymentForAmountMismatch()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], ['vpa' => 'unexpectedpayment@abfspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['gateway' => 'upi_juspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        $content['upi']['vpa'] = 'unexpectedpayment@abfspay';
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals($upi['npci_reference_id'], $content['upi']['npci_reference_id']);

        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

    /**
     * Tests the payment create for multiple payments with same RRN
     */
    public function testUnexpectedPaymentForDuplicateRRN()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals($upi['npci_reference_id'], $content['upi']['npci_reference_id']);

        $payment = $this->createDependentEntitiesForPayment(50000);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], ['vpa' => 'unexpectedpayment@abfspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['gateway' => 'upi_juspay']);
        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);
        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

    protected function paymentReconAsserts(array $payment)
    {
        $updatedPayment = $this->getDbEntity('payment', ['id' => $payment['id']]);

        $this->assertEquals(true, $updatedPayment['gateway_captured']);

        /**
         * @var $upi \RZP\Gateway\Upi\Base\Entity
         */
        $upi = $this->getDbEntity('upi', ['payment_id' => $updatedPayment['id']]);

        // Assert RRN is updated both in payment and UPI entity
        $this->assertEquals('009007125383', $upi->getNpciReferenceId());
        $this->assertEquals('009007125383', $updatedPayment['reference16']);

        // Assert vpa is updated both in payment and UPI entity
        $this->assertEquals('john.miller@juspay', $upi->getVpa());

        //Assert upi.npci_txn_id
        $this->assertEquals('BJJ08df8cc33c68435988aafa54de908913', $upi->getNpciTransactionId());


        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedPayment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function refundReconAsserts(array $refund)
    {
        $updatedRefund = $this->getDbEntity('refund', ['id' => $refund['id']]);

        $this->assertNotNull($updatedRefund['reference1']);

        $gatewayEntity = $this->getDbEntity(
            'mozart',
            [
                'payment_id' => $updatedRefund['payment_id'],
                'action'     => 'refund',
            ]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['gatewayTransactionId'], 'BJJdcf478fff4b9a8ae78fb40b3384c2d01');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedRefund['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function createDependentEntitiesForPayment($amount, $status = 'authorized', $bankRef = 'abc123456')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'method'           => 'upi',
            'status'           => $status,
            'gateway'          => 'upi_juspay',
            'terminal_id'      => $this->terminal['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        $payment = $this->fixtures->create(
            'payment',
            $paymentArray
        )->toArray();

        $this->fixtures->create('upi', ['payment_id'            => $payment['id'],
                                                 'action'               => 'authorize',
                                                 'npci_reference_id'    => '009007125383',
                                                 'npci_txn_id'          => 'BJJ08df8cc33c68435988aafa54de908913',
            ]);

        return $payment;
    }

    protected function createDependentEntitiesForRefund($amount, $status = 'authorized', $bankRef = 'abc123456')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'method'           => 'upi',
            'status'           => $status,
            'gateway'          => 'upi_juspay',
            'terminal_id'      => $this->terminal['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        $payment = $this->fixtures->create('payment', $paymentArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'gateway'    => 'upi_juspay',
                'amount'     => $amount,
                'raw'        => json_encode(
                    [
                        'rrn'                   => '',
                        'type'                  => 'MERCHANT_CREDITED_VIA_PAY',
                        'amount'                => $amount,
                        'status'                => 'payment_successful',
                        'payeeVpa'              => 'billpayments@abfspay',
                        'payerVpa'              => '',
                        'payerName'             => 'JOHN MILLER',
                        'paymentId'             => $payment['id'],
                        'gatewayResponseCode'   => '00',
                        'gatewayTransactionId'  => 'BJJ08df8cc33c68435988aafa54de908913'
                    ]
                )
            )
        );

        // Creating payment txn, as refund missing txn won't be
        // created during recon if payment txn is missing.
        $txnArray = [
            'entity_id'   => $payment['id'],
            'type'        => 'payment',
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
        ];

        $paymentTxn = $this->fixtures->create('transaction', $txnArray)->toArray();

        $this->fixtures->edit('payment', $payment['id'], ['transaction_id' => $paymentTxn['id']]);

        $refundArray = [
            'payment_id'  => $payment['id'],
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
            'status'      => 'processed',
            'gateway'     => 'upi_juspay',
        ];

        $refund = $this->fixtures->create('refund', $refundArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'refund',
                'refund_id'  => $refund['id'],
                'gateway'    => 'upi_juspay',
                'amount'     => $payment['amount'],
                'raw'        => json_encode(
                    [
                        'status' 				=> 'refund_initiated_successfully',
                        'apiStatus' 			=> 'SUCCESS',
                        'merchantId' 			=> 'BAJAJBILLPAYMENTS',
                        'refundAmount' 			=> $payment['amount'],
                        'responseCode' 			=> 'SUCCESS',
                        'responseMessage' 		=> 'SUCCESS',
                        'merchantRequestId' 	=> $payment['id'],
                        'transactionAmount' 	=> $payment['amount'],
                        'gatewayResponseCode' 	=> '00',
                        'gatewayTransactionId' 	=> 'BJJdcf478fff4b9a8ae78fb40b3384c2d01',
                    ]
                )
            )
        );

        return $refund;
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
    /**
     * Validate negative case of authorizing successful payment
     */
    public function testForceAuthorizeSuccessfulPayment1()
    {
        $payment = $this->createDependentEntitiesForPayment(50000);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'captured',
            ]);

        unset($content['upi']['npci_txn_id']);

        $content['upi']['gateway'] = 'upi_juspay';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;
        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'Non failed payment given for authorization');
    }
    //Tests for force authorize with mismatched amount in request.
    public function testForceAuthorizePaymentAmountMismatch()
    {
        $payment = $this->createDependentEntitiesForPayment(500000);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       =>  null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(\RZP\Gateway\Upi\Base\Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        // Change amount to 60000 for mismatch scenario
        $content['payment']['amount'] = 60000;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The amount does not match with payment amount');

    }

    /**
     * Checks for validation failure in case of missing payment_id
     */
    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The payment.id field is required.');
    }
    /**
     * Checks for validation failure in case of missing npci_reference_id
     */
    public function testForceAuthorizePaymentValidationFailure2()
    {
        $payment = $this->createDependentEntitiesForPayment(500000);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['upi']['gateway'] = 'upi_juspay';

        $content['meta']['force_auth_payment'] = false;

        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The upi.npci reference id field is required.');
    }
    /**
     * @return array
     */
    protected function buildUnexpectedPaymentRequest()
    {
        $this->fixtures->merchant->createAccount('100DemoAccount');
        $this->fixtures->merchant->enableUpi('100DemoAccount');

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        // Unsetting fields which will not be present in UpiIcici MIS
        unset($content['upi']['account_number']);
        unset($content['upi']['ifsc']);
        unset($content['upi']['npci_txn_id']);
        unset($content['upi']['gateway_data']);
        $content['upi']['vpa']='unexpectedpayment@abfspay';
        $content['terminal']['gateway'] = 'upi_juspay';
        $content['terminal']['gateway_merchant_id'] = $this->terminal->getGatewayMerchantId();
        $content['payment']['vpa'] = 'unexpectedpayment@abfspay';

        return $content;
    }
    protected function makeUnexpectedPaymentAndGetContent(array $content)
    {
        $request = [
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    private function makeUpiJuspayPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiJuspayPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }
    private function doUpiJuspayPayment()
    {
        $attributes = [
            'terminal_id'       => $this->terminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
            'cps_route'         => Entity::UPI_PAYMENT_SERVICE,
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction',
            ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action' => 'authorize',
                'gateway' => 'upi_juspay',
                'amount' => $payment['amount'],
                'raw' => json_encode(
                    [
                        'rrn' => '227121351902',
                        'type' => 'MERCHANT_CREDITED_VIA_PAY',
                        'amount' => $payment['amount'],
                        'status' => 'payment_successful',
                        'payeeVpa' => 'billpayments@abfspay',
                        'payerVpa' => '',
                        'payerName' => 'JOHN MILLER',
                        'paymentId' => $payment['id'],
                        'gatewayResponseCode' => '00',
                        'gatewayTransactionId' => 'FT2022712537204137'
                    ]
                )
            )
        );

        return $payment->getId();
    }
    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
