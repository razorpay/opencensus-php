<?php

namespace RZP\Models\Reversal;

use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $transfer->getId(),
                'amount'      => $amount
            ]);

        $transfer->reverseAmount($amount);

        $this->repo->saveOrFail($transfer);

        $reversal = $this->createEntity($amount, $transfer->getCurrency());

        $reversal->transfer()->associate($transfer);

        $reversal->merchant()->associate($merchant);

        $txn = (new Transaction\Core)->createFromReversal($reversal);

        $this->repo->saveOrFail($txn);

        $reversal->transaction()->associate($txn);

        $this->repo->saveOrFail($reversal);

        $this->traceSuccess($reversal);

        return $reversal;
    }

    /**
     * Create and process a reversal on a transfer
     *
     * @param  string           $id
     * @param  array            $input
     * @param  Merchant\Entity  $merchant
     * @return Reversal\Entity
     */
    public function reverse(string $id, array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $id,
                'input'       => $input
            ]);

        $transfer = $this->repo
                         ->transfer
                         ->findByPublicIdAndMerchant($id, $merchant);

        (new Validator)->validateReversalAmount($transfer, $input);

        // Reversals not handled yet for customer wallet - transfer refunds
        // @todo: Change flow to create reversals for both customer/account transfers
        assert ($transfer->getToType() === E::MERCHANT);

        // If amount is not sent in input,
        // reverse the entire transfer amount pending
        $amount = $input['amount'] ?? $transfer->getAmountUnreversed();

        return $this->repo->transaction(function () use ($transfer, $amount, $merchant)
        {
            $reversal = (new Payment\Processor\Processor($merchant))
                            ->refundPaymentAndReverseTransfer($transfer, $amount);

            $this->traceSuccess($reversal);

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

    protected function traceSuccess(Entity $reversal)
    {
        $traceMessage = [
            'transfer_id'       => $reversal->getTransferId(),
            'reversal_id'       => $reversal->getId(),
            'refund_amount'     => $reversal->getAmount()
        ];

        $this->trace->info(TraceCode::TRANSFER_REVERSAL_SUCCESS, $traceMessage);
    }
}
