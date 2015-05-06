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

    protected $guarded = array('*');

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::BRAND);
    }
}