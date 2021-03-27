<?php

namespace RZP\Models\WalletAccount;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\VirtualAccount;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const ENTITY_ID            = 'entity_id';
    const ENTITY_TYPE          = 'entity_type';
    const PHONE                = 'phone';
    const EMAIL                = 'email';
    const NAME                 = 'name';
    const PROVIDER             = 'provider';
    const MERCHANT_ID          = 'merchant_id';
    const FTS_FUND_ACCOUNT_ID  = 'fts_fund_account_id';
    const DELETED_AT           = 'deleted_at';

    protected $generateIdOnCreate = true;

    protected $primaryKey = self::ID;

    protected $entity = 'wallet_account';

    protected static $sign = 'wa_';

    protected $fillable = [
        self::PHONE,
        self::PROVIDER,
        self::EMAIL,
        self::NAME,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::PHONE,
        self::EMAIL,
        self::NAME,
        self::PROVIDER,
        self::MERCHANT_ID,
        self::FTS_FUND_ACCOUNT_ID,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PHONE,
        self::PROVIDER,
        self::EMAIL,
        self::NAME,
    ];

    protected $ignoredRelations = [
        'source',
    ];

    public function matches(array $input)
    {
        // We do a build so that validations and other things are run before checking for duplicate
        $new = (new Entity)->build($input);

        return (($this->getPhone() === $new->getPhone()) and
            ($this->getProvider() === $new->getProvider()));
    }

    // ----------------------- Getters -----------------------

    public function getPhone()
    {
        return $this->getAttribute(self::PHONE);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getProvider()
    {
        return $this->getAttribute(self::PROVIDER);
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

    public function setPhone($phone)
    {
        return $this->setAttribute(self::PHONE, $phone);
    }

    public function setEmail($email)
    {
        return $this->setAttribute(self::EMAIL, $email);
    }

    public function setName($name)
    {
        return $this->setAttribute(self::NAME, $name);
    }

    public function setProvider($provider)
    {
        return $this->setAttribute(self::PROVIDER, $provider);
    }

    public function setFtsFundAccountId($ftsFundAccountId)
    {
        return $this->setAttribute(self::FTS_FUND_ACCOUNT_ID, $ftsFundAccountId);
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
}
