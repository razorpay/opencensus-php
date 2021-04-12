<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiSharpGatewayTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testValidateVpaNoName()
    {
        $this->validateVpa('test@razorpay');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame(null, $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa->getReceivedAt());
    }

    public function testValidateVpaWithName()
    {
        $this->validateVpa('withname@razorpay');

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('Razorpay Customer', $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa->getReceivedAt());
    }

    public function testValidateInvalidVpa()
    {
        $this->validateVpa('invalidvpa@razorpay', false);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame(null, $vpa);
    }

    public function testValidateVpaExisting()
    {
        $this->createUpiPaymentsLocalCustomerVpa([
            'username'  => 'withname',
            'handle'    => 'razorpay',
            'name'      => 'tobeupdated',
        ]);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('withname', $vpa->getUsername());
        $this->assertSame('razorpay', $vpa->getHandle());
        $this->assertSame('tobeupdated', $vpa->getName());
        $this->assertSame(null, $vpa->getStatus());
        $this->assertSame(null, $vpa->getReceivedAt());

        $this->validateVpa('withname@razorpay');

        $vpa2 = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame($vpa->getId(), $vpa2->getId());

        $this->assertSame('Razorpay Customer', $vpa2->getName());
        $this->assertSame('valid', $vpa2->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa2->getReceivedAt());
    }

    public function testValidateVpaExpired()
    {
        $this->createUpiPaymentsLocalCustomerVpa([
            'username'      => 'withname',
            'handle'        => 'razorpay',
            'name'          => 'tobeupdated',
            'status'        => 'valid',
            'received_at'   => Carbon::now()->subSeconds(15552001)->getTimestamp(),
        ]);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('withname', $vpa->getUsername());
        $this->assertSame('razorpay', $vpa->getHandle());
        $this->assertSame('tobeupdated', $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThan(null, $vpa->getReceivedAt());

        $this->validateVpa('withname@razorpay');

        $vpa2 = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame($vpa->getId(), $vpa2->getId());

        $this->assertSame('Razorpay Customer', $vpa2->getName());
        $this->assertSame('valid', $vpa2->getStatus());
        $this->assertGreaterThanOrEqual(1600000000, $vpa2->getReceivedAt());
    }

    public function testValidateVpaCached()
    {
        $receivedAt = Carbon::now()->subSeconds(600005)->getTimestamp();

        $this->createUpiPaymentsLocalCustomerVpa([
            'username'      => 'withname',
            'handle'        => 'razorpay',
            'status'        => 'valid',
            'name'          => 'nottobeupdated',
            'received_at'   => $receivedAt,
        ]);

        $vpa = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame('withname', $vpa->getUsername());
        $this->assertSame('razorpay', $vpa->getHandle());
        $this->assertSame('nottobeupdated', $vpa->getName());
        $this->assertSame('valid', $vpa->getStatus());
        $this->assertGreaterThan(null, $vpa->getReceivedAt());

        $this->validateVpa('withname@razorpay');

        $vpa2 = $this->getDbLastEntity('payments_upi_vpa');

        $this->assertSame($vpa->getId(), $vpa2->getId());

        $this->assertSame('nottobeupdated', $vpa2->getName());
        $this->assertSame('valid', $vpa2->getStatus());
        $this->assertSame($receivedAt, $vpa2->getReceivedAt());
    }

    public function collectPaymentMccValidation()
    {
        $cases = [];

        $cases['6540_failed'] = [
            '6540',
            50000,
            ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_BLOCKED,
            [
                'description'   => 'UPI Collect is not allowed for your merchant category by NPCI.' .
                                   ' Please reach out to Razorpay support if you need any help.',
            ],
        ];

        $cases['4812_failed'] = [
            '4812',
            500001,
            ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_AMOUNT_LIMIT_REACHED,
            [
                'description'   => 'UPI Collect payment more than INR 5000 is not allowed on your merchant category ' .
                                   'by NPCI. Reach out to Razorpay support if you need any help',
            ],
        ];

        $cases['4814_failed'] = [
            '4814',
            500001,
            ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_AMOUNT_LIMIT_REACHED,
            [
                'description'   => 'UPI Collect payment more than INR 5000 is not allowed on your merchant category ' .
                                   'by NPCI. Reach out to Razorpay support if you need any help',
            ],
        ];

        $cases['4814_success'] = [
            '4814',
            500000,
            null,
            [],
        ];

        return $cases;
    }

    /**
     * @dataProvider collectPaymentMccValidation
     */
    public function testCollectPaymentMccValidation($mcc, $amount, $iec, $error)
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $merchant->setCategory($mcc);
        $merchant->saveOrFail();

        $input = $this->getDefaultUpiPaymentArray();

        $input['amount'] = $amount;

        if (is_string($iec) === true)
        {
            $testData = [
                'response' => [
                    'content' => [
                        'error' => array_merge([
                            'code'          => ErrorCode::BAD_REQUEST_ERROR,
                            'description'   => 'Something went wrong, please try again after sometime.',
                        ], $error),
                    ],
                    'status_code' => 400,
                ],
                'exception' => [
                    'class' => 'RZP\Exception\BadRequestException',
                    'internal_error_code' => $iec,
                ],
            ];

            $this->runRequestResponseFlow($testData, function () use ($input)
            {
                $this->doAuthPaymentViaAjaxRoute($input);
            });
        }
        else
        {
            $response = $this->doAuthPaymentViaAjaxRoute($input);

            $this->assertArraySubset([
                'type'      => 'async',
                'method'    => 'upi',
            ], $response);
        }


    }
}
