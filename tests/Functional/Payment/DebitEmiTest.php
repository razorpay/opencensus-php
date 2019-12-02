<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class DebitEmiTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'debit_emi';

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray();

        $this->ba->publicAuth();
    }

    public function testHdfcDebitEmiPaymentCreate()
    {
        $this->fixtures->emiPlan->create(
            [
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $this->fixtures->iin->create(
            [
                'iin'     => '485446',
                'network' => 'Visa',
                'type'    => 'debit',
                'issuer'  => 'HDFC',
                'network' => 'Visa',
            ]);

        $this->fixtures->create('terminal:hdfc_debit_emi');

        $res = $this->doAuthPayment($this->payment);

    }

    protected function getDefaultEmiPaymentArray()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4854460100840607';
        $payment['amount']         = 300000;
        $payment['method']         = 'emi';
        $payment['emi_duration']   = 3;

        return $payment;
    }
}
