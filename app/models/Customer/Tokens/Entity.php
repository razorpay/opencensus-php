<?php

namespace Models\Customer\Token;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const CUSTOMER_ID           = 'customer_id';
    const TOKEN                 = 'token';
    const METHOD                = 'method';
    const CARD_ID               = 'card_id';
    const CARD                  = 'card';
    const BANK                  = 'bank';
    const WALLET                = 'wallet';
    const GATEWAY_TOKEN         = 'gateway_token';

    protected static $sign      = 'tokn';

    protected $entity           = 'token';

    protected $table            = \Constants\Table::TOKEN;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::TOKEN,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::GATEWAY_TOKEN,
    );

    protected $visible = array(
        self::ID,
        self::BANK,
        self::WALLET,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::CARD,
        self::CUSTOMER_ID,
        self::GATEWAY_TOKEN,
    );

    protected $public = array(
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::CARD,
    );

    protected $defaults = array();

    protected $publicSetters = array(
        self::CARD,
    );

    public function customer()
    {
        return $this->belongsTo('Models\Customer\Entity');
    }

    public function card()
    {
        return $this->belongsTo('Models\Card\Entity');
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

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getGatewayToken()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function setPublicCardAttribute(array & $array)
    {
        if($this->card !== null)
        {
            $array[self::CARD] = $this->card->toArrayPublic();
        }
    }
}