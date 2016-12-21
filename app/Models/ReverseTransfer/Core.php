<?php

namespace RZP\Models\ReverseTransfer;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * Create a reverse_transfer for a Marketplace refund,
     * and a transaction that updates the Marketplace balance
     *
     * @param  Transfer\Entity              $transfer
     * @param  Merchant\Entity              $merchant
     * @param  int                          $amount
     * @return ReverseTransfer\Entity
     */
    public function createForMarketplaceRefund(Transfer\Entity $transfer, Merchant\Entity $merchant, int $amount) : Entity
    {
        $transfer->reverseAmount($amount);

        $reverseTrf = $this->createEntity($amount);

        $reverseTrf->transfer()->associate($transfer);

        $reverseTrf->merchant()->associate($merchant);

        $txn = (new Transaction\Core)->createFromReverseTransfer($reverseTrf);

        $this->repo->saveOrFail($txn);

        $reverseTrf->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        $this->repo->saveOrFail($reverseTrf);

        return $reverseTrf;
    }

    protected function createEntity(int $amount) : Entity
    {
        $data = [
            'amount'    => $amount
        ];

        $reverseTrf = (new Entity)->fill($data);

        $reverseTrf->generateId();

        return $reverseTrf;
    }

}
