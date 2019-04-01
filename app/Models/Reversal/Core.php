<?php

namespace RZP\Models\Reversal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Constants\Entity as E;
use RZP\Models\Adjustment\Core as AdjustmentCore;

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
     * @param  Refund\Entity   $refund
     * @param array            $input
     *
     * @return Entity
     */
    public function createForMarketplaceRefund(
        Transfer\Entity $transfer,
        Merchant\Entity $merchant,
        Refund\Entity $refund,
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

        $refund->reversal()->associate($reversal);

        $this->repo->saveOrFail($refund);

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
                $this->repo->reload($transfer);

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

    /**
     * Creates a customer reversal and credits merchant fee.
     *
     * @param Payout\Entity $payout
     *
     * @return Entity
     */
    private function reverseCustomerPayout(Payout\Entity $payout): Entity
    {
        // Not taking a mutex lock because we have select for update on customer and merchant balances and
        // Reversals are initiated by internal razorpay FTA recon cron.
        $reversalInput = [
            Entity::AMOUNT   => $payout->getAmount(),
            Entity::CURRENCY => $payout->getCurrency(),
        ];

        $payoutFee = $payout->getFees();

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);

        $reversal->entity()->associate($payout);

        $reversal->customer()->associate($payout->customer);

        $reversal = $this->repo->transaction(function () use ($reversal, $payoutFee)
        {
            // Creates a customer transaction for crediting the amount debited during the payout.
            $customerTxn = (new Customer\Transaction\Core)->createForCustomerCredit($reversal,
                                                                                    $reversal->getAmount(),
                                                                                    $reversal->getCustomerId(),
                                                                                    $reversal->merchant);
            $reversal->transaction()->associate($customerTxn);

            if ($payoutFee > 0)
            {
                // Creating the positive adjustment with source as reversal for the merchant fee charged on payout.
                $this->reverseMerchantFeeForCustomerPayoutReversal($reversal, $payoutFee);
            }

            $this->repo->saveOrFail($reversal);

            return $reversal;
        });

        return $reversal;
    }

    private function reverseMerchantFeeForCustomerPayoutReversal(Entity $reversal, int $payoutFee)
    {
        // For crediting customer payout fee we will create a positive adjustment for the merchant.
        $adjustmentData = [
            Adjustment\Entity::CURRENCY    => $reversal->getCurrency(),
            Adjustment\Entity::AMOUNT      => $payoutFee,
            Adjustment\Entity::DESCRIPTION => 'Credit wallet withdrawal fee amount for payout reversal',
        ];

        // Create merchant adjustment.
        (new AdjustmentCore)->createAdjustmentForSource($adjustmentData, $reversal);
    }

    /**
     * Creates a merchant payout reversal.
     *
     * @param \RZP\Models\Payout\Entity $payout
     *
     * @return Entity
     */
    private function reverseMerchantPayout(Payout\Entity $payout): Entity
    {
        $reversalInput = [
            Entity::AMOUNT   => $payout->getAmount() + $payout->getFees(),
            Entity::CURRENCY => $payout->getCurrency(),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);
        $reversal->entity()->associate($payout);

        $reversal->balance()->associate($payout->balance);

        $reversal = $this->repo->transaction(function() use ($reversal)
        {
            $txn = (new Transaction\Core)->createFromPayoutReversal($reversal);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($reversal);

            return $reversal;
        });

        return $reversal;
    }

    /**
     * Create a full reversal for a payout
     *
     * @param Payout\Entity $payout
     *
     * @return Entity
     */
    public function reverseForPayout(Payout\Entity $payout): Entity
    {
        if ($payout->isCustomerPayout() === true)
        {
            $reversal = $this->reverseCustomerPayout($payout);
        }
        else
        {
            $reversal = $this->reverseMerchantPayout($payout);
        }

        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_CREATED,
            [
                'payout_id' => $payout->getId(),
                'reversal_id' => $reversal->getId(),
            ]);

        return $reversal;
    }

    /**
     * Create a full reversal for a refund
     **
     * @return Entity
     */
    public function reverseForRefund(Payment\Refund\Entity $refund): Entity
    {
        $reversalInput = [
            Entity::AMOUNT   => $refund->getAmount() + $refund->getFees(),
            Entity::CURRENCY => $refund->getCurrency(),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($refund->getChannel());

        $reversal->merchant()->associate($refund->merchant);
        $reversal->entity()->associate($refund);

        // Todo: change below line in refunds balance_id PR - currently refunds does not have any balance
         $reversal->balance()->associate($refund->merchant->primaryBalance);

        $reversal = $this->repo->transaction(function() use ($reversal)
        {
            $txn = (new Transaction\Core)->createFromRefundReversal($reversal);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($reversal);

            return $reversal;
        });

        $this->trace->info(
            TraceCode::REFUND_REVERSAL_CREATED,
            [
                'refund_id'   => $refund->getId(),
                'reversal_id' => $reversal->getId(),
                'payment_id'  => $refund->getPaymentId()
            ]);

        return $reversal;
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
