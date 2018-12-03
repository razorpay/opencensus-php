<?php

namespace RZP\Models\FundAccount;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Contact;
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
    const CONTACT_ID   = 'contact_id';
    const ACCOUNT_TYPE = 'account_type';
    const ACCOUNT_ID   = 'account_id';
    const ACTIVE       = 'active';

    const ACCOUNT = 'account';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ACTIVE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_ID,
        self::ACTIVE,
        self::ACCOUNT,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
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

    protected static $sign = 'fundacc';

    protected $entity = 'fund_account';

    // --------------- Getters ---------------

    public function getBeneficiaryId()
    {
        return $this->getAttribute(self::CONTACT_ID);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getAccountId()
    {
        return $this->getAttribute(self::ACCOUNT_ID);
    }

    public function getActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    public function setPublicContactIdAttribute(array & $array)
    {
        $contactId = $this->getAttribute(self::CONTACT_ID);

        $array[self::CONTACT_ID] = Contact\Entity::getSignedIdOrNull($contactId);
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

    public function contact()
    {
        return $this->belongsTo(Contact\Entity::class);
    }

    public function account()
    {
        return $this->morphTo();
    }

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------
}
