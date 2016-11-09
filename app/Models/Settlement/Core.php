<?php

namespace RZP\Models\Settlement;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }
}
