<?php

namespace RZP\Models\Settlement\OndemandPayout;
use App;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Services\FTS\Constants;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Settlement\Ondemand;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandPayoutReversal;

class Core extends Base\Core
{
    const MODE_BUFFER_TIME = 1800;

    const PAYOUT_VALID_STATUS_LIST = [
        'processing',
        'processed',
        'reversed',
    ];

    const MAX_IMPS_AMOUNT = FundTransfer\Base\Initiator\NodalAccount::MAX_IMPS_AMOUNT * 100;

    const PREVIOUS_MAX_IMPS_AMOUNT = 200000 * 100;

    const PAYOUT_PROCESSED_EVENT = 'payout.processed';

    const PAYOUT_REVERSED_EVENT = 'payout.reversed';

    const ADJUSTMENT = 'adjustment';

    const PAYOUT = 'payout';

    const MIN_SPLIT_AMOUNT = 10000;

    public function __construct()
    {
        parent::__construct();

        $this->user = $this->app['basicauth']->getUser();
    }

    public function createSettlementOndemandPayout($settlementOndemand, $requestDetails)
    {
        $mode = $this->setMode($settlementOndemand->getAmount(), $settlementOndemand->getScheduled());

        return $this->createPayoutsFromOndemand($settlementOndemand, $mode, $requestDetails);
    }

    public function setMode($amount, $scheduled = false)
    {
        if((new Ondemand\Service)->isMerchantWithXSettlementAccount($this->merchant->getId()))
        {
            return null;
        }
        else if ($scheduled == true && $amount > self::MAX_IMPS_AMOUNT)
        {
            return Mode::NEFT;
        }
        else
        {
            //Temporary fix - always returning IMPS to use PG ICIC nodal for X merchant
            return FundTransfer\Mode::IMPS;
        }

        //For ES_AUTOMATIC Merchants IMPS mode is used always regardless of banking or non-banking hour
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_AUTOMATIC) === true)
        {
            return FundTransfer\Mode::IMPS;
        }

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $isCurrentTimeOutsideBankingHours = $this->isOutsideBankingHours($currentTime);

        $time = $currentTime + self::MODE_BUFFER_TIME;

        $isOutsideBankingHours = $this->isOutsideBankingHours($time);

        if (($isOutsideBankingHours === true) or
            ($isCurrentTimeOutsideBankingHours === true))
        {
            return FundTransfer\Mode::IMPS;
        }
        else
        {
            return FundTransfer\Mode::NEFT;
        }
    }

    public function isOutsideBankingHoursWithBufferTime(): bool
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $isCurrentTimeOutsideBankingHours = $this->isOutsideBankingHours($currentTime);

        $time = $currentTime + self::MODE_BUFFER_TIME;

        $isOutsideBankingHours = $this->isOutsideBankingHours($time);

        return $isOutsideBankingHours or $isCurrentTimeOutsideBankingHours;
    }

    public function isOutsideBankingHours($time): bool
    {
        $date = Carbon::createFromTimestamp($time, Timezone::IST);

        $date->setTime(0,0);

        // Checks for holidays.
        if (Holidays::isWorkingDay($date) === false)
        {
            return true;
        }

        // Banking hours start time.
        $startTime = $date->hour(Constants::NEFT_CUTOFF_HOUR_MIN )->getTimestamp();

        // Banking hours end time.
        $endTime = $date->hour(Constants::NEFT_CUTOFF_HOUR_MAX)->minute(Constants::NEFT_CUTOFF_MINUTE_MAX)->getTimestamp();

        // Checks for non Banking hours on working days.
        if (($time < $startTime) or
            ($time > $endTime))
        {
            return true;
        }

        return false;
    }

    /**
     * @throws BadRequestException
     */
    public function createPayoutsFromOndemand($settlementOndemand, $mode, $requestDetails): array
    {
        $splitAmount = $this->splitAmountBasedOnMode($settlementOndemand->getAmount(), $mode);

        $settlementOndemandPayouts = [];

        foreach($splitAmount as $settlementOndemandPayoutAmount)
        {
            $data = [
                Entity::MERCHANT_ID            => $settlementOndemand->getMerchantId(),
                Entity::USER_ID                => $settlementOndemand->getUserId() ?? null,
                Entity::SETTLEMENT_ONDEMAND_ID => $settlementOndemand->getId(),
                Entity::MODE                   => $mode,
                Entity::AMOUNT                 => $settlementOndemandPayoutAmount,
                Entity::STATUS                 => Status::CREATED,
                Entity::UTR                    => null,
                Entity::INITIATED_AT           => null,
                Entity::PROCESSED_AT           => null,
                Entity::REVERSED_AT            => null,
            ];

            /** @var Entity $settlementOndemandPayout */
            $settlementOndemandPayout = (new Entity)->build($data);

            $settlementOndemandPayout->generateId();

            $settlementOndemandPayout->scheduled = $settlementOndemand->getScheduled();


            $settlementOndemandPayout->merchant()->associate($this->merchant);


            if (is_null($settlementOndemand->getUserId()) === false)
            {
                $settlementOndemand->user()->associate($this->user);
            }

            $isLinkedAccountSettlement = ($this->merchant->isFeatureEnabled(Feature\Constants::ONDEMAND_LINKED) && isset($requestDetails['settlement_type']) && $requestDetails['settlement_type'] === 'linked_account_settlement');
            $isPrepaidLinkedAccountSettlement = false;
            /** @var  $parentMerchant  MerchantEntity */
            if ($isLinkedAccountSettlement)
            {
                $parentMerchant = $this->repo->merchant->findOrFail($this->merchant->getParentId());
                $isPrepaidLinkedAccountSettlement = $parentMerchant->isFeatureEnabled(Feature\Constants::ONDEMAND_LINKED_PREPAID);
            }

            // Without ONDEMAND_LINKED_PREPAID feature flag, No fees will be deducted from child merchants settlements as by default Is for route is a postpaid model
            if (!$isLinkedAccountSettlement)
            {
                $this->addOndemandPayoutFees($settlementOndemandPayout);
            } else if ($isPrepaidLinkedAccountSettlement)
            {
                // Prepaid linked account settlements will be charged based on the parent merchant pricing plan.
                $settlementOndemandPayout->merchant()->associate($parentMerchant);
                $this->addOndemandPayoutFees($settlementOndemandPayout);
            }

            $this->repo->saveOrFail($settlementOndemandPayout);

            array_push($settlementOndemandPayouts, $settlementOndemandPayout);
        }

        return $settlementOndemandPayouts;
    }

    public function updateStatusAfterPayoutRequest($payoutStatus,
                                                   $payoutId,
                                                   Entity $settlementOndemandPayout,
                                                   $response)
    {
        $this->trace->info(TraceCode::UPDATE_SETTLEMENT_ONDEMAND_PAYOUT_STATUS,
                           ['payout_id' => $payoutId, 'status' => $payoutStatus]
        );

        if ((empty($payoutId) === true) or
            (in_array($payoutStatus, self::PAYOUT_VALID_STATUS_LIST, true) === false))
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_CREATION_FAILURE,
                null,
                [
                    'response'   => $response,
                ]);
        }
        else
        {
            $settlementOndemandPayout->setInitiatedAt(Carbon::now(Timezone::IST)->getTimestamp());

            $settlementOndemandPayout->setPayoutId($payoutId);

            $settlementOndemandPayout->setEntityType(self::PAYOUT);

            if ($payoutStatus === Status::PROCESSED)
            {
                return $this->handlePayoutProcessedEvent($settlementOndemandPayout, $response['utr']);
            }

            if ($payoutStatus === Status::REVERSED)
            {
                return (new Ondemand\Core)->createPartialReversal($settlementOndemandPayout, $response['failure_reason'] ?: 'failed');
            }

            $settlementOndemandPayout->setStatus(Status::INITIATED);

            $this->repo->saveOrFail($settlementOndemandPayout);
        }
    }

    public function initiateReversal($settlementOndemandPayoutId, $merchantId, $reversalReason)
    {
        CreateSettlementOndemandPayoutReversal::dispatch($this->mode,
                                                         $settlementOndemandPayoutId,
                                                         $merchantId,
                                                         $reversalReason);
    }

    public function updateOndemandPayoutStatus($event, $payoutData)
    {
        $response = [];

        $settlementOndemandPayout = (new Repository)->findbyIdAndPayoutIdWithLock($payoutData['reference_id'], $payoutData['id']);

        switch($event)
        {
            case self::PAYOUT_PROCESSED_EVENT:
                if ($settlementOndemandPayout->getStatus() === Status::PROCESSED)
                {
                    $response = ['response' => 'status already updated'];
                }
                else
                {
                    $this->handlePayoutProcessedEvent($settlementOndemandPayout, $payoutData['utr']);

                    $response = ['response' => 'status updated'];
                }
                break;

            case self::PAYOUT_REVERSED_EVENT:
                if ($settlementOndemandPayout->getStatus() === Status::REVERSED)
                {
                    $response = ['response' => 'status already updated'];
                }
                else
                {
                    $this->initiateReversal($settlementOndemandPayout->getId(),
                                            $settlementOndemandPayout->getMerchantId(),
                                            $payoutData['failure_reason']);

                    $response = ['response' => 'status updated'];
                }
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'not a valid ondemand_payout event');
        }
        return $response;
    }

    public function handlePayoutProcessedEvent($settlementOndemandPayout, $utr)
    {
        if ($settlementOndemandPayout->getStatus() === Status::PROCESSED)
        {
            return;
        }

        $this->repo->transaction(
            function() use ($settlementOndemandPayout, $utr)
            {
                $settlementOndemand = (new Ondemand\Repository)->findByIdAndMerchantIdWithLock(
                                                            $settlementOndemandPayout->getOndemandId(),
                                                            $settlementOndemandPayout->getMerchantId());

                $settlementOndemandPayout->setStatus(Status::PROCESSED);

                $settlementOndemandPayout->setUtr($utr);

                $settlementOndemandPayout->setProcessedAt(Carbon::now(Timezone::IST)->getTimestamp());

                $this->repo->saveOrFail($settlementOndemandPayout);

                (new Ondemand\Core)->handleOndemandPayoutProcessed($settlementOndemand, $settlementOndemandPayout);
            });
    }

    public function splitAmountBasedOnMode($amount, $mode)
    {
        $totalAmountRemaining = $amount;

        $splitAmount = [];

        switch($mode)
        {
            case FundTransfer\Mode::IMPS:

                $maxIMPSAmount = self::MAX_IMPS_AMOUNT;

                while($totalAmountRemaining > 0)
                {
                    if ($totalAmountRemaining > $maxIMPSAmount)
                    {
                        $totalAmountRemaining -= $maxIMPSAmount;

                        $payoutAmount = $maxIMPSAmount;

                        if(($totalAmountRemaining < self::MIN_SPLIT_AMOUNT) and ($totalAmountRemaining > 0))
                        {
                            $payoutAmount -= self::MIN_SPLIT_AMOUNT;

                            $totalAmountRemaining += self::MIN_SPLIT_AMOUNT;
                        }

                        array_push($splitAmount , $payoutAmount);
                    }
                    else
                    {
                        array_push($splitAmount , $totalAmountRemaining);

                        $totalAmountRemaining = 0;
                    }
                }

                break;

            case null:
            case FundTransfer\Mode::NEFT:
                array_push($splitAmount , $totalAmountRemaining);

                $totalAmountRemaining = 0;

                break;

            default:
                return '';
        }
        return $splitAmount;
    }

    public function makePayoutRequest($settlementOndemandPayoutId, $currency)
    {
        $settlementOndemandPayout = (new Repository)->findOrFail($settlementOndemandPayoutId);

        $payoutAmount = $settlementOndemandPayout->getPayoutAmount();

        $fundAccount = (new OndemandFundAccount\Repository)->findByMerchantId($settlementOndemandPayout->getMerchantId());

        $fundAccountId = $fundAccount[OndemandFundAccount\Entity::FUND_ACCOUNT_ID];

        $data = [
            'amount'           => $payoutAmount,
            'fund_account_id'  => $fundAccountId,
            'currency'         => $currency,
            'mode'             => $settlementOndemandPayout[Entity::MODE],
            'reference_id'     => $settlementOndemandPayout->getId(),
        ];

        $response = $this->app->razorpayXClient->makePayoutRequest($data, $settlementOndemandPayoutId, false);

        $status = $response['status'] ?: null;

        $payoutId = $response['id'] ?: null;

        return [$status, $payoutId, $response];
    }

    public function addOndemandPayoutFees($settlementOndemandPayout)
    {
        [$fees, $tax, $feesSplit] = (new Pricing\Fee)->calculateMerchantFees($settlementOndemandPayout);

        $settlementOndemandPayout->setFees($fees);

        $settlementOndemandPayout->setTax($tax);

        $payoutAmount = $settlementOndemandPayout->getAmount() - ($fees);

        if ($payoutAmount < 100)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
                null,
                [
                    'amount' => $payoutAmount
                ]);
        }

        (new Repository)->saveOrFail($settlementOndemandPayout);

        return $feesSplit;
    }

    public function setAdjustmentId($settlementOndemandPayouts, $adjId)
    {
        foreach ($settlementOndemandPayouts as $settlementOndemandPayout)
        {
            $settlementOndemandPayout->setPayoutId($adjId);

            $settlementOndemandPayout->setEntityType(self::ADJUSTMENT);

            $settlementOndemandPayout->setStatus(Status::PROCESSED);

            $this->repo->saveOrFail($settlementOndemandPayout);
        }
    }
}
