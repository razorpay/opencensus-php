<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcDebitEmiTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/HdfcDebitEmiTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

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

    public function testHdfcDebitEmiCheckEligibilityFailure()
    {
        $this->createDependentEntitiesForSuccessPayment();

        $this->mockServerContentFunction(function(& $content, $action = '')
        {
            if ($action === 'authenticate_init')
            {
                $content['data']['status']                  = 'OTP_send_failed';
                $content['data']['AuthenticationErrorCode'] = 'A034';
                $content['success']                         = 'false';

                $content['error'] = [
                    'description'               => 'Customer is not eligible',
                    'gateway_error_code'        => 'A034',
                    'gateway_error_description' => 'Customer is not eligible',
                    'gateway_status_code'       => 200,
                    'internal_error_code'       => 'BAD_REQUEST_HDFC_DEBIT_EMI_CUSTOMER_NOT_ELIGIBLE',
                ];
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    public function testHdfcDebitEmiPartialRefundDisabled()
    {
        $card = $this->fixtures->card->create(
            [
                'name'         => 'Albin',
                'merchant_id'  => '10000000000000',
                'expiry_month' => 12,
                'expiry_year'  => 2024,
                'iin'          => '485446',
                'last4'        => '0607',
                'network'      => 'Visa',
                'type'         => 'debit',
                'issuer'       => 'HDFC',
                'emi'          => false,
                'vault'        => 'rzpvault',
                'vault_token'  => 'NDg1NDQ2MDEwMDg0MDYwNw==',
            ]);

        $payment = $this->fixtures->payment->create(
            [
                'amount'      => 300000,
                'merchant_id' => '10000000000000',
                'method'      => 'emi',
                'status'      => 'captured',
                'gateway'     => 'hdfc_debit_emi',
                'card_id'     => $card->getId(),
            ]);

        $transaction = $this->fixtures->create('transaction',
            ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->mozart->create(
            [
                'payment_id' => $payment->getId(),
                'action'     => 'authorize',
                'amount'     => 300000,
                'gateway'    => 'hdfc_debit_emi',
                'raw'        => '{"Token": "123456", "Status": "Success", "ErrorCode": "0000", "BankReferncNo": "abc123456", "EligibilityStatus": "Yes", "MerchantReferenceNo": "DoERhejxpA5CjO", "OrderConfirmationStatus": "Yes"}',
            ]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->refundPayment('pay_' . $payment->getId(), 10000);
            });

        $paymentArr = $this->getDbLastEntityToArray('payment');

        // We need to make sure the payment is not marked as refunded even though refund is initiated from merchant.
        $this->assertArraySelectiveEquals(
            [
                'id'     => $payment->getId(),
                'status' => 'captured',
            ],
            $paymentArr
        );
    }

    public function testHdfcDebitEmiMissingEmiPlan()
    {
        $this->createDependentEntitiesForSuccessPayment(false);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });
    }

    public function testHdfcDebitEmiMissingContact()
    {
        $this->createDependentEntitiesForSuccessPayment(false);

        $data = $this->testData[__FUNCTION__];

        $payment = $this->payment;
        unset($payment['contact']);

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    // ------------- Helpers -----------------
    protected function createDependentEntitiesForSuccessPayment($addEmiPlan = true)
    {
        if ($addEmiPlan === true)
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
        }

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
                'gateway' => 'hdfc_debit_emi',

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
                'Token'                      => '123456',
                'status'                     => 'OTP_sent',
                'AuthenticationErrorCode'    => '0000',
                'AuthenticationErrorMessage' => '',
                'BankReferenceNo'             => 'abc123456',
                'EligibilityStatus'          => 'Yes',
                'MerchantReferenceNo'        => $payment['id'],
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
