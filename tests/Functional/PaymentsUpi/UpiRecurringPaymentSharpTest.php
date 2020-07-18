<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class UpiRecurringPaymentSharpTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;
    use PaymentsUpiRecurringTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
    }

    public function testCreateFirstUpiRecurringPaymentSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($payment);

        $order = $this->getDbLastEntity('order');
        $upiMandate = $this->getDbLastEntity('upi_mandate');
        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);
        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);
        $this->assertEquals('confirmed', $upiMandate['status']);

        $this->assertArraySubset([
            'method'            => 'upi',
            'recurring_status'  => 'confirmed',
            'recurring'         => true,
        ], $token->toArray(), true);

        $this->assertArraySubset([
            'method'            => 'upi',
            'status'            => 'paid',
            'authorized'        => true,
            'payment_capture'   => true,
        ], $order->toArray(), true);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCaptured());
        $this->assertNotNull($payment->getReference16());
        $this->assertEquals('vishnu@icici', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentFailure()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'failure@razorpay';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('failure@razorpay', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentRejected()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'rejected@razorpay';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('rejected@razorpay', $payment->getVpa());
    }

    public function testCreateAutoRecurringPaymentSuccess()
    {
        $mandate = $this->createFirstUpiRecurringPayment();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['token'] = $mandate->token->getPublicId();

        $orderId = $this->createUpiOrder();

        $payment['order_id'] = $orderId;

        unset($payment['vpa']);

        $response = $this->doS2SRecurringPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'terminal_id'   => '1000SharpTrmnl',
            'status'        => 'created',
            'verify_at'     => null,
        ], $payment->toArray());
    }

    public function testRevokeMandate()
    {
        $mandate = $this->createFirstUpiRecurringPayment();

        $token = $this->getDbLastEntity('token');

        $this->revokeUpiRecurringMandate($token->getPublicId());

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);
    }

    public function testRevokeCreatedMandate()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['vpa'] = 'failure@razorpay';

        $this->doAuthPayment($payment);

        $mandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Status::CREATED, $mandate['status']);

        $data = $this->getRevokeCreatedMandateResponse();

        $this->runRequestResponseFlow($data, function() use ($token) {
            $this->revokeUpiRecurringMandate($token->getPublicId());
        });

        $mandate->reload();

        $this->assertEquals(Status::CREATED, $mandate['status']);
    }

    protected function getRevokeCreatedMandateResponse()
    {
        return [
            'response' => [
                'content'     => [
                    'error' => [
                        'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
            ],
        ];
    }

    protected function revokeUpiRecurringMandate(string $tokenId)
    {
        $this->ba->privateAuth();

        $request = [
            'method'  => 'PUT',
            'content' => [],
            'url' => '/customers/cust_100000customer/tokens/' . $tokenId . '/cancel',
        ];

        $this->makeRequestAndGetContent($request);
    }
}
