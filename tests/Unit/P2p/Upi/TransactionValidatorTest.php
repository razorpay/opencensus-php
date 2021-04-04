<?php

namespace RZP\Tests\Unit\P2p\Upi;

use RZP\Tests\Functional\TestCase;
use RZP\Models\P2p\Transaction\Validator;
use RZP\Exception\BadRequestValidationFailureException;

class TransactionValidatorTest extends TestCase
{
    /**
     * Instance of transaction validator
     * @var Validator
     */
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();
    }

    public function testInitiatePayRulesWithSpecial()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $input = $this->getInitiateData();

        $input['description'] = 'somethingwith@-';

        $this->validator->validateInput('initiatePay', $input);
    }

    public function testInitiatePayRulesWithoutSpecial()
    {
        $input = $this->getInitiateData();

        $input['description'] = 'somethingwithout123334';

        $this->validator->validateInput('initiatePay', $input);

        $this->assertTrue(true);
    }


    public function testInitiateCollectRulesWithSpecial()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $input = $this->getInitiateData();

        $input['description'] = 'somethingwith@';

        $this->validator->validateInput('initiateCollect', $input);
    }

    public function testInitiateCollectRulesWithoutSpecial()
    {
        $input = $this->getInitiateData();

        $input['description'] = 'somethingwith1133AA';

        $this->validator->validateInput('initiateCollect', $input);
    }

    public function testIncomingPayWithSpecial()
    {
        $input = $this->getIncomingData();

        $input['transaction']['description'] = 'asasd@-asd';

        $this->validator->validateInput('incomingPay', $input);
    }

    public function testIncomingPayWithoutSpecial()
    {
        $input = $this->getIncomingData();

        $input['transaction']['description'] = 'asasd121212';

        $this->validator->validateInput('incomingPay', $input);
    }

    public function testIncomingCollectWithSpecial()
    {
        $input = $this->getIncomingData();

        $input['transaction']['description'] = 'asasd121212@asdasd-asd';

        $this->validator->validateInput('incomingCollect', $input);
    }

    public function testIncomingCollectWithoutSpecial()
    {
        $input = $this->getIncomingData();

        $input['transaction']['description'] = 'asas123442A';

        $this->validator->validateInput('incomingCollect', $input);
    }

    protected function getIncomingData()
    {
        return [
            'transaction' => [
                'payer' => 'somepayer',
                'payee' => 'somepayee',
                'amount' => 4500,
                'currency' => 'INR',
            ],
            'upi' => [
                'network_transaction_id' => 'someid',
                'gateway_transaction_id' => 'someid',
                'gateway_reference_id'   => 'someid',
                'rrn'                    => 'somerrn',
            ]
        ];
    }

    protected function getInitiateData()
    {
        return [
            'payer' => 'some payer',
            'payee' => 'some payee',
            'amount' => 4500,
            'currency' => 'INR',
        ];
    }
}
