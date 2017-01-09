<?php

namespace RZP\Models\Reversal;

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
     * Create a reversal for a Marketplace refund,
     * and a transaction that updates the Marketplace balance
     *
     * @param  Transfer\Entity              $transfer
     * @param  Merchant\Entity              $merchant
     * @param  int                          $amount
     * @return Reversal\Entity
     */
    public function createForMarketplaceRefund(Transfer\Entity $transfer, Merchant\Entity $merchant, int $amount) : Entity
    {
        $reversal = $this->createEntity($amount, $transfer->getCurrency());

        $reversal->transfer()->associate($transfer);

        $reversal->merchant()->associate($merchant);

        $reversal->setBaseAmount();

        $txn = (new Transaction\Core)->createFromReversal($reversal);

        $this->repo->saveOrFail($txn);

        $reversal->transaction()->associate($txn);

        $transfer->reverseAmount($amount, $reversal->getBaseAmount());

        $this->repo->saveOrFail($transfer);

        $this->repo->saveOrFail($reversal);

        return $reversal;
    }


    /**
     * Creates a Reversal from a direct transfer
     *
     * @param  Transfer\Entity      $transfer
     * @param  array                $input
     * @return Reversal\Entity
     */
    public function createForTransferReversal(Transfer\Entity $transfer, array $input) : Entity
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

        $reversal = (new Entity)->fill($data);

        $reversal->generateId();

        return $reversal;
    }

}
