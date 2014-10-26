<?php

namespace Models\Terminal;

use Crypt;
use Models\Base;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletingTrait;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const USED_COUNT                = 'used_count';
    const GATEWAY                   = 'gateway';
    const GATEWAY_MERCHANT_ID       = 'gateway_merchant_id';
    const GATEWAY_TERMINAL_ID       = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD = 'gateway_terminal_password';

    const DELETED_AT                = 'deleted_at';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_PASSWORD);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_PASSWORD,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT);

    protected $table = 'terminals';

    protected $hidden = array(self::GATEWAY_TERMINAL_PASSWORD);

    protected $genereateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $sign = '';

    protected static $delimiter = '';

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
        $this->attributes[self::GATEWAY_TERMINAL_PASSWORD] = Crypt::encrypt($password);
    }

    public function getGatewayTerminalPasswordAttribute()
    {
        return Crypt::decrypt($this->attributes[self::GATEWAY_TERMINAL_PASSWORD]);
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
}