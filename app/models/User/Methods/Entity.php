<?php

namespace Models\User\Methods;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const USER_ID       =       'user_id';
    const METHOD        =       'method';
    const CARD_ID       =       'card_id';
    const BANK          =       'bank';
    const WALLET        =       'wallet';

    protected static $sign      = '';

    protected $entity           = 'user_method';

    protected $table            = \Constants\Table::USER_METHOD;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::USER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,
    );

    protected $visible = array(
        self::ID,
        self::USER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,        
    );

    protected $public = array(
        self::ID,
        self::USER_ID,
        self::METHOD,
        self::CARD_ID,
        self::BANK,
        self::WALLET,        
    );
}


