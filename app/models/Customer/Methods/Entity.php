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

    protected static $sign      = '';

    protected $entity           = 'customer_method';

    protected $table            = \Constants\Table::CUSTOMER_METHOD;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::CUSTOMER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,
    );

    protected $visible = array(
        self::ID,
        self::CUSTOMER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,        
    );

    protected $public = array(
        self::ID,
        self::CUSTOMER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,        
    );
}


