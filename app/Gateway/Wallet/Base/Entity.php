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

    protected $fields = [
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
    ];

    protected $fillable = [
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
    ];

    protected $casts = [
        self::AMOUNT => 'string',
    ];

    public function getStatusCode()
    {
        return $this->getAttribute(self::STATUS_CODE);
    }

    public function getResponseCode()
    {
        return $this->getAttribute(self::RESPONSE_CODE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function getGatewayPaymentId2()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID2);
    }

    public function getGatewayRefundId()
    {
        return $this->getAttribute(self::GATEWAY_REFUND_ID);
    }

    public function getDate()
    {
        return $this->getAttribute(self::DATE);
    }

    public function setWallet($wallet)
    {
        $this->setAttribute(self::WALLET, $wallet);
    }

    public function setGatewayPaymentId($gatewayPaymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $gatewayPaymentId);
    }

    public function setGatewayRefundId(string $gatewayRefundId)
    {
        $this->setAttribute(self::GATEWAY_REFUND_ID, $gatewayRefundId);
    }

    public function setDate(string $date)
    {
        $this->setAttribute(self::DATE, $date);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    protected function setErrorMessageAttribute($message)
    {
        //to reduce the length of error message in case it extends database column field size.
        $this->attributes[self::ERROR_MESSAGE] = substr($message, 0, 255);
    }

    public function setEmail(string $email)
    {
        $this->setAttribute(self::EMAIL, $email);
    }

    public function setContact(string $contact)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }
}
