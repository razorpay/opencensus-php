<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiPaymentServiceTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $terminal;

    protected $upiPaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService =  Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);

        // We have Airtel Gateway Enabled for Service
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_airtel_v1', 'upips');
        });
    }

    /**
     * Test Validation failure for collect payment
     *
     * @return void
     */
    public function testCollectPaymentCreateValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'validation_failure_collect_vpa';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\BadRequestException::class,
            'Vpa is required for UPI collect request');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_INPUT_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    /**
     * test UPS service failure
     *
     * @return void
     */
    public function testPaymentServiceFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'service_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\ServerErrorException::class,
            'internal server error');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'SERVER_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'SERVER_ERROR_UPI_PAYMENT_SERVICE_FAILURE'
            ], $payment->toArray());
    }

    /**
     * Test Mozart failure for collect payment
     *
     * @return void
     */
    public function testMozartFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'mozart_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\GatewayErrorException::class);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_ENCRYPTION_ERROR'
            ], $payment->toArray()
        );
    }

    /**
     * Test Mozart failure for collect payment
     *
     * @return void
     */
    public function testMozartValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'mozart_validation_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\BadRequestException::class);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    protected function createDependentEntitiesForRefund($payment, $status = 'authorized')
    {
        $refundArray = [
            'payment_id'  => $payment['id'],
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
            'base_amount' => $payment['amount'],
            'status'      => 'processed',
            'gateway'     => 'upi_airtel',
        ];

        $refund = $this->fixtures->create('refund', $refundArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'refund',
                'refund_id'  => $refund['id'],
                'gateway'    => 'upi_airtel',
                'amount'     => $payment['amount'],
                'raw'        => json_encode(
                    [
                        'status'                => 'refund_initiated_successfully',
                        'apiStatus'             => 'SUCCESS',
                        'merchantId'            => '',
                        'refundAmount'          => $payment['amount'],
                        'responseCode'          => 'SUCCESS',
                        'responseMessage'       => 'SUCCESS',
                        'merchantRequestId'     => $payment['id'],
                        'transactionAmount'     => $payment['amount'],
                        'gatewayResponseCode'   => '00',
                        'gatewayTransactionId'  => 'FT2022712537204137',
                    ]
                )
            )
        );

        return $refund;
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

        $this->assertEquals($data['gatewayTransactionId'], 'FT2022712537204137');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedRefund['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function paymentReconAsserts(array $payment)
    {
        $updatedPayment = $this->getDbEntity('payment', ['id' => $payment['id']]);

        $this->assertEquals(true, $updatedPayment['gateway_captured']);

        $this->assertEquals(Entity::UPI_PAYMENT_SERVICE, $updatedPayment['cps_route']);

        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $updatedPayment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['rrn'], '227121351902');

        $this->assertEquals($data['rrn'], $updatedPayment['reference16']);

        $this->assertEquals($data['gatewayTransactionId'], 'FT2022712537204137');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedPayment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function mockServerRequestFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('request')->andReturnUsing($closure);
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }

    /**
     * sets the razox mock
     *
     * @param [type] $closure
     * @return void
     */
    protected function setRazorxMock($closure)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback($closure));
    }

    /**
     * returns a mock response of the razorx request
     *
     * @param string $inputFeature
     * @param string $expectedFeature
     * @param string $variant
     * @return string
     */
    protected function getRazoxVariant(string $inputFeature, string $expectedFeature, string $variant): string
    {
        if ($expectedFeature === $inputFeature)
        {
            return $variant;
        }

        return 'control';
    }

    protected function doAjaxPaymentWithUps(string $terminalResource, string $gateway)
    {
        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create($terminalResource);

        $this->gateway = $gateway;

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_'.$this->gateway.'_v1', 'upips');
        });

        $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    protected function doAjaxPayment(string $terminalResource, string $gateway)
    {
        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create($terminalResource);

        $this->gateway = $gateway;

        $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    protected function makeUpdatePostReconRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createTpvOrder()
    {
        $this->fixtures->merchant->enableTpv();

        $this->ba->privateAuth();

        $this->testData['createTpvOrder'] = [
            'request' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                    'method'         => 'upi',
                    'bank_account'   => [
                        'name'           => 'Test User',
                        'account_number' => '04030403040304',
                        'ifsc'           => 'RATN0000001'
                    ]
                ],
                'method'    => 'POST',
                'url'       => '/orders',
            ],
            'response' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                ],
            ],
        ];

        return $this->startTest();
    }

    protected function createUpsUploadedFile($file, $fileName = 'file.xlsx', $mimeType = null)
    {
        $this->assertFileExists($file);

        $mimeType = $mimeType ?? 'text/csv';
        $fileName = ($fileName == 'file.xlsx') ? $file : $fileName;

        $uploadedFile = new UploadedFile(
            $file,
            $fileName,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }
}
