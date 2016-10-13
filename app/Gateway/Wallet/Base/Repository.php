<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Models\Payment\Processor\Wallet;

class Repository extends Base\Repository
{
    protected $entity = 'Wallet';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID    => 'sometimes|string|min:14|max:18',
        Entity::WALLET        => 'sometimes|in:payzapp,payumoney,olamoney,airtelmoney,freecharge',
    );

    protected function validateWallet($attribute, $value)
    {
        if (Wallet::exists($value) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }
    }

    public function fetchGatewayPaymentId2ByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID , '=', $paymentId)
                    ->pluck(Entity::GATEWAY_PAYMENT_ID2);
    }

    public function fetchWalletByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID , '=', $paymentId)
                    ->first();
    }

    public function findByGatewayRefundId($gatewayRefundId)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_REFUND_ID, '=', $gatewayRefundId)
                    ->firstOrFail();
    }
}
