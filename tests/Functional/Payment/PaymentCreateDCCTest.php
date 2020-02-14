<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;

class PaymentCreateDCCTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateDCCTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['dcc']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPaymentCreateWithDCC()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals(true, $responseContent['is_international']);
        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];
        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['dcc_amount'] = $usdAmount;
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment, $usdAmount, $cardCurrency);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);
    }

    public function testPaymentCreateWithDccInvalidAmount()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];
        $cardCurrency = $responseContent['card_currency'];
        $invalidDccAmount = 1;

        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['dcc_amount'] = $invalidDccAmount;
        $payment['currency_request_id'] = $currencyRequestId;

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentCreateWithDccInvalidCurrencyRequestId()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['dcc_amount'] = $usdAmount;
        $payment['currency_request_id'] = "currencyRequestId";

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    private function getDefaultPaymentFlowsRequestData()
    {
        $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'Visa',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);

        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'iin' => $iin->getIin()],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }
}
