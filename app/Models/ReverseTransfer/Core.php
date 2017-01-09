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
        $reverseTrf = $this->createEntity($amount, $transfer->getCurrency());

        $reverseTrf->transfer()->associate($transfer);

        $reverseTrf->merchant()->associate($merchant);

        $reverseTrf->setBaseAmount();

        $txn = (new Transaction\Core)->createFromReverseTransfer($reverseTrf);

        $this->repo->saveOrFail($txn);

        $reverseTrf->transaction()->associate($txn);

        $transfer->reverseAmount($amount, $reverseTrf->getBaseAmount());

        $this->repo->saveOrFail($transfer);

        $this->repo->saveOrFail($reverseTrf);

        return $reverseTrf;
    }

    public function createForTransferReversal(Transfer\Entity $transfer, array $input)
    {
        (new Validator)->validateInput('reversal', $input);

        $amount = $input['amount'] ?? $transfer->getAmountUnreversed();

        return $this->repo->transaction(function () use ($transfer, $input, $amount)
        {
            return $this->createForMarketplaceRefund($transfer, $this->merchant, $amount);
        });
    }

    protected function createEntity(int $amount, string $currency) : Entity
    {
        $data = [
            'amount'    => $amount,
            'currency'  => $currency,
        ];

        $reverseTrf = (new Entity)->fill($data);

        $reverseTrf->generateId();

        return $reverseTrf;
    }

}
