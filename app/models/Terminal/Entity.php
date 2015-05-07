<?php

namespace Models\Terminal;

use Crypt;
use Models\Base;
use Models\Payment;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletingTrait;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const USED_COUNT                    = 'used_count';
    const GATEWAY                       = 'gateway';
    const GATEWAY_MERCHANT_ID           = 'gateway_merchant_id';
    const GATEWAY_TERMINAL_ID           = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD     = 'gateway_terminal_password';
    const GATEWAY_ACCESS_CODE           = 'gateway_access_code';
    const GATEWAY_SECURE_SECRET         = 'gateway_secure_secret';

    const CARD                          = 'card';
    const DELETED_AT                    = 'deleted_at';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_PASSWORD);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::USED_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT);

    protected $table = 'terminals';

    protected $hidden = array(self::GATEWAY_TERMINAL_PASSWORD);

    protected $genereateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::CARD);

    public function generateCard($input)
    {
        if ((isset($input[self::CARD]) === false) and
            ($input[self::GATEWAY] === Payment\Gateway::HDFC))
        {
            $this->setAttribute(self::CARD, 1);
        }
    }

    public function incrementUsedCount()
    {
        $usedCount = $this->getUsedCount() + 1;

        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function getMerchantId()
    {
        return $this->attributes[self::MERCHANT_ID];
    }

    public function getGatewayMerchantId()
    {
        return $this->attributes[self::GATEWAY_MERCHANT_ID];
    }

    protected function setGatewayTerminalPasswordAttribute($password)
    {
        if ($password === null)
            $password = '';

        $this->attributes[self::GATEWAY_TERMINAL_PASSWORD] = Crypt::encrypt($password);
    }

    protected function setGatewaySecureSecretAttribute($secret)
    {
        if ($secret === null)
            $secret = '';

        $this->attributes[self::GATEWAY_SECURE_SECRET] = Crypt::encrypt($secret);
    }

    protected function getGatewayTerminalPasswordAttribute()
    {
        $pwd = $this->attributes[self::GATEWAY_TERMINAL_PASSWORD];

        if ($pwd === null)
            return $pwd;

        return Crypt::decrypt($pwd);
    }

    protected function getGatewaySecureSecretAttribute()
    {
        $secret = $this->attributes[self::GATEWAY_SECURE_SECRET];

        if ($secret === null)
            return $secret;

        return Crypt::decrypt($secret);
    }

    public function getGatewayTerminalId()
    {
        return $this->getAttribute(self::GATEWAY_TERMINAL_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function getUsedCountAttribute()
    {
        return (int) $this->attributes[self::USED_COUNT];
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function toArrayWithPassword()
    {
        $terminal = $this->toArray();

        $terminal[self::GATEWAY_TERMINAL_PASSWORD] = $this->getGatewayTerminalPasswordAttribute();

        return $terminal;
    }

    public function isCardEnabled()
    {
        return (((int)$this->getAttribute(self::CARD)) === 1);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }
}