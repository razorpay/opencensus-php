<?php

namespace Models\Settlement\Daily;

use Models\Base;
use Models\Gateway;
use Models\Settlement\Daily;

class Service extends Base\Service
{
    public function fetch($id)
    {
        Daily\Entity::verifyIdAndStripSign($id);

        $setl = (new Daily\Repository)->findOrFailPublic($id);

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $settlements = (new Daily\Repository)->fetch($input);

        return $settlements->toArrayPublic();
    }
}