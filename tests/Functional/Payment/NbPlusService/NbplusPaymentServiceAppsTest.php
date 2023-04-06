<?php

namespace RZP\Tests\Functional\Payment;

use App;
use Mockery;

use RZP\Constants\Mode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbPlusPaymentServiceAppsTest extends TestCase
{
    use PaymentNbplusTrait;

    const AUTHORIZE_ACTION_INPUT = [
        'payment',
        'callbackUrl',
        'otpSubmitUrl',
        'payment_analytics',
        'token',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
    ];

    const CALLBACK_ACTION_INPUT = [
        'callbackUrl',
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return 'nbplusps';
                })
            );

        $this->terminal = $this->fixtures->create('terminal:twid_terminal');

        $this->provider = "twid";

        $this->fixtures->merchant->enableApp('10000000000000', 'twid');

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\AppMethod', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->payment = $this->getDefaultAppPayment($this->provider);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testAuthorize()
    {
        $paymentArray = $this->payment;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $acquirerData = [
            'transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment[Payment\Entity::ACQUIRER_DATA]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithINRCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testTrustlyPaymentWithoutBillingAddress()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        unset($paymentArray["billing_address"]);

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            BadRequestException::class,"Billing Address is Empty");
    }

    public function testTrustlyPaymentWithoutFirstOrLastName()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        unset($paymentArray["billing_address"]["first_name"]);
        unset($paymentArray["billing_address"]["last_name"]);

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            BadRequestException::class,"First or Last Name is Empty");
    }

    public function testSavedBillingAddressDetailsForTrustlyPayment()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;

        $expectedBillingAddress =  $paymentArray['billing_address'];

        // We Save Concatenation of First and Last name as name in addresses table.
        $expectedBillingAddress["name"] = $expectedBillingAddress['first_name'] . " " . $expectedBillingAddress['last_name'];
        unset($expectedBillingAddress['first_name']);
        unset($expectedBillingAddress['last_name']);

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        // Validate Saved Billing Address for a Payment
        $paymentEntity = $this->getDbEntityById('payment', $payment['id']);
        $actualAddress = $paymentEntity->fetchBillingAddress();

        $this->assertEquals($expectedBillingAddress['name'], $actualAddress->getName());
        $this->assertEquals($expectedBillingAddress['line1'], $actualAddress->getLine1());
        $this->assertEquals($expectedBillingAddress['line2'], $actualAddress->getLine2());
        $this->assertEquals($expectedBillingAddress['city'], $actualAddress->getCity());
        $this->assertEquals($expectedBillingAddress['postal_code'], $actualAddress->getZipCode());
        $this->assertEquals($expectedBillingAddress['state'], $actualAddress->getState());
        $this->assertEquals($expectedBillingAddress['country'], $actualAddress->getCountry());

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);
    }

    public function testAuthorizeTrustlyPaymentWithUSDCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'USD';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'USD';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithGatewaySupportedCurrency()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);

        $this->assertEquals(false,$payment['dcc_offered']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeTrustlyPaymentWithGatewaySupportedCurrencyButChooseDCC()
    {
        $this->setConfigurationInternationalApp('trustly');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'GBP';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizePoliPaymentWithINRCurrency()
    {
        $this->setConfigurationInternationalApp('poli');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';
        $flowsRequestData['content']['provider'] = 'poli';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'AUD';

        $this->assertEquals("AUD", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizePoliPaymentWithGatewaySupportedCurrency()
    {
        $this->setConfigurationInternationalApp('poli');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'AUD';
        $flowsRequestData['content']['provider'] = 'poli';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'AUD';

        $this->assertEquals("AUD", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'AUD';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPayment($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);

        $this->assertEquals(false,$payment['dcc_offered']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeCaptureAndRefundPoliPayment()
    {
        $this->setConfigurationInternationalApp('poli');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'INR';
        $flowsRequestData['content']['provider'] = 'poli';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'AUD';

        $this->assertEquals("AUD", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthCaptureAndRefundPaymentViaAjaxRoute($paymentArray);
            },
            BadRequestException::class);

    }

    public function testVerify()
    {
        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $response = $this->doAuthPayment($paymentArray);

        $this->verifyPayment($response['razorpay_payment_id']);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    public function testPaymentFailedVerifySuccess()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->verifyPayment($payment[Payment\Entity::ID]);
            },
            PaymentVerificationException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(0, $payment[Payment\Entity::VERIFIED]);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->authorizedFailedPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertTrue($payment[Payment\Entity::LATE_AUTHORIZED]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $acquirerData = [
            'transaction_id' => '1234'
        ];

        $this->assertArraySelectiveEquals($acquirerData, $payment[Payment\Entity::ACQUIRER_DATA]);
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                        ]
                    ],
                ];
            }

            if ($action === NbPlusPaymentService\Action::VERIFY)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_CANCELLED_BY_USER'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);

        $this->verifyPayment($payment[Payment\Entity::ID]);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);

        // error code is updated on verify response
        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER, $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testAuthorizeHandleErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE  => null,
                NbPlusPaymentService\Response::ERROR     => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED'
                    ]
                ],
            ];
        });

        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals('BAD_REQUEST_ERROR', $payment[Payment\Entity::ERROR_CODE]);

        $this->assertEquals('BAD_REQUEST_PAYMENT_FAILED', $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    public function testAuthorizeHandleGatewayErrorResponse()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'GATEWAY_ERROR_UNKNOWN_ERROR'
                    ]
                ],
            ];
        });

        $paymentArray = $this->getDefaultAppPayment($this->provider);

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);

        $this->assertEquals('GATEWAY_ERROR', $payment[Payment\Entity::ERROR_CODE]);

        $this->assertEquals('GATEWAY_ERROR_UNKNOWN_ERROR', $payment[Payment\Entity::INTERNAL_ERROR_CODE]);
    }

    private function getDefaultPaymentFlowsRequestData($provider = 'trustly')
    {
        $flowsData = [
            'content' => ['amount' => 100000, 'currency' => 'INR', 'provider' => $provider],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        return $flowsData;
    }

    private function setConfigurationInternationalApp($provider = 'trustly'){

        $this->terminal = $this->fixtures->create('terminal:emerchantpay_terminal');

        $this->provider = $provider;

        $this->fixtures->merchant->enableApp('10000000000000', $provider);

        $this->fixtures->merchant->addFeatures(['address_name_required']);

        $this->fixtures->merchant->edit('10000000000000');

        $this->payment = $this->getDefaultAppPayment($this->provider);

        $this->payment['_']['library'] = 'checkoutjs';
        $this->payment['billing_address'] = $this->getBillingAddressDetails();

        $this->ba->privateAuth();
    }

    private function getBillingAddressDetails(){
        $billing_address['first_name'] = "Max";
        $billing_address['last_name']  = "Musterman";
        $billing_address['line1'] = "91,Apartment 7R";
        $billing_address['line2'] = "Wellington Street";
        $billing_address['city'] = "Striya";
        $billing_address['state'] = "Tauchen";
        $billing_address['country'] = "at";
        $billing_address['postal_code'] = "202112";

        return $billing_address;
    }

    public function testAuthorizeSofortPaymentWithINRCurrency(){

        $this->setConfigurationInternationalApp('sofort');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData('sofort');

        $response = $this->sendRequest($flowsRequestData);

        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';
        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeSofortPaymentWithGatewaySupportedCurrency()
    {
        $this->setConfigurationInternationalApp('sofort');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';
        $flowsRequestData['content']['provider'] = 'sofort';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);

        $this->assertEquals(false,$payment['dcc_offered']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeSofortPaymentWithUSDCurrency()
    {
        $this->setConfigurationInternationalApp('sofort');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'USD';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'USD';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeGiropayPaymentWithINRCurrency(){

        $this->setConfigurationInternationalApp('giropay');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData('giropay');

        $response = $this->sendRequest($flowsRequestData);

        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';
        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeGiropayPaymentWithGatewaySupportedCurrency()
    {
        $this->setConfigurationInternationalApp('giropay');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'EUR';
        $flowsRequestData['content']['provider'] = 'giropay';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'EUR';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);

        $this->assertEquals(false,$payment['dcc_offered']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testAuthorizeGiropayPaymentWithUSDCurrency()
    {
        $this->setConfigurationInternationalApp('giropay');

        $flowsRequestData = $this->getDefaultPaymentFlowsRequestData();
        $flowsRequestData['content']['currency'] = 'USD';

        $response = $this->sendRequest($flowsRequestData);
        $responseContent = json_decode($response->getContent(), true);

        $app_currency = $responseContent['app_currency'];
        $currencyRequestId = $responseContent['currency_request_id'];
        $customerSelectedCurrency = 'EUR';

        $this->assertEquals("EUR", $app_currency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($currencyRequestId);

        $convertedCurrency = $responseContent['all_currencies'][$customerSelectedCurrency]['amount'];

        $paymentArray = $this->payment;
        $paymentArray['currency'] = 'USD';

        $paymentArray['dcc_currency'] = $customerSelectedCurrency;
        $paymentArray['currency_request_id'] = $currencyRequestId;


        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals("authorized", $payment['status']);
        $this->assertEquals($payment['id'], 'pay_' . $paymentMeta['payment_id']);
        $this->assertEquals($customerSelectedCurrency, $paymentMeta['gateway_currency']);
        $this->assertEquals($convertedCurrency, $paymentMeta['gateway_amount']);

        //Payment entity fetch with Admin auth
        $responseContent = $this->getEntityById('payment', $paymentMeta['payment_id'], true);

        $this->assertEquals(true, $responseContent['dcc']);
        $this->assertEquals($convertedCurrency, $responseContent['gateway_amount']);
        $this->assertEquals($customerSelectedCurrency, $responseContent['gateway_currency']);
        $this->assertEquals($paymentMeta['forex_rate'], $responseContent['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $responseContent['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $responseContent['dcc_mark_up_percent']);

        $dccMarkupAmount = (int) ceil(($payment['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;

        $this->assertEquals($dccMarkupAmount, $responseContent['dcc_markup_amount']);

        $this->assertEquals(6,$responseContent['dcc_mark_up_percent']);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $this->assertEquals($this->terminal->getId(), $payment[Payment\Entity::TERMINAL_ID]);
    }

    public function testPaymentCreateForAppWithDCCS2SJson()
    {

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $this->setConfigurationInternationalApp();
        $this->redirectToDCCInfo = false;
        $this->redirectToUpdateAndAuthorize = false;

        $paymentArray = $this->payment;
        unset($paymentArray['_']);
        unset($paymentArray['billing_address']);

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $responseContent = $this->doS2SPrivateAuthJsonPayment($paymentArray);

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
        $this->assertEquals($paymentEntity['id'], $content['razorpay_payment_id']);
        $this->assertEquals($paymentEntity['cps_route'],3);
        $this->assertEquals('EUR', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);
        $dccMarkupAmount = (int) ceil(($paymentArray['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;
        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testPaymentCreateForAppWithDCConMCCS2SJson()
    {

        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);
        $this->setConfigurationInternationalApp();
        $this->redirectToDCCInfo = false;
        $this->redirectToUpdateAndAuthorize = false;

        $paymentArray = $this->payment;
        $paymentEntity['currency'] = 'USD';
        unset($paymentArray['_']);
        unset($paymentArray['billing_address']);

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });

        $responseContent = $this->doS2SPrivateAuthJsonPayment($paymentArray);

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
        $this->assertEquals($paymentEntity['id'], $content['razorpay_payment_id']);
        $this->assertEquals($paymentEntity['cps_route'],3);
        $this->assertEquals('EUR', $paymentMeta['gateway_currency']);
        $this->assertEquals(true, $paymentEntity['dcc']);
        $this->assertEquals($paymentMeta['forex_rate'], $paymentEntity['forex_rate']);
        $this->assertEquals($paymentMeta['dcc_offered'], $paymentEntity['dcc_offered']);
        $this->assertEquals($paymentMeta['dcc_mark_up_percent'], $paymentEntity['dcc_mark_up_percent']);
        $dccMarkupAmount = (int) ceil(($paymentArray['amount'] * $paymentMeta['forex_rate'] * $paymentMeta['dcc_mark_up_percent'])/100) ;
        $this->assertEquals($dccMarkupAmount, $paymentEntity['dcc_markup_amount']);
    }

    public function testTrustlyPaymentFromRazorpayjsLibrary()
    {
        $this->setConfigurationInternationalApp('trustly');

        $paymentArray = $this->payment;
        $paymentArray['_']['library'] = \RZP\Models\Payment\Analytics\Metadata::RAZORPAYJS;

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
                    break;
            }
        });
        $responseContent = $this->doAuthPaymentViaAjaxRoute($paymentArray);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->ba->privateAuth();

        $paymentEntity = $this->getEntityById('payment', $responseContent['razorpay_payment_id'],true);
        $paymentMeta = $this->getLastEntity('payment_meta', true);

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($paymentEntity['id'], $responseContent['razorpay_payment_id']);
        $this->assertEquals($paymentEntity['cps_route'],3);
        $this->assertEquals('EUR', $paymentMeta['gateway_currency']);
    }
}
