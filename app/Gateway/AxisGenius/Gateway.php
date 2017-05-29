<?php

namespace RZP\Gateway\AxisGenius;

use RZP\Constants\HashAlgo;
use RZP\Exception;
use RZP\Gateway\AxisGenius;
use RZP\Gateway\AxisMigs;
use RZP\Gateway\Base;

class Gateway extends AxisMigs\Gateway
{
    protected $gateway = 'axis_genius';

    protected $authorize = false;

    protected function getPaymentCaptureRequestContent($input, $payment)
    {
        $content = parent::getPaymentCaptureRequestContent($input, $payment);

        $content['vpc_ReceiptNo'] = $payment['vpc_ReceiptNo'];
        unset($content['vpc_TransNo']);

        return $content;
    }

    protected function parseQueryResponse($response)
    {
        $content = parent::parseQueryResponse($response);

        if (isset($content['SecureHash']))
        {
            $content['vpc_SecureHash'] = $content['SecureHash'];
            unset($content['SecureHash']);
        }

        return $content;
    }

    protected function getPaymentRefundRequestContent($input, $payment)
    {
        $content = parent::getPaymentRefundRequestContent($input, $payment);

        $content['vpc_ReceiptNo'] = $payment['vpc_ReceiptNo'];
        unset($content['vpc_TransNo']);

        return $content;
    }

    protected function addAmaTransactionFields(array & $content, $input)
    {
        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['SecureHash'] = $this->generateHash($content);
    }

    protected function addMerchantIdAndAccessCode(array & $content, $terminal)
    {
        parent::addMerchantIdAndAccessCode($content, $terminal);

        if ($this->action === Base\Action::AUTHORIZE)
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

        return parent::getRelativeUrl($type);
    }

    protected function getHashOfString($str)
    {
        $str = $this->getSecret() . $str;

        return strtoupper(hash(HashAlgo::SHA256, $str, false));
    }

    protected function getAmaTxnResponseContent($response)
    {
        $content = parent::getAmaTxnResponseContent($response);

        $content['vpc_Command'] = $this->getAmaTransactionCommand();

        return $content;
    }

    protected function getAmaTransactionCommand()
    {
        $command = $this->action;

        if ($command === Base\Action::VERIFY)
            $command = AxisMigs\Command::QUERYDR;

        return $command;
    }
}
