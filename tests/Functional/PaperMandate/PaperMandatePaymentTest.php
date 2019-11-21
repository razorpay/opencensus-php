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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaperMandateTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        (new Terminal)->createNachTerminal();

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
                    E::PAPER_MANDATE => [
                        'amount' => 0,
                        PaperMandate\Entity::STATUS => PaperMandate\Status::AUTHENTICATED,
                    ],
                ],
            ],
        ]);

        $this->startTest();
    }

    public function testCreatePaymentForNachFormNotSubmitted()
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
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
                            E::PAPER_MANDATE => [
                                'amount' => 0,
                                'status' => PaperMandate\Status::AUTHENTICATED,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
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
            E::CUSTOMER,
            ['id' => '1000000000cust']
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