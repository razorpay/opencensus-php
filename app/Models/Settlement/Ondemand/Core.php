<?php

namespace RZP\Models\Settlement\Ondemand;

use App;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Pricing;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\OndemandPayout;

class Core extends Base\Core
{
    public function createSettlementOndemand(array $input, Merchant\Entity $merchant, User\Entity $user = null)
    {
        if (isset($input['settle_full_balance']) === true && boolval($input['settle_full_balance']) === true)
        {
            $amount = $merchant->primaryBalance->getBalance();

            $input[Entity::AMOUNT] = $amount;

        }
        else
        {
            $amount = $input[Entity::AMOUNT];

            if ($amount > $merchant->primaryBalance->getBalance())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
                    null,
                    [
                        'amount'  => $amount,
                        'balance' => $merchant->primaryBalance->getBalance(),
                    ]);
            }
        }

        $this->checkMerchantFundsOnHold();

        $input = $input + [
            Entity::TOTAL_AMOUNT_SETTLED  => 0,
            Entity::TOTAL_AMOUNT_REVERSED => 0,
            Entity::STATUS                => Status::CREATED,
            Entity::CURRENCY              => $input[Entity::CURRENCY] ?? Currency::INR,
            Entity::MAX_BALANCE           => $input['settle_full_balance'] ?? 0,
            Entity::NOTES                 => $input[Entity::NOTES] ?? null,
            Entity::NARRATION             => $input['description'] ?? null,
        ];


        $data = $input;

        unset($data['expand']);
        unset($data['settle_full_balance']);
        unset($data['description']);

        /** @var Entity $settlementOndemand */
        $settlementOndemand = (new Entity)->build($data);

        $settlementOndemand->generateId();

        $settlementOndemand->merchant()->associate($merchant);

        if (isset($user) === true)
        {
            $settlementOndemand->user()->associate($user);
        }

        $settlementOndemandPayouts = (new OndemandPayout\Service)
                                        ->createSettlementOndemandPayout($settlementOndemand);

        $txn = $this->createTransaction($settlementOndemand);

        $settlementOndemand->setTotalAmountPending($settlementOndemand->getAmountToBeSettled());

        $this->repo->saveOrFail($settlementOndemand);

        return [$settlementOndemand, $settlementOndemandPayouts, $txn];
    }

    public function createTransaction($settlementOndemand)
    {
        [$txn, $feeSplit] = (new Transaction\Processor\SettlementOndemand($settlementOndemand))
                                    ->createTransaction();

        $settlementOndemand->setFees($txn->getFee());

        $settlementOndemand->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);

        return $txn;
    }

    public function getFeesSplit($input, $merchant , $user)
    {
        return $this->repo->beginTransactionAndRollback(function () use ($input, $merchant , $user)
        {
            [$settlementOndemand, $settlementOndemandPayouts] = $this->createSettlementOndemand($input, $merchant , $user);

            [$fees, $tax, $feesSplit] = (new Pricing\Fee)->calculateMerchantFees($settlementOndemandPayouts[0]);

            $feesSplit = $feesSplit->toArrayPublic();

            $feesSplit['items'][0]['amount'] = $settlementOndemand->getTotalFees() - $settlementOndemand->getTotalTax();

            $feesSplit['items'][1]['amount'] = $settlementOndemand->getTotalTax();

            return $feesSplit;
        });
    }

    public function createPartialReversal($settlementOndemandPayout, $reversalReason)
    {
        if ($settlementOndemandPayout->getStatus() === OndemandPayout\Status::REVERSED)
        {
            throw new Exception\LogicException(
                'This payout is already reversed',
                ErrorCode::BAD_REQUEST_ONDEMAND_PAYOUT_REVERSAL_FAILURE,
                [
                    'settlement_ondemand_id'          => $settlementOndemandPayout->getOndemandId(),
                    'settlement_ondemand_payout_id'   => $settlementOndemandPayout->getId(),
                ]);
        }

        $this->repo->transaction(
            function() use ($settlementOndemandPayout, $reversalReason)
            {
                /** @var Entity $settlementOndemand */
                $settlementOndemand = (new Repository)->findByIdAndMerchantIdWithLock(
                                            $settlementOndemandPayout->getOndemandId(),
                                            $settlementOndemandPayout->getMerchantId());

                (new Reversal\Core)->partialReversalForSettlementOndemand($settlementOndemand, $settlementOndemandPayout);

                $settlementOndemandPayout = $this->updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, $reversalReason);

                $this->updateOndemandOnPayoutReversal($settlementOndemand, $settlementOndemandPayout);
            });
    }

    public function handleOndemandPayoutProcessed($settlementOndemand, $settlementOndemandPayout)
    {
        $settlementOndemand->deductFromTotalAmountPending($settlementOndemandPayout->getAmountToBeSettled());

        $settlementOndemand->addToTotalAmountSettled($settlementOndemandPayout->getAmountToBeSettled());

        if ($settlementOndemand->getTotalAmountPending() === 0)
        {
            if ($settlementOndemand->getTotalAmountReversed() === 0)
            {
                $settlementOndemand->setStatus(Status::PROCESSED);
            }
            else
            {
                $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
            }
        }
        else
        {
            $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
        }

        $this->repo->saveOrFail($settlementOndemand);
    }

    public function updateOndemandOnPayoutReversal($settlementOndemand, $settlementOndemandPayout)
    {
        $settlementOndemand->addToTotalAmountReversed($settlementOndemandPayout->getAmount());

        //this is in the case RazorpayX send the status as reversed after it have already sent processed status
        if (is_null($settlementOndemandPayout->getProcessedAt()) === true)
        {
            $settlementOndemand->deductFromTotalAmountPending($settlementOndemandPayout->getAmountToBeSettled());
        }
        else
        {
            $settlementOndemand->deductFromTotalAmountSettled($settlementOndemandPayout->getAmountToBeSettled());
        }

        if ($settlementOndemand->getTotalAmountReversed() === $settlementOndemand->getAmount())
        {
            $settlementOndemand->setStatus(Status::REVERSED);
        }
        else if ($settlementOndemand->getTotalAmountSettled() > 0)
        {
            $settlementOndemand->setStatus(Status::PARTIALLY_PROCESSED);
        }
        else
        {
            $settlementOndemand->setStatus(Status::INITIATED);
        }

        $settlementOndemand->deductFromTotalTax($settlementOndemandPayout->getTax());

        $settlementOndemand->deductFromTotalFees($settlementOndemandPayout->getFees());

        $this->repo->saveOrFail($settlementOndemand);

        return $settlementOndemand;
    }

    public function updateOndemandPayoutOnPayoutReversal($settlementOndemandPayout, $reversalReason)
    {
        $settlementOndemandPayout->setFailureReason($reversalReason);

        $settlementOndemandPayout->setStatus(Status::REVERSED);

        $settlementOndemandPayout->setReversedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($settlementOndemandPayout);

        return $settlementOndemandPayout;
    }

    public function checkMerchantFundsOnHold()
    {
        if ($this->merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }
    }
}
