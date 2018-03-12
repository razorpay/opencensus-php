<?php

namespace RZP\Gateway\Enach\Base;

use Crypt;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const ACTION                = 'action';
    const BANK                  = 'bank';
    const RECEIVED              = 'received';
    const SIGNED_XML            = 'signed_xml';
    const UMRN                  = 'umrn';

    protected $entity = 'enach';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::ACTION,
        self::BANK,
        self::RECEIVED,
        self::SIGNED_XML,
        self::UMRN,
    ];

    protected $fillable = [
        self::BANK,
        self::RECEIVED,
        self::SIGNED_XML,
    ];

    protected $defaults = [
        self::UMRN => null,
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

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
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
