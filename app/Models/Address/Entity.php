<?php

namespace RZP\Models\Address;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const TYPE                  = 'type';
    const PRIMARY               = 'primary';
    const LINE1                 = 'line1';
    const LINE2                 = 'line2';
    const ZIPCODE               = 'zipcode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const DELETED_AT            = 'deleted_at';
    const CONTACT               = 'contact';
    const NAME                  = 'name';
    const TAG                   = 'tag';
    const LANDMARK              = 'landmark';
    const SOURCE_ID             = 'source_id';
    const SOURCE_TYPE           = 'source_type';
    const PINCODE               = 'pincode';

    protected static $sign      = 'addr';

    protected $entity           = 'address';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CONTACT,
        self::NAME,
        self::TAG,
        self::LANDMARK,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
    ];

    protected static $modifiers = [
        self::COUNTRY,
    ];

    protected $visible = [
        self::ID,
        self::LINE1,
        self::LINE2,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::PRIMARY,
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
        self::SOURCE_ID,
        self::SOURCE_TYPE,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CONTACT,
        self::NAME,
        self::TAG,
        self::LANDMARK,
    ];

    protected $defaults = [
        self::LINE2         => null,
        self::ZIPCODE       => null,
        self::PRIMARY       => true,
        self::COUNTRY       => Constants\Country::IN,
        self::SOURCE_ID     => null,
        self::SOURCE_TYPE   => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
    ];

    protected $casts = [
        self::PRIMARY => 'bool'
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isPrimary()
    {
        return $this->getAttribute(self::PRIMARY);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    /**
     * Returns mapped country name. Note that country attribute actually holds code.
     * @return string|null
     */
    public function getCountryName()
    {
        $code = $this->getAttribute(self::COUNTRY);

        return $code !== null ? Constants\Country::getCountryNameByCode($code) : null;
    }

    public function getCountryNameFormatted()
    {
        $name = $this->getCountryName();

        return $name !== null ? ucwords($name) : null;
    }

    public function getLine1()
    {
        return $this->getAttribute(self::LINE1);
    }

    public function getLine2()
    {
        return $this->getAttribute(self::LINE2);
    }

    public function getZipcode()
    {
        return $this->getAttribute(self::ZIPCODE);
    }

    public function getCity()
    {
        return $this->getAttribute(self::CITY);
    }

    public function getState()
    {
        return $this->getAttribute(self::STATE);
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getBillingAddress()
    {
        return [
            'line1'         => $this->getLine1(),
            'line2'         => $this->getLine2(),
            'city'          => $this->getCity(),
            'state'         => $this->getState(),
            'country'       => $this->getCountry(),
            'postal_code'   => $this->getZipcode(),
        ];
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getTag()
    {
        return $this->getAttribute(self::TAG);
    }

    public function getLandmark()
    {
        return $this->getAttribute(self::LANDMARK);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    // ----------------------------------- END GETTERS -----------------------------------

    // ----------------------------------- SETTERS -----------------------------------

    public function setEntityType($entityType)
    {
        Type::validateEntityType($entityType);

        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setPrimary($primary)
    {
        $this->setAttribute(self::PRIMARY, $primary);
    }

    public function setLine1($line1)
    {
        $this->setAttribute(self::LINE1, $line1);
    }

    public function setLine2($line2)
    {
        return $this->setAttribute(self::LINE2, $line2);
    }

    public function setZipcode($zipCode)
    {
        return $this->setAttribute(self::ZIPCODE, $zipCode);
    }

    public function setCity($city)
    {
        return $this->setAttribute(self::CITY, $city);
    }

    public function setState($state)
    {
        return $this->setAttribute(self::STATE, $state);
    }

    public function setCountry($country)
    {
        return $this->setAttribute(self::COUNTRY, $country);
    }

    public function setTag($tag)
    {
        return $this->setAttribute(self::TAG, $tag);
    }

    public function setLandmark($landmark)
    {
        return $this->setAttribute(self::LANDMARK, $landmark);
    }

    public function setContact($contact)
    {
        return $this->setAttribute(self::CONTACT, $contact);
    }

    // ----------------------------------- END SETTERS -----------------------------------

    // ----------------------------------- MUTATORS -----------------------------------

    protected function setCountryAttribute($country)
    {
        $countryCode = Constants\Country::getCountryCode($country);

        $this->attributes[self::COUNTRY] = $countryCode;
    }

    // ----------------------------------- END MUTATORS -----------------------------------

    // ----------------------------------- PUBLIC SETTERS -----------------------------------

    public function setPublicEntityIdAttribute(array & $array)
    {
        $entity = Type::getEntityClass($array[self::ENTITY_TYPE]);

        $sign = $entity::getIdPrefix();

        $array[self::ENTITY_ID] = $sign . $array[self::ENTITY_ID];
    }

    // ----------------------------------- END PUBLIC SETTERS -----------------------------------

    // ----------------------------------- RELATIONS -----------------------------------

    public function source()
    {
        $entityType = $this->getAttribute(self::ENTITY_TYPE);

        Type::validateEntityType($entityType);

        $class = Constants\Entity::getEntityClass($entityType);

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    public function sourceAssociate(Base\Entity $entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->source()->associate($entity);
    }

    // ----------------------------------- END RELATIONS -----------------------------------

    public function formatAsText(string $delimiter = PHP_EOL): string
    {
        return Utility::formatAddressAsText(
            [
                self::LINE1     => $this->getAttribute(self::LINE1),
                self::LINE2     => $this->getAttribute(self::LINE2),
                self::ZIPCODE   => $this->getAttribute(self::ZIPCODE),
                self::CITY      => $this->getAttribute(self::CITY),
                self::STATE     => $this->getAttribute(self::STATE),
                self::COUNTRY   => $this->getCountryNameFormatted(),
            ],
            $delimiter);
    }

    public function buildForPayment($input)
    {
        $this->modify($input);

        $this->validateInput('create_for_payment', $input);

        $this->generate($input);

        $this->unsetInput('create_for_payment', $input);

        $this->fill($input);

        return $this;
    }

    public function buildForCustomer($input)
    {
        $this->modify($input);

        $this->validateInput('create_for_customer', $input);

        $this->generate($input);

        $this->unsetInput('create_for_customer', $input);

        $this->fill($input);

        return $this;
    }

    public function editForCustomer($input)
    {
        $this->validateInput('edit_for_customer', $input);

        $this->unsetInput('edit_for_customer', $input);

        $this->fill($input);

        return $this;
    }
}
