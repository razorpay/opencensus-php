<?php

namespace RZP\Models\Adjustment;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Models\Settlement\Channel as BankingChannel;

class Core extends Base\Core
{
    // input param for adjustment creation on capital collection balances.
    const BALANCE_ID = 'balance_id';

    public function createAdjustment(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        // Create input for adjustment
        $adjInput = $input;

        // Create input for Merchant Invoice
        $merchantInvoiceInput = $input;

        // Checking validations on input array
        (new Validator)->validateAdjustmentCreateInput($input);

        $amount = $input[Entity::AMOUNT] ?? 0;

        $tax =  $input[MerchantInvoice\Entity::TAX] ?? 0;

        $fees = $input[Entity::FEES] ?? 0;

        $balanceType = $input[Entity::TYPE] ?? Balance\Type::PRIMARY;

        $adjInput[Entity::AMOUNT] = $amount + $tax + $fees;

        unset($adjInput[Entity::FEES]);

        unset($adjInput[MerchantInvoice\Entity::TAX]);

        unset($adjInput[Entity::TYPE]);

        $sendReserveBalanceMail = false;

        if (empty($input[self::BALANCE_ID]) === false)
        {
            // used for capital collections transactions.
            // capital-collections uses balance_id for reference, not balance type
            // as there can be multiple balances of same type on same merchant id:
            // like multiple principal balances if merchant has multiple loc withdrawals.

            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findByIdAndMerchant($input[self::BALANCE_ID], $merchant);

            // check balance is of type: principal, charge, interest
            if (in_array($balance->getType(), Balance\Type::$capitalBalances, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException('invalid capital collections balance: '.
                    $balance->getType(), self::BALANCE_ID, $balance->toArrayPublic());
            }

            unset($adjInput[self::BALANCE_ID]);
        }
        else if (($balanceType === Balance\Type::RESERVE_BANKING) or
            ($balanceType === Balance\Type::RESERVE_PRIMARY))
        {
            [$balance, $sendReserveBalanceMail] = (new Balance\Core)->createOrFetchReserveBalance($merchant,
                                                                        $balanceType, $this->mode);

        }
        else
        {
            $balance = $merchant->getBalanceByTypeOrFail($balanceType);
        }

        $adj = (new Adjustment\Entity)->build($adjInput);

        $adj->balance()->associate($balance);

        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        /** @var Entity|null $adjustment */
        $adjustment = null;

        if (isset($input[Entity::AMOUNT]) === true)
        {
            // Creating adjustment only, since no invoice record is reqd
            $adjustment = $this->transaction([$this, 'createAdjInTransaction'], $adj, $merchant);

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,
                [
                    'adjustment transaction'  => $adjustment,
                    'balance_type'             => $balanceType,
                    'merchant_id'              => $merchant->getMerchantId()
                ]
            );
        }
        else
        {
            // Creating merchant invoice entries too along with adj
            // because of adjustment entries (fees and tax)
            $merchantInvoiceInput[MerchantInvoice\Entity::TAX] = $tax;

            $merchantInvoiceInput[MerchantInvoice\Entity::AMOUNT] = $fees;

            unset($merchantInvoiceInput['fees']);

            $adjustment = $this->repo->transaction(
                function () use ($adj, $merchant, $merchantInvoiceInput)
                {
                    // 1. Create adjustment
                    // 2. Create Invoice entity for adjustment
                    $adjustment = $this->createAdjInTransaction($adj, $merchant);

                    (new Merchant\Invoice\Core)->createAdjustmentInvoiceEntity($adj, $merchantInvoiceInput);

                    $this->processLedgerAdjustment($adjustment);

                    return $adjustment;
                }
            );
        }

        if ($sendReserveBalanceMail === true)
        {
            (new Balance\NegativeReserveBalanceMailers())->sendReserveBalanceActivatedMail($merchant, $balance);
        }

        $this->processLedgerAdjustment($adjustment);

        return $adjustment;
    }

    public function createAdjustmentForSource(array $input, Base\PublicEntity $source): Entity
    {
        $traceCode = Constants::getAdjustmentCreateRequestTraceCode($source->getEntityName());

        $this->trace->info(
            $traceCode,
            [
                'input'       => $input,
                'merchant_id' => $source->getMerchantId()
            ]);

        (new Validator)->validateMerchantBalance($source->merchant, $source, $input);

        $adjustment = $this->createAdjustment($input, $source->merchant);

        $adjustment->entity()->associate($source);

        $this->repo->saveOrFail($adjustment);

        return $adjustment;
    }

    public function splitAdjustments(array $adjustment): array
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_SPLIT_REQUEST,
            [
                'input' => $adjustment
            ]);

        (new Validator)->validateInput('split_adjustment', $adjustment);

        $adj = $this->repo->adjustment->findOrFail($adjustment[Entity::ID]);

        $count = 1;

        list($valid, $data) = $this->verifyAmountsToSplit($adj, $adjustment[Dispute\Entity::PAYMENT_ID]);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException('Amounts do not seem to add up');
        }
        try
        {
            $this->repo->transaction(function () use ($data, $count, $adj)
            {
                $originalAmount = $adj->getAmount();

                $txn = $this->repo->transaction->findOrFail($adj->getTransactionId());

                $setlDetails = $this->repo->settlement_details->fetch([
                    Settlement\Details\Entity::SETTLEMENT_ID => $txn->getSettlementId(),
                    Settlement\Details\Entity::COMPONENT     => $txn->getType()
                ])->first();

                $setlDetails->update([Settlement\Details\Entity::COUNT => count($data) + $setlDetails->getCount() - 1]);

                $balance = 0;

                $adjId = $adj->getId();

                $txnId = $txn->getId();

                foreach ($data as $id => $amount)
                {
                    if ($count === 1)
                    {
                        $adj->update([Entity::AMOUNT => 0 - $amount]);

                        $this->updateTransactionAmountAndBalance($txn, $amount, $originalAmount);

                        $balance = $txn->getBalance();

                        $count++;
                    }
                    else
                    {
                        $newAdjId = $this->getNextId($adjId);

                        $newAdj = $this->insertSplitAdjustment($newAdjId, $amount, $adjId);

                        $adjId = $newAdjId;

                        $newTxnId = $this->getNextId($txnId);

                        $newTxn = $this->insertSplitTransaction($newTxnId, $newAdj, $amount, $balance, $txn);

                        $txnId = $newTxnId;

                        $balance -= $amount;

                        $newAdj->transaction()->associate($newTxn);

                        $this->repo->adjustment->saveOrFail($newAdj);
                    }
                }
            });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'          => $adjustment[Entity::ID],
                    'payment_ids' => $adjustment[Dispute\Entity::PAYMENT_ID]
                ]);
        }

        return ['success' => true];
    }

    protected function verifyAmountsToSplit(Entity $adjustment, string $paymentIds): array
    {
        $paymentIds = explode(',', $paymentIds);

        $data = [];

        $amount = 0;

        $adjId = $adjustment->getId();

        if (($adjustment->getAmount() >= 0) === true)
        {
            $this->trace->info(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'     => $adjId,
                    'reason' => 'Positive amount not expected'
                ]);

            return [false, []];
        }

        foreach ($paymentIds as $paymentId)
        {
            $paymentId = trim($paymentId);

            $payment = $this->repo->payment->findByPublicId($paymentId);

            $amount += $payment[Payment\Entity::AMOUNT];

            $data[$paymentId] = $payment[Payment\Entity::AMOUNT];
        }

        if ($amount !== (int) abs($adjustment->getAmount()))
        {
            $this->trace->debug(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id' => $adjId,
                ]);

            return [false,[]];
        }

        return [true, $data];
    }

    protected function createAdjInTransaction($adj, $merchant): Entity
    {
        $this->repo->assertTransactionActive();

        // set channel if not set already from input
        if ($adj->getChannel() === null)
        {
            if ($adj->isBalanceTypeBanking() === true)
            {
                // TODO : Remove second condition later
                $channel = $adj->balance->getChannel() ?? BankingChannel::YESBANK;

                $adj->setChannel($channel);
            }
            else if ($adj->isBalanceTypeCommission() === true)
            {
                // commission adjustments to be made from yes_bank channel
                $adj->setChannel(BankingChannel::YESBANK);
            }
            else
            {
                $adj->setChannel($merchant->getChannel());
            }
        }

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($adj);

        (new Transaction\Core)->dispatchEventForTransactionCreated($txn);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }

    /**
     * @param Transaction\Entity $txn
     * @param int                $amount
     * @param int                $originalAmount
     *
     * DO NOT USE THIS FUNCTION CASUALLY, THIS IS
     * WRITTEN FOR THE ABOVE SPLIT MIGRATION BUT SHOULD
     * NOT BE NEEDED IN GENERAL.
     */
    protected function updateTransactionAmountAndBalance(
        Transaction\Entity $txn,
        int $amount,
        int $originalAmount)
    {
        $newBalance = $txn->getBalance() + abs($originalAmount) - $amount;

        $txn->update([
            Transaction\Entity::AMOUNT  => abs($amount),
            Transaction\Entity::DEBIT   => abs($amount),
            Transaction\Entity::BALANCE => $newBalance
        ]);

        $this->repo->saveOrFail($txn);
    }

    protected function insertSplitAdjustment(string $newAdjId, int $amount, string $adjId): Entity
    {
        $adj = $this->repo->adjustment->findOrFail($adjId);

        $newAdj = $adj->replicate();

        $newAdj->setAmount(0 - $amount);

        $newAdj->setCreatedAt($adj->getCreatedAt());

        $newAdj->setUpdatedAt($adj->getUpdatedAt());

        $newAdj->setId($newAdjId);

        $newAdj->saveOrFail();

        return $newAdj;
    }

    protected function insertSplitTransaction(
        string $newTxnId,
        Entity $newAdj,
        int $amount,
        int $balance,
        Transaction\Entity $txn): Transaction\Entity
    {
        $newTxn = $txn->replicate();

        $newTxn->setCreatedAt($txn->getCreatedAt());

        $newTxn->setUpdatedAt($txn->getUpdatedAt());

        $newTxn->setAmount(abs($amount));

        $newTxn->setDebit(abs($amount));

        $newTxn->setBalance($balance - $amount);

        $newTxn->setId($newTxnId);

        $newTxn->source()->associate($newAdj);

        $newTxn->saveOrFail();

        return $newTxn;
    }

    /**
     * @param  string $oldId
     *
     * @return string $newId
     *
     * This function does not cover a lot of corner cases
     * as the current data does not need them. Those checks
     * should be added if ever needed.
     */
    protected function getNextId(string $oldId)
    {
        $last = substr($oldId, -1, 1);

        $newId = substr_replace($oldId, ++$last, -1, 1);

        return $newId;
    }

    protected function processLedgerAdjustment(Entity $adjustment)
    {
        // In case env variable ledger.enabled is false, return.
        // We shall also skip the ledger creation
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($adjustment->balance->isTypeBanking() === false))
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($adjustment->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        $event = self::getLedgerEventBasedOnAdjustment($adjustment);

        (new Ledger\Adjustment)->pushTransactionToLedger($adjustment, $event);
    }

    protected function getLedgerEventBasedOnAdjustment(Entity $adjustment)
    {
        if ($adjustment->getAmount() >= 0)
        {
            return Ledger\Adjustment::POSITIVE_ADJUSTMENT_PROCESSED;
        }
        else
        {
            return Ledger\Adjustment::NEGATIVE_ADJUSTMENT_PROCESSED;
        }
    }

    public function createAdjustmentForSubBankingBalance(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST_FOR_SUB_BALANCES,
            [
                'input'    => $input,
                'merchant' => $merchant->getId()
            ]);

        // Create input for adjustment
        $adjInput = $input;

        // Checking validations on input array
        (new Validator)->validateInput(Validator::SUB_BANKING_BALANCE_ADJUSTMENT_CREATE, $input);

        /** @var Balance\Entity $balance */
        $balance = $this->repo->balance->findByIdAndMerchant($input[self::BALANCE_ID], $merchant);

        $balanceType = $balance->getType();

        // check balance is of type: principal, charge, interest
        if ($balanceType !== Balance\Type::BANKING)
        {
            throw new Exception\BadRequestValidationFailureException('invalid balance type: '.
                                                                     $balance->getType(), self::BALANCE_ID, $balance->toArrayPublic());
        }

        unset($adjInput[self::BALANCE_ID]);

        $adj = (new Adjustment\Entity)->build($adjInput);

        $adj->balance()->associate($balance);

        /** @var Entity|null $adjustment */
        $adjustment = null;

        if (isset($input[Entity::AMOUNT]) === true)
        {
            // Creating adjustment only, since no invoice record is reqd
            $adjustment = $this->createAdjInTransactionWithoutNotification($adj, $merchant);

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,
                               [
                                   'adjustment transaction'  => $adjustment,
                                   'balance_type'             => $balanceType,
                                   'merchant_id'              => $merchant->getMerchantId()
                               ]
            );
        }

        $this->processLedgerAdjustment($adjustment);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_RESPONSE_SUB_BALANCE,
            [
                'input'      => $input,
                'merchant'   => $merchant->getId(),
                'adjustment' => $adjustment->toArrayPublic(),
            ]);

        return $adjustment;
    }

    public function subBalanceAdjustment(array $input, Merchant\Entity $merchant)
    {
        try
        {
            $sourceAdInput = $input;

            $sourceAdInput[Entity::BALANCE_ID] = $sourceAdInput[Entity::SOURCE_BALANCE_ID];

            $sourceAdInput[Entity::AMOUNT] = -1 * $sourceAdInput[Entity::AMOUNT];

            unset($sourceAdInput[Entity::SOURCE_BALANCE_ID]);
            unset($sourceAdInput[Entity::DESTINATION_BALANCE_ID]);

            $destinationAdjustmentInput = $input;

            $destinationAdjustmentInput[Entity::BALANCE_ID] = $destinationAdjustmentInput[Entity::DESTINATION_BALANCE_ID];

            unset($destinationAdjustmentInput[Entity::SOURCE_BALANCE_ID]);
            unset($destinationAdjustmentInput[Entity::DESTINATION_BALANCE_ID]);

            [$sourceAdjustment, $destinationAdjustment] = $this->repo->transaction(function() use ($sourceAdInput, $destinationAdjustmentInput, $merchant) {

                $sourceAdjustment = $this->createAdjustmentForSubBankingBalance($sourceAdInput, $merchant);

                $this->trace->info(
                    TraceCode::ADJUSTMENT_CREATED_FOR_SOURCE_BALANCE_ID,
                    [
                        'source_adjustment_input' => $sourceAdInput,
                        'merchant'                => $merchant->getId(),
                        'source_adjustment'       => $sourceAdjustment->toArrayPublic(),
                    ]);

                $destinationAdjustment = $this->createAdjustmentForSubBankingBalance($destinationAdjustmentInput, $merchant);

                return [$sourceAdjustment, $destinationAdjustment];
            });
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }

        $this->trace->info(
            TraceCode::ADJUSTMENT_BETWEEN_BALANCE_CREATE_RESPONSE,
            [
                'input'                  => $input,
                'merchant'               => $merchant->getId(),
                'source_adjustment'      => $sourceAdjustment->toArrayPublic(),
                'destination_adjustment' => $destinationAdjustment->toArrayPublic()
            ]);

        return [
            'source_adjustment'      => $sourceAdjustment->toArrayPublic(),
            'destination_adjustment' => $destinationAdjustment->toArrayPublic()
        ];
    }

    protected function createAdjInTransactionWithoutNotification($adj, $merchant): Entity
    {
        $this->repo->assertTransactionActive();

        // set channel if not set already from input
        if ($adj->getChannel() === null)
        {
            if ($adj->isBalanceTypeBanking() === true)
            {
                // TODO : Remove second condition later
                $channel = $adj->balance->getChannel() ?? BankingChannel::YESBANK;

                $adj->setChannel($channel);
            }
            else
            {
                $adj->setChannel($merchant->getChannel());
            }
        }

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($adj);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }
}
