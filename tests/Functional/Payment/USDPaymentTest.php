<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Admin\ConfigKey;

class USDPaymentTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/USDPaymentTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testUsdPaymentDomesticCardOnApiWithOrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], true);
        $this->assertEquals($payment['base_amount'], 50000);
    }

    public function testUsdPaymentDomesticCardOnGatewayWithOrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], true);
        $this->assertEquals($payment['base_amount'], 50000);
    }


    public function testUsdPaymentInternationalCardOnApiWithOrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);
        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', ['international' => 1]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        //International card
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], null);
        $this->assertEquals($payment['base_amount'], 49000);
    }

    public function testUsdPaymentInternationalCardOnGatewayWithOrder()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);
        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', ['international' => 1, 'currency' => 'USD']);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], null);
        $this->assertEquals($payment['base_amount'], 49000);
    }

    public function testUsdPaymentInternationalCardOnApiWithOrderAndDccDisabled()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);
        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', ['international' => 1]);
        $this->fixtures->merchant->addFeatures([Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], true);
        $this->assertEquals($payment['base_amount'], 50000);
    }

    public function testUsdPaymentInternationalCardOnGatewayWithOrderAndDccDisabled()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);
        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', ['international' => 1, 'currency' => 'USD']);
        $this->fixtures->merchant->addFeatures([Constants::DISABLE_NATIVE_CURRENCY]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], false);
        $this->assertEquals($payment['base_amount'], 49000);
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

    // Pick value from merchant level mcc markdown value
    public function testMCCPaymentWithMerchantLevelConfig()
    {
        $mccMarkdownPercent = 4;
        $this->fixtures->merchant->addMccMarkdownPaymentConfig($mccMarkdownPercent);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        //International card
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], null);
        $this->assertEquals($payment['base_amount'], 48000);
    }

    // Checks if Merchant Level Config is empty and them pick admin config key value
    public function testMCCPaymentWithAdminConfigKey()
    {
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE => "3"
            ]);

        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        //International card
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], null);
        $this->assertEquals($payment['base_amount'], 48500);
    }

    // Picks Hardcoded Value from Code i.e 2%
    public function testMCCPaymentWithNoAdminConfigKeyAndNoMerchantLevel()
    {
        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = $order['amount'];
        $payment['currency'] = $order['currency'];
        //International card
        $payment['card']['number'] = '4012 0111 1111 1113';

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], null);
        $this->assertEquals($payment['base_amount'], 49000);
    }

        // Checks if Merchant Level Config is empty and them pick admin config key value
        public function testMCCPaymentWithAdminConfigKeyDecimalMarkdownPercentage()
        {
            (new AdminService)->setConfigKeys(
                [
                    ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE => "2.5"
                ]);

            $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'USD']);

            $order = $this->getLastEntity('order', true);

            $payment = $this->getDefaultPaymentArray();
            $payment['order_id'] = $order['id'];
            $payment['amount'] = $order['amount'];
            $payment['currency'] = $order['currency'];
            //International card
            $payment['card']['number'] = '4012 0111 1111 1113';

            $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals($payment['convert_currency'], null);
            $this->assertEquals($payment['base_amount'], 48750);
        }
}
