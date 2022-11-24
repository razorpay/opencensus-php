<?php

namespace RZP\Gateway\Mpi\Base;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                     = 'id';
    const ACQUIRER               = 'acquirer';
    const GATEWAY                = 'gateway';
    const AMOUNT                 = 'amount';
    const STATUS                 = 'status';
    const CAVV                   = 'cavv';
    const CAVV_ALGORITHM         = 'cavvAlgorithm';
    const ECI                    = 'eci';
    const XID                    = 'xid';
    const ENROLLED               = 'enrolled';
    const CURRENCY               = 'currency';
    const MER_ID                 = 'merID';
    const ACC_ID                 = 'accID';
    const GATEWAY_PAYMENT_ID     = 'gateway_payment_id';
    const RESPONSE_CODE          = 'response_code';
    const RESPONSE_DESCRIPTION   = 'response_description';
    const ACS_URL                = 'acs_url';

    protected $fields = [
        self::ID,
        self::GATEWAY,
        self::ENROLLED,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ECI,
        self::CAVV,
        self::CAVV_ALGORITHM,
        self::XID,
        self::MER_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::GATEWAY_PAYMENT_ID,
        self::RESPONSE_CODE,
        self::RESPONSE_DESCRIPTION,
        self::ACS_URL,
    ];

    protected $fillable = [
        self::ENROLLED,
        self::ECI,
        self::CAVV,
        self::CAVV_ALGORITHM,
        self::XID,
        self::STATUS,
        self::RECEIVED,
        self::AMOUNT,
        self::PAYMENT_ID,
        self::CURRENCY,
        self::ACC_ID,
        self::GATEWAY_PAYMENT_ID,
        self::RESPONSE_CODE,
        self::RESPONSE_DESCRIPTION,
        self::ACS_URL,
    ];

    protected $casts = [
        self::AMOUNT      => 'int'
    ];

    protected $entity = Constants\Entity::MPI;

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Refund\Entity', self::REFUND_ID, self::ID);
    }

    public function getAcsUrl()
    {
        return $this->getAttribute(self::ACS_URL);
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

    public function getXid()
    {
        return $this->getAttribute(self::XID);
    }

    public function getAccId()
    {
        return $this->getAttribute(self::ACC_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getEnrolled()
    {
        return $this->getAttribute(self::ENROLLED);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAcsUrl(string $url)
    {
        $this->setAttribute(self::ACS_URL, $url);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setAcquirer(string $acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setGateway(string $gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setXid(string $xid)
    {
        $this->setAttribute(self::XID, $xid);
    }

    public function setCavv(string $cavv)
    {
        $this->setAttribute(self::CAVV, $cavv);
    }

    public function setCavvAlgorithm(string $cavvAlgo)
    {
        $this->setAttribute(self::CAVV_ALGORITHM, $cavvAlgo);
    }

    public function setEci(string $eci)
    {
        $this->setAttribute(self::ECI, $eci);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
