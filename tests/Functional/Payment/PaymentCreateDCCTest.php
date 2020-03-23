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

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];
        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);
    }

    public function testPaymentCreateWithDCCINR()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $payment = $this->payment;
        $payment['dcc_currency'] = 'INR';
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['amount'], $paymentMeta['gateway_amount']);
    }

    public function testDccForMccPayment()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 5000;
        $payment['currency'] = 'USD';

        $flowsData = [
            'content' => ['amount' => $payment['amount'], 'currency' => $payment['currency'], 'card_number' => $payment['card']['number']],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('currency_request_id', $responseContent);
        $this->assertArrayNotHasKey('all_currencies', $responseContent);

        $this->doAuthAndCapturePayment($payment, $payment['amount'], $payment['currency']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['convert_currency'], true);
        $this->assertEquals($payment['base_amount'], 50000);
    }

    public function testPaymentCreateWithDccInvalidCurrencyRequestId()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = "currencyRequestId";

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentFlowsDccDisabledMerchants()
    {
        $this->fixtures->merchant->removeFeatures(['dcc']);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentFlowsNonInternationalCards()
    {
        $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'IN', 'issuer' => 'UTIB', 'network' => 'Visa',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData($iin));
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentFlowsDccNonSupportedNetworkCards()
    {
        $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'RUPAY',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData($iin));
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentFlowsCurrencyInfoWithToken()
    {
        $token = $this->fixtures->create('token', [
            'method'  => 'card',
            'card_id' => '100000001lcard',
            'bank'    => null,
            'wallet'  => null
        ]);

        $card = $this->getDbEntityById('card', $token['card_id']);

        $this->fixtures->iin->edit($card['iin'], ['country' => 'US', 'network' => 'Visa']);

        $tokenId = 'token_' . $token['id'];

        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'token' => $tokenId],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);
    }

    private function getDefaultPaymentFlowsRequestData($iin = null)
    {
        if ($iin === null)
        {
            $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'Visa',
                'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);
        }

        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'iin' => $iin->getIin()],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }
}
