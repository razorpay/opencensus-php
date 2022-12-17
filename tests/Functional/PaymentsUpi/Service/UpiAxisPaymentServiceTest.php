<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;


use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

class UpiAxisPaymentServiceTest extends UpiPaymentServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPaymentSuccessWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE           => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upi['npci_reference_id'],
            Entity::VPA             => $upi['vpa'],
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::STATUS_CODE          => '00',
            UpiEntity::MERCHANT_REFERENCE   => $payment['id']
        ], $upi);
    }

    public function testPaymentFailureWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE           => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_axis')
                        ->getAsyncCallbackContent($upi, $payment, 'U30', 'Debit Failed');

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $this->assertEquals(
            [
                'callBackstatusCode'        => 'U30',
                'callBackstatusDescription' => 'Debit Failed',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::REFERENCE16         => null,
            Entity::CPS_ROUTE           => 0,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::STATUS_CODE          => 'U30',
            UpiEntity::MERCHANT_REFERENCE   => $payment['id']
        ], $upi);
    }

    public function testUnexpectedPaymentWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_axis';

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $id = str_random(12);

        $content = $this->unexpectedPaymentContent($id);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']         => $paymentEntity['gateway'],
            $id                                    => $authorizeUpiEntity['merchant_reference']
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    protected function unexpectedPaymentContent(string $id)
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $upi = [
            'amount'    => '60000',
            'vpa'       => 'unexpected@axisbank'
        ];

        $payment = [
            'id'        => $id,
        ];

        return $this->mockServer('upi_axis')->getAsyncCallbackContent($upi, $payment);
    }
}
