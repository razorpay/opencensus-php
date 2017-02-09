<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\Transfer;

trait Reversal
{
    /**
     * Refund the transfer payment and
     * create a reversal for the transfer
     *
     * @param  Payment\Entity $payment
     * @param  string         $accountId
     * @param  int            $amount
     */
    public function refundPaymentAndReverseTransfer(Transfer\Entity $transfer, int $amount)
    {

        $transferPayment = $this->repo
                                ->payment
                                ->findByTransferIdAndMerchant(
                                    $transfer->getId(), $transfer->getToId());

        // Refund the transfer payment - this debits the account balance
        (new Processor($transferPayment->merchant))
            ->refundTransferPayment($transferPayment, $amount);

        // Reverse the associated transfer - this credits the marketplace balance
        return (new ReversalCore)
                    ->createForMarketplaceRefund($transfer, $this->merchant, $amount);
    }

    /**
     * Process reversal of transfers send in the `reversals` attribute
     *
     * @param  array  $reversals
     */
    protected function processReversals(array $reversals)
    {
        foreach ($reversals as $reversal)
        {
            $transfer = $this->repo
                             ->transfer
                             ->findByPublicIdAndMerchant($reversal['transfer'], $this->merchant);

            $amountUnreversed = $transfer->getAmountUnreversed();

            if ($reversal['amount'] > $amountUnreversed)
            {
                $message = 'Reversal amount exceeds the unreversed amount for transfer_id: ' . $transfer->getPublicId();

                throw new Exception\BadRequestValidationFailureException(
                    $message,
                    'reversal_amount',
                    ['unreversed_amount' => $amountUnreversed]
                    );
            }

            $this->refundPaymentAndReverseTransfer($transfer, $reversal['amount']);
        }
    }

    /**
     * Check if the refund should be processed with Marketplace transfer reversals,
     * This also modifies the input array to add reversals, if required
     *
     * @param  Payment\Entity   $payment
     * @param  array            $input
     * @return bool
     */
    protected function shouldProcessReversals(Payment\Entity $payment, array & $input) : bool
    {
        // Dont process if either:
        //  Payment has not been transferred (amount_transferred = 0), or
        //  Payment method = 'transfer', or
        //  Payment is fully refunded
        if (($payment->isTransferred() === false) or
            ($payment->isTransfer() === true) or
            ($payment->getRefundStatus() === Payment\Refund\Status::FULL))
        {
            return false;
        }

        $transfers = null;

        $reverseAll = $this->checkReversalsOnRefundType($payment, $input, $transfers);

        if ((isset($input['reversals']) === false) and
            ($reverseAll === true))
        {
            $this->implicitAddReversalsForFullRefund($transfers, $input);
        }

        return true;
    }

    /**
     * Based on the type of refund being processed, providing the
     * `reversals` array in input may be optional or mandatory. This
     * function validates the logic around this.
     *
     * @param  Payment\Entity           $payment
     * @param  array                    $input
     * @param  PublicCollection         $transfers
     * @return bool
     */
    protected function checkReversalsOnRefundType(Payment\Entity $payment, array $input, & $transfers) : bool
    {
        $refundType = $this->getPaymentRefundType($payment, $input);

        $reverseAll = false;

        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourcePaymentIdAndMerchant($payment->getId(), $this->merchant);

        if ($refundType === Payment\Refund\Status::FULL)
        {
            $reverseAll = true;
        }
        else if ($refundType === Payment\Refund\Status::PARTIAL)
        {
            $transferCount = count($transfers);

            assert ($transferCount !== 0);

            if ($transferCount > 1)
            {
                // `reversals` must be provided when there are multiple
                // transfers created on the payment, on partial refund
                (new Payment\Refund\Validator)->validateReversalsRequired($input);
            }
            else if ($transferCount === 1)
            {
                // When only a single transfer exists, we auto-reverse
                // the entire transfer amount
                $reverseAll = true;
            }
        }
        else
        {
            assert (false);
        }

        return $reverseAll;
    }

    /**
     * When reversals are to be processed but not provided
     * in input, we  implicitly add reversals for the transfers
     * corresponding to the paymment being refunded
     *
     * @param  PublicCollection     $transfers
     * @param  array                $input
     */
    protected function implicitAddReversalsForFullRefund($transfers, array & $input)
    {
        $reversals = [];

        foreach ($transfers as $transfer)
        {
            $amountToReverse = $transfer->getAmountUnreversed();

            if ($amountToReverse === 0)
            {
                continue;
            }

            $reversals[] = [
                'transfer'  => $transfer->getPublicId(),
                'amount'    => $amountToReverse,
            ];
        }

        $input['reversals'] = $reversals;
    }
}
