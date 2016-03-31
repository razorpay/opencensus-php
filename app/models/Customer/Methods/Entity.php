<?php

namespace Models\Customer\Methods;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const CUSTOMER_ID   =       'customer_id';
    const METHOD        =       'method';
    const CARD_ID       =       'card_id';
    const BANK          =       'bank';
    const WALLET        =       'wallet';
    const ACCOUNT_KEY   =       'account_key';
    const NOTES         =       'notes';

    protected static $sign      = '';

    protected $entity           = 'customer_method';

    protected $table            = \Constants\Table::CUSTOMER_METHOD;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::NOTES,
        self::WALLET,
        self::METHOD,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::ACCOUNT_KEY,
    );

    protected $visible = array(
        self::ID,
        self::BANK,
        self::NOTES,
        self::WALLET,
        self::METHOD,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::ACCOUNT_KEY,
    );

    protected $public = array(
        self::ID,
        self::BANK,
        self::NOTES,
        self::WALLET,
        self::METHOD,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::ACCOUNT_KEY,
    );

    protected $defaults = array(
        self::NOTES             => [],
    );

    public function customer()
    {
        return $this->belongsTo('Models\Customer\Account\Entity');
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

    public function getAccountKey()
    {
        return $this->getAttribute(self::ACCOUNT_KEY);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function setNotesAttribute($notes)
    {
        $this->attributes[self::NOTES] = json_encode($notes);
    }

    public function getNotesAttribute()
    {
        return json_decode($this->attributes[self::NOTES]);
    }

    public function getFormattedMethod()
    {
        $formattedMethod = array();

        if ($this->getMethod() == 'card')
        {
            $formattedMethod['card_id'] = $this->getCardId();
            $formattedMethod['last4'] = $this->card->getLast4();
            $formattedMethod['issuer'] = $this->card->getIssuer();
            $formattedMethod['network'] = $this->card->getNetwork();
            $formattedMethod['emi'] = false;
        }

        return $formattedMethod;
    }
}