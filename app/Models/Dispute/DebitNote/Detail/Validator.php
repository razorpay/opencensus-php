<?php

namespace RZP\Models\Dispute\DebitNote\Detail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DEBIT_NOTE_ID => 'required|string|exists:debit_note,id',
        Entity::DETAIL_TYPE   => 'required|string|in:dispute',
        Entity::DETAIL_ID     => 'required|string|size:14',
    ];
}