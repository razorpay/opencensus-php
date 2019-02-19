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
 *
 * @property Merchant\Entity $merchant
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    // Attributes
    const NAME         = 'name';
    const CONTACT      = 'contact';
    const EMAIL        = 'email';
    const TYPE         = 'type';

    //
    // Reference ID is metadata set by the merchant, this does not
    // refer to any entity on our system
    //
    const REFERENCE_ID = 'reference_id';
    const NOTES        = 'notes';
    const ACTIVE       = 'active';

    // Additional input & output attributes
    const ACCOUNT_NUMBER  = 'account_number';
    const FUND_ACCOUNT_ID = 'fund_account_id';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::CONTACT,
        self::EMAIL,
        self::TYPE,
        self::REFERENCE_ID,
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
        self::REFERENCE_ID,
        self::ACTIVE,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::CONTACT      => null,
        self::EMAIL        => null,
        self::TYPE         => null,
        self::REFERENCE_ID => null,
        self::NOTES        => [],
        self::ACTIVE       => true,
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

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    public function setType(string $type = null)
    {
        $this->setAttribute(self::TYPE, $type);
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

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------
}
