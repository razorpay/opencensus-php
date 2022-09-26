<?php

namespace RZP\Tests\Functional\Payment\NbPlusService;

use App;
use Mockery;
use Carbon\Carbon;

use RZP\Error\Error;
use RZP\Constants\Mode;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Models\Customer\GatewayToken\Entity as GatewayToken;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbplusPaymentServiceEmandateTest extends TestCase
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
        'order',
        'token',
    ];

    const CALLBACK_ACTION_INPUT = [
        'payment',
        'gateway',
        'terminal',
        'merchant',
        'cps_route',
        'merchant_detail',
        'token',
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = 'Functional/Gateway/Netbanking/Icici/EMandate/NetbankingIciciEMandateTestData.php';

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

        $this->enableNbPlusConfig();

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Emandate', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->bank = IFSC::ICIC;

        $this->terminal = $this->fixtures->create('terminal:shared_emandate_icici_terminal');

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray($this->bank);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $this->payment['amount']]);

        $this->payment['order_id'] = $order->getPublicId();

        $this->payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'ICIC0002766',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];
    }

    public function testEMandateInitialPayment()
    {
        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            $assertContent = $content;

            unset($assertContent['input']['gateway_config']);

            $this->assertEquals($this->terminal->getGateway(), $content[NbPlusPaymentService\Request::GATEWAY]);
            $this->assertEquals(true, $this->terminal->isEmandateEnabled());

            switch ($action)
            {
                case NbPlusPaymentService\Action::AUTHORIZE:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::AUTHORIZE_ACTION_INPUT);
                    break;
                case NbPlusPaymentService\Action::CALLBACK:
                    $this->assertArrayKeysExist($assertContent[NbPlusPaymentService\Request::INPUT], self::CALLBACK_ACTION_INPUT);
            }
        });

        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    public function testEMandateScheduledPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(1180, $paymentEntity[Payment::FEE]);
        $this->assertEquals(180, $paymentEntity[Payment::TAX]);

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $paymentEntity[Payment::TOKEN_ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(2360, $payment[Payment::FEE]);
        $this->assertEquals(360, $payment[Payment::TAX]);
    }

    public function testEMandateScheduledPaymentWithoutMethod()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $paymentEntity[Payment::TOKEN_ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        unset($payment[Payment::METHOD]);
        unset($payment[Payment::AUTH_TYPE]);
        unset($payment[Payment::BANK_ACCOUNT]);

        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);
    }

    public function testEMandateScheduledPaymentFailure()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $paymentEntity[Payment::TOKEN_ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        $this->mockScheduledPaymentFailure();

        $data = $this->testData[__FUNCTION__];

        unset($data['exception']['gateway_error_code']);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doS2SRecurringPayment($payment);
        });

        $token   = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals('failed', $payment[Payment::STATUS]);

        $this->assertEquals(1, $token[Token::USED_COUNT]);
        $this->assertEquals(true, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);
    }

    /**
     * This is the case that the SI registration step failed on the bank's end
     */
    public function testEMandateSiRejected()
    {
        $this->mockRejectedToken();

        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateRejectedToken();
    }

    public function testScheduledPaymentWithRejectedToken()
    {
        $this->mockRejectedToken();

        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $token1 = $this->getLastEntity(Entity::TOKEN, true);

        $this->mockRejectedToken(false);

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $paymentEntity[Payment::TOKEN_ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        //
        // Second auth payment for the recurring product.
        // Since an invalid recurring token is used here, the
        // payment will go through like a regular non-recurring payment.
        // This is because it is not a "Second Recurring" payment
        //
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doS2SRecurringPayment($payment);
        });

        $token2 = $this->getLastEntity(Entity::TOKEN, true);

        // We assert that the new payment was initiated using the saved token
        $this->assertEquals($token1[Token::ID], $token2[Token::ID]);
    }

    /**
     * When the SI registration status is not set,
     * we throw an exception. Therefore, the token is not saved with the
     * recurring fields, and no gateway token is created
     */
    public function testSiRecurringStatusNotSet()
    {
        $payment = $this->payment;

        $this->mockSiRecurringStatusFiled();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->assertEMandateStrangeStatus();
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

        // First E-Mandate registration payment
        $this->doAuthPayment($payment);

        $token1        = $this->getLastEntity(Entity::TOKEN, true);
        $gatewayToken1 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::ORDER_ID] = $order->getPublicId();

        // Second E-Mandate registration payment
        $this->doAuthPayment($payment);

        $token2        = $this->getLastEntity(Entity::TOKEN, true);
        $gatewayToken2 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Assert that both the tokens are different
        // Also assert their gateway_tokens are different
        $this->assertNotEquals($token1[Token::ID], $token2[Token::ID]);
        $this->assertNotEquals($token1[Token::GATEWAY_TOKEN], $token2[Token::GATEWAY_TOKEN]);

        // Assert that the customer, merchant and terminal are the same
        $this->assertEquals($token1[Token::CUSTOMER_ID], $token2[Token::CUSTOMER_ID]);
        $this->assertEquals($token1[Token::MERCHANT_ID], $token2[Token::MERCHANT_ID]);
        $this->assertEquals($token1[Token::TERMINAL_ID], $token2[Token::TERMINAL_ID]);

        // Assert that both the gateway tokens are different
        // Also assert that their token id's are different
        $this->assertNotEquals($gatewayToken1[GatewayToken::ID], $gatewayToken2[GatewayToken::ID]);
        $this->assertNotEquals($gatewayToken1[GatewayToken::TOKEN_ID], $gatewayToken2[GatewayToken::TOKEN_ID]);

        // Assert that the merchant and terminal are the same
        $this->assertEquals($gatewayToken1[GatewayToken::MERCHANT_ID], $gatewayToken2[GatewayToken::MERCHANT_ID]);
        $this->assertEquals($gatewayToken1[GatewayToken::TERMINAL_ID], $gatewayToken2[GatewayToken::TERMINAL_ID]);
    }

    /**
     * This is the case that a debit request was made with a valid recurring token,
     * but failed on the banks end - this results in an exception
     */
    public function testDebitRequestFailure()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $this->mockDebitRequestFailure();

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $token[Token::ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doS2SRecurringPayment($payment);
        });

        $this->assertDebitRequestFailure($token, $gatewayToken);
    }

    public function testPaymentVerify()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        // We are verifying a payment made with a valid recurring token
        $verify = $this->verifyPayment($paymentEntity[Payment::ID]);

        $this->assertNotNull($verify['gateway']['verifyResponseContent']['gateway_token']);

        $this->assertEquals($verify['gateway']['verifyResponseContent']['bank_reference_id'], '1234');
    }

    public function testPaymentVerifyFailed()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $this->mockVerifyFailure();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentEntity)
        {
            $this->verifyPayment($paymentEntity[Payment::ID]);
        });
    }

    /**
     * This test is to check if the right error is thrown when a null
     * response is returned by the second recurring call
     */
    public function testNullSecondRecurringResponse()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity(Entity::PAYMENT, true);

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = $paymentEntity[Payment::TOKEN_ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        $this->mockSiRecurringStatusFiled();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doS2SRecurringPayment($payment);
        });
    }

    /**
     * This test is to ensure that if a token is used for a first recurring
     * payment, the validate netbanking recurring method should throw an error
     */
    public function testTokenPassedInFirstRecurringPayment()
    {
        $timestamp = Carbon::now()->timestamp;

        $token = $this->fixtures->create(
            Entity::TOKEN,
            [
                Token::USED_AT => $timestamp,
                Token::RECURRING => true,
                Token::USED_COUNT => 1
            ]);

        $payment = $this->payment;

        $payment[Payment::AMOUNT] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment[Payment::AMOUNT]]);

        $payment[Payment::TOKEN]    = 'token_' . $token[Token::ID];
        $payment[Payment::ORDER_ID] = $order->getPublicId();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    protected function assertEMandateEntities($initial = true)
    {
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        if ($initial === true)
        {
            $usedCount = 1;

            if ($payment[Payment::AMOUNT] > 0)
            {
                $this->assertNotNull($payment[Payment::REFERENCE1]);
            }
        }
        else
        {
            // For every SI execution payment we increment the used count
            $usedCount = 2;
            $this->assertNotNull($payment[Payment::REFERENCE1]);
        }

        // Assert Token Entity
        $this->assertNotEmpty($token[Token::GATEWAY_TOKEN]);
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals($payment[Payment::CPS_ROUTE], 3);
        $this->assertEquals(true, $token[Token::RECURRING]);
        $this->assertEquals(Token::DEFAULT_MAX_AMOUNT, $token[Token::MAX_AMOUNT]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);
        $this->assertEquals($usedCount, $token[Token::USED_COUNT]);
        $this->assertEquals($payment[Payment::CREATED_AT], $token[Token::USED_AT]);
        $this->assertEquals($payment[Payment::MERCHANT_ID], $token[Token::MERCHANT_ID]);
        $this->assertEquals($this->terminal->getId(), $payment[Payment::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $token[Token::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_' . $token[Token::CUSTOMER_ID]);
        $this->assertEquals($this->bank, $payment[Payment::BANK]);
        $this->assertEquals(PaymentMethod::EMANDATE, $token[Token::METHOD]);
        $this->assertEquals($this->bank, $token[Token::BANK]);

        // Assert GatewayToken entity
        $this->assertEquals($token[Token::ID], 'token_' . $gatewayToken[GatewayToken::TOKEN_ID]);
        $this->assertEquals(null, $gatewayToken[GatewayToken::REFERENCE]);
        $this->assertEquals($token[Token::RECURRING], $gatewayToken[GatewayToken::RECURRING]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $gatewayToken[GatewayToken::TERMINAL_ID]);
    }

    protected function assertEMandateRejectedToken()
    {
        // Assert that the token was rejected
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Recurring remains in false, recurring status = rejected and gateway token
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(false, $token[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::REJECTED, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        // We update gateway token only if token recurring status is confirmed
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // Assert GatewayToken entity
        $this->assertEquals($token[Token::ID], 'token_' . $gatewayToken[GatewayToken::TOKEN_ID]);
        $this->assertEquals(null, $gatewayToken[GatewayToken::REFERENCE]);
        // Gateway token recurring will be false, as token recurring is false
        $this->assertEquals($token[Token::RECURRING], $gatewayToken[GatewayToken::RECURRING]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $gatewayToken[GatewayToken::TERMINAL_ID]);

        $this->assertEquals($payment[Payment::MERCHANT_ID], $token[Token::MERCHANT_ID]);
        $this->assertEquals($payment[Payment::TERMINAL_ID], $token[Token::TERMINAL_ID]);
        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_' . $token[Token::CUSTOMER_ID]);
        $this->assertEquals($this->bank, $payment[Payment::BANK]);
        $this->assertEquals(PaymentMethod::EMANDATE, $token[Token::METHOD]);
        $this->assertEquals($this->bank, $token[Token::BANK]);
    }

    protected function assertDebitRequestFailure(array $token1, array $gatewayToken1)
    {
        $token2 = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken2 = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                'status'              => 'failed',
                'method'              => 'emandate',
                'recurring_type'      => 'auto',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER',
                'error_description'   => 'The payment could not be completed as it was cancelled by the customer.'
            ],
            $payment
        );

        // Asserting that the failed second recurring payment was
        // made with the same token id as above
        $this->assertEquals($token1[Token::ID], $token2[Token::ID]);
        $this->assertEquals($payment[Payment::TOKEN_ID], $token2[Token::ID]);

        // Asserting that the payment was attempted with a valid token
        $this->assertNotNull($token2[Token::GATEWAY_TOKEN]);
        $this->assertEquals(true, $token2[Token::RECURRING]);
        $this->assertEquals(RecurringStatus::CONFIRMED, $token2[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token2[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        // Gateway Token is not created - as it is only created during SI registration flow
        $this->assertArraySelectiveEquals($gatewayToken1, $gatewayToken2);
    }

    protected function assertEMandateStrangeStatus()
    {
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $gatewayToken = $this->getLastEntity(Entity::GATEWAY_TOKEN, true);

        // Gateway token is not set here
        $this->assertEquals($payment[Payment::TOKEN_ID], $token[Token::ID]);
        $this->assertEquals(null, $token[Token::GATEWAY_TOKEN]);

        // Since recurring status is not set, the token entity will not contain recurring fields
        $this->assertEquals(false, $token[Token::RECURRING]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_STATUS_SHORT]);
        $this->assertEquals(null, $token[Token::RECURRING_DETAILS][Token::RECURRING_FAILURE_REASON_SHORT]);

        // Assert gateway token was created
        $this->assertNull($gatewayToken);
    }

    protected function mockScheduledPaymentFailure($status = 'PaymentDateOverdue')
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($status)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            Error::INTERNAL_ERROR_CODE  => 'GATEWAY_ERROR_PREMATURE_SI_EXECUTION',
                        ]
                    ],
                ];
            });
    }

    protected function mockSiRecurringStatusFiled()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            Error::INTERNAL_ERROR_CODE  => 'GATEWAY_ERROR_INVALID_RESPONSE',
                        ]
                    ],
                ];
            });
    }

    protected function mockRejectedToken($apply = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($apply)
            {
                if (($apply === true) and ($action == 'callback'))
                {
                    $content['response']['data']['recurring_status'] = 'rejected';
                }
            });
    }

    protected function mockVerifyFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            Error::INTERNAL_ERROR_CODE  => 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED',
                        ]
                    ],
                ];
            });
    }

    protected function mockDebitRequestFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR     => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            Error::INTERNAL_ERROR_CODE  => 'BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER',
                        ]
                    ],
                ];
            });
    }
}
