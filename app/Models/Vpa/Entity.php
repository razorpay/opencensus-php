<?php

namespace RZP\Models\Vpa;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\VirtualAccount;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const ENTITY_ID            = 'entity_id';
    const ENTITY_TYPE          = 'entity_type';
    const USERNAME             = 'username';
    const HANDLE               = 'handle';
    const MERCHANT_ID          = 'merchant_id';
    const FTS_FUND_ACCOUNT_ID  = 'fts_fund_account_id';
    const DELETED_AT           = 'deleted_at';

    const ADDRESS = 'address';

    const AROBASE = '@';

    // Made it blank so that pl service can decide the entiure upi descriptor
    const PAYMENT_LINK_VPA_PREFIX = "";

    protected $generateIdOnCreate = true;

    protected $primaryKey = self::ID;

    protected $entity = 'vpa';

    protected static $sign = 'vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::USERNAME,
        self::HANDLE,
        self::MERCHANT_ID,
        self::FTS_FUND_ACCOUNT_ID,
        self::ADDRESS,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::USERNAME,
        self::HANDLE,
        self::ADDRESS,
    ];

    protected $appends = [
        self::ADDRESS,
    ];

    protected $ignoredRelations = [
        'source',
    ];

    protected static $generators = [
        'user_name_and_handle',
    ];

    protected static $unsetCreateInput = [
        self::ADDRESS,
    ];

    public function matches(array $input)
    {
        // We do a build so that validations and other things are run before checking for duplicate
        $new = (new Entity)->build($input);

        return ($this->getAddress() === $new->getAddress());
    }

    public function virtualAccount()
    {
        return $this->hasOne(VirtualAccount\Entity::class);
    }

    public function qrCode()
    {
        return $this->belongsTo(QrV2\Entity::class, Entity::ENTITY_ID);
    }

    // ----------------------- Generators ------------------

    protected function generateUserNameAndHandle($input)
    {
        $addressArray = explode(self::AROBASE, $input[self::ADDRESS]);

        $this->setAttribute(self::USERNAME, strtolower($addressArray[0]));
        $this->setAttribute(self::HANDLE, $addressArray[1]);
    }

    // ----------------------- Getters -----------------------

    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    public function getAddress()
    {
        return $this->getAttribute(self::ADDRESS);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getFtsFundAccountId()
    {
        return $this->getAttribute(self::FTS_FUND_ACCOUNT_ID);
    }

    // ----------------------- Setters -----------------------

    public function setHandle($handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    public function setFtsFundAccountId($ftsFundAccountId)
    {
        return $this->setAttribute(self::FTS_FUND_ACCOUNT_ID, $ftsFundAccountId);
    }

    public function setAddress($address)
    {
        list($username, $handle) = explode(self::AROBASE, $address);
        $this->setAttribute(self::USERNAME, $username);
        $this->setAttribute(self::HANDLE, $handle);
    }

    // ----------------------- Accessor ----------------------

    protected function getAddressAttribute()
    {
        return $this->getUsername() . self::AROBASE . $this->getHandle();
    }

    // ----------------------- Relations -----------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function scopeAddress(BuilderEx $query, string $address)
    {
        list($username, $handle) = explode(self::AROBASE, $address);

        $query->where(Entity::USERNAME, $username)
              ->where(Entity::HANDLE, $handle);
    }
}
