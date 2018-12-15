<?php

namespace RZP\Models\FundAccount;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Customer;
use RZP\Models\Merchant;

/**
 * Class Entity
 *
 * @package RZP\Models\FundAccount
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    // Attributes
    const ACCOUNT_TYPE  = 'account_type';
    const ACCOUNT_ID    = 'account_id';
    const SOURCE_TYPE   = 'source_type';
    const SOURCE_ID     = 'source_id';
    const ACTIVE        = 'active';

    const CONTACT_ID    = 'contact_id';
    const CUSTOMER_ID   = 'customer_id';
    const SOURCE        = 'source';

    const ACCOUNT = 'account';
    const DETAILS = 'details';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ACTIVE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::CUSTOMER_ID,
        self::ACCOUNT_TYPE,
        self::DETAILS,
        self::ACTIVE,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::DETAILS,
    ];

    protected $embeddedRelations = [
        self::ACCOUNT,
    ];
    protected $defaults = [
        self::ACTIVE => true,
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected static $sign = 'fa';

    protected $entity = 'fund_account';

    // --------------- Getters ---------------

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getAccountId()
    {
        return $this->getAttribute(self::ACCOUNT_ID);
    }

    public function getActive(): bool
    {
        return $this->getAttribute(self::ACTIVE);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    public function setPublicSourceAttribute(array & $array)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $sourceType = $this->getAttribute(self::SOURCE_TYPE);

        //
        // The `source` relation is polymorphic internally. Externally, we
        // want to show it separately as `contact_id` and `customer_id`
        //
        if ($sourceType === Constants\Entity::CONTACT)
        {
            $array[self::CONTACT_ID] = Contact\Entity::getSignedIdOrNull($sourceId);
        }
        else if ($sourceType === Constants\Entity::CUSTOMER)
        {
            $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($sourceId);
        }
    }

    public function setPublicDetailsAttribute(array & $array)
    {
        // Expose the account relation in the `details` attribute.
        $publicAttributes = $this->account->toArrayPublic();

        // For now, don't expose the public id and entity attributes from any of the related entities
        array_forget($publicAttributes, [Base\PublicEntity::ID, Base\PublicEntity::ENTITY]);

        $array[self::DETAILS] = $publicAttributes;
    }

    // ------------- End Setters -------------

    // --------------- Helpers ---------------

    public function isActive(): bool
    {
        return ($this->getActive() === true);
    }

    // ------------- End Helpers -------------

    // -------------- Relations --------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function account()
    {
        return $this->morphTo();
    }

    public function source()
    {
        return $this->morphTo();
    }

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------
}
