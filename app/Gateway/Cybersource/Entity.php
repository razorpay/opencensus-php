<?php

namespace RZP\Gateway\Cybersource;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                     = 'id';
    const ACQUIRER               = 'acquirer';
    const VERES_ENROLLED         = 'veresEnrolled';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const STATUS                 = 'status';
    const CAVV                   = 'cavv';
    const ECI                    = 'eci';
    const COLLECTION_INDICATOR   = 'collection_indicator';
    const PARES_STATUS           = 'pares_status';
    const AVS_CODE               = 'avsCode';
    const CARD_CATEGORY          = 'cardCategory';
    const CARD_GROUP             = 'cardGroup';
    const CV_CODE                = 'cvCode';
    const MERCHANT_ADVICE_CODE   = 'merchantAdviceCode';
    const GATEWAY_TRANSACTION_ID = 'gatewayTransactionId';
    const PROCESSOR_RESPONSE     = 'processorResponse';
    const AUTH_DATA              = 'auth_data';
    const AUTHORIZATION_CODE     = 'authorizationCode';
    const RECEIPT_NUMBER         = 'receiptNumber';
    const COMMERCE_INDICATOR     = 'commerce_indicator';
    const REF                    = 'ref';
    const CAPTURE_REF            = 'capture_ref';
    const XID                    = 'xid';
    const REASON_CODE            = 'reason_code';

    protected $fields = [
        self::ID,
        self::VERES_ENROLLED,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ECI,
        self::PARES_STATUS,
        self::AVS_CODE,
        self::CARD_CATEGORY,
        self::CARD_GROUP,
        self::CV_CODE,
        self::CAVV,
        self::MERCHANT_ADVICE_CODE,
        self::GATEWAY_TRANSACTION_ID,
        self::PROCESSOR_RESPONSE,
        self::AUTH_DATA,
        self::AUTHORIZATION_CODE,
        self::RECEIPT_NUMBER,
        self::COMMERCE_INDICATOR,
        self::REF,
        self::CAPTURE_REF,
        self::XID,
        self::REASON_CODE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $fillable = [
        self::VERES_ENROLLED,
        self::ECI,
        self::PARES_STATUS,
        self::AVS_CODE,
        self::CARD_CATEGORY,
        self::CARD_GROUP,
        self::CV_CODE,
        self::CAVV,
        self::AUTH_DATA,
        self::COMMERCE_INDICATOR,
        self::MERCHANT_ADVICE_CODE,
        self::GATEWAY_TRANSACTION_ID,
        self::PROCESSOR_RESPONSE,
        self::AUTHORIZATION_CODE,
        self::RECEIPT_NUMBER,
        self::PARES_STATUS,
        self::REASON_CODE,
        self::REF,
        self::XID,
        self::STATUS,
        self::RECEIVED
    ];

    protected $casts = [
        self::REASON_CODE => 'int',
        self::AMOUNT      => 'int'
    ];

    protected $entity = Constants\Entity::CYBERSOURCE;

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Refund\Entity', self::REFUND_ID, self::ID);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getCommerceIndicator()
    {
        return $this->getAttribute(self::COMMERCE_INDICATOR);
    }

    public function getUcafAuthenticationData()
    {
        return $this->getAttribute(self::AUTH_DATA);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getEci()
    {
        return $this->getAttribute(self::ECI);
    }

    public function getCavv()
    {
        return $this->getAttribute(self::CAVV);
    }

    public function getRequestId()
    {
        return $this->getAttribute(self::REF);
    }

    public function getCaptureRequestId()
    {
        $captureRef = $this->getAttribute(self::CAPTURE_REF);

        if ($captureRef === null)
        {
            return $this->getAttribute(self::REF);
        }

        return $captureRef;
    }

    public function getXid()
    {
        return $this->getAttribute(self::XID);
    }

    public function getParesStatus()
    {
        return $this->getAttribute(self::PARES_STATUS);
    }

    public function getVeresEnrolled()
    {
        return $this->getAttribute(self::VERES_ENROLLED);
    }

    public function getReasonCode()
    {
        return $this->getAttribute(self::REASON_CODE);
    }

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTHORIZATION_CODE);
    }

    public function getRef()
    {
        return $this->getAttribute(self::REF);
    }

    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::GATEWAY_TRANSACTION_ID);
    }

    public function setGatewayTransactionId($gatewayTransactionId)
    {
        $this->setAttribute(self::GATEWAY_TRANSACTION_ID, $gatewayTransactionId);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
