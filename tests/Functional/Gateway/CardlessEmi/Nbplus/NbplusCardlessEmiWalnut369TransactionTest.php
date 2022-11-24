<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi\Nbplus;

use RZP\Constants\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Payment\NbplusPaymentServiceCardlessEmiTest;

class NbplusCardlessEmiWalnut369TransactionTest extends NbplusPaymentServiceCardlessEmiTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = 'walnut369';

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
    }

    public function testWalnut369NonSourcedByMerchantTransaction()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertEquals(2950, $transactionEntity['mdr']); // 5% of 500 = 25 + 18% gst = 29
        $this->assertEquals(2950, $transactionEntity['fee']); // 5% of 500 = 25 + 18% gst = 29
        $this->assertEquals(450, $transactionEntity['tax']);  // 18% gst of 25 = 29
        $this->assertEquals(47050, $transactionEntity['credit']);  // 500 - 5% pricing charge

    }

    public function testWalnut369SourcedByMerchantTransaction()
    {
        $this->fixtures->merchant->addFeatures(['sourced_by_walnut369']);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertEquals(0, $transactionEntity['mdr']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity['fee']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity['tax']); // since there is no fee, so tax as well

        $this->assertEquals(48500, $transactionEntity['credit']); // 3% gst of 500
    }

    public function testWalnut369SourcedByMerchantTransactionLateAuth()
    {
        $this->fixtures->merchant->addFeatures(['sourced_by_walnut369']);

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE) {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE => 'GATEWAY_ERROR_UNKNOWN_ERROR'
                        ]
                    ],
                ];
            }
        });

        $paymentArray = $this->payment;

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doAuthPayment($paymentArray);
            },
            GatewayErrorException::class);

        $payment = $this->getLastPayment(true);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);

        $this->assertEquals('failed', $payment[Payment::STATUS]);

        $this->authorizeFailedPayment($payment['id']);

        $this->capturePayment($payment['id'], 50000);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertEquals(0, $transactionEntity['mdr']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity['fee']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity['tax']); // since there is no fee, so tax as well

        $this->assertEquals(48500, $transactionEntity['credit']); // 3% gst of 500
    }

    public function testWalnut369SourcedByMerchantTransactionFullRefund()
    {
        $this->markTestSkipped('the test does not test the actual flow');

        $this->fixtures->merchant->addFeatures(['sourced_by_walnut369']);

        $paymentRefund = $this->doAuthCaptureAndRefundPayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], 'refunded');

        $paymentId = $paymentRefund['payment_id'];
        $refundId = $paymentRefund['id'];
        $this->fixtures->stripSign($paymentId);
        $this->fixtures->stripSign($refundId);

        $transactionEntity = $this->getDbEntities(Entity::TRANSACTION, ['entity_id' => $paymentId, 'type' => 'payment']);

        $this->assertEquals(0, $transactionEntity[0]['mdr']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity[0]['fee']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity[0]['tax']); // since there is no fee, so tax as well

        $this->assertEquals(48500, $transactionEntity[0]['credit']); // 3% gst of 500

        $transactionEntity = $this->getDbEntities(Entity::TRANSACTION, ['entity_id' => $refundId, 'type' => 'refund']);

        $this->assertEquals(48500, $transactionEntity[0]['debit']); // 3% gst of 500
    }

    public function testWalnut369SourcedByMerchantTransactionPartialRefund()
    {
        $this->markTestSkipped('the test does not test the actual flow');

        $this->fixtures->merchant->addFeatures(['sourced_by_walnut369']);

        $paymentRefund = $this->doAuthCaptureAndRefundPayment($this->payment, 25000);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $paymentId = $paymentRefund['payment_id'];
        $refundId = $paymentRefund['id'];
        $this->fixtures->stripSign($paymentId);
        $this->fixtures->stripSign($refundId);

        $transactionEntity = $this->getDbEntities(Entity::TRANSACTION, ['entity_id' => $paymentId, 'type' => 'payment']);

        $this->assertEquals(0, $transactionEntity[0]['mdr']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity[0]['fee']); // we ignore our pricing for CF sourced merchants
        $this->assertEquals(0, $transactionEntity[0]['tax']); // since there is no fee, so tax as well

        $this->assertEquals(48500, $transactionEntity[0]['credit']); // 3% gst of 500

        $transactionEntity = $this->getDbEntities(Entity::TRANSACTION, ['entity_id' => $refundId, 'type' => 'refund']);

        $this->assertEquals(25000, $transactionEntity[0]['debit']); // no discount is applicable on partial refund..we recover the entire amount from merchant
    }

}
