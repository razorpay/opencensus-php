<?php

namespace Gateway\Kotak;

use Models\Base;

class Entity extends Base\PublicEntity
{
    protected $fields = array(
        'TxnType',
        'TxnRefNo',
        'OrderInfo',
        'Amount',
        'Currency',
        'MCC',
        'AuthCode',
        'CardType',
        'ResponseCode',
        'RetRefNo',
    );
}