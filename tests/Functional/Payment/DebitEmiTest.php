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

        $this->ba->publicAuth();
    }

    public function testHdfcDebitEmiPaymentSuccess()
    {
        $this->createDependentEntitiesForSuccessPayment();

        $this->doAuthPayment($this->payment);

        $payment= $this->getDbLastEntity('payment');

        $this->assertCreateSuccess($payment);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $this->assertAuthorized();
    }

    // ------------- Helpers -----------------
    protected function createDependentEntitiesForSuccessPayment()
    {
        $this->fixtures->emiPlan->create(
            [
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
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

    protected function assertCreateSuccess($payment)
    {
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

        $mozart = $this->getDbLastEntityToArray('mozart');
        $mozart = json_decode($mozart['raw'], true);

        $this->assertArraySelectiveEquals(
            [
                'Token'               => '123456',
                'Status'              => 'Success',
                'ErrorCode'           => '0000',
                'BankReferncNo'       => 'abc123456',
                'EligibilityStatus'   => 'Yes',
                'MerchantReferenceNo' => $payment['id'],
            ],
            $mozart
        );
    }

    protected function assertAuthorized()
    {
        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status'  => 'authorized',
            ],
            $payment
        );

        $mozart = $this->getDbLastEntityToArray('mozart');
        $mozart = json_decode($mozart['raw'], true);

        $this->assertArraySelectiveEquals(
            [
                'OrderConfirmationStatus' => 'Yes',
            ],
            $mozart
        );
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
