<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Constants\Table;

class Detail extends Base\Entity
{
    const IIN       = 'iin';
    const CATEGORY  = 'category';
    const NETWORK   = 'network';
    const TYPE      = 'type';
    const COUNTRY   = 'country';
    const ISSUER    = 'issuer';
    const TRIVIA    = 'trivia';

    public $timestamps = false;

    protected $guarded = array('*');

    public function __construct()
    {

    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }
}