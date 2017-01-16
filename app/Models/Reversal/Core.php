<?php

namespace RZP\Models\Reversal;

use RZP\Constants\Entity as E;
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
        $transfer->reverseAmount($amount);

        $this->repo->saveOrFail($transfer);

        $reversal = $this->createEntity($amount, $transfer->getCurrency());

        $reversal->transfer()->associate($transfer);

        $reversal->merchant()->associate($merchant);

        $txn = (new Transaction\Core)->createFromReversal($reversal);

        $this->repo->saveOrFail($txn);

        $reversal->transaction()->associate($txn);

        $this->repo->saveOrFail($reversal);

        return $reversal;
    }

    /**
     * Create and process a reversal on a transfer
     *
     * @param  string           $id
     * @param  array            $input
     * @return Reversal\Entity
     */
    public function reverse(string $id, array $input)
    {
        $transfer = $this->repo
                         ->transfer
                         ->findByPublicIdAndMerchant($id, $this->merchant);

        (new Validator)->validateReversalAmount($transfer, $input);

        // Reversals not handled yet for customer wallet - transfer refunds
        // @todo: Change flow to create reversals for both customer/account transfers
        assert ($transfer->getToType() === E::MERCHANT);

        // If amount not sent in input,
        // reverse the entire transfer amount pending
        $amount = $input['amount'] ?? $transfer->getAmountUnreversed();

        return $this->repo->transaction(function () use ($transfer, $amount)
        {
            $reversal = (new Payment\Processor\Processor($this->merchant))
                            ->refundPaymentAndReverseTransfer($transfer, $amount);

            return $reversal;
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
