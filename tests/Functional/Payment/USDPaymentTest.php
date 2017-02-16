<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class USDPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/USDPaymentTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testUsdPaymentOnApiWithOrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);
    }

    public function testUsdPaymentMerchantUsdDisabled()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['currency'] = 'USD';

        $testData = $this->testData['testUsdNotSupported'];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testUsdPaymentMerchantFeeBearer()
    {
        $this->fixtures->merchant->edit('10000000000000', ['fee_bearer' => 'customer']);

        $payment = $this->getDefaultPaymentArray();
        $payment['currency'] = 'USD';

        $testData = $this->testData['testUsdNotSupported'];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testUsdNBPayment()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['currency'] = 'USD';

        $testData = $this->testData['testUsdNotSupported'];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testUsdPaymentOnApiWithINROrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->fixtures->create('order');

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = 'USD';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }
}
