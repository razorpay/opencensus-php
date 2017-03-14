<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const WALLET                = 'wallet';
    const RECEIVED              = 'received';
    const AMOUNT                = 'amount';
    const ACTION                = 'action';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const GATEWAY_PAYMENT_ID2   = 'gateway_payment_id_2';
    const GATEWAY_REFUND_ID     = 'gateway_refund_id';
    const RESPONSE_CODE         = 'response_code';
    const RESPONSE_DESCRIPTION  = 'response_description';
    const STATUS_CODE           = 'status_code';
    const MERCHANT_CODE         = 'merchant_code';
    const ERROR_MESSAGE         = 'error_message';
    const DATE                  = 'date';
    const REFUND_ID             = 'refund_id';
    const REFERENCE1            = 'reference1';
    const REFERENCE2            = 'reference2';

    protected $entity = 'wallet';

    protected $fields = array(
        self::ID,
        self::PAYMENT_ID,
        self::WALLET,
        self::RECEIVED,
        self::AMOUNT,
        self::EMAIL,
        self::CONTACT,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID2,
        self::GATEWAY_REFUND_ID,
        self::RESPONSE_CODE,
        self::RESPONSE_DESCRIPTION,
        self::STATUS_CODE,
        self::MERCHANT_CODE,
        self::ERROR_MESSAGE,
        self::DATE,
        self::REFUND_ID,
        self::REFERENCE1,
        self::REFERENCE2,
    );

    protected $fillable = array(
        self::PAYMENT_ID,
        self::WALLET,
        self::RECEIVED,
        self::AMOUNT,
        self::ACTION,
        self::EMAIL,
        self::CONTACT,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID2,
        self::GATEWAY_REFUND_ID,
        self::RESPONSE_CODE,
        self::RESPONSE_DESCRIPTION,
        self::STATUS_CODE,
        self::MERCHANT_CODE,
        self::ERROR_MESSAGE,
        self::DATE,
        self::REFUND_ID,
        self::REFERENCE1,
        self::REFERENCE2,
    );

    public function getStatusCode()
    {
        return $this->getAttribute(self::STATUS_CODE);
    }

    public function setStatusCode($statusCode)
    {
        $this->setAttribute(self::STATUS_CODE, $statusCode);
    }

    public function getResponseCode()
    {
        return $this->getAttribute(self::RESPONSE_CODE);
    }

    public function setResponseCode(string $responseCode)
    {
        $this->setAttribute(self::RESPONSE_CODE, $responseCode);
    }

    public function setWallet($wallet)
    {
        $this->setAttribute(self::WALLET, $wallet);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getDate()
    {
        return $this->getAttribute(self::DATE);
    }

    public function setDate(string $date)
    {
        $this->setAttribute(self::DATE, $date);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function setGatewayPaymentId(string $gatewayPaymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $gatewayPaymentId);
    }
}
