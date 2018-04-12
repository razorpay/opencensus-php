<?php

namespace RZP\Gateway\Enach\Base;

use Crypt;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';
    const ACQUIRER              = 'acquirer';
    const ACTION                = 'action';
    const BANK                  = 'bank';
    const AMOUNT                = 'amount';
    const STATUS                = 'status';
    const SIGNED_XML            = 'signed_xml';
    const UMRN                  = 'umrn';
    const GATEWAY_REFERENCE_ID  = 'gateway_reference_id';
    const ACKNOWLEDGE_STATUS    = 'acknowledge_status';
    const REGISTRATION_STATUS   = 'registration_status';
    const ERROR_MESSAGE         = 'error_message';
    const ERROR_CODE            = 'error_code';

    protected $entity = 'enach';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACTION,
        self::BANK,
        self::AMOUNT,
        self::STATUS,
        self::GATEWAY_REFERENCE_ID,
        self::SIGNED_XML,
        self::UMRN,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
    ];

    protected $fillable = [
        self::RECEIVED,
        self::SIGNED_XML,
        self::UMRN,
        self::STATUS,
        self::ACQUIRER,
        self::GATEWAY_REFERENCE_ID,
        self::ACKNOWLEDGE_STATUS,
        self::REGISTRATION_STATUS,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
    ];

    protected $defaults = [
        self::REFUND_ID             => null,
        self::STATUS                => null,
        self::UMRN                  => null,
        self::GATEWAY_REFERENCE_ID  => null,
        self::ACKNOWLEDGE_STATUS    => null,
        self::REGISTRATION_STATUS   => null,
        self::ERROR_MESSAGE         => null,
        self::ERROR_CODE            => null,
    ];

    public function payment()
    {
        return $this->belongsTo(\RZP\Models\Payment\Entity::class);
    }

    protected function setSignedXmlAttribute($signedXml)
    {
        if ($signedXml !== null)
        {
            $signedXml = Crypt::encrypt($signedXml);
        }

        $this->attributes[self::SIGNED_XML] = $signedXml;
    }

    protected function getSignedXmlAttribute($signedXml)
    {
        if ($signedXml === null)
        {
            return $signedXml;
        }

        return Crypt::decrypt($signedXml);
    }

    public function setUmrn(string $umrn)
    {
        $this->setAttribute(self::UMRN, $umrn);
    }

    public function setAcquirer(string $acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setAmount(string $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setBank(string $bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }
}
