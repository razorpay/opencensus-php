<?php

namespace RZP\Models\Customer;

use App;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Address;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HardDeletes;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HardDeletes;
    use HasFactory;

    const NAME                  = 'name';
    const CONTACT               = 'contact';
    const EMAIL                 = 'email';
    const MERCHANT_ID           = 'merchant_id';
    const GLOBAL_CUSTOMER_ID    = 'global_customer_id';
    const GSTIN                 = 'gstin';
    const ACTIVE                = 'active';
    const NOTES                 = 'notes';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    const FAIL_EXISTING         = 'fail_existing';

    const VPAS                  = 'vpas';
    const BANK_ACCOUNTS         = 'bank_accounts';

    protected static $sign      = 'cust';

    protected $entity           = 'customer';

    //
    // Additional input keys. Not attributes of entity.
    //
    const BILLING_ADDRESS       = 'billing_address';
    const SHIPPING_ADDRESS      = 'shipping_address';
    const BILLING_ADDRESS_ID    = 'billing_address_id';
    const SHIPPING_ADDRESS_ID   = 'shipping_address_id';

    // shared customer id
    const SHARED_CUSTOMER_CONTACT = '+919999999999';
    const SHARED_CUSTOMER_EMAIL   = 'void@razorpay.com';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::GSTIN,
        self::CONTACT,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::NOTES,
        self::ACTIVE,
        self::CONTACT,
        self::GSTIN,
        self::SHIPPING_ADDRESS,
        self::MERCHANT_ID,
        self::GLOBAL_CUSTOMER_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::VPAS,
        self::BANK_ACCOUNTS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::CONTACT,
        self::GSTIN,
        self::NOTES,
        self::SHIPPING_ADDRESS,
        self::CREATED_AT,
        self::VPAS,
        self::BANK_ACCOUNTS,
    ];

    protected $defaults = [
        self::NAME                  => null,
        self::CONTACT               => null,
        self::EMAIL                 => null,
        self::GSTIN                 => null,
        self::ACTIVE                => true,
        self::NOTES                 => [],
        self::GLOBAL_CUSTOMER_ID    => null,
    ];

    protected $appends = [
        self::SHIPPING_ADDRESS,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SHIPPING_ADDRESS,
    ];

    // ----------------------------------- GETTERS ----------------------------

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }

    public function isGlobal()
    {
        return ($this->getMerchantId() === Account::SHARED_ACCOUNT);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getGstin()
    {
        return $this->getAttribute(self::GSTIN);
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function hasGlobalCustomer(): bool
    {
        return $this->isAttributeNotNull(self::GLOBAL_CUSTOMER_ID);
    }

    // ----------------------------------- END GETTERS ------------------------

    // ----------------------------------- ACCESSORS --------------------------

    protected function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    protected function getShippingAddressAttribute()
    {
        $input[Address\Entity::TYPE] = Address\Type::SHIPPING_ADDRESS;

        $app = App::getFacadeRoot();

        $shippingAddresses = $app['repo']->address->fetchAddressesForEntity($this, $input);

        if ($shippingAddresses->count() === 0)
        {
            return null;
        }

        return $shippingAddresses->toArrayPublicEmbedded();
    }

    // ----------------------------------- END ACCESSORS ----------------------

    // ----------------------------------- PUBLIC SETTERS ---------------------

    public function setPublicShippingAddressAttribute(array & $array)
    {
        if (empty($array[self::SHIPPING_ADDRESS]) === true)
        {
            unset($array[self::SHIPPING_ADDRESS]);
        }
    }

    // ----------------------------------- END PUBLIC SETTERS -----------------

    // ----------------------------------- MUTATORS ---------------------------

    protected function setNameAttribute($name)
    {
        $trimmedName = ($name === null) ? null : trim($name);

        $this->attributes[self::NAME] = $trimmedName;
    }

    protected function setEmailAttribute($email)
    {
        $formattedEmail = ($email === null) ? null : mb_strtolower(trim($email));

        $this->attributes[self::EMAIL] =  $formattedEmail;
    }

    public function setContactAttribute($contact)
    {
        $trimmedContact = ($contact === null) ? null : trim($contact);

        $this->attributes[self::CONTACT] = $trimmedContact;
    }

    // ----------------------------------- END MUTATORS -----------------------

    // ----------------------------------- RELATIONS --------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function tokens()
    {
        return $this->hasMany(Token\Entity::class);
    }

    public function vpas()
    {
        return $this->hasMany(Vpa\Entity::class);
    }

    public function bank_accounts()
    {
        return $this->hasMany('RZP\Models\BankAccount\Entity', 'entity_id');
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'to');
    }

    public function globalCustomer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity', self::GLOBAL_CUSTOMER_ID, self::ID);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice\Entity::class);
    }

    // ----------------------------------- END RELATIONS ----------------------

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
