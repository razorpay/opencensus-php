<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCreateConvenienceFeeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateConvenienceFeeTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPayment()
    {
        $payment           = $this->getDefaultPaymentArray();

        $feesArray         = $this->createAndGetFeesForPayment($payment);

        assert($feesArray['fees'] === 1173);

        assert($feesArray['service_tax'] === 149);

        $payment['amount'] = $payment['amount'] + $feesArray['fees'];

        $payment['fee']    = $feesArray['fees'];

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastPayment();

        assert($payment['fee'] === 1173);

        assert($payment['service_tax'] === 149);
    }

    public function testAmountMismatch()
    {
        $payment           = $this->getDefaultPaymentArray();

        $feesArray         = $this->createAndGetFeesForPayment($payment);

        $feesArray['fees'] = 0;

        $feesArray['service_tax'] = 0;

        $payment['amount'] = $payment['amount'] + $feesArray['fees'];

        $payment['fee']    = $feesArray['fees'];

        try {
            $this->doAuthAndCapturePayment($payment);
        } catch (\Exception $e) {
            assert($e->getMessage()
                === 'Fees or service tax fields have been tampered with.');
        }
    }

    // TODO Add tests for create with order
    public function testPaymentWithOrder()
    {
        $this->markTestIncomplete();
    }
}
