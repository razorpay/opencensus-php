<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici\EMandate;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIciciEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $gateway;

    protected $fixtures;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciEMandateTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_icici';

        $this->fixtures->create('terminal:shared_netbanking_icici_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getNetbankingRecurringPaymentArray('ICIC');
        unset($this->payment['card']);

        $this->mockTokenex();
    }

    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    /**
     * This is the case that the payment failed
     */
    public function testEMandateInitialPaymentFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockSiPaymentFailure();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });

        $this->assertEMandateInitialFailEntities();
    }

    public function testEMandateScheduledPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);
    }

    public function testEMandateSiRejected()
    {
        $this->mockRejectedToken();

        $this->doAuthPayment($this->payment);

        $this->assertEMandateRejectedToken();
    }

    public function testScheduledPaymentWithRejectedToken()
    {
        $payment = $this->payment;

        $this->mockRejectedToken();

        $this->doAuthPayment($payment);

        // Assert that the token was rejected
        $netbanking = $this->getLastEntity('netbanking', true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];

        $this->mockRejectedToken(false);

        //
        // Second auth payment for the recurring product.
        // Since an invalid recurring token is used here, the
        // payment will go through like a regular non-recurring payment.
        // This is because it is not a "First Recurring" payment/
        //
        $this->doS2SRecurringPayment($payment);

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNull($netbanking['si_ref_id']);
        $this->assertNull($netbanking['si_message']);
        $this->assertNull($netbanking['si_status']);
        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    public function testSiRecurringStatusNotSet()
    {
        $payment = $this->payment;

        $this->mockSiRecurringStatusNotSet();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->assertEMandateInitialTokenSaveFailed();
    }

    public function testSiRecurringMessageNotSet()
    {
        $payment = $this->payment;

        $this->mockSiRecurringMessageNotSet();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->assertEMandateInitialTokenSaveFailed('N');
    }

    public function testAuthSecondRecurringNullGatewayToken()
    {
        $payment = $this->payment;

        $this->mockSiRecurringGatewayTokenNotSet();

        $this->doAuthPayment($payment);

        $data = $this->testData[__FUNCTION__];

        $this->mockSiRecurringGatewayTokenNotSet(false);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);
        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals($payment['token_id'], $token['id']);

        // gateway token is set to null
        $this->assertEquals(null, $token['gateway_token']);
        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $this->assertNotNull($netbanking['si_ref_id']);
        $this->assertEquals('Y', $netbanking['si_status']);
        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }


    /**
     * This test case tests the netbanking first recurring flow.
     * In netbanking recurring payments, we create a new token
     * for each and every new first recurring payment.
     * We also create a new gateway token for each of them as well,
     * even if the customer, merchant and terminal are all the same
     */
    public function testTwoEMandateRegistrationPayments()
    {
        $payment = $this->payment;

        // First E Mandate registration payment
        $this->doAuthPayment($payment);

        $token1 = $this->getLastEntity('token', true);
        $gatewayToken1 = $this->getLastEntity('gateway_token', true);

        // Second E Mandate registration payment
        $this->doAuthPayment($payment);

        $token2 = $this->getLastEntity('token', true);
        $gatewayToken2 = $this->getLastEntity('gateway_token', true);

        // Assert that both the tokens are different
        // Also assert their gateway_tokens are different
        $this->assertNotEquals($token1['id'], $token2['id']);
        $this->assertNotEquals($token1['gateway_token'], $token2['gateway_token']);

        // Assert that the customer, merchant and terminal are the same
        $this->assertEquals($token1['customer_id'], $token2['customer_id']);
        $this->assertEquals($token1['merchant_id'], $token2['merchant_id']);
        $this->assertEquals($token1['terminal_id'], $token2['terminal_id']);

        // Assert that both the gateway tokens are different
        // Also assert that their token id's are different
        $this->assertNotEquals($gatewayToken1['id'], $gatewayToken2['id']);
        $this->assertNotEquals($gatewayToken1['token_id'], $gatewayToken2['token_id']);

        // Assert that the merchant and terminal are the same
        $this->assertEquals($gatewayToken1['merchant_id'], $gatewayToken2['merchant_id']);
        $this->assertEquals($gatewayToken1['terminal_id'], $gatewayToken2['terminal_id']);
    }

    protected function assertEMandateRejectedToken()
    {
        // Assert that the token was rejected
        $netbanking = $this->getLastEntity('netbanking', true);
        $this->assertEquals('N', $netbanking['si_status']);
        $this->assertEquals('Failure', $netbanking['si_message']);

        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals(false, $token['recurring']);
        $this->assertEquals('rejected', $token['recurring_status']);
    }

    protected function assertEMandateEntities($initial = true)
    {
        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($netbanking['si_ref_id']);

        if ($initial === true)
        {
            $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
            $this->assertEquals('Y', $netbanking['si_status']);
            $this->assertEquals('Success', $netbanking['si_message']);
        }

        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals($netbanking['si_ref_id'], $token['gateway_token']);

        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals(100000, $token['max_amount']);
        $this->assertEquals('confirmed', $token['recurring_status']);
    }

    protected function assertEMandateInitialTokenSaveFailed($expectedSiStatus = 'C')
    {
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);
        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals($payment['token_id'], $token['id']);

        $this->assertEquals(null, $token['gateway_token']);

        $this->assertNotNull($netbanking['si_ref_id']);
        $this->assertEquals($expectedSiStatus, $netbanking['si_status']);
        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    protected function assertEMandateInitialFailEntities()
    {
        $netbanking = $this->getLastEntity('netbanking', true);
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals(null, $token['gateway_token']);
        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('Y', $netbanking['si_status']);

        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    protected function mockSiPaymentFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['PAID'] = 'N';
            });
    }

    protected function mockSiRecurringStatusNotSet()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                // This maps to a null recurring status
                $content['SCHSTATUS'] = 'C';
            });
    }

    protected function mockSiRecurringMessageNotSet()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['SCHSTATUS'] = 'N';
                $content['SCHMSG'] = '';
            });
    }

    protected function mockRejectedToken($apply = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($apply)
            {
                if ($apply === true)
                {
                    $content['SCHSTATUS'] = 'N';
                    $content['SCHMSG'] = 'Failure';
                }
            });
    }

    protected function mockSiRecurringGatewayTokenNotSet($set = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($set)
            {
                if ($set === true)
                {
                    $content['RID'] = '';
                }
            });
    }
}
