<?php

namespace RZP\Gateway\Enach\Base;

use Crypt;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                     = 'id';
    const PAYMENT_ID             = 'payment_id';
    const AUTHENTICATION_GATEWAY = 'authentication_gateway';
    const REFUND_ID              = 'refund_id';
    const ACQUIRER               = 'acquirer';
    const ACTION                 = 'action';
    const BANK                   = 'bank';
    const AMOUNT                 = 'amount';
    const STATUS                 = 'status';
    const SIGNED_XML             = 'signed_xml';
    const UMRN                   = 'umrn';
    const GATEWAY_REFERENCE_ID   = 'gateway_reference_id';
    const GATEWAY_REFERENCE_ID2  = 'gateway_reference_id2';
    const ACKNOWLEDGE_STATUS     = 'acknowledge_status';
    const REGISTRATION_STATUS    = 'registration_status';
    const REGISTRATION_DATE      = 'registration_date';
    const ERROR_MESSAGE          = 'error_message';
    const ERROR_CODE             = 'error_code';

    protected $entity = 'enach';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::AUTHENTICATION_GATEWAY,
        self::REFUND_ID,
        self::ACTION,
        self::BANK,
        self::AMOUNT,
        self::STATUS,
        self::GATEWAY_REFERENCE_ID,
        self::GATEWAY_REFERENCE_ID2,
        self::SIGNED_XML,
        self::UMRN,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
    ];

    protected $fillable = [
        self::RECEIVED,
        self::AUTHENTICATION_GATEWAY,
        self::SIGNED_XML,
        self::UMRN,
        self::STATUS,
        self::ACQUIRER,
        self::GATEWAY_REFERENCE_ID,
        self::GATEWAY_REFERENCE_ID2,
        self::ACKNOWLEDGE_STATUS,
        self::REGISTRATION_STATUS,
        self::REGISTRATION_DATE,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
    ];

    protected $defaults = [
        self::REFUND_ID             => null,
        self::STATUS                => null,
        self::UMRN                  => null,
        self::GATEWAY_REFERENCE_ID  => null,
        self::GATEWAY_REFERENCE_ID2 => null,
        self::ACKNOWLEDGE_STATUS    => null,
        self::REGISTRATION_STATUS   => null,
        self::REGISTRATION_DATE     => null,
        self::ERROR_MESSAGE         => null,
        self::ERROR_CODE            => null,
    ];

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }

    public function getGatewayReferenceId()
    {
        return $this->getAttribute(self::GATEWAY_REFERENCE_ID);
    }

    public function getGatewayReferenceId2()
    {
        return $this->getAttribute(self::GATEWAY_REFERENCE_ID2);
    }

    public function getSignedXml()
    {
        return $this->getAttribute(self::SIGNED_XML);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    public function getUmrn()
    {
        return $this->getAttribute(self::UMRN);
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

    public function setAuthenticationGateway($gateway)
    {
        $this->setAttribute(self::AUTHENTICATION_GATEWAY, $gateway);
    }

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
}
