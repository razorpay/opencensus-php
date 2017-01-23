<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Payment;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\Transfer;

trait Reversal
{
    /**
     * Refund the transfer payment
     * and create a reversal for the transfer
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

        // Refund the transfer payment
        (new Processor($transferPayment->merchant))
            ->refundTransferPayment($transferPayment, $amount);

        // Reverse the associated transfer
        return (new ReversalCore)
                    ->createForMarketplaceRefund($transfer, $this->merchant, $amount);
    }

    /**
     * Check if the refund should be processed with Marketplace reversals,
     * Also modifies input to add reversals, if required
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

        $reverseAll = $this->checkReversalsOnRefundType($payment, $input, $transfers);

        if ((isset($input['reversals']) === false) and
            ($reverseAll === true))
        {
            $this->implicitAddReversalsForFullRefund($transfers, $input);
        }

        return true;
    }

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
                // Reversals need to be provided only if there are
                // multiple transfers created on a payment.
                (new Payment\Refund\Validator)->validateReversalsRequired($input);
            }
            else if ($transferCount === 1)
            {
                // When only a single transfer exists, we reverse
                // the amount on it.
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
     * When reversals are to be processed but not provided,
     * we fetch and implicitly add reversals
     *
     * @param  PublicCollection     $transfers
     * @param  Payment\Entity       $payment
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
