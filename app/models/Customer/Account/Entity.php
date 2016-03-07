<?php

namespace Models\Customer;

use Models\Base;

class Entity extends Base\PublicEntity
{
        const NAME              =       'name';
        const EMAIL             =       'email';
        const CONTACT           =       'contact';
        const MERCHANT_ID       =       'merchant_id';

        protected static $sign      = '';

        protected $entity           = 'customer';

        protected $table            = \Constants\Table::CUSTOMER;

        protected $genereateIdOnCreate = true;

        protected $fillable = array(
            self::ID,
            self::NAME,
            self::EMAIL,
            self::CONTACT,
            self::MERCHANT_ID,
        );

        protected $visible = array(
            self::ID,
            self::NAME,
            self::EMAIL,
            self::CONTACT,
            self::MERCHANT_ID,
        );

        protected $public = array(
            self::ID,
            self::NAME,
            self::EMAIL,
            self::CONTACT
        );
}


