<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\Reversal\Entity as ReversalEntity;

trait Reversal
{
    /**
     * Fetches and refunds the transfer payment and
     * create a reversal for the transfer
     *
     * @param Transfer\Entity $transfer
     * @param array $input
     * @param Merchant\Entity $initiator Route Merchant / Linked Account initiating the reversal
     *
     * @return array Containing reversal and refund entity in indexes 0 and 1 respectively
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function refundPaymentAndReverseTransfer(
        Transfer\Entity $transfer,
        array $input,
        Merchant\Entity $initiator = null)
    {
        $transferPayment = $this->repo
                                ->payment
                                ->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());

        $transferPayment = $this->repo->payment->findOrFail($transferPayment->getId());

        //
        // If amount is not sent in input,
        // reverse the entire transfer amount pending
        //
        $input[ReversalEntity::AMOUNT] = $input[ReversalEntity::AMOUNT] ?? $transfer->getAmountUnreversed();

        //
        // If the transfer source is a payment or an order, we decrement
        // the payment.amount_transferred with the amount of the reversal.
        // This is to allow further transfers to be made on the payment.
        //
        if ($transfer->getSourceType() === E::PAYMENT)
        {
            $sourcePayment = $transfer->source;

            $sourcePayment->decrementAmountTransferred($input[ReversalEntity::AMOUNT]);

        }
        else if ($transfer->getSourceType() === E::ORDER)
        {
            $sourceOrderId = $transfer->getSourceId();

            $sourcePayment = $this->repo->payment->getCapturedPaymentForOrder($sourceOrderId);

            // fetching payment again to get from sources configured for archived entity
            // As of now, archived payment fetch with findOrFail happens on fallback replica
            // This will also prevent columns like _record_source from warm storage to be present in entity attributes
            $sourcePayment = $this->repo->payment->findOrFail($sourcePayment->getId());

            $sourcePayment->decrementAmountTransferred($input[ReversalEntity::AMOUNT]);
        }

        $refundNotes = (new Transfer\Core)->getLinkedAccountNotes($input);

        // If reversal initiated by linked account then store the same notes in
        // reversal.notes and (transfer payment's) refund.notes
        if ((empty($initiator) === false) and ($initiator->isLinkedAccount() === true))
        {
            $refundNotes = $input[ReversalEntity::NOTES] ?? [];
        }

        // Refund the transfer payment - this debits the account balance
        $refund = $this->mutex->acquireAndRelease($transferPayment->getId(), function() use ($input, $transferPayment, $refundNotes)
        {
            $refundInput = [
                Refund\Entity::AMOUNT => $input[ReversalEntity::AMOUNT],
                Refund\Entity::NOTES  => $refundNotes,
            ];

            return (new Processor($transferPayment->merchant))
                ->refundTransferPayment($transferPayment, $refundInput);
        });

        // Reverse the associated transfer - this credits the marketplace balance
        return (new ReversalCore)
                    ->createForMarketplaceRefund($transfer, $this->merchant, $refund, $input, $initiator);
    }

    /**
     * Refund an internal marketplace payment (method = transfer)
     *
     * @param  Payment\Entity $payment
     * @param  array          $input
     *
     * @throws Exception\BadRequestException
     * @return Refund\Entity
     */
    protected function refundTransferPayment(Payment\Entity $payment, array $input): Refund\Entity
    {
        if ($payment->isTransfer() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_TRANSFER);
        }

        $this->validatePaymentForRefund($payment, $input);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        $refund->setBaseAmount();

        $refund->setGatewayAmountCurrency();

        $refund->balance()->associate($refund->merchant->primaryBalance);

        $this->validateMerchantBalance($refund, 'reversal');

        list($txn, $feesSplit) = (new Transaction\Core)->createFromRefund($refund);

        $this->repo->saveOrFail($txn);

        $amount = $refund->getAmount();

        $baseAmount = $refund->getBaseAmount();

        if ($payment->isExternal() === false)
        {
            $payment = $this->repo->payment->lockForUpdate($payment->getKey());
        }

        $payment->refundAmount($amount, $baseAmount);

        $this->repo->saveOrFail($payment);

        return $refund;
    }

    /**
     * Process reversal of transfers send in the `reversals` attribute
     *
     * @param array $reversals
     *
     * @return array of refunds created of corresponding reversals
     */
    protected function processReversals(array $reversals)
    {
        $refunds = [];

        foreach ($reversals as $reversal)
        {
            if ($reversal['transfer'] instanceof Transfer\Entity)
            {
                $transfer = $reversal['transfer'];
            }
            else
            {
                $transfer = $this->repo
                                 ->transfer
                                 ->findByPublicIdAndMerchant($reversal['transfer'], $this->merchant);
            }

            if ($transfer->isFailed() === true)
            {
                continue;
            }

            unset($reversal['transfer']);

            $refund = $this->mutex->acquireAndRelease(
                $transfer->getId(),
                function() use ($transfer, $reversal)
                {
                    $amountUnreversed = $transfer->getAmountUnreversed();

                    if ($reversal['amount'] > $amountUnreversed)
                    {
                        throw new Exception\LogicException(
                            'Reversal amount exceeds the unreversed amount',
                            null,
                            [
                                'transfer_id'       => $transfer->getId(),
                                'amount'            => $reversal['amount'],
                                'unreversed_amount' => $amountUnreversed,
                            ]
                        );
                    }

                    $result = $this->refundPaymentAndReverseTransfer($transfer, $reversal);

                    // result has reversal and refund entity in indexes 0 and 1 respectively
                    // returning refund entity
                    $reversal = $result[0] ?? null;
                    $refund = $result[1] ?? null;

                    (new ReversalCore())->createLedgerEntriesForRouteReversal($this->merchant, $reversal, $refund);

                    return $refund;
                });

            array_push($refunds , $refund);
        }

        return $refunds;
    }

    /**
     * Check if the refund should be processed with Marketplace transfer reversals,
     * This also modifies the input array to add reversals, if required
     *
     * Documented here:
     * https://docs.google.com/document/d/1Tz_apm6NU0SJPl6z4ctZ9PRfvmf-nliT1Xz1rhJfoPA/edit#heading=h.tu2zrnpp106m
     *
     * @param  Payment\Entity $payment
     * @param  array          $input
     * @return bool
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function shouldProcessReversals(Payment\Entity $payment, array & $input) : bool
    {
        //
        // Don't process if either:
        //  - Payment has not been transferred (amount_transferred = 0), or
        //  - Payment method = 'transfer'
        //
        if (($payment->isTransferred() === false) or
            ($payment->isTransfer() === true))
        {
            return false;
        }

        $transfers = null;

        $validator = new Payment\Refund\Validator;

        $validator->setPayment($payment);

        // Validating here to verify reversal attributes in the refund request
        $validator->validateInput('create', $input);

        //
        // @todo: Commenting this block of code for now, in favor of the reverse_all
        // flag, will be added back in later - after discussions
        //
        // $reverseAll = $this->checkReversalsOnRefundType($payment, $input, $transfers);
        //
        // if ((isset($input['reversals']) === false) and
        //     ($reverseAll === true))
        // {
        //     $this->implicitAddReversalsForFullRefund($transfers, $input);
        // }

        $reverseAll = boolval($input['reverse_all'] ?? '0');

        if ($reverseAll === true)
        {

            $transfers = new Base\PublicCollection();

            $transfersFromPayment = (new Transfer\Core())->getForPayment($payment->getId());

                foreach ($transfersFromPayment as $transfer)
                {
                    if ($transfer->isFailed() === true and
                        $transfer->getAttempts() < Transfer\Constant::MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TRANSFER_IN_PROGRESS);
                    }

                    $transfers->push($transfer);
                }

                if ($payment->hasOrder() === true)
                {
                    $orderId = $payment->getApiOrderId();

                    $transfersFromOrder = (new Transfer\Core())->getForOrder($orderId);

                    foreach ($transfersFromOrder as $transfer)
                    {
                        if ($transfer->isFailed() === true and
                            $transfer->getAttempts() < Transfer\Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS)
                        {
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TRANSFER_IN_PROGRESS);
                        }

                        $transfers->push($transfer);
                    }
                }

            $refundType = $this->getPaymentRefundType($input, $payment);

            $validator->validateReverseAll($refundType, $transfers);

            $this->implicitAddReversalsForFullRefund($transfers, $input);
        }

        return $reverseAll;
    }

    /**
     * Based on the type of refund being processed, providing the
     * `reversals` array in input may be optional or mandatory. This
     * function validates the logic around this.
     *
     * @param  Payment\Entity   $payment
     * @param  array            $input
     * @param  PublicCollection $transfers
     *
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    protected function checkReversalsOnRefundType(Payment\Entity $payment, array $input, & $transfers) : bool
    {
        $refundType = $this->getPaymentRefundType($input, $payment);

        $reverseAll = false;

        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourceTypeAndIdAndMerchant($payment->getEntity(), $payment->getId(), $this->merchant);

        if ($refundType === Payment\RefundStatus::FULL)
        {
            $reverseAll = true;
        }
        else if ($refundType === Payment\RefundStatus::PARTIAL)
        {
            $transferCount = $transfers->count();

            if ($transferCount === 0)
            {
                throw new Exception\LogicException(
                    'Zero transfers found for reversal',
                    null,
                    [
                        'payment_id'    => $payment->getId(),
                        'refund_type'   => $refundType,
                    ]);
            }

            if ($transferCount > 1)
            {
                //
                // For partial refunds:
                // 1. `reversals` must be provided when there are multiple
                //     transfers created on the payment.
                // 2. This isn't required though, when all these transfers are fully
                //    reversed, during a previous partial refund on the payment
                //

                $allTransfersReversed = $this->checkIfAllTransfersReversed($transfers);

                if ($allTransfersReversed === false)
                {
                    (new Payment\Refund\Validator)->validateReversalsRequired($input);
                }
            }
            else if ($transferCount === 1)
            {
                //
                // When only a single transfer exists, we auto-reverse
                // the entire transfer amount
                //

                $reverseAll = true;
            }
        }
        else
        {
            throw new Exception\LogicException(
                'Payment transfer reversal - Invalid refund type',
                null,
                [
                    'refund_type'   => $refundType,
                    'payment_id'    => $payment->getId(),
                ]);
        }

        return $reverseAll;
    }

    /**
     * Returns true if all transfers passed to the function are fully reversed
     *
     * @param  Base\PublicCollection        $transfers
     * @return bool
     */
    protected function checkIfAllTransfersReversed(Base\PublicCollection $transfers) : bool
    {
        foreach ($transfers as $transfer)
        {
            $amountPendingToReverse = $transfer->getAmountUnreversed();

            if ($amountPendingToReverse !== 0)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * When reversals are to be processed but not provided in input, we
     * implicitly add reversals for the transfers corresponding to the payment
     * being refunded
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
                'transfer'  => $transfer,
                'amount'    => $amountToReverse,
            ];
        }

        $input['reversals'] = $reversals;
    }
}
