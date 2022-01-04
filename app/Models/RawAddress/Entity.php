<?php

namespace RZP\Models\RawAddress;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const DELETED_AT            = 'deleted_at';
    const CONTACT               = 'contact';
    const STATUS                = 'status';
    const LINE1                 = 'line1';
    const LINE2                 = 'line2';
    const ZIPCODE               = 'zipcode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const NAME                  = 'name';
    const TAG                   = 'tag';
    const LANDMARK              = 'landmark';
    const MERCHANT_ID           = 'merchant_id';
    const BATCH_ID              = 'batch_id';

    protected static $sign      = 'rawaddr';

    protected $entity           = 'raw_address';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::CONTACT,
        self::STATUS,
        self::LINE1,
        self::LINE2,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::NAME,
        self::TAG,
        self::LANDMARK,
        self::MERCHANT_ID,
        self::BATCH_ID,
    ];


    protected $visible = [
        self::ID,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CONTACT,
        self::NAME,
        self::TAG,
        self::LANDMARK,
        self::LINE1,
        self::LINE2,
        self::STATUS,
        self::MERCHANT_ID,
        self::BATCH_ID,
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::CONTACT,
    ];

    protected $defaults = [
        self::STATUS  => "pending",
        self::ZIPCODE => "",
        self::CITY => "",
        self::STATE => "",
        self::COUNTRY => "",
        self::LINE1 => "",
        self::NAME => "",
        self::CONTACT => "",
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];


    // ----------------------------------- MODIFIERS -----------------------------------

    protected function modifyCountry(& $input)
    {
        if (empty($input[self::COUNTRY]) === true)
        {
            return;
        }

        $country = & $input[self::COUNTRY];

        $country = strtolower($country);
        // Remove dots
        $country = str_replace('.', '', $country);
        // Replace hyphens and underscores with a space
        $country = str_replace(['-', '_'], ' ', $country);
    }

    // ----------------------------------- END MODIFIERS -----------------------------------

    // ----------------------------------- GETTERS -----------------------------------

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    // ----------------------------------- END GETTERS -----------------------------------

    // ----------------------------------- SETTERS -----------------------------------


    public function setContact($contact)
    {
        return $this->setAttribute(self::CONTACT, $contact);
    }

    public function setStatus($value)
    {
       return  $this->setAttribute(self::STATUS, $value);
    }

    // ----------------------------------- END SETTERS -----------------------------------
}
