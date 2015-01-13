<?php

namespace Models\Card;

use Models\Base;
use Constants\Table;

class Detail extends Base\Entity
{
    const IIN       = 'iin';
    const CATEGORY  = 'category';
    const NETWORK   = 'network';
    const TYPE      = 'type';
    const COUNTRY   = 'country';
    const ISSUER    = 'issuer';

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