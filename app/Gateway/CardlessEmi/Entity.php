<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                   = 'id';
    const ACTION               = 'action';
    const PAYMENT_ID           = 'payment_id';
    const REFUND_ID            = 'refund_id';
    const AMOUNT               = 'amount';
    const CURRENCY             = 'currency';
    const STATUS               = 'status';
    const GATEWAY              = 'gateway';
    const PROVIDER             = 'provider';
    const GATEWAY_REFERENCE_ID = 'gateway_reference_id';
    const GATEWAY_PLAN_ID      = 'gateway_plan_id';
    const ERROR_CODE           = 'error_code';
    const ERROR_DESCRIPTION    = 'error_description';
    const CONTACT              = 'contact';
    const EMAIL                = 'email';
    const RECEIVED             = 'received';

    protected $entity = Constants\Entity::CARDLESS_EMI;

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::GATEWAY,
        self::GATEWAY_REFERENCE_ID,
        self::GATEWAY_PLAN_ID,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CONTACT,
        self::EMAIL,
        self::RECEIVED,
    ];

    protected $fillable = [
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::GATEWAY,
        self::GATEWAY_REFERENCE_ID,
        self::GATEWAY_PLAN_ID,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CONTACT,
        self::EMAIL,
        self::RECEIVED,
    ];

    public function getGatewayReferenceId()
    {
        $this->getAttribute(self::GATEWAY_REFERENCE_ID);
    }

    public function getStatus()
    {
        $this->getAttribute(self::STATUS);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setGatewayPlanId($gatewayPlanId)
    {
        $this->setAttribute(self::GATEWAY_PLAN_ID, $gatewayPlanId);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setProvider($provider)
    {
        $this->setAttribute(self::PROVIDER, $provider);
    }

    public function setContact($contact)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }

    public function setEmail($email)
    {
        $this->setAttribute(self::EMAIL, $email);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setGatewayReferenceID($referenceId)
    {
        $this->setAttribute(self::GATEWAY_REFERENCE_ID, $referenceId);
    }
}
