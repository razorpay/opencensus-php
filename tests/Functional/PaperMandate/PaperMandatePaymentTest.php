<?php


namespace RZP\Tests\Functional\PaperMandate;

use Carbon\Carbon;

use RZP\Models\PaperMandate;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PaperMandatePaymentTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaperMandateTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        $this->fixtures->create('terminal:nach');

        $this->ba->publicAuth();
    }

    public function testCreatePaymentForNach()
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'auth_type' => 'physical',
                    E::PAPER_MANDATE => [
                        PaperMandate\Entity::AMOUNT           => 0,
                        PaperMandate\Entity::STATUS           => PaperMandate\Status::CREATED,
                        PaperMandate\Entity::UPLOADED_FILE_ID => '10000000000000',
                    ],
                ],
            ],
        ]);

        $response = $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $response['razorpay_payment_id'] ?? null);

        $this->assertNotNull($response['razorpay_signature']);
    }

    public function testCreatePaymentForNachAuto()
    {
        $this->testCreatePaymentForNach();

        $token = $this->getDbLastEntity('token');
        $token->setRecurringStatus('confirmed');
        $token->setRecurring(true);
        $token->saveOrFail();

        $this->fixtures->create(E::ORDER,[
            'id'     => '100000001order',
            'amount' => 10000
        ]);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $response['razorpay_payment_id'] ?? null);

        $this->assertNotNull($response['razorpay_signature'] ?? null);
    }

    public function testCreatePaymentForNachAutoProxyAuth()
    {
        $this->testCreatePaymentForNach();

        $token = $this->getDbLastEntity('token');
        $token->setRecurringStatus('confirmed');
        $token->setRecurring(true);
        $token->saveOrFail();

        $this->fixtures->create(E::ORDER,[
            'id'     => '100000001order',
            'amount' => 10000
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/subscription_registration/tokens/' .
            $token->getPublicId() . '/charge';

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment->getPublicId(), $response['razorpay_payment_id'] ?? null);
    }

    public function testCreatePaymentForNachFormNotSubmitted()
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'auth_type' => 'physical',
                    E::PAPER_MANDATE => [
                        'amount' => 0,
                        PaperMandate\Entity::STATUS => PaperMandate\Status::CREATED,
                    ],
                ],
            ],
        ]);

        $this->startTest();
    }

    public function testCreatePaymentNachForAlreadyActivePaymentForNach()
    {
        $this->createToken([
            'terminal_id' => '1citinachDTmnl',
            'method' => 'nach',
            'recurring' => true,
            'recurring_status' => 'initiated',
            E::PAYMENT => [
                'amount' => 0,
                'method' => 'nach',
                'status' => 'created',
                'token_id' => '100000000token',
                'order_id' => '100000000order',
                E::ORDER => [
                    'amount' => 0,
                    'method' => 'nach',
                    E::INVOICE => [
                        'amount' => 0,
                        E::SUBSCRIPTION_REGISTRATION => [
                            'token_id' => '100000000token',
                            'auth_type' => 'physical',
                            E::PAPER_MANDATE => [
                                'amount' => 0,
                                'status' => PaperMandate\Status::AUTHENTICATED,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->startTest();
    }

    public function testRetryTokenForNach()
    {
        $this->createToken([
            'terminal_id' => '1citinachDTmnl',
            'method' => 'nach',
            'recurring' => true,
            'recurring_status' => 'rejected',
            E::PAYMENT => [
                'amount' => 0,
                'method' => 'nach',
                'status' => 'failed',
                'token_id' => '100000000token',
                'order_id' => '100000000order',
                E::ORDER => [
                    'amount' => 0,
                    'method' => 'nach',
                    E::INVOICE => [
                        'amount' => 0,
                        E::SUBSCRIPTION_REGISTRATION => [
                            'token_id' => '100000000token',
                            'auth_type' => 'physical',
                            E::PAPER_MANDATE => [
                                'amount' => 0,
                                'uploaded_file_id' => '1000000000file',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testNachTokenSBNRO()
    {
        $this->createNachTokenByPayment(['account_type' => 'nro']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($token['method'], 'nach');
        $this->assertEquals($token['account_type'], 'nro');
    }

    public function testUMRNInsteadOfTokenForDebitPayment()
    {
        $this->fixtures->merchant->addFeatures(['recurring_debit_umrn']);

        $token = $this->createAcceptedTokenForNACH();

        $order = $this->fixtures->create('order', [
            'amount' => 3000,
            'payment_capture' => true,
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => $token->customer->getPublicId(),
            'currency'    => 'INR',
            'amount'      => 3000,
            'recurring'   => true,
            'token'       => $token->gateway_token,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/recurring',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($token->getId(), $payment->getTokenId());
    }

    protected function createAcceptedTokenForNACH()
    {
        return $this->createToken([
            'terminal_id'      => '1citinachDTmnl',
            'method'           => 'nach',
            'recurring'        => true,
            'recurring_status' => 'confirmed',
            'gateway_token'    => 'NACH0000000000012345',
            E::PAYMENT => [
                'amount' => 0,
                'method' => 'nach',
                'status' => 'captured',
                'token_id' => '100000000token',
                'order_id' => '100000000order',
                E::ORDER => [
                    'amount' => 0,
                    'method' => 'nach',
                    E::INVOICE => [
                        'amount' => 0,
                        E::SUBSCRIPTION_REGISTRATION => [
                            'token_id' => '100000000token',
                            'auth_type' => 'physical',
                            E::PAPER_MANDATE => [
                                'uploaded_file_id' => '1000000000file',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function createNachTokenByPayment(array $bankAcc)
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            'merchant_id'           => '10000000000000',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'token_id'   => '100000000token',
                    'max_amount' => 1000000,
                    'auth_type'  => 'physical',
                    E::PAPER_MANDATE => [
                        'amount' => 1000000,
                        'status' => PaperMandate\Status::AUTHENTICATED,
                        'uploaded_file_id' => '1000000000file',
                        E::BANK_ACCOUNT => [
                            'ifsc_code' => 'HDFC0001233',
                            'account_type' => 'nro'
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->doAuthPayment([
            "amount"      => 0,
            "currency"    => "INR",
            "method"      => "nach",
            "order_id"    => "order_100000000order",
            "customer_id" => "cust_1000000000cust",
            "recurring"   => true,
            "contact"     => "9483159238",
            "email"       => "r@g.c",
            "auth_type"   => "physical",
        ]);
    }

    protected function doAuthPayment($payment = null, $server = null, $key = null)
    {
        $request = $this->buildAuthPaymentRequest($payment, $server);

        $this->ba->publicAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function buildAuthPaymentRequest($payment = null, $server = null): array
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        return $request;
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }

    protected function getDefaultPaymentArrayNeutral()
    {
        //
        // default payment object
        //
        $payment = [
            'amount'            => '50000',
            'currency'          => 'INR',
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => [
                'merchant_order_id' => 'random order id',
            ],
            'description'       => 'random description',
            'bank'              => 'UCBA',
        ];

        return $payment;
    }

    protected function createToken(array $overrideWith = [])
    {
        $payment = array_pull($overrideWith, E::PAYMENT, []);

        $token = $this->fixtures
            ->create(
                E::TOKEN,
                array_merge(
                    [
                        'id'              => '100000000token',
                    ],
                    $overrideWith
                )
            );

        $this->createPayment($payment);

        return $token;
    }

    protected function createPayment(array $overrideWith = [])
    {
        $order = array_pull($overrideWith, E::ORDER, []);

        $this->createOrder($order);

        $payment = $this->fixtures
            ->create(
                E::PAYMENT,
                array_merge(
                    [
                        'id'              => '1000000payment',
                    ],
                    $overrideWith
                )
            );

        return $payment;
    }

    protected function createOrder(array $overrideWith = [])
    {
        $invoice = array_pull($overrideWith, E::INVOICE, []);

        $order = $this->fixtures
            ->create(
                E::ORDER,
                array_merge(
                    [
                        'id'              => '100000000order',
                        'amount'          => 100000,
                    ],
                    $overrideWith
                )
            );

        $this->createInvoiceForOrder($invoice);

        return $order;
    }

    protected function createInvoiceForOrder(array $overrideWith = [])
    {
        $subscriptionRegistration = array_pull($overrideWith, E::SUBSCRIPTION_REGISTRATION, []);

        $subscriptionRegistrationId = UniqueIdEntity::generateUniqueId();

        $order = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => '1000000invoice',
                        'order_id'        => '100000000order',
                        'entity_type'     => 'subscription_registration',
                        'entity_id'       => $subscriptionRegistrationId,
                    ],
                    $overrideWith
                )
            );

        $subscriptionRegistration['id'] = $subscriptionRegistrationId;

        $this->createSubscriptionRegistration($subscriptionRegistration);

        return $order;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
        $paperMandate = array_pull($overrideWith, E::PAPER_MANDATE, []);

        $paperMandateId = UniqueIdEntity::generateUniqueId();

        $subscriptionRegistration = $this->fixtures
            ->create(
                'subscription_registration',
                array_merge(
                    [
                        'method'          => 'nach',
                        'notes'           => [],
                        'entity_type'     => 'paper_mandate',
                        'entity_id'       => $paperMandateId,
                    ],
                    $overrideWith
                )
            );

        $paperMandate['id'] = $paperMandateId;

        $this->createPaperMandate($paperMandate);

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountId = UniqueIdEntity::generateUniqueId();

        $bankAccount = array_pull($overrideWith, E::BANK_ACCOUNT, []);

        $this->fixtures->create(
            E::CUSTOMER,[
                'id'    => '1000000000cust',
                'email' => 'r@g.c',
            ]
        );

        $paperMandate = $this->fixtures
            ->create(
                'paper_mandate',
                array_merge(
                    [
                        'bank_account_id'   => $bankAccountId,
                        'amount'            => 1000,
                        'status'            => PaperMandate\Status::CREATED,
                        'debit_type'        => PaperMandate\DebitType::MAXIMUM_AMOUNT,
                        'type'              => PaperMandate\Type::CREATE,
                        'frequency'         => PaperMandate\Frequency::YEARLY,
                        'start_at'          => (new Carbon('+5 day'))->timestamp,
                        'utility_code'      => 'NACH00000000013149',
                        'sponsor_bank_code' => 'RATN0TREASU',
                        'terminal_id'       => '1citinachDTmnl',
                    ],
                    $overrideWith
                )
            );

        $bankAccount['id'] = $bankAccountId;

        $this->createBankAccount($bankAccount);

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'beneficiary_name' => 'dead pool',
                        'ifsc_code'        => 'HDFC0001233',
                        'account_number'   => '1111111111111',
                        'account_type'     => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }
}
