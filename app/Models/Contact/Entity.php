<?php

namespace RZP\Models\Contact;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * Class Entity
 *
 * @package RZP\Models\Contact
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    // Attributes
    const NAME    = 'name';
    const CONTACT = 'contact';
    const EMAIL   = 'email';
    const TYPE    = 'type';
    const NOTES   = 'notes';
    const ACTIVE  = 'active';

    // search attributes
    const FUND_ACCOUNT_ID = 'fund_account_id';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::CONTACT,
        self::EMAIL,
        self::TYPE,
        self::ACTIVE,
        self::NOTES,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::CONTACT,
        self::EMAIL,
        self::TYPE,
        self::ACTIVE,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::CONTACT => null,
        self::EMAIL   => null,
        self::TYPE    => null,
        self::NOTES   => [],
        self::ACTIVE  => true,
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected static $sign = 'cont';

    protected $entity = 'contact';

    // --------------- Getters ---------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
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

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------
}
