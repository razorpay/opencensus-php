<?php

namespace RZP\Models\ReverseTransfer;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Payment;

class Core extends Base\Core
{

    public function createForRefund(Transfer\Entity $transfer, $merchant, $amount)
    {
        $reverseTrf = $this->createEntity($amount);

        $reverseTrf->transfer()->associate($transfer);

        $reverseTrf->merchant()->associate($merchant);

        $txn = (new Transaction\Core)->createFromReverseTransfer($reverseTrf);

        $this->repo->saveOrFail($txn);

        $reverseTrf->transaction()->associate($txn);

        $this->repo->saveOrFail($reverseTrf);

        return $reverseTrf;
    }

    protected function createEntity($amount) : Entity
    {
        $data = [
            'amount'    => $amount
        ];

        $reverseTrf = (new Entity)->fill($data);

        $reverseTrf->generateId();

        return $reverseTrf;
    }

}
