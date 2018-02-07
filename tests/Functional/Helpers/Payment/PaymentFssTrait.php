<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Gateway\Card\Fss\Fields;
use RZP\Gateway\Card\Fss\Status;

trait PaymentFssTrait
{
    protected function runPaymentCallbackFlowCardFss($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

    public function setFailedStatusInReturn()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content[Fields::RESULT] = Status::NOT_CAPTURED;
        });
    }

    public function setVerifyRefundNotCapturedResult()
    {
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content[Fields::RESULT] = 'Failure(NOT CAPTURED)';
            }
        });
    }
}
