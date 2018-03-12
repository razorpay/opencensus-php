<?php

namespace RZP\Gateway\Enach\Base;

use Crypt;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const ACTION                = 'action';
    const RECEIVED              = 'received';
    const SIGNED_XML            = 'signed_xml';
    const UMRN                  = 'umrn';
    const ACKNOWLEDGE_STATUS    = 'acknowledge_status';
    const REGISTRATION_STATUS   = 'registration_status';

    protected $entity = 'enach';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::ACTION,
        self::RECEIVED,
        self::SIGNED_XML,
        self::UMRN,
    ];

    protected $fillable = [
        self::RECEIVED,
        self::SIGNED_XML,
        self::UMRN,
        self::ACKNOWLEDGE_STATUS,
        self::REGISTRATION_STATUS,
    ];

    protected $defaults = [
        self::UMRN                  => null,
        self::ACKNOWLEDGE_STATUS    => null,
        self::REGISTRATION_STATUS   => null,
    ];

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

    public function getReceived()
    {
        return $this->getAttribute(self::RECEIVED);
    }
}
