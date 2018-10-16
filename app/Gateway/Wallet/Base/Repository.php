<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Exception;
use RZP\Error;
use RZP\Gateway\Base;
use RZP\Models\Payment\Processor\Wallet;

class Repository extends Base\Repository
{
    protected $entity = 'wallet';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID         => 'sometimes|string|min:14|max:18',
        Entity::WALLET             => 'sometimes|custom',
        Entity::GATEWAY_PAYMENT_ID => 'sometimes|string|max:50',
    );

    protected function validateWallet($attribute, $value)
    {
        if (Wallet::exists($value) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
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

    public function fetchWalletByGatewayPaymentId2($gatewayPayment2)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_PAYMENT_ID2 , '=', $gatewayPayment2)
                    ->first();
    }

    public function findByGatewayPaymentId($gatewayPaymentId)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_PAYMENT_ID, '=', $gatewayPaymentId)
                    ->firstOrFail();
    }

    public function findByGatewayRefundId($gatewayRefundId)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_REFUND_ID, '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId($refundId, $wallet)
    {
        $refundEntities =  $this->newQuery()
                                ->where(Entity::REFUND_ID, '=', $refundId)
                                ->where(Entity::WALLET, '=', $wallet)
                                ->whereNotNull(Entity::GATEWAY_REFUND_ID)
                                ->get();

        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //

        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple successful refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }

    public function findByGatewayPaymentIdAndAction(string $gatewayPaymentId, string $action, string $wallet)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_PAYMENT_ID, '=', $gatewayPaymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->where(Entity::WALLET, '=', $wallet)
                    ->firstOrFail();
    }
}
