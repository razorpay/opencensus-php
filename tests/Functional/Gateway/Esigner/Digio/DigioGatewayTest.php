<?php

namespace RZP\Tests\Functional\Gateway\Esigner\Digio;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class DigioGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/DigioGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_digio_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'esigner_digio';

        $this->markTestSkipped();
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    public function testSuccessfulBiometricEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar_fp', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    public function testDigioCancelledByUser()
    {
        $this->setMockGatewayTrue();

        $this->mockCancelledByUser();

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc' => 'utib0000123',
            'name' => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals(
            ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
            $payment['internal_error_code']
        );
    }

    protected function runPaymentCallbackFlowEsignerDigio($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                    $url, $method, $content);
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function mockCancelledByUser()
    {
        $this->mockServerContentFunction(function(& $request, $action = null)
        {
            if ($action === 'sign')
            {
                $request['content']['status'] = 'cancel';

                $request['content']['message'] = 'Signing Cancelled';
            }
        }, 'esigner_digio');
    }
}
