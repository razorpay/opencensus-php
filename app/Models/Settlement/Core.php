<?php

namespace RZP\Models\Settlement;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }
}
