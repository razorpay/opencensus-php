<?php

namespace RZP\Gateway\Hitachi;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ACQUIRER               = 'acquirer';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const REQUEST_ID             = 'pRequestId';
    const RESPONSE_CODE          = 'pRespCode';
    const AUTH_STATUS            = 'pAuthStatus';
    const ALGORITHM              = 'pALGO';
    const CAVV2                  = 'pCAVV2';
    const UCAF                   = 'pUCAF';
    const AUTH_ID                = 'pAuthID';
    const RRN                    = 'pRRN';
    const STATUS                 = 'pStatus';
    const MASKED_CARD_NUMBER     = 'masked_card_number';
    const CARD_NETWORK           = 'card_network';
    const MERCHANT_REFERENCE     = 'merchant_reference';
    const AUTHENTICATION_GATEWAY = 'authentication_gateway';

    protected $entity = 'hitachi';

    /**
     * Currently all the fields are visible
     */
    protected $fields = [
        self::ID,
        self::ACQUIRER,
        self::AMOUNT,
        self::CURRENCY,
        self::REQUEST_ID,
        self::RESPONSE_CODE,
        self::AUTH_STATUS,
        self::ALGORITHM,
        self::CAVV2,
        self::UCAF,
        self::AUTH_ID,
        self::RRN,
        self::STATUS,
        self::MASKED_CARD_NUMBER,
        self::CARD_NETWORK,
        self::MERCHANT_REFERENCE,
        self::AUTHENTICATION_GATEWAY,
    ];

    protected $fillable = [
        self::REQUEST_ID,
        self::RESPONSE_CODE,
        self::AUTH_STATUS,
        self::ALGORITHM,
        self::CAVV2,
        self::UCAF,
        self::AUTH_ID,
        self::RRN,
        self::RECEIVED,
        self::STATUS,
        self::MASKED_CARD_NUMBER,
        self::CARD_NETWORK,
        self::MERCHANT_REFERENCE,
        self::AUTHENTICATION_GATEWAY,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
        self::ALGORITHM => 'int',
    ];

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

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTH_ID);
    }

    public function getResponseCode()
    {
        return $this->getAttribute(self::RESPONSE_CODE);
    }

    public function getMerchantReference()
    {
        return $this->getAttribute(self::MERCHANT_REFERENCE);
    }
}
