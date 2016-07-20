<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Olamoney\RequestFields as RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields as ResponseFields;

trait Refund
{
    protected function createWalletRefundEntity($content, $input)
    {
        $refundAttributes = $this->getRefundEntityAttributesFromRefundResponse($content, $input);

        return $this->createGatewayRefundEntity($refundAttributes);
    }

    protected function getRefundEntityAttributesFromRefundResponse($content, $input)
    {
        $gateway_refund_id = isset($content[ResponseFields::TRANSACTION_ID]) ? $content[ResponseFields::TRANSACTION_ID] : null;

        $response_code = isset($content[ResponseFields::ERROR_CODE]) ? $content[ResponseFields::ERROR_CODE] : null;

        $error_message = isset($content[ResponseFields::MESSAGE]) ? $content[ResponseFields::MESSAGE] : null;

        $refundAttributes = array(
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['payment']['amount'],
            'received'              => 1,
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'contact'               => $input['payment']['contact'],
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'gateway_refund_id'     => $gateway_refund_id,
            'refund_id'             => $input['refund']['id'],
            'response_code'         => $response_code,
            'status_code'           => $content[ResponseFields::STATUS],
            'error_message'         => $error_message,
        );

        return $refundAttributes;
    }

    protected function getRefundRequest($input)
    {
        $content = array(
            RequestFields::COMMAND          => Command::REFUND,
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID        => $input['refund']['id'],
            RequestFields::COMMENTS         => 'Razorpay_refund',
            RequestFields::UDF              => $input['payment']['public_id'],
            RequestFields::RETURN_URL       => '',
            RequestFields::NOTIFICATION_URL => '',
            RequestFields::AMOUNT           => (string) ($input['refund']['amount'] / 100),
            RequestFields::BALANCE_TYPE     => 'cash',
            RequestFields::BALANCE_NAME     => 'cash',
            RequestFields::SALE_ID          => $input['payment']['id'],
            RequestFields::CURRENCY         => $input['payment']['currency'],
        );

        $content[RequestFields::HASH] = $this->getHashForRefundRequest($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders();

        return $request;
    }

    protected function getHashForRefundRequest(array $content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::UNIQUE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::BALANCE_TYPE,
            RequestFields::BALANCE_NAME,
            RequestFields::SALE_ID,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }
}