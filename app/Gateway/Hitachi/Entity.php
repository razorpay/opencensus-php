<?php

namespace RZP\Gateway\Hitachi;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ACQUIRER      = 'acquirer';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const REQUEST_ID    = 'pRequestId';
    const RESPONSE_CODE = 'pRespCode';
    const ENROLLED      = 'pEnrolled';
    const AUTH_STATUS   = 'pAuthStatus';
    const ECI           = 'pECI';
    const XID           = 'pXID';
    const ALGORITHM     = 'pALGO';
    const CAVV2         = 'pCAVV2';
    const UCAF          = 'pUCAF';
    const AUTH_ID       = 'pAuthID';
    const RRN           = 'pRRN';
    const STATUS        = 'pStatus';

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
        self::ENROLLED,
        self::AUTH_STATUS,
        self::ECI,
        self::XID,
        self::ALGORITHM,
        self::CAVV2,
        self::UCAF,
        self::AUTH_ID,
        self::RRN,
        self::STATUS,
    ];

    protected $fillable = [
        self::REQUEST_ID,
        self::RESPONSE_CODE,
        self::ENROLLED,
        self::AUTH_STATUS,
        self::ECI,
        self::XID,
        self::ALGORITHM,
        self::CAVV2,
        self::UCAF,
        self::AUTH_ID,
        self::RRN,
        self::RECEIVED,
        self::STATUS,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
        self::ECI       => 'string',
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
}
