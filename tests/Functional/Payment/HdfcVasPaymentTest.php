<?php

namespace RZP\Tests\Functional\Payment;

use Redis;

use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal\Type;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class HdfcVasPaymentTest extends TestCase
{
    use MocksRazorx;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();



        $this->ba->publicAuth();
    }

    public function testHdfcVasSurchargePaymentHappy()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $merchant = $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Admin\Org\Entity::HDFC_ORG_ID
            ]
        );

        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', [
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
            ]]);

        $attributes = [
            'name'        => 'hdfc_vas_cards_surcharge',
            'entity_id'   => $org->getId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 2000100;

        $amount = $payment['amount'];

        $payment = $this->getFeesForPayment($payment)['input'];

        $oldMerchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', substr($response['razorpay_payment_id'], 4 ));

        $hdfc = $this->getLastEntity('hdfc', true);

        $paymentMeta = $payment->paymentMeta;

        $gatewayAmount = $paymentMeta->getGatewayAmount();

        $this->assertEquals($hdfc['payment_id'], $payment->getId());

        $this->assertEquals($hdfc['amount'] * 100, $gatewayAmount);

        $this->assertEquals($amount, $gatewayAmount);

        $merchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        self::assertEquals($oldMerchantBalance, $merchantBalance);
    }

    public function testHdfcVasSurchargeNonDSPaymentHappy()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->mockRazorxTreatmentV2('hdfc_vas_surcharge_2', 'on');

        $merchant = $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Admin\Org\Entity::HDFC_ORG_ID
            ]
        );

        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $attributes = [
            'name'        => 'hdfc_vas_cards_surcharge',
            'entity_id'   => $org->getId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 2000100;

        $amount = $payment['amount'];

        $payment = $this->getFeesForPayment($payment)['input'];

        $oldMerchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        $response = $this->doAuthPayment($payment);

        $this->capturePayment($response['razorpay_payment_id'], $amount);

        $payment = $this->getDbEntityById('payment', substr($response['razorpay_payment_id'], 4 ));

        $hdfc = $this->getLastEntity('hdfc', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($hdfc['payment_id'], $payment->getId());

        $this->assertEquals($payment['mdr'], 0);

        $this->assertEquals($payment['fee'], 0);

        $this->assertEquals($payment['tax'], 0);

        $this->assertEquals($txn['mdr'], 0);

        $this->assertEquals($txn['fee'], 0);

        $this->assertEquals($txn['tax'], 0);

        $merchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        self::assertEquals($oldMerchantBalance + $amount, $merchantBalance);
    }

    public function testHdfcVasSurchargePaymentRefund()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $merchant = $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Admin\Org\Entity::HDFC_ORG_ID
            ]
        );

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', [
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
            ]]);

        $attributes = [
            'name'        => 'hdfc_vas_cards_surcharge',
            'entity_id'   => $org->getId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 2000100;

        $amount = $payment['amount'];

        $payment = $this->getFeesForPayment($payment)['input'];

        $oldMerchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', substr($response['razorpay_payment_id'], 4 ));

        $hdfc = $this->getLastEntity('hdfc', true);

        $paymentMeta = $payment->paymentMeta;

        $gatewayAmount = $paymentMeta->getGatewayAmount();

        $this->assertEquals($amount, $gatewayAmount);

        $this->assertEquals($hdfc['payment_id'], $payment->getId());

        $this->assertEquals($hdfc['amount'] * 100, $gatewayAmount);

        $this->assertEquals($amount, $gatewayAmount);

        $merchantBalance = $this->getEntityById('balance', $merchant['id'], true)['balance'];

        self::assertEquals($oldMerchantBalance, $merchantBalance);

        $refund = $this->refundPayment( $payment->getPublicId());

        $this->assertEquals($payment['amount'], $refund['amount']);
    }

    public function testHdfcVasSurchargePaymentPartialRefunds()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $merchant = $this->fixtures->merchant->edit('10000000000000',
            [
                'fee_bearer'  => 'customer',
                'org_id'      =>  Admin\Org\Entity::HDFC_ORG_ID
            ]
        );

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $this->fixtures->terminal->edit('1n25f6uN5S1Z5a', [
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
            ]]);

        $attributes = [
            'name'        => 'hdfc_vas_cards_surcharge',
            'entity_id'   => $org->getId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 2000100;

        $payment = $this->getFeesForPayment($payment)['input'];

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', substr($response['razorpay_payment_id'], 4 ));

        $refund1 = $this->refundPayment($payment->getPublicId(), 1000000);

        $this->assertEquals(1000000, $refund1['amount']);

        $refund2 = $this->refundPayment($payment->getPublicId(), 1000200);

        $this->assertEquals(1000200, $refund2['amount']);

        try
        {
            $this->refundPayment($payment->getPublicId(), 40000);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED, $ex->getCode());
        }
    }
}
