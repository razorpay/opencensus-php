<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use Database\Factories\P2PBankFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{
    use Base\Traits\HasHandle;
    use HasFactory;

    const HANDLE           = 'handle';
    const NAME             = 'name';
    const IFSC             = 'ifsc';
    const UPI_IIN          = 'upi_iin';
    const UPI_FORMAT       = 'upi_format';
    const GATEWAY_DATA     = 'gateway_data';
    const ACTIVE           = 'active';
    const SPOC             = 'spoc';

    const UPI              = 'upi';
    const BANK             = 'bank';
    const BANKS            = 'banks';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_bank';
    protected $primaryKey         = self::ID;
    protected $generateIdOnCreate = true;
    protected static $generators  = [
        Entity::REFRESHED_AT,
    ];
    protected static $sign        = 'bank';

    protected $dates = [
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::IFSC,
        Entity::NAME,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::UPI_IIN,
        Entity::UPI_FORMAT,
        Entity::ACTIVE,
    ];

    protected $visible = [
        Entity::ID,
        Entity::IFSC,
        Entity::NAME,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::UPI_IIN,
        Entity::UPI_FORMAT,
        Entity::ACTIVE,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::ENTITY,
        Entity::IFSC,
        Entity::NAME,
        Entity::UPI,
        Entity::ACTIVE,
    ];

    protected $defaults = [
        Entity::IFSC             => null,
        Entity::NAME             => null,
        Entity::UPI_IIN          => null,
        Entity::UPI_FORMAT       => null,
        Entity::ACTIVE           => null,
    ];

    protected $casts = [
        Entity::IFSC             => 'string',
        Entity::NAME             => 'string',
        Entity::HANDLE           => 'string',
        Entity::GATEWAY_DATA     => 'array',
        Entity::UPI_IIN          => 'string',
        Entity::UPI_FORMAT       => 'string',
        Entity::ACTIVE           => 'bool',
        Entity::REFRESHED_AT     => 'int',
        Entity::CREATED_AT       => 'int',
        Entity::UPDATED_AT       => 'int',
    ];

    protected $publicSetters = [
        Entity::ID,
        Entity::UPI,
        Entity::ENTITY,
    ];

    /***************** SETTERS *****************/

    public static function verifyUniqueId($id, $throw = true)
    {
        return false;
    }

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setIfsc(string $ifsc)
    {
        return $this->setAttribute(self::IFSC, $ifsc);
    }

    /**
     * @return $this
     */
    public function setName(string $name)
    {
        return $this->setAttribute(self::NAME, $name);
    }

    /**
     * @return $this
     */
    public function setUpiIin(string $upiIin)
    {
        return $this->setAttribute(self::UPI_IIN, $upiIin);
    }

    /**
     * @return $this
     */
    public function setUpiFormat(string $upiFormat)
    {
        return $this->setAttribute(self::UPI_FORMAT, $upiFormat);
    }

    /**
     * @return $this
     */
    public function setActive(bool $active)
    {
        return $this->setAttribute(self::ACTIVE, $active);
    }

    /**
     * @return $this
     */
    public function setSpoc(array $spoc)
    {
        return $this->setAttribute(self::SPOC, $spoc);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::IFSC
     */
    public function getIfsc()
    {
        return $this->getAttribute(self::IFSC);
    }

    /**
     * @return string self::NAME
     */
    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    /**
     * @return string self::UPI_IIN
     */
    public function getUpiIin()
    {
        return $this->getAttribute(self::UPI_IIN);
    }

    /**
     * @return string self::UPI_FORMAT
     */
    public function getUpiFormat()
    {
        return $this->getAttribute(self::UPI_FORMAT);
    }

    /**
     * @return bool self::ACTIVE
     */
    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    /**
     * @return array self::SPOC
     */
    public function getSpoc()
    {
        return $this->getAttribute(self::SPOC);
    }

    /***************** MUTATORS *****************/

    public function setPublicUpiAttribute(array & $array)
    {
        $array[self::UPI] = true;
    }

    protected static function newFactory(): P2PBankFactory
    {
        return P2PBankFactory::new();
    }
}
