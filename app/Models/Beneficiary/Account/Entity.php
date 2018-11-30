<?php

namespace RZP\Models\Beneficiary\Account;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Beneficiary;

/**
 * Class Entity
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    // Attributes
    const BENEFICIARY_ID = 'beneficiary_id';
    const ACCOUNT_TYPE   = 'account_type';
    const ACCOUNT_ID     = 'account_id';
    const ACTIVE         = 'active';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ACTIVE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::BENEFICIARY_ID,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_ID,
        self::ACTIVE,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::ACTIVE => true,
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    protected $dates = [
        self::CREATED_AT,
    ];

    protected static $sign = 'beneacc';

    protected $entity = 'beneficiary_account';

    // --------------- Getters ---------------

    public function getBeneficiaryId()
    {
        return $this->getAttribute(self::BENEFICIARY_ID);
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

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary\Entity::class);
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
