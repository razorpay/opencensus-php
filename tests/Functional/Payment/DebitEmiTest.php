<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class DebitEmiTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DebitEmiTestData.php';

        parent::setUp();

        $this->gateway = 'debit_emi';

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray();

        $this->createDependentEntities();

        $this->ba->publicAuth();
    }

    public function testHdfcDebitEmiPaymentSuccess()
    {
        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'created',
                'amount'  => 300000,
                'method'  => 'emi',
                'gateway' => 'debit_emi',

            ],
            $payment->toArray()
        );

        $card = $this->getDbLastEntityToArray('card');

        $this->assertArraySelectiveEquals(
            [
                'id'     => $payment['card_id'],
                'iin'    => '485446',
                'issuer' => 'HDFC',
            ],
            $card
        );

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }

    protected function createDependentEntities()
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
