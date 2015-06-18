<?php

namespace Models\Card\IIN;

use Models\Base;
use Constants\Table;

class Entity extends Base\PublicEntity
{
    const IIN       = 'iin';
    const CATEGORY  = 'category';
    const NETWORK   = 'network';
    const TYPE      = 'type';
    const COUNTRY   = 'country';
    const ISSUER    = 'issuer';
    const TRIVIA    = 'trivia';

    const ID_LENGTH = 6;

    protected $entity = 'iin';

    protected $table = Table::IIN;

    protected $primaryKey = self::IIN;

    public $timestamps = false;

    protected $fillable = array(
        self::IIN,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::COUNTRY,
        self::ISSUER,
        self::TRIVIA);

    protected $public = array(
        self::IIN,
        self::ENTITY,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::COUNTRY,
        self::ISSUER,
        self::TRIVIA,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::BRAND);
    }

    public function getIinAttribute()
    {
        return (int) $this->attributes[self::IIN];
    }
}