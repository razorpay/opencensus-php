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

class UpiMindgatePaymentServiceTest extends UpiPaymentServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPaymentSuccessWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => $upi['npci_reference_id'],
            Entity::VPA => $upi['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE => Flow::COLLECT,
            UpiEntity::ACTION => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
            UpiEntity::STATUS_CODE => '00',
            UpiEntity::MERCHANT_REFERENCE => $payment['id']
        ], $upi);
    }

    public function testPaymentFailureWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->payment['vpa'] = 'failed@hdfcbank';

        $this->doAjaxPayment('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 0,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE => Flow::COLLECT,
            UpiEntity::ACTION => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
            UpiEntity::STATUS_CODE => 'ZA',
            UpiEntity::MERCHANT_REFERENCE => $payment['id']
        ], $upi);
    }

    public function testUpsPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => '910000123456',
            Entity::VPA => $payment['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);
    }

    public function testUpsPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->payment['vpa'] = 'failed@hdfcbank';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upiEntity, $payment);

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi');

        $this->assertNull($upi);

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 4,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);
    }

    public function testUpsTpvPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_tpv_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => '910000123456',
            Entity::VPA => $payment['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);
    }

    public function testUpsTpvPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['vpa'] = 'failed@hdfcbank';
        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_tpv_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upiEntity, $payment);

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi');

        $this->assertNull($upi);

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 4,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);
    }
}
