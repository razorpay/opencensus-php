<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Bob;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base\Repository as NetbankingRepository;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Gateway\Netbanking\Bob\Constants;
use RZP\Gateway\Netbanking\Bob\RequestFields;
use RZP\Gateway\Netbanking\Bob\ResponseFields;
use RZP\Models\Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Timezone;

class NetbankingBobGatewayTest extends TestCase
{
    use PaymentTrait;

    const CUSTOMER_ACCOUNT_NUMBER = '10430200000843';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingBobGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_bob';

        $this->bank = 'BARB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_bob_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testAuthorizeFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $this->testAuthorizeFailed();

        $data = $this->testData['testVerifyMismatch'];

        $payment = $this->getLastEntity('payment', true);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->verifyPayment($payment['id']);
            });

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifySuccessEntity');
    }

    public function testRefundExcelFile()
    {
        Mail::fake();

        $payments = [];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payments[] = $payment['id'];

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payments[] = $payment['id'];

        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 10;

            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payments[] = $payment['id'];

        $this->updateAccountDetailsInNetbankingEntity($payments);

        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForNb('BARB');

        $this->assertEquals($data['netbanking_bob']['count'], 3);

        $this->assertTrue(file_exists($data['netbanking_bob']['file']));
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS] = Constants::STATUS_FAILURE;
                unset($content[ResponseFields::BANK_REF_NUMBER]);
            }
        });
    }

    // In reconciliation, we get more details and add it to the gateway entities.
    // Here we manually update these values with hardcoded ones to emulate this behaviour
    protected function updateAccountDetailsInNetbankingEntity(array $payments)
    {
        foreach ($payments as $id)
        {
            $paymentId = Payment\Entity::verifyIdAndStripSign($id);

            $repo = new NetbankingRepository();

            $gatewayPayment = $repo->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);

            $gatewayPayment->fill(
                [
                    NetbankingEntity::ACCOUNT_NUMBER     => self::CUSTOMER_ACCOUNT_NUMBER,
                ]
            );

            $gatewayPayment->saveOrFail();
        }
    }
}
