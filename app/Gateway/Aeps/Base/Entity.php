<?php

namespace RZP\Gateway\Aeps\Base;

use Crypt;
use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const ACQUIRER              = 'acquirer';
    const RECEIVED              = 'received';
    const REVERSED              = 'reversed';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const AADHAAR_NUMBER        = 'aadhaar_number';
    const RRN                   = 'rrn';
    const COUNTER               = 'counter';
    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';

    protected $fields = [
        self::ACTION,
        self::AMOUNT,
        self::ACQUIRER,
        self::RECEIVED,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::REVERSED,
        self::RRN,
    ];

    protected $fillable = [
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::ACQUIRER,
        self::RECEIVED,
        self::REVERSED,
        self::RRN,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $entity = Constants\Entity::AEPS;

    protected function setAadhaarNumberAttribute($aadhaarNumber)
    {
        $encryptedAadhaar = Crypt::encrypt($aadhaarNumber);

        $this->attributes[self::AADHAAR_NUMBER] = $encryptedAadhaar;
    }

    protected function getAadhaarNumberAttribute()
    {
        $encryptedAadhaar = $this->attributes[self::AADHAAR_NUMBER];

        return Crypt::decrypt($encryptedAadhaar);
    }

    public function setAadhaarNumber($aadhaarNumber)
    {
        $this->setAttribute(self::AADHAAR_NUMBER, $aadhaarNumber);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setCounter($counter)
    {
        $this->setAttribute(self::COUNTER, $counter);
    }

    public function setRrn($rrn)
    {
        $this->setAttribute(self::RRN, $rrn);
    }

    public function setReceived($recieved)
    {
        $this->setAttribute(self::RECEIVED, $recieved);
    }

    public function setErrorCode($code)
    {
        $this->setAttribute(self::ERROR_CODE, $code);
    }

    public function setErrorDescription($desc)
    {
        $this->setAttribute(self::ERROR_DESCRIPTION, $desc);
    }
}
