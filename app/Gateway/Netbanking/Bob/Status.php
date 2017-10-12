<?php

namespace RZP\Gateway\Netbanking\Bob;

use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Status
{
    const SUCCESS = 'S';
    const FAILURE = 'F';

    public static function isStatusCodeSuccess($content): bool
    {
        return ($content[NetbankingEntity::STATUS] === Status::SUCCESS);
    }
}
