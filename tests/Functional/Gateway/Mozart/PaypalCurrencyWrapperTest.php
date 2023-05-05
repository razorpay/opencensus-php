<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Services\SplitzService;
use Mockery;

class PaypalCurrencyWrapperTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PaypalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal', ['currency' => ['USD'], 'merchant_id' => '10000000000000']);

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

        $this->payment = $this->getDefaultWalletPaymentArray('paypal');

        $this->ba->privateAuth();
    }

    public function testPaymentCreate()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $walletCurrency = $responseContent['wallet_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $walletCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $usdAmount = $responseContent['all_currencies'][$walletCurrency]['amount'];
        $payment = $this->payment;
        $payment['dcc_currency'] = $walletCurrency;
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($walletCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($usdAmount, $responseContent['gateway_amount']);
        $this->assertEquals($walletCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateWithInvalidCurrencyRequestId()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $walletCurrency = $responseContent['wallet_currency'];

        $payment = $this->payment;
        $payment['dcc_currency'] = $walletCurrency;
        $payment['currency_request_id'] = "currencyRequestId";

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentFlowsRestrictINR(){
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal', ['currency' => ['USD', 'INR'], 'merchant_id' => '10000000000000', 'id'=> '2ShrdPaypalTml']);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $response_content = json_decode($response->getContent(), true);

        $currencies = $response_content['all_currencies'];
        self::assertFalse(array_search(Currency::INR, $currencies));
        self::assertArrayHasKey(Currency::USD, $currencies);
    }

    private function getDefaultPaymentFlowsRequestData()
    {
        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'wallet' => 'paypal'],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

    protected function runPaymentCallbackFlowWalletPaypal($response, &$callback = null)
    {
        $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response,$callback);

        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

        return $this->submitPaymentCallbackRequest($data);
    }

    public function testPaypalSupportedCurrencies()
    {
        $payment = $this->payment;
        $payment['dcc_currency'] = Currency::NGN;
        $payment['currency_request_id'] = "currencyRequestId";

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaypalSupportedCurrenciesForNonDcc()
    {
        $payment = $this->payment;

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function () use ($payment) {
            $this->doAuthPayment($payment);
        });
    }
}
