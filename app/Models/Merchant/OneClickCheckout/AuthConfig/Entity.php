<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use App;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    const PLATFORM    = 'platform';
    const CONFIG      = 'config';
    const VALUE       = 'value';

    protected $entity = 'merchant_1cc_auth_configs';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PLATFORM,
        self::CONFIG,
        self::VALUE,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::PLATFORM,
        self::CONFIG,
        self::VALUE,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant\Entity::class, Merchant\Entity::ID, self::MERCHANT_ID);
    }

    public function getConfig()
    {
        return $this->getAttributeValue(self::CONFIG);
    }

    public function getValue()
    {
        $value = $this->getAttribute(self::VALUE);

        $config = $this->getConfig();

        $app = App::getFacadeRoot();

        if (in_array($config, Constants::ENCRYPTED_FIELDS))
        {
            $value = $app['encrypter']->decrypt($value);
        }
        return $value;
    }

    public function setConfig(string $value)
    {
        $this->setAttribute(self::CONFIG, $value);
    }

    public function setValue(string $value)
    {
        $app = App::getFacadeRoot();

        if (in_array($value, Constants::ENCRYPTED_FIELDS))
        {
            $value = $app['encrypter']->encrypt($value);
        }

        $this->setAttribute(self::VALUE, $value);
    }
}
