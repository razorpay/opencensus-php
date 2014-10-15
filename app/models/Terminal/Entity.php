<?php

namespace Models\Terminal;

use Crypt;
use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const GATEWAY                   = 'gateway';
    const GATEWAY_MERCHANT_ID       = 'gateway_merchant_id';
    const GATEWAY_TERMINAL_ID       = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD = 'gateway_terminal_password';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_PASSWORD);

    protected $table = 'terminals';

    protected $hidden = array(self::GATEWAY_TERMINAL_PASSWORD);

    protected $genereateIdOnCreate = true;

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = array('inputRemoveBlanks');

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
        return $this->attribute[self::GATEWAY_TERMINAL_ID];
    }

    public function getGateway()
    {
        return $this->attribute[self::GATEWAY];
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