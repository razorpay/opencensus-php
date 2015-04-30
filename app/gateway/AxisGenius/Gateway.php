<?php

namespace Gateway\Genius;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Axis;
use Gateway\Base;
use Gateway\Genius;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Axis\Gateway
{
    protected function getPaymentCaptureRequestContent($input, $payment)
    {
        $content = parent::getPaymentCaptureRequestContent($input, $payment);

        $content['vpc_ReceiptNo'] = $payment['vpc_ReceiptNo'];
        unset($content['vpc_TransNo']);

        return $content;
    }

    protected function getPaymentVerifyRequestContent($input, $payment)
    {
        $content = array(
            'vpc_Command'       => Axis\Command::QUERY,
            'vpc_MerchTxnRef'   => $input['payment']['id'],
        );

        return $content;
    }

    protected function getPaymentRefundRequestContent($input, $payment)
    {
        $content = parent::getPaymentRefundRequestContent($input, $payment);

        $content['vpc_ReceiptNo'] = $payment['vpc_ReceiptNo'];
        unset($content['vpc_TransNo']);

        return $content;
    }

    protected function addAmaTransactionFields(array & $content)
    {
        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['vpc_SecureHash'] = $this->generateHash($content);
    }

    protected function addMerchantIdAndAccessCode(array & $content, $terminal)
    {
        parent::addMerchantIdAndAccessCode($content, $terminal);

        if ($this->action === Base\Gateway\Action::PAY)
        {
            $content['vpc_MerchantId'] = $content['vpc_Merchant'];
            unset($content['vpc_Merchant']);
        }
    }

    protected function getRelativeUrl($type)
    {
        if ($this->action === Base\Action::VERIFY)
        {
            $type = 'QUERY';
        }

        return constant(__NAMESPACE__.'\Url::'.$type);
    }

    protected function loadGatewayConfig()
    {
        $app = \App::getFacadeRoot();
        $this->config = $app['config']->get('gateway.genius');
    }
}