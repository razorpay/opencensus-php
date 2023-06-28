<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Database\Eloquent\Factory;
use RZP\Constants\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Terminal\Type;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Fixtures\Entity\Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;

class PaymentCreateDCCTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateDCCTestData.php';

        parent::setUp();

        $this->payment = $this->getPaymentArrayInternational();

        $this->ba->privateAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    private function mockRazorxWith(string $featureUnderTest, string $value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->will(
            $this->returnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            ));
    }

    public function testPaymentCreateWithDCCS2SFeatureNotEnabled()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->fixtures->merchant->addFeatures(['disable_native_currency']);
        $responseContent = $this->doS2SPrivateAuthAndCapturePayment($payment);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $responseContent['id'],true);

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertEquals(false, $paymentEntity['dcc']);
    }

    public function testPaymentCreateWithDCCS2SNonINR()
    {
        $payment = $this->payment;
        $payment['currency'] = 'EUR';
        $this->fixtures->merchant->addFeatures(['s2s']);
        $responseContent = $this->doS2SPrivateAuthAndCapturePayment($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $responseContent['id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCS2SRedirect()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s']);
        $responseContent = $this->doS2SPrivateAuthAndCapturePayment($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $responseContent['id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCS2SJson()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $response = $this->makeRedirectToDCCInfo($redirectContent['url']);

        $content = $this->getJsonContentFromResponse($response, null);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $content['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentValidateAndRedirectDCCS2S()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/dcc_info');

        $this->redirectToDCCInfo = true;

        $url = $this->getPaymentRedirectToDCCInfoUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $firstRequest = [
            'content'=>['currency_request_id'=>$content['currency_request_id'],'dcc_currency'=>$content['dcc_currency']],
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getEntityById('payment', $id,true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $SecondRequest = [
            'content'=>['currency_request_id'=>$content['currency_request_id'],'dcc_currency'=>$content['dcc_currency']],
            'method'=>$method,
            'url'=>$url
        ];

        try {
            $SecondResponse=$this->sendRequest($SecondRequest);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("Duplicate request. This request has already been processed.", $e->getMessage());
        }
    }

    protected function paymentValidateAndRedirectDCCS2SForShaadiCom(bool $featureEnabled=true)
    {
        $payment = $this->payment;
        $features = array('s2s','s2s_json');

        $this->fixtures->merchant->addFeatures($features);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/dcc_info');

        $this->redirectToDCCInfo = true;

        $url = $this->getPaymentRedirectToDCCInfoUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['dcc_currency'] = "BHD";

        $firstRequest = [
            'content'=>['currency_request_id'=>$content['currency_request_id'],'dcc_currency'=>$content['dcc_currency']],
            'method'=>$method,
            'url'=>$url
        ];

        $this->sendRequest($firstRequest);

        return $id;
    }

    public function testPaymentValidateMCCS2SForShaadiCom()
    {
        $payment = $this->payment;
        $features = array('s2s','s2s_json');

        $this->fixtures->merchant->addFeatures($features);
        $payment['amount'] = 5000;
        $payment['currency'] = 'KWD';

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/dcc_info');

        $paymentEntity = $this->getEntityById('payment', $id,true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals(10,$paymentMeta['mcc_forex_rate']);
    }

    protected function enableFeatureOnMerchant($feature){
        $features[] = $feature;
        $this->fixtures->merchant->addFeatures($features);
    }

    // Generate IIN as required
    protected function createIIN($iinNumber,$iinCountry){
        $iin = $this->fixtures->iin->create(['iin' => $iinNumber, 'country' => $iinCountry, 'issuer' => 'UTIB', 'network' => 'Visa',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1',]]);
        return $iin;
    }

    protected function getFlowsData($iin){
        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'iin' => $iin->getIin()],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];
        return $this->sendRequest($flowsData);
    }

    public function testKWDInStandardCheckout()
    {
        $iin = $this->createIIN('542859','KW');
        $responseContent = json_decode($this->getFlowsData($iin)->getContent(), true);
        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $showMarkup = $responseContent['show_markup'];

        // KWD shouldnt be selected as the feature is not for this merchant
        $this->assertEquals("KWD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);
        $this->assertEquals(false, $showMarkup);

        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['card']['number'] = '5428590000004146';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);
    }

    public function testPaymentValidateAndRedirectDCCS2SForShaadiComFeatureEnabled()
    {
        $id = $this->paymentValidateAndRedirectDCCS2SForShaadiCom();

        $paymentEntity = $this->getEntityById('payment', $id,true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('BHD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);
    }

    public function testPaymentCreateWithDCC()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $showMarkup = $responseContent['show_markup'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);
        $this->assertEquals(false, $showMarkup);

        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];
        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($usdAmount, $responseContent['gateway_amount']);
        $this->assertEquals($cardCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDynamicMarkupDCC()
    {
        $dccMarkupPercent = 3.23;
        $this->fixtures->merchant->addDccPaymentConfig($dccMarkupPercent);
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
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $dccMarkupPercent);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;
        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDynamicMarkupDCCINR()
    {
        $dccMarkupPercent = 3;
        $this->fixtures->merchant->addDccPaymentConfig($dccMarkupPercent);
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
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['amount'], $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals($paymentMeta['gateway_amount'], $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentFetchWithDCCINR()
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
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['amount'], $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals($paymentMeta['gateway_amount'], $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCInternationalDisabledMerchant()
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

        $this->fixtures->merchant->disableInternational();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow( $testData, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals("failed", $payment['status']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $payment['id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertFalse($responseContent['dcc']);
    }

    public function testPaymentCreateDccWithDomesticCard()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $payment = $this->payment;
        $payment['card']['number']  =  '4012001038443335';
        $payment['dcc_currency'] = 'USD';
        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals(50000, $payment['base_amount']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('INR', $payment['currency']);
        $this->assertNull($paymentMeta);

        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $payment['id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals(50000, $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
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
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

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

        $this->assertEquals(true, $payment['convert_currency']);
        $this->assertEquals(50000, $payment['base_amount']);
        $this->assertEquals(5000, $payment['amount']);
        $this->assertEquals('USD', $payment['currency']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $payment['id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['mcc']);
        $this->assertEquals(false, $responseContent['dcc']);

        $forexRateApplied = number_format($payment['base_amount']/$payment['amount'], 6, '.', ',');
        $forexRateReceived = number_format($forexRateApplied/0.99, 6, '.', ',');

        $this->assertEquals($forexRateApplied, $responseContent['forex_rate_applied']);
        $this->assertEquals($forexRateReceived, $responseContent['forex_rate_received']);
    }

    public function testDccForeignCurrencyMerchantInternationalCard()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $payment = $this->payment;
        $payment['currency'] = 'EUR';

        $flowsData = [
            'content' => ['amount' => $payment['amount'], 'currency' => $payment['currency'], 'card_number' => $payment['card']['number']],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];
        $cardCurrency = $responseContent['card_currency'];

        $payment = $this->payment;
        $payment['currency'] = 'EUR';
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;

        $usdAmount = $responseContent['all_currencies'][$payment['dcc_currency']]['amount'];
        $inrAmount = $responseContent['all_currencies']['INR']['amount'];
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);


        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals(null, $payment['convert_currency']);
        $this->assertEquals(490000, $payment['base_amount']);
        $this->assertEquals('USD', $cardCurrency);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);


        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($usdAmount, $responseContent['gateway_amount']);
        $this->assertEquals($paymentMeta['gateway_currency'], $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testDccForeignCurrencyMerchantInternationalCardINRSelected()
    {
        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'EUR']);

        $payment = $this->payment;
        $payment['currency'] = 'EUR';

        $flowsData = [
            'content' => ['amount' => $payment['amount'], 'currency' => $payment['currency'], 'card_number' => $payment['card']['number']],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];

        $payment = $this->payment;
        $payment['currency'] = 'EUR';
        $payment['dcc_currency'] = 'INR';
        $payment['currency_request_id'] = $currencyRequestId;

        $inrAmount = $responseContent['all_currencies']['INR']['amount'];
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals(null, $payment['convert_currency']);
        $this->assertEquals(490000, $payment['base_amount']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('EUR', $payment['currency']);
        $this->assertEquals($inrAmount, $paymentMeta['gateway_amount']);
        $this->assertEquals('INR', $paymentMeta['gateway_currency']);
        $this->assertEquals(8, $paymentMeta['dcc_mark_up_percent']);


        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['mcc']);
        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($inrAmount, $responseContent['gateway_amount']);
        $this->assertEquals($paymentMeta['gateway_currency'], $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testDccForeignCurrencyMerchantInternationalCardMerchantCurrencySelected()
    {
        $this->fixtures->create('order', [ 'amount' => 5000, 'currency' => 'EUR']);

        $payment = $this->payment;
        $payment['currency'] = 'EUR';

        $flowsData = [
            'content' => ['amount' => $payment['amount'], 'currency' => $payment['currency'], 'card_number' => $payment['card']['number']],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $currencyRequestId = $responseContent['currency_request_id'];

        $payment = $this->payment;
        $payment['currency'] = 'EUR';
        $payment['dcc_currency'] = 'EUR';
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals(null, $payment['convert_currency']);
        $this->assertEquals(490000, $payment['base_amount']);
        $this->assertEquals($paymentMeta['gateway_amount'], $payment['amount']);
        $this->assertEquals($paymentMeta['gateway_currency'], $payment['currency']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals($paymentMeta['gateway_amount'], $responseContent['gateway_amount']);
        $this->assertEquals($paymentMeta['gateway_currency'], $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals(0, $responseContent['dcc_mark_up_percent']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDccInvalidCurrencyRequestId()
    {
        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];

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
        $this->fixtures->merchant->addFeatures([Constants::DISABLE_NATIVE_CURRENCY]);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentFlowsInternationalDisabledMerchant()
    {
        $this->fixtures->merchant->disableInternational();
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

    public function testPaymentFlowsDccBlacklistedIIINS()
    {
        $iin = $this->fixtures->iin->create(['iin' => '414366', 'country' => 'US', 'issuer' => 'UTIB', 'network' => 'Visa',
            'flows'   => ['3ds' => '1', 'pin' => '1', 'otp' => '1','dcc_blacklisted' => '1',]]);

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData($iin));
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentFlowsCurrencyInfoWithToken()
    {
        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'token' => $this->getTokenIdForDCC()],
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

    public function testGetPaymentFlowsCurrencyInfoWithToken()
    {
        $flowsData = [
            'content' => ['amount' => 50000, 'currency' => 'INR', 'token' => $this->getTokenIdForDCC()],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $this->ba->publicAuth();

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);
    }

    public function testPaymentFlowsWithNewDccResponseParams()
    {
        $flowsData = $this->getDefaultPaymentFlowsRequestData();
        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $cardCurrencyObject = $responseContent['all_currencies'][$cardCurrency];

        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === true);
        $this->assertNotNull($cardCurrencyObject);

        $forexRate = number_format($cardCurrencyObject['forex_rate'],6, '.','');
        $fee = $cardCurrencyObject['fee'];
        $amount = $cardCurrencyObject['amount'];
        $baseAmount = $flowsData['content']['amount'];
        $markup = 0.08;
        $feeExpected = $forexRate * $markup * $baseAmount;
        $feeExpected = number_format($feeExpected, 2, '.','');

        $amountExpected = ceil($forexRate * $markup * $baseAmount + $forexRate * $baseAmount);

        $this->assertEquals(10, $forexRate);
        $this->assertEquals($feeExpected, $fee);
        $this->assertEquals($amountExpected, $amount);
    }

    public function testPaymentFlowsWithDynamicMarkup()
    {
        $dccMarkupPercent = 3.24;
        $this->fixtures->merchant->addDccPaymentConfig($dccMarkupPercent);
        $flowsData = $this->getDefaultPaymentFlowsRequestData();
        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $cardCurrencyObject = $responseContent['all_currencies'][$cardCurrency];

        $this->assertNotNull($cardCurrencyObject);

        $forexRate = number_format($cardCurrencyObject['forex_rate'],6, '.','');
        $fee = $cardCurrencyObject['fee'];
        $amount = $cardCurrencyObject['amount'];
        $baseAmount = $flowsData['content']['amount'];

        $dccMarkup = $dccMarkupPercent/100;
        $feeExpected = $forexRate * $dccMarkup * $baseAmount;
        $feeExpected = number_format($feeExpected, 2, '.','');

        $amountExpected = ceil($forexRate * $dccMarkup * $baseAmount + $forexRate * $baseAmount);

        $this->assertEquals(10, $forexRate);
        $this->assertEquals($feeExpected, $fee);
        $this->assertEquals($amountExpected, $amount);
    }

    private function getTokenIdForDCC()
    {
        $token = $this->fixtures->create('token', [
            'method'  => 'card',
            'card_id' => '100000001lcard',
            'bank'    => null,
            'wallet'  => null
        ]);

        $card = $this->getDbEntityById('card', $token['card_id']);

        $this->fixtures->iin->edit($card['iin'], ['country' => 'US', 'network' => 'Visa']);

        return 'token_' . $token['id'];
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

    private function getPaymentArrayInternational()
    {
        $paymentArray = $this->getDefaultPaymentArray();
        $paymentArray['card']['number'] = '4012010000000007';

        return $paymentArray;
    }

    private function getPaymentArrayInternationalForRecurringAutoOnDirect()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['card']['number'] = '4012010000000007';
        $paymentArray['meta']['action_type'] = "capture";
        $paymentArray['meta']['reference_id'] = "G3wgYct2N47hhWWqCLsLMsy";
        $paymentArray['method'] = "card";
        $paymentArray['recurring'] = "auto";

        unset($paymentArray['bank']);
        unset($paymentArray['description']);
        unset($paymentArray['notes']);

        return $paymentArray;
    }

    public function testForceOfferPaymentCreateWithDCC()
    {
        $this->mockCardVaultWithCryptogram();

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $flowsData = $this->getDefaultPaymentFlowsRequestData();
        $flowsData['content']['amount'] = 90000;

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $payment = $this->payment;
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;


        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $paymentMeta = $this->getLastEntity('payment_meta', true);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);
    }

    public function testOfferPaymentCreateWithDCC()
    {
        $this->mockCardVaultWithCryptogram();

        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers([$offer]);

        $flowsData = $this->getDefaultPaymentFlowsRequestData();
        $flowsData['content']['amount'] = 90000;

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $payment = $this->payment;
        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();
        $payment['offer_id'] = $offer->getPublicId();
        $payment['dcc_currency'] = $cardCurrency;
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(90000, $payment['amount']);
        $this->assertEquals('authorized', $payment['status']);

        $paymentMeta = $this->getLastEntity('payment_meta', true);
        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($usdAmount, $paymentMeta['gateway_amount']);
    }

    public function testOfferPaymentCreateWithDCCINR()
    {
        $offer = $this->fixtures->create('offer');

        $order = $this->fixtures->order->createWithOffers([$offer]);

        $flowsData = $this->getDefaultPaymentFlowsRequestData();
        $flowsData['content']['amount'] = 90000;

        $response = $this->sendRequest($flowsData);
        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];

        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $payment = $this->payment;
        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();
        $payment['offer_id'] = $offer->getPublicId();
        $payment['dcc_currency'] = 'INR';
        $payment['currency_request_id'] = $currencyRequestId;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CHECKOUTJS;

        $this->mockCardVaultWithCryptogram();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals($payment['amount'], $paymentMeta['gateway_amount']);

        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
    }

    public function testPaymentCreateWithDCCCustomCheckout()
    {
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;
        $this->mockRazorxWith(RazorxTreatment::DCC_ON_INTERNATIONAL);
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCCustomLibrary()
    {
        $this->fixtures->merchant->addFeatures([Constants::DCC_ON_OTHER_LIBRARY]);
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CUSTOM;
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCEmbeddedLibrary()
    {
        $this->fixtures->merchant->addFeatures([Constants::DCC_ON_OTHER_LIBRARY]);
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::EMBEDDED;
        $this->mockRazorxWith(RazorxTreatment::DCC_ON_INTERNATIONAL);
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCDirectLibrary()
    {
        $payment = $this->payment;
        //setMetadataForPublicAuthPayment will take care of setting library to direct
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCDirectLibraryWithMetadata()
    {
        $payment = $this->payment;
        $payment['meta']['action_type'] = 'authenticate';
        $payment['meta']['reference_id'] = 'G3oNMjSDsXfRp';

        //setMetadataForPublicAuthPayment will take care of setting library to direct
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);
        $this->assertEquals($paymentMeta['action_type'], $payment['meta']['action_type']);
        $this->assertEquals($paymentMeta['reference_id'], $payment['meta']['reference_id']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);

        $url = 'http://localhost/v1/payments/'.$paymentMeta['payment_id'].'/updateAndRedirect';

        $duplicateRequest = [
            'content'=>['currency_request_id'=>'Iqweyfh','dcc_currency'=>$paymentMeta['gateway_currency']],
            'method'=>'post',
            'url'=>$url
        ];

        try {
            $SecondResponse=$this->sendRequest($duplicateRequest);
            $responseContent = json_decode($SecondResponse->getContent(), true);

            $this->assertEquals($paymentEntity['id'],$responseContent['razorpay_payment_id']);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("Duplicate request. This request has already been processed.", $e->getMessage());
        }
    }

    public function testPaymentCreateWithDCCCustomLibraryWithoutFeature()
    {
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::CUSTOM;
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCEmbeddedLibraryWithoutFeature()
    {
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::EMBEDDED;
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);


        $this->assertTrue($this->redirectToDCCInfo);
        $this->assertTrue($this->redirectToUpdateAndAuthorize);

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals('USD', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateWithDCCDirectLibraryWithoutFeature()
    {
        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentCustomCheckoutDCCDisabledMerchants()
    {
        $this->fixtures->merchant->addFeatures([Constants::DISABLE_NATIVE_CURRENCY]);
        $this->mockRazorxWith(RazorxTreatment::DCC_ON_INTERNATIONAL);

        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testPaymentCustomCheckoutRazorXNegative()
    {
        $this->mockRazorxWith(RazorxTreatment::DCC_ON_INTERNATIONAL);

        $payment = $this->payment;
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;
        $this->mockRazorxWith(RazorxTreatment::DCC_ON_INTERNATIONAL, 'control');
        $responseContent = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
    }

    public function testShowMorTncForS2S()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json','show_mor_tnc']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/dcc_info');

        $this->redirectToDCCInfo = true;

        $url = $this->getPaymentRedirectToDCCInfoUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();

        $this->assertTrue(str_contains($content, '"show_mor_tnc":true'));
    }

    public function testNegativeFlowShowMorTncForS2S()
    {
        $payment = $this->payment;
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);
        $this->assertArrayHasKey('next', $responseContent);
        $this->assertArrayHasKey('action', $responseContent['next'][0]);
        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToDCCInfoUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/dcc_info');

        $this->redirectToDCCInfo = true;

        $url = $this->getPaymentRedirectToDCCInfoUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();

        $this->assertTrue(str_contains($content, '"show_mor_tnc":false'));
    }

    public function testPaymentCreateRecurringAutoOnDirectWithDCC()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $payment = $this->getPaymentArrayInternationalForRecurringAutoOnDirect();
        $payment['card']['number'] = '4012010000000007';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;

        $this->fixtures->merchant->addFeatures(['recurring_auto']);

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment->getStatus());
        $this->assertEquals($payment->getPublicId(), 'pay_' . $paymentMeta['payment_id']);

        $cardCurrency = "USD";
        $cardCurrencyGatewayAmount = $payment->getAmount() * 10;
        $cardCurrencyGatewayAmount += (int) ceil(($cardCurrencyGatewayAmount * ($payment->merchant->getDccRecurringMarkupPercentage()/100)));

        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($cardCurrencyGatewayAmount, $paymentMeta['gateway_amount']);

        $this->assertEquals("capture", $paymentMeta['action_type']);
        $this->assertEquals("G3wgYct2N47hhWWqCLsLMsy", $paymentMeta['reference_id']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($cardCurrencyGatewayAmount, $responseContent['gateway_amount']);
        $this->assertEquals($cardCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount,$responseContent['dcc_markup_amount']);
    }

    public function testPaymentCreateRecurringAutoOnDirectWithDynamicMarkupDCC()
    {
        $dccMarkupPercent = 2;
        $this->fixtures->merchant->addDccRecurringPaymentConfig($dccMarkupPercent);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $payment = $this->getPaymentArrayInternationalForRecurringAutoOnDirect();
        $payment['card']['number'] = '4012010000000007';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;

        $this->fixtures->merchant->addFeatures(['recurring_auto']);

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment->getStatus());
        $this->assertEquals($payment->getPublicId(), 'pay_' . $paymentMeta['payment_id']);

        $cardCurrency = "USD";
        $cardCurrencyGatewayAmount = $payment->getAmount() * 10;
        $cardCurrencyGatewayAmount += (int) ceil(($cardCurrencyGatewayAmount * ($payment->merchant->getDccRecurringMarkupPercentage()/100)));

        $this->assertEquals($cardCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($cardCurrencyGatewayAmount, $paymentMeta['gateway_amount']);

        $this->assertEquals("capture", $paymentMeta['action_type']);
        $this->assertEquals("G3wgYct2N47hhWWqCLsLMsy", $paymentMeta['reference_id']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($cardCurrencyGatewayAmount, $responseContent['gateway_amount']);
        $this->assertEquals($cardCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount,$responseContent['dcc_markup_amount']);
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testPaymentCreateRecurringAutoOnDirectWithDCCExperimentOff()
    {
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($output);

        $payment = $this->getPaymentArrayInternationalForRecurringAutoOnDirect();
        $payment['card']['number'] = '4012010000000007';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;

        $this->fixtures->merchant->addFeatures(['recurring_auto']);

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment->getStatus());
        $this->assertEquals($payment->getPublicId(), 'pay_' . $paymentMeta['payment_id']);

        $this->assertEquals("capture", $paymentMeta['action_type']);
        $this->assertEquals("G3wgYct2N47hhWWqCLsLMsy", $paymentMeta['reference_id']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals(50000, $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
    }

    public function testPaymentCreateRecurringAutoOnDirectWithDomesticCard()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $payment = $this->getPaymentArrayInternationalForRecurringAutoOnDirect();
        $payment['card']['number'] = '4012001038443335';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;

        $this->fixtures->merchant->addFeatures(['recurring_auto']);

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment->getStatus());
        $this->assertEquals($payment->getPublicId(), 'pay_' . $paymentMeta['payment_id']);

        $this->assertEquals("capture", $paymentMeta['action_type']);
        $this->assertEquals("G3wgYct2N47hhWWqCLsLMsy", $paymentMeta['reference_id']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals(50000, $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
    }

    public function testPaymentCreateRecurringAutoOnDirectWithCurrencyNotSupportedByRzp()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $payment = $this->getPaymentArrayInternationalForRecurringAutoOnDirect();

        $this->fixtures->iin->create(['iin' => '400155', 'country' => 'KW', 'issuer' => 'UTIB', 'network' => 'Visa', 'recurring' => 1,
            'flows'   => ['3ds' => '1']]);

        $payment['card']['number'] = '4001553716254122';
        $payment['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::DIRECT;

        $this->fixtures->merchant->addFeatures(['recurring_auto']);

        $paymentAuth = $this->doAuthPayment($payment);
        $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);

        $this->assertFalse($this->redirectToDCCInfo);
        $this->assertFalse($this->redirectToUpdateAndAuthorize);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("captured", $payment->getStatus());
        $this->assertEquals($payment->getPublicId(), 'pay_' . $paymentMeta['payment_id']);

        $this->assertEquals("capture", $paymentMeta['action_type']);
        $this->assertEquals("G3wgYct2N47hhWWqCLsLMsy", $paymentMeta['reference_id']);

        //Payment entity fetch with Admin auth
        $paymentFetchRequestData = [
            'method'  => 'GET',
            'url'     => '/admin/payment/' . $paymentMeta['payment_id'],
        ];

        $response = $this->sendRequest($paymentFetchRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(false, $responseContent['dcc']);
        $this->assertEquals(50000, $responseContent['gateway_amount']);
        $this->assertEquals('INR', $responseContent['gateway_currency']);
    }

    public function testMccInternationalPaymentWithDccCompliance()
    {
        $payment = $this->fixtures->create('payment:card_captured');
        $payment['currency'] = 'AUD';
        $payment['amount'] ='1000';
        $paymentMetaInput = [
            'gateway_amount'            => '1000',
            'gateway_currency'          => 'AUD',
            'forex_rate'                => '1.23',
            'dcc_offered'               => true,
            'payment_id'                => $payment->getId(),
            'dcc_mark_up_percent'       => '10'
        ];
        $paymentMetaEntity = (new \RZP\Models\Payment\PaymentMeta\Core)->create($paymentMetaInput);
        $paymentMetaEntity->payment()->associate($payment);
        $mockService = \Mockery::mock('\RZP\Models\Merchant\Entity')->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('isFeatureEnabled')->with('send_dcc_compliance')->andReturn(true);
        $mockService->shouldReceive('isFeatureEnabled')->andReturn(false);
        $mockService->shouldReceive('isAVSEnabledInternationalMerchant')->andReturn(false);
        $payment->merchant = $mockService;
        $methodRepoMock = \Mockery::mock('\RZP\Models\Merchant\Methods\Repository');
        $methodRepoMock->shouldReceive('toArray')->andReturn([]);

        $data = $payment->toArrayGateway();
        //ASSERTION

        self::assertEquals(false,$data['dcc']);
        self::assertTrue(empty($data['merchant_currency']));
    }

    public function testInternationalPaymentWithDccCompliance()
    {
        $payment = $this->fixtures->create('payment:card_captured');
        $payment['currency'] = 'INR';
        $payment['amount'] = '60';
        $paymentMetaInput = [
            'gateway_amount'            => '1000',
            'gateway_currency'          => 'AUD',
            'forex_rate'                => '1.23',
            'dcc_offered'               => true,
            'payment_id'                => $payment->getId(),
            'dcc_mark_up_percent'       => '10'
        ];
        $paymentMetaEntity = (new \RZP\Models\Payment\PaymentMeta\Core)->create($paymentMetaInput);
        $paymentMetaEntity->payment()->associate($payment);
        $mockService = \Mockery::mock('\RZP\Models\Merchant\Entity')->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('isFeatureEnabled')->with('send_dcc_compliance')->andReturn(true);
        $mockService->shouldReceive('isFeatureEnabled')->andReturn(false);
        $mockService->shouldReceive('isAVSEnabledInternationalMerchant')->andReturn(false);
        $payment->merchant = $mockService;
        $methodRepoMock = \Mockery::mock('\RZP\Models\Merchant\Methods\Repository');
        $methodRepoMock->shouldReceive('toArray')->andReturn([]);

        $data = $payment->toArrayGateway();
        //ASSERTION

        self::assertEquals(true,$data['dcc']);
        self::assertEquals('INR',$data['merchant_currency']);
    }

    public function testInternationalDccOnMccPaymentWithDccCompliance()
    {
        $payment = $this->fixtures->create('payment:card_captured');
        $payment['currency'] = 'EUR';
        $payment['amount'] = '200';
        $paymentMetaInput = [
            'gateway_amount'            => '1000',
            'gateway_currency'          => 'AUD',
            'forex_rate'                => '1.23',
            'dcc_offered'               => true,
            'payment_id'                => $payment->getId(),
            'dcc_mark_up_percent'       => '10'
        ];
        $paymentMetaEntity = (new \RZP\Models\Payment\PaymentMeta\Core)->create($paymentMetaInput);
        $paymentMetaEntity->payment()->associate($payment);
        $mockService = \Mockery::mock('\RZP\Models\Merchant\Entity')->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('isFeatureEnabled')->with('send_dcc_compliance')->andReturn(true);
        $mockService->shouldReceive('isFeatureEnabled')->andReturn(false);
        $mockService->shouldReceive('isAVSEnabledInternationalMerchant')->andReturn(false);
        $payment->merchant = $mockService;
        $methodRepoMock = \Mockery::mock('\RZP\Models\Merchant\Methods\Repository');
        $methodRepoMock->shouldReceive('toArray')->andReturn([]);

        $data = $payment->toArrayGateway();
        //ASSERTION

        self::assertEquals(true,$data['dcc']);
        self::assertEquals('INR',$data['merchant_currency']);
    }

    public function testInternationalPaymentWithOutDccCompliance()
    {
        //setup
        $payment = $this->fixtures->create('payment:card_captured');
        $paymentMetaInput = [
            'gateway_amount'            => '2000',
            'gateway_currency'          => 'EUR',
            'forex_rate'                => '1.23',
            'dcc_offered'               => true,
            'payment_id'                => $payment->getId(),
            'dcc_mark_up_percent'       => '10'
        ];
        $paymentMetaEntity = (new \RZP\Models\Payment\PaymentMeta\Core)->create($paymentMetaInput);
        $paymentMetaEntity->payment()->associate($payment);
        $mockService = \Mockery::mock('\RZP\Models\Merchant\Entity')->shouldAllowMockingProtectedMethods();
        $mockService->shouldReceive('isFeatureEnabled')->with('send_dcc_compliance')->andReturn(false);
        $mockService->shouldReceive('isFeatureEnabled')->andReturn(false);
        $mockService->shouldReceive('isAVSEnabledInternationalMerchant')->andReturn(false);
        $payment->merchant = $mockService;
        $methodRepoMock = \Mockery::mock('\RZP\Models\Merchant\Methods\Repository');
        $methodRepoMock->shouldReceive('toArray')->andReturn([]);

        //act
        $data = $payment->toArrayGateway();

        //assert
        self::assertTrue(empty($data['dcc']));
        self::assertTrue(empty($data['merchant_currency']));
        self::assertEquals('EUR',$data['currency']);
    }
    public function testPaymentFlowsDccForRaasMerchants()
    {
        $this->fixtures->merchant->addFeatures([Constants::RAAS]);
        $this->fixtures->merchant->enableInternational();

        $response = $this->sendRequest($this->getDefaultPaymentFlowsRequestData());
        $responseContent = json_decode($response->getContent(), true);

        $this->assertTrue(array_key_exists('currency_request_id', $responseContent) === false);
        $this->assertTrue(array_key_exists('all_currencies', $responseContent) === false);
        $this->fixtures->merchant->removeFeatures([Constants::RAAS]);
        $this->fixtures->merchant->disableInternational();
    }
}
