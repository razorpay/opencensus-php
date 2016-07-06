<?php

namespace Models\Settlement;

use RZP\Exception;
use Models\Base;
use Models\Card;
use Models\Transaction;
use Models\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }
}