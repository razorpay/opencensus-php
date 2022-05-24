<?php

namespace RZP\Models\Address\AddressConsent1ccAudits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID            = 'id';
    const CONTACT       = 'contact';
    const UNIQUE_ID     = 'unique_id';

    protected $entity = 'address_consent_1cc_audits';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    // Added this to prevent errors on absence of updated_at field
    const UPDATED_AT = null;

    protected $fillable = [
        self::CONTACT,
        self::UNIQUE_ID,
    ];

    protected $public = [
        self::ID,
        self::CONTACT,
        self::UNIQUE_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::DELETED_AT,
    ];

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getUuid()
    {
        return $this->getAttribute(self::UNIQUE_ID);
    }

    public function setUuid(string $uuid)
    {
        $this->setAttribute(self::UNIQUE_ID, $uuid);
    }

    public function setContact(string $contact)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }

}
