<?php

namespace Models\Terminal;

use Crypt;
use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const GATEWAY                   = 'gateway';
    const GATEWAY_TERMINAL_ID       = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD = 'gateway_terminal_password';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_PASSWORD);

    protected $table = 'terminals';

    protected $hidden = array(self::GATEWAY_TERMINAL_PASSWORD);

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = array('inputRemoveBlanks');

    protected function setGatewayTerminalPassword($password)
    {
        $this->attribute[self::GATEWAY_TERMINAL_PASSWORD] = Crypt::encrypt($password);
    }

    protected function getMerchantId()
    {
        return $this->attribute[self::MERCHANT_ID];
    }

    protected function getGatewayTerminalPassword()
    {
        return Crypt::decrypt($this->attribute[self::GATEWAY_TERMINAL_PASSWORD]);
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
}