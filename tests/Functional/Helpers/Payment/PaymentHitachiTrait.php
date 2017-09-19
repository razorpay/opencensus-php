<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Models\Card;
use RZP\Gateway\Hitachi;

trait PaymentHitachiTrait
{
    public function runPaymentCallbackFlowHitachi($response, & $callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function paymentRefundReverseTestHelper(array $payment,
                                                      int $refundAmount = 50000,
                                                      string $refundStatus = 'full')
    {
        $refund = $this->getLastEntity('refund', true);

        $this->assertTestResponse($refund, 'testPaymentRefundEntity');
        $this->assertEquals($refundAmount, $refund['amount']);
        $this->assertEquals($refundAmount, $refund['base_amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($refundAmount, $payment['amount_refunded']);
        $this->assertEquals($refundAmount, $payment['base_amount_refunded']);
        $this->assertEquals($refundStatus, $payment['refund_status']);

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertEquals($refundAmount, $hitachi[Hitachi\Entity::AMOUNT]);
        $this->assertEquals(Hitachi\Status::SUCCESS_CODE, $hitachi[Hitachi\Entity::RESPONSE_CODE]);
        $this->assertNotNull($hitachi[Hitachi\Entity::RRN]);
    }

    protected function refundReverseFailureTestHelper(array $payment)
    {
        $refund = $this->getLastEntity('refund', true);

        $this->assertTestResponse($refund, 'testPaymentRefundFailureEntity');

        $hitachi = $this->getLastEntity('hitachi', true);

        $this->assertEquals('06', $hitachi[Hitachi\Entity::RESPONSE_CODE]);
    }

    protected function mockVerifyFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Hitachi\ResponseFields::STATUS] = 'F';
                $content[Hitachi\ResponseFields::RESPONSE_CODE] = '55';
            }
        );
    }

    protected function mockAuthFormatError()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content = '{"pRespCode":"30","pRespDesc":"Format Error"}{"pRespCode":"30","pRespDesc":"Format Error"}';
            }
        );
    }

    protected function mockFailureResponseCode()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                $content[Hitachi\ResponseFields::RESPONSE_CODE] = '06';

                if ($action === 'capture')
                {
                    //
                    // Response Code will not be found in mapping
                    //
                    $content[Hitachi\ResponseFields::RESPONSE_CODE] = '79';
                }
            }
        );
    }
}
