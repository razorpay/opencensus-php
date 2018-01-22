<?php

namespace RZP\Gateway\Hitachi;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const QR_CODE_ID    = 'qr_code_id';
    const ACQUIRER      = 'acquirer';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const REQUEST_ID    = 'pRequestId';
    const RESPONSE_CODE = 'pRespCode';
    const AUTH_STATUS   = 'pAuthStatus';
    const ALGORITHM     = 'pALGO';
    const CAVV2         = 'pCAVV2';
    const UCAF          = 'pUCAF';
    const AUTH_ID       = 'pAuthID';
    const RRN           = 'pRRN';
    const STATUS        = 'pStatus';
    const CARD_NUMBER   = 'card_number';
    const CARD_NETWORK  = 'card_network';

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
        self::AMOUNT,
        self::CARD_NUMBER,
        self::QR_CODE_ID,
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

    public function setQrCodeId($qrCodeId)
    {
        $this->setAttribute(self::QR_CODE_ID, $qrCodeId);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCardNumber()
    {
        return $this->getAttribute(self::CARD_NUMBER);
    }

    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTH_ID);
    }

    public function getRequestId()
    {
        return $this->getAttribute(self::REQUEST_ID);
    }

    public function getQrCodeId()
    {
        return $this->getAttribute(self::QR_CODE_ID);
    }
}
