<?php

namespace RZP\Models\Settlement;

use EE\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }
}