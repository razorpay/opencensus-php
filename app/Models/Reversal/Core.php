<?php

namespace RZP\Models\Reversal;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a reversal for a Marketplace refund,
     * and a transaction that updates the Marketplace balance
     *
     * @param  Transfer\Entity $transfer
     * @param  Merchant\Entity $merchant
     * @param array            $input
     *
     * @return Entity
     */
    public function createForMarketplaceRefund(
        Transfer\Entity $transfer,
        Merchant\Entity $merchant,
        array $input) : Entity
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $transfer->getId(),
                'input'       => $input
            ]);

        $transfer->reverseAmount($input[Entity::AMOUNT]);

        $this->repo->saveOrFail($transfer);

        $input[Entity::CURRENCY] = $transfer->getCurrency();

        $reversal = $this->create($input);

        $reversal->merchant()->associate($merchant);

        $reversal->entity()->associate($transfer);

        $txn = (new Transaction\Core)->createFromTransferReversal($reversal);

        $this->repo->saveOrFail($txn);

        $reversal->transaction()->associate($txn);

        $this->repo->saveOrFail($reversal);

        $this->traceSuccess(TraceCode::TRANSFER_REVERSAL_SUCCESS, $reversal);

        return $reversal;
    }

    /**
     * Create and process a reversal on a transfer
     *
     * @param  Transfer\Entity $transfer
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\LogicException
     */
    public function reverseForTransfer(Transfer\Entity $transfer, array $input, Merchant\Entity $merchant) : Entity
    {
        // Reversals not handled yet for customer wallet - transfer refunds
        // @todo: Change flow to create reversals for both customer/account transfers
        if ($transfer->getToType() !== E::MERCHANT)
        {
            throw new Exception\LogicException(
                'Reversal attempted on invalid transfer to_type - ' . $transfer->getToType()
            );
        }

        return $this->mutex->acquireAndRelease(
            $transfer->getId(),
            function() use ($transfer, $input, $merchant)
            {
                (new Validator)->validateReversalAmount($transfer, $input);

                return $this->repo->transaction(function () use ($transfer, $input, $merchant)
                {
                    $reversal = (new Payment\Processor\Processor($merchant))
                                    ->refundPaymentAndReverseTransfer($transfer, $input);

                    $this->traceSuccess(TraceCode::DISPUTE_TRANSFER_SUCCESS, $reversal);

                    return $reversal;
                });
            });
    }

    protected function create(array $input) : Entity
    {
        $reversal = (new Entity)->build($input);

        $reversal->generateId();

        return $reversal;
    }

    protected function traceSuccess(string $code, Entity $reversal)
    {
        $traceMessage = [
            'entity_type'       => $reversal->getEntityType(),
            'entity_id'         => $reversal->getEntityId(),
            'reversal_id'       => $reversal->getId(),
            'refund_amount'     => $reversal->getAmount()
        ];

        $this->trace->info($code, $traceMessage);
    }
}
