<?php

namespace Models\Customer\Token;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID           = 'merchant_id';
    const CUSTOMER_ID           = 'customer_id';
    const TERMINAL_ID           = 'terminal_id';
    const TOKEN                 = 'token';
    const METHOD                = 'method';
    const CARD_ID               = 'card_id';
    const CARD                  = 'card';
    const BANK                  = 'bank';
    const WALLET                = 'wallet';
    const GATEWAY_TOKEN         = 'gateway_token';
    const GATEWAY_TOKEN2        = 'gateway_token2';
    const EXPIRES_AT            = 'expires_at';

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $table            = \Constants\Table::TOKEN;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::EXPIRES_AT,
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::CARD,
        self::CUSTOMER_ID,
        self::TERMINAL_ID,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::EXPIRES_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $public = array(
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::CARD,
    );

    protected $defaults = array(
        self::WALLET         => null,
        self::BANK           => null,
        self::CARD_ID        => null,
        self::GATEWAY_TOKEN2 => null,
        self::EXPIRES_AT     => null
    );

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::CARD);

    protected static $generators = array(
        self::TOKEN
    );

    public function customer()
    {
        return $this->belongsTo('Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function card()
    {
        return $this->belongsTo('Models\Card\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('Models\Terminal\Entity');
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getGatewayToken()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN);
    }

    public function setPublicCardAttribute(array & $array)
    {
        if ($this->card !== null)
        {
            $array[self::CARD] = $this->card->toArrayPublic();
        }
    }

    protected function generateToken($input)
    {
        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 14);

        $this->setAttribute(self::TOKEN, $token);
    }
}
