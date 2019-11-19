<?php

namespace RZP\Models\PaperMandate;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'paper_mandate';

    protected $expands = [
        Entity::BANK_ACCOUNT,
    ];
}
