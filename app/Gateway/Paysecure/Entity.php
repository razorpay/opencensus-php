<?php

namespace RZP\Gateway\Paysecure;

use Crypt;
use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                      = 'id';
    const STATUS                  = 'status';
    const GATEWAY_TRANSACTION_ID  = 'gateway_transaction_id';
    const ERROR_CODE              = 'error_code';
    const ERROR_MESSAGE           = 'error_message';
    const FLOW                    = 'flow';
    const HKEY                    = 'hkey';
    const AUTH_NOT_REQUIRED       = 'auth_not_required';
    const APPRCODE                = 'apprcode';
    const RRN                     = 'rrn';
    const TRAN_DATE               = 'tran_date';
    const TRAN_TIME               = 'tran_time';

    protected $fillable = [
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::STATUS,
        self::GATEWAY_TRANSACTION_ID,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
        self::FLOW,
        self::HKEY,
        self::AUTH_NOT_REQUIRED,
        self::APPRCODE,
        self::RRN,
        self::TRAN_DATE,
        self::TRAN_TIME,
    ];

    protected $hidden = [
        self::HKEY,
    ];

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::PAYSECURE;

    public $incrementing = true;

    public function setFlow(string $flow)
    {
        $this->setAttribute(self::FLOW, $flow);
    }

    protected function getHkeyAttribute()
    {
        $hkey = $this->attributes[self::HKEY];

        if ($hkey === null)
        {
            return $hkey;
        }

        return Crypt::decrypt($hkey);
    }

    protected function setHkeyAttribute($hkey)
    {
        if ($hkey === null)
        {
            $hkey = '';
        }

        $this->attributes[self::HKEY] = Crypt::encrypt($hkey);
    }

    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
