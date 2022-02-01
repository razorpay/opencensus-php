<?php

namespace RZP\Models\Settlement;

use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Balance;
use RZP\Models\Partner\Activation;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Settlement\Bucket\Constants;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\FundTransfer\Attempt\Purpose;
use RZP\Models\Settlement\Merchant as SetlMerchant;
use RZP\Constants\SettlementChannelMedium as Medium;
use RZP\Models\Settlement\Details\Component as SetlComponent;

trait SettlementTrait
{
    protected function traceMerchantSettlementSkip(Merchant\Entity $merchant, array $data)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_SKIPPED,
            [
                'merchant_id'    => $merchant->getId(),
            ] + $data
        );

        $customProperties = [
            'merchant_id'    => $merchant->getId(),
            'channel'        => $merchant->getChannel(),
        ] + $data;

        $this->app['diag']->trackSettlementEvent(
            EventCode::SETTLEMENT_CREATION_SKIPPED,
            null,
            null,
            $customProperties);
    }

    /**
     * validates if merchant is eligible for settlement
     *
     * @param Merchant\Entity $merchant
     * @param string $balanceType
     * @param bool $forceFlag
     * @return array
     */
    public function isMerchantSettlementAllowed(Merchant\Entity $merchant, string $balanceType, $forceFlag = false): array
    {
        // 1. Do not proceed further if the merchant account is suspended
        // 2. For non-active merchants check if the settlement is not for partner commissions
        if (($merchant->isSuspended() === true) || (($merchant->isActivated() === false) and
            ($this->isPartnerCommissionSettlement($merchant, $balanceType) === false)))
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'merchant is not active',
                ]);

            return [
                false,
                [
                    'caption' => 'Settlement is not enabled',
                    'reason'  => 'Only active merchants can get settlements',
                    'on_hold' => false,
                ]
            ];
        }

        // Do not proceed further if merchant funds are on hold
        // TODO: hold_funds to be checked on partner entity for commission settlement after partner service migration
        if ($merchant->isFundsOnHold() === true)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'merchant funds are on hold',
                ]);

            return [
                false,
                [
                    'caption' => 'Settlements are on hold',
                    'reason'  => $merchant->getHoldFundsReason(),
                    'on_hold' => true,
                ]
            ];
        }

        // If it's a partner commission settlement than proceed further only if partner is activated
        if ($this->isPartnerCommissionSettlement($merchant, $balanceType) === true)
        {
            list ($status, $data, $stlSkipReason) = $this->isPartnerCommissionSettlementAllowed($merchant, $balanceType);

            if ($status === false)
            {
                $this->traceMerchantSettlementSkip(
                    $merchant,
                    [
                        'reason' => $stlSkipReason,
                    ]);

                return [$status, $data];
            }
        }

        $merchantSettleToPartner = (new Merchant\Core)->getPartnerBankAccountIdsForSubmerchants([$merchant->getId()]);

        if ($this->skipSpecificMerchants($merchant) === true)
        {
            return [
                false,
                [
                    'caption' => 'Settlement will be skipped',
                    'reason'  => 'Settlements skipped based on merchant preference',
                    'on_hold' => false,
                ]
            ];
        }

        $destinationMerchantId = $this->settlementToPartner($merchant->getId());

        $isAggregateSettlement = (bool) $destinationMerchantId;

        if($isAggregateSettlement === true)
        {
            return [true, []];
        }

        if (isset($merchantSettleToPartner[$merchant->getId()]) === true)
        {
            $bankAccountId = $merchantSettleToPartner[$merchant->getId()];

            $bankAccount = $this->repo->bank_account->getBankAccountById($bankAccountId);
        }
        else
        {
            $bankAccount = $merchant->bankAccount;
        }

        // Do not proceed if merchant does not have active bank account and the merchant is not settling to the partner.
        if ($bankAccount === null)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'merchant doesnt have a active bank account registered',
                ]);

            return [
                false,
                [
                    'caption' => 'Settlement will be skipped',
                    'reason'  => 'Merchant doesnt have a active bank account registered',
                    'on_hold' => false,
                ]
            ];
        }

        $channel = $merchant->getChannel();

        $allowedChannelFor24x7Settlement = Channel::get24x7Channels();

        //
        // Allow settlement to create of the channel allows 24/7 functionality
        // bene registration check is not required
        //
        if (($this->env !== Environment::TESTING) and
            (in_array($channel, $allowedChannelFor24x7Settlement, true) === true))
        {
            return [true, []];
        }

        if($forceFlag === true)
        {
            return [true, []];
        }

        $today = Carbon::today(Timezone::IST);

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $cutoffConfig = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::REMOVE_SETTLEMENT_BA_COOL_OFF]);

        $coolOff = (bool) ($cutoffConfig ?? false);

        $accountChange = $this->repo->bank_account->isBankAccountChanged($bankAccount);

        if (($coolOff === true) and ($accountChange === true) and ($this->env !== Environment::TESTING))
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_NOT_SKIPPING_BANK_ACCOUNT_RECENT_CREATION,
                [
                    'bank_account_created_at'   => $bankAccount->getCreatedAt(),
                    'last_working_day'          => $lastWorkingDay->getTimestamp(),
                    'channel'                   => $channel,
                    'merchant_id'               => $merchant->getId(),
                    'bank_account_id'           => $bankAccount->getId(),
                ]);

            return [true, []];
        }

        //
        // Check if beneficiary registration cutoff is crossed
        // required for all non 24/7 channels
        //
        if (($this->env !== Environment::TESTING) and
            ($bankAccount->getCreatedAt() > $lastWorkingDay->getTimestamp()))
        {

            $createdAt = Carbon::createFromTimestamp($bankAccount->getCreatedAt(), Timezone::IST)->format('Y-m-d H:i:s');

                $this->traceMerchantSettlementSkip(
                    $merchant,
                    [
                        'reason'               => 'bank account created yesterday',
                        'bank_account_created' => $createdAt,
                    ]);

                return [
                    false,
                    [
                        'caption' => 'There won\'t be any settlement',
                        'reason'  => 'Bank account created yesterday. bank account was created/updated at '
                            . $createdAt
                            . '. It would require a day (except bank holidays)'
                            . ' to register the same with our banking partners',
                        'on_hold' => false,
                    ]
                ];

        }

        return [true, []];
    }

    /**
     * validates if a partner merchant is eligible for commission settlement
     *
     * @param Merchant\Entity $merchant
     * @param string $balanceType
     * @return array
     */
    // TODO: should also check hold_funds on partner entity for commission settlement after partner service migration
    protected function isPartnerCommissionSettlementAllowed(Merchant\Entity $merchant, string $balanceType)
    {
        if ($this->isPartnerCommissionSettlement($merchant, $balanceType) === true)
        {
            $partnerActivation = (new Activation\Core())->createOrFetchPartnerActivationForMerchant($merchant, false);

            if ($partnerActivation->getActivationStatus() !== Activation\Constants::ACTIVATED)
            {
                return [
                    false,
                    [
                        'caption' => 'Settlement is not enabled',
                        'reason'  => 'Only active partner merchant`s commissions are settled',
                        'on_hold' => $partnerActivation->isFundsOnHold(),
                    ],
                    'partner merchant is not active'
                ];
            }
            else
            {
                return [true, [], ''];
            }
        }

        return [false, ['reason' => 'Settlement is not of type partner commissions'], ''];
    }

    /**
     * Validates if the merchant is a partner and it's a commission settlement
     *
     * @param Merchant\Entity $merchant
     * @param string $balanceType
     * @return bool
     */
    protected function isPartnerCommissionSettlement(Merchant\Entity $merchant, string $balanceType)
    {
        if (($merchant->isPartner() === true) and ($balanceType === Transaction\Type::COMMISSION))
        {
            return true;
        }

        return false;
    }

    /**
     * Few merchant doesnt want settlement
     * so skip such merchants, mostly there are route merchants
     * there are 3 type of merchants defined which has to be skipped
     * 1. WEALTHY merchant on saturday
     * 2. MIDs is in NO_SETTLEMENT_MIDS
     * 3. If the merchant has feature block_settlements/daily_settlement
     *
     * @param Merchant\Entity $merchant
     * @return bool
     */
    protected function skipSpecificMerchants(Merchant\Entity $merchant): bool
    {
        $today = Carbon::today(Timezone::IST);

        // redundant check to ensure this does not happen while creating the settlement
        if (($merchant->getParentId() === Preferences::MID_WEALTHY) and
            ($today->dayOfWeek === Carbon::SATURDAY))
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => Metric::BLOCK_WEALTHY_ON_SATURDAY,
                ]);

            return true;
        }

        if (in_array($merchant->getId(), Preferences::NO_SETTLEMENT_MIDS, true) === true)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'parent merchant opted for no settlement',
                ]);

            return true;
        }

        // MIDs that have the block_settlements/daily_settlement feature enabled
        $skipSetlFeatureEnabled = $this->repo
                                       ->feature
                                       ->findMerchantWithFeatures(
                                           $merchant->getId(),
                                           [
                                               Feature\Constants::BLOCK_SETTLEMENTS
                                           ]);

        if ($skipSetlFeatureEnabled->isNotEmpty() === true)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'merchant has block_settlement feature',
                ]);

            return true;
        }

        return false;
    }

    /**
     * @param $txns
     * @param $merchantSettleToPartner
     *
     * @return array
     * Returns array keyed by merchant id, and
     * the values are the filtered transactions for that merchant
     */
    protected function filterTransactionsForSettlement($txns, $merchantSettleToPartner): array
    {
        $filterGroupedTxns       = [];

        $transactionSkipCount    = 0;

        $transactionsSettleCount = 0;

        $esMerchants = $this->repo->feature
                            ->findMerchantsHavingFeatures([Feature\Constants::ES_AUTOMATIC])
                            ->pluck(Feature\Entity::ENTITY_ID)
                            ->toArray();

        $esMerchantsThreePm = $this->repo->feature
                                  ->findMerchantsHavingFeatures([Feature\Constants::ES_AUTOMATIC_THREE_PM])
                                  ->pluck(Feature\Entity::ENTITY_ID)
                                  ->toArray();

        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENTS_TXNS_GROUP_BY_MERCHANT_START);

         // Here we are fetch each transaction using a reference.
         // This avoids loading the entire transaction entity from
         // Public Collection preventing extra memory consumption.
        foreach ($txns as $txn)
        {
            // skip if txn not to be settled
            if ($this->shouldSettle($txn, $merchantSettleToPartner) === false)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForRefundAuthTxn = $this->skipForRefundAuthTxn($txn);

            if ($skipForRefundAuthTxn === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForEarlySettlement = $this->skipForEarlySettlement($txn, $esMerchants, $esMerchantsThreePm);

            if ($skipForEarlySettlement === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForNewSettlementService = (new Bucket\Core())->shouldProcessViaNewService($txn->getMerchantId());

            if ($skipForNewSettlementService === true)
            {
                $transactionSkipCount++;

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id' => $txn->getMerchantId(),
                        'txn_id'      => $txn->getId(),
                        'reason'      => 'settlement will be processed via new settlement service',
                    ]);

                continue;
            }

            $skipForDsp = $this->skipForDsp($txn);

            if ($skipForDsp === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForMutualFundsMarketplace = $this->skipForMutualFundsMarketplace($txn);

            if ($skipForMutualFundsMarketplace === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForKarvy = $this->skipForKarvy($txn);

            if ($skipForKarvy === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $skipForScripBox = $this->skipForScripBox($txn);

            if ($skipForScripBox === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $transactionsSettleCount++;

            $merchantId = $txn->getMerchantId();

            $filterGroupedTxns[$merchantId] = ($filterGroupedTxns[$merchantId] ?? (new Base\PublicCollection));

            $filterGroupedTxns[$merchantId]->push($txn);

            $txn = null;
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTIONS_SKIPPED,
            [
                'transactions_skip_count'   => $transactionSkipCount,
                'transactions_settle_count' => $transactionsSettleCount
            ]
        );

        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENTS_TXNS_GROUP_BY_MERCHANT_END);

        return $filterGroupedTxns;
    }

    protected function skipForRefundAuthTxn($txn): bool
    {
        // skip if txn is refund of authorized txn and update the txn
        if (($txn->getBalance() === 0) and
            ($txn->isTypeRefund()))
        {
            $payment = $txn->source->payment;

            if ($payment->hasBeenCaptured() === false)
            {
                $txn[Transaction\Entity::SETTLED_AT] = null;

                $this->repo->saveOrFail($txn);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::REFUND_AUTH_PAYMENT
                    ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Early settlement timing check added
     *
     * @param $txn
     * @param $esMerchants
     * @return bool
     */
    protected function skipForEarlySettlement($txn, $esMerchants, $esMerchantsThreePm): bool
    {
        $mid = $txn->getMerchantId();

        if (in_array($mid, $esMerchants, true) === false)
        {
            return false;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $fivePm = Carbon::today(Timezone::IST)->hour(17)->getTimestamp();

        $sixPm = Carbon::today(Timezone::IST)->hour(18)->getTimestamp();

        $nineAm = Carbon::today(Timezone::IST)->hour(9)->getTimestamp();

        $tenAm = Carbon::today(Timezone::IST)->hour(10)->getTimestamp();

        //
        // Settle the transaction if time is between 9-10 am or 5-6pm
        // This is the time window promised to the merchants on ES.
        // For example, if a transaction's settled_at is 7 am, this
        // condition ensures that it doesn't get settled in the 7 or 8 am
        // batch but only in the 9 am batch.
        //
        if ((($now >= $nineAm) and ($now < $tenAm)) or
            (($now >= $fivePm) and ($now < $sixPm)))
        {
            return false;
        }

        //
        // If settlement was delayed for some reason, beyond our control, settle ASAP
        // For example, if a transaction's settled_at is 7 am, but for some
        // reason the transaction wasn't settled at 9 am, and now it is 2 pm
        // then we want the transactions to be settled even if it's outside
        // the merchant's settlement window, because this transaction's
        // settlement should have been done at 9, and it's not delayed.
        //
        if ((($txn->getSettledAt() <= $fivePm) and ($now > $fivePm)) or
            (($txn->getSettledAt() <= $nineAm) and ($now > $nineAm)))
        {
            return false;
        }

        if (in_array($mid, $esMerchantsThreePm, true) === true)
        {
            $threePm = Carbon::today(Timezone::IST)->hour(15)->getTimestamp();

            if (($txn->getSettledAt() <= $threePm) and ($now > $threePm))
            {
                return false;
            }

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_OUTSIDE_ES_THREE_PM_WINDOW
                ]);

            return true;
        }

        $this->trace->count(
            Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
            [
                Metric::SKIP_REASON => Metric::BLOCK_OUTSIDE_ES_WINDOW
            ],
            1);

        $this->trace->info(
            TraceCode::SETTLEMENT_SKIPPED,
            [
                'merchant_id'       => $txn->getMerchantId(),
                'transaction_id'    => $txn->getId(),
                'source_id'         => $txn->getEntityId(),
                'reason'            => Metric::BLOCK_OUTSIDE_ES_WINDOW
            ]);

        return true;
    }

    protected function skipForDsp($txn): bool
    {
        // DSP wants settlements only between 10 am and 3 pm ¯\_(ツ)_/¯
        //
        // TODO : Move this to schedules
        // https://github.com/razorpay/api/issues/5347
        //
        if ($txn->getMerchantId() === '7thBRSDflu7NHL')
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $tenAm = Carbon::today(Timezone::IST)->hour(10)->getTimestamp();

            $threePm = Carbon::today(Timezone::IST)->hour(15)->minute(10)->getTimestamp();

            //This condition used when dsp transactions misses the settlement window of 10am - 3pm
            // but needs to be settled immediately on the next cron run, same day
            if (($now > $threePm) and ($txn->getSettledAt() <= $threePm))
            {
                return false;
            }

            if (($now >= $tenAm) and
                ($now <= $threePm))
            {
                return false;
            }

            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                ]);

            return true;
        }

        return false;
    }

    protected function skipForMutualFundsMarketplace($txn): bool
    {
        // Mutual Fund Marketplace Merchant ids
        $mfMids = [
            Preferences::MID_GOALWISE_TPV,
            Preferences::MID_GOALWISE_NON_TPV,
            Preferences::MID_WEALTHAPP,
            Preferences::MID_WEALTHY,
            Preferences::MID_PAISABAZAAR,
        ];

        $mid = $txn->getMerchantId();

        $merchant = $this->merchants[$mid];

        $parentId = $merchant->getParentId();

        // Check if it is a sub-merchant of a Mutual-fund account
        if (($txn->isTypePayment() === true) and
            ($merchant->isLinkedAccount() === true) and
            (in_array($parentId, $mfMids, true) === true))
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $onePm = Carbon::today(Timezone::IST)->hour(13)->getTimestamp();

            $twoPm = Carbon::today(Timezone::IST)->hour(14)->getTimestamp();

            $twoThirtyPm = Carbon::today(Timezone::IST)->hour(14)->minute(30)->getTimestamp();

            $twelvePm = Carbon::today(Timezone::IST)->hour(12)->getTimestamp();

            // If settlement was delayed for some reason, beyond our control, settle ASAP
            if ($this->isDelayedSettlement($txn) === true)
            {
                return false;
            }

            if (($parentId === Preferences::MID_WEALTHY) and
                (($now < $twelvePm) or
                    ($now >= $onePm)))
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ]);

                return true;
            }

            //
            // Maps the mids that want to receive only 1 settlement per day.
            // They need all transactions till 1 pm to be settled in the 1 pm cycle.
            //
            $oneSetlAt1PmMids = [
                Preferences::MID_PAISABAZAAR,
            ];

            if ((in_array($parentId, $oneSetlAt1PmMids, true) === true) and
                (($now < $onePm) or
                 ($now >= $twoPm)))
            {
                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ],
                    1);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ]);

                return true;
            }

            // Normal MF settlement window is 1pm-2pm (2 settlements)
            if (($now < $onePm) or
                ($now > $twoThirtyPm))
            {
                $this->trace->count(
                    Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                    [
                        Metric::SKIP_REASON => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ],
                    1);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SKIPPED,
                    [
                        'merchant_id'       => $txn->getMerchantId(),
                        'transaction_id'    => $txn->getId(),
                        'source_id'         => $txn->getEntityId(),
                        'reason'            => Metric::BLOCK_MF_OUTSIDE_TIME_PERIOD
                    ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Applicable only for Mutual Fund Transactions.
     * That need to be settled only between 1-2 PM
     *
     * @param $txn
     * @return bool
     */
    protected function isDelayedSettlement($txn): bool
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $twoPm = Carbon::today(Timezone::IST)->hour(14)->getTimestamp();

        $today = Carbon::today(Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::SETTLEMENT_DELAYED_MF_CHECK,
            [
                'now'               => $now,
                'two_pm'            => $twoPm,
                'today'             => $today,
                'txn_settled_at'    => $txn->getSettledAt()
            ]);

        //
        // If the transaction was due settlement before today, but wasn't
        // settled for whatever reason, we want to try to settle it immediately.
        //
        if ($txn->getSettledAt() < $today)
        {
            return true;
        }

        //
        // Settle transaction which needed to be settled before 2 pm today
        // but for whatever reason weren't picked up then.
        // In this case, the below condition of settlement window of 1-2 PM
        // is not applicable, because these were due for settlement
        // before 2 pm, and should have been picked up.
        //
        if (($txn->getSettledAt() <= $twoPm) and ($now > $twoPm))
        {
            return true;
        }

        return false;
    }

    protected function createSettlementsFromTxns(
        $txns, string $channel, $merchantSettleToPartner, Balance\Entity $balance, array $params): array
    {
        $merchantId = $txns->first()->getMerchantId();

        $merchant = $this->merchants[$merchantId];

        if ($this->isDebugEnabled() === true)
        {
            $this->trace->info(
                TraceCode::SETTLEMENTS_CREATE_ENTITIES_FOR_MERCHANT,
                [
                    'merchant' => $merchantId,
                    'balance'  => $balance->getId(),
                ]
            );
        }

        try
        {
            list($setlAmount, $setlFee, $setlApiFee, $tax ) = $this->getSettlementAmountsForMerchant($txns);

            if (($setlAmount < 100) or ($setlAmount > $balance->getBalance()) or ($setlAmount >= 50000000000))
            {
                $skipReason = null;

                if($setlAmount < 100)
                {
                    $skipReason = Metric::MIN_SETTLEMENT_AMOUNT_BLOCK;
                }
                else if($setlAmount > $balance->getBalance())
                {
                    $skipReason = Metric::SETTLEMENT_AMOUNT_LESS_THAN_BALANCE;
                }
                else if($setlAmount >= 50000000000)
                {
                    $operation = 'Settlement skipped due to amount grater than 50 Cr';

                    $traceData = [
                        'merchant_id' => $merchant->getId(),
                        'balance'     => $balance->getBalance(),
                        'setlAmount'  => $setlAmount,
                    ];

                    (new SlackNotification)->send(
                        $operation,
                        $traceData,
                        null,
                        1,
                        'settlement_alerts');

                    $skipReason = Metric::MAX_SETTLEMENT_AMOUNT_BLOCK;
                }
                    $this->trace->count(
                        Metric::MERCHANTS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                        [
                            Metric::SKIP_REASON => $skipReason
                        ]);

                $this->traceMerchantSettlementSkip(
                    $merchant,
                    [
                        'balance_id'   => $balance->getId(),
                        'balance_type' => $balance->getType(),
                        'balance'      => $balance->getBalance(),
                        'merchant'     => $merchant->getId(),
                        'setlAmount'   => $setlAmount,
                        'reason'       => 'settlement amount less than 1rs or greater than balance or greater than 50Cr',
                    ]);

                    return [null, null];
            }

            $setlDetails = $this->calculateSettlementDetailAmounts($txns);

            list($setl, $transferAttempt) =
                $this->repo->transaction(function () use($merchant, $channel, $txns, $setlAmount, $setlFee, $setlApiFee,
                                                        $tax, $merchantSettleToPartner, $balance, $params, $setlDetails){
                 return $this->settleForMerchant($merchant, $channel, $txns, $setlAmount, $setlFee, $setlApiFee, $tax,
                                                   $merchantSettleToPartner, $balance, $params, $setlDetails);
            });

            if(($setl !== null) and ($transferAttempt !== null))
            {
                $transactionCount = $txns->count();

                $customProperties = [
                    'merchant_id'           => $merchantId,
                    'channel'               => $channel,
                    'settlement_id'         => $setl->getId(),
                    'settlement_amount'     => $setlAmount,
                    'transaction_count'     => $transactionCount
                ];

                $this->app['diag']->trackSettlementEvent(
                    EventCode::SETTLEMENT_CREATION_SUCCESS,
                    $setl,
                    null,
                    $customProperties);

                $medium = in_array($channel, Channel::getApiBasedChannels(), true) ?
                    Medium::API : Medium::FILE;

                $destination = $this->repo
                                    ->settlement_destination
                                    ->fetchActiveDestination($setl->getId());

                $destinationType = null;

                if($destination !==null)
                {
                    $destinationType = $destination->getDestinationType();
                }

                $customProperties += [
                    'fund_transfer_attempt_id'                => $transferAttempt->getId(),
                    'fund_transfer_attempt_mode'              => $transferAttempt->getMode(),
                    'fund_transfer_attempt_medium'            => $medium,
                    'fund_transfer_attempt_purpose'           => Purpose::SETTLEMENT,
                    'destination_type'                        => $destinationType,
                ];

                $this->app['diag']->trackSettlementEvent(
                    EventCode::FTA_CREATION_SUCCESS,
                    $setl,
                    null,
                    $customProperties);
            }

            return [$setl, $transferAttempt];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $transactionCount = $txns->count();

            $customProperties = [
                'merchant_id'           => $merchantId,
                'channel'               => $channel,
                'settlement_amount'     => $setlAmount,
                'transaction_count'     => $transactionCount
            ];

            $this->app['diag']->trackSettlementEvent(
                EventCode::SETTLEMENT_CREATION_FAILED,
                null,
                $exception,
                $customProperties);

            $medium = in_array($channel, Channel::getApiBasedChannels(), true) ?
                Medium::API : Medium::FILE;

            $customProperties += ['fund_transfer_attempt_medium' => $medium];

            $this->app['diag']->trackSettlementEvent(
                EventCode::FTA_CREATION_FAILED,
                null,
                $exception,
                $customProperties);

            return [null, null];
        }
    }

    protected function getSettlementAmountsForMerchant($txns): array
    {
        $setlAmount = $setlApiFee = 0;
        $setlFee    = $tax = 0;

        foreach ($txns as $txn)
        {
            $setlAmount += $txn->getCredit() - $txn->getDebit();
            $setlApiFee += $txn->getApiFee();
            $setlFee    += $txn->getFee();
            $tax        += $txn->getTax();
        }

        return [$setlAmount, $setlFee, $setlApiFee, $tax];
    }

    /**
     * [Marketplace] Updates the recipient's settlement id in the transfer entity.
     *
     *  When the transactions for the internal payments (payments triggered by the transfer
     *  from master merchant to the linked account) are settled, the settlement_id of those
     *  transactions will be updated for the transfer entity that initiated these payments.
     *
     * @param $txns
     */
    protected function updateSettlementIdInTransfer(\Illuminate\Support\Collection $txns)
    {
        $filteredTxnIds = [];

        if ($this->isDebugEnabled() === true)
        {
            $startTime = microtime(true);
        }

        foreach ($txns as $txn)
        {
            $merchantId = $txn->getMerchantId();

            $merchant = $this->merchants[$merchantId];

            if (($txn->isTypePayment() === true) and ($merchant->isLinkedAccount() === true))
            {
                $filteredTxnIds[] = $txn->getId();
            }
        }

        if (empty($filteredTxnIds) === true)
        {
            if ($this->isDebugEnabled() === true)
            {
                $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_NO_TXNS_TO_UPDATE);
            }

            return;
        }

        try
        {
            $relations = ['source', 'source.transfer'];

            $filteredTxns = $this->repo->transaction->findManyWithRelations($filteredTxnIds, $relations);

            foreach ($filteredTxns as $txn)
            {
                $settlementId = $txn->getSettlementId();

                if ($settlementId === null)
                {
                    continue;
                }

                $transfer = $txn->source->transfer;

                $transfer->setRecipientSettlementId($settlementId);

                $this->repo->saveOrFail($transfer);
            }

            if ($this->isDebugEnabled() === true)
            {
                $timeTaken = microtime(true) - $startTime;

                $this->trace->info(TraceCode::RECIPIENT_SETTLEMENT_UPDATE_TIME_TAKEN, ['time_taken' => $timeTaken]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::CRITICAL, TraceCode::TRANSFER_UPDATE_SETTLEMENT_ID_FAILED, $filteredTxnIds);
        }
    }

    /**
     * Used payout mutex to block merchant from creating a
     * settlement when payout is in process for the same merchant.
     *
     * @param                $merchant
     * @param                $channel
     * @param                $setlTxns
     * @param                $setlAmount
     * @param                $setlFee
     * @param                $setlApiFee
     * @param                $tax
     * @param                $merchantSettleToPartner
     * @param Balance\Entity $balance
     * @param array          $params
     * @param                $setlDetails
     *
     * @return array
     */
    protected function settleForMerchant(
        $merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $merchantSettleToPartner,
        Balance\Entity $balance, $params, $setlDetails): array
    {
        $settlement = null;

        $transferAttempt = null;

        $mutexResource = sprintf(PayoutCore::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use($merchant, $channel, $setlTxns, $setlAmount, $setlFee, $setlApiFee, $tax, $settlement, $transferAttempt,
                 $merchantSettleToPartner, $balance, $params, $setlDetails) {
                try
                {
                        $destinationMerchantId = $this->settlementToPartner($merchant->getId());

                        $isAggregateSettlement = (bool) $destinationMerchantId;

                        // create settlement and attempt
                        $merchantSettler = new SetlMerchant(
                            $merchant,
                            $channel,
                            $this->repo,
                            $this->debug,
                            $merchantSettleToPartner,
                            $isAggregateSettlement);

                        $this->traceSettlementDelayOfTransactions($setlTxns);

                        $settlement = $merchantSettler->settle(
                            $setlTxns,
                            $setlAmount,
                            $setlFee,
                            $setlApiFee,
                            $tax,
                            $this->setlTime,
                            $setlDetails,
                            $merchantSettleToPartner,
                            $balance);

                        $merchantSettler->createTransaction($settlement);

                        $transferAttempt = null;

                        if ($destinationMerchantId !== null)
                        {

                            $transferAttempt = (new Transfer\Core)->transfer(
                                $settlement,
                                $destinationMerchantId,
                                $balance->getType());
                        }
                        else
                        {
                            $transferAttempt = $merchantSettler->createSettlementAttempt($merchantSettleToPartner, $params);
                        }

                        return [$settlement, $transferAttempt];
                }
                catch (\Exception $ex)
                {
                    $traceData = [
                        'merchant_id'   => $merchant->getId(),
                        'setlAmount'    => $setlAmount,
                        'reason'        => $ex->getMessage(),
                    ];

                    if ($settlement !== null)
                    {
                        $traceData['settlement_id'] = $settlement->getId();
                    }

                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::SETTLEMENT_SKIPPED,
                        $traceData);

                    (new SlackNotification)->send('setl_skipped', $traceData, $ex);

                    throw $ex;
                }
            },
            PayoutCore::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    protected function traceSettlementDelayOfTransactions($setlTxns)
    {
        foreach ($setlTxns as $txn)
        {
            $timeTaken = intval(($this->setlTime - $txn->getSettledAt()) / 60);

            $this->trace->histogram(
                Metric::TRANSACTION_SETTLEMENT_INITIATION_DELAY_MINUTES,
                $timeTaken,
                [Metric::CHANNEL => $txn->getChannel()]
            );
        }
    }

    /**
     * Settlement is done only bank account change is not recent as we need some
     * time till beneficiary is updated in kotak
     *
     * @param Transaction\Entity $txn
     * @param $merchantSettleToPartner
     * @return bool
     */
    protected function shouldSettle(Transaction\Entity $txn, $merchantSettleToPartner): bool
    {
        $mid = $txn->getMerchantId();

        $merchant = $this->merchants[$mid];

        $today = Carbon::today(Timezone::IST);

        // Do not settle for Wealthy's sub-merchants on Saturday
        if (($merchant->getParentId() === Preferences::MID_WEALTHY) and
            ($today->dayOfWeek === Carbon::SATURDAY))
        {
            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_WEALTHY_ON_SATURDAY
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $mid,
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_WEALTHY_ON_SATURDAY
                ]);

            return false;
        }

        $shouldSettle = true;

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $bankAccount = $merchant->bankAccount;

        if(isset($merchantSettleToPartner[$mid]) === true)
        {
            $bankAccountId = $merchantSettleToPartner[$mid];

            $bankAccount = $this->repo->bank_account->getBankAccountById($bankAccountId);

            if (empty($bankAccount) === true)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_SETTLING_PARTNER_BANK_ACCOUNT_NOT_MAPPED,
                    [
                        'merchant_id'    => $merchant->getId(),
                        'transaction_id' => $txn->getId()
                    ]
                );

                return false;
            }
        }

        if (empty($bankAccount) === true)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_MERCHANT_BANK_ACCOUNT_NOT_MAPPED,
                [
                    'merchant_id'    => $merchant->getId(),
                    'transaction_id' => $txn->getId()
                ]
            );

            return false;
        }

        $channel = $txn->getChannel();

        $allowedChannelFor24x7Settlement = Channel::get24x7Channels();

        if (($this->env !== 'testing') and
            (in_array($channel, $allowedChannelFor24x7Settlement, true) === true))
        {
            return true;
        }

        if (($this->env !== 'testing') and
            ($bankAccount->getCreatedAt() > $lastWorkingDay->getTimestamp()))
        {
            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BANK_ACCOUNT_CREATED_YESTERDAY
                ]);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $mid,
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BANK_ACCOUNT_CREATED_YESTERDAY,
                    'created_at'        => Carbon::createFromTimestamp($txn->getCreatedAt(), Timezone::IST)->format('Y-m-d H:i:s'),
                ]);

            $shouldSettle = false;
        }

        return $shouldSettle;
    }

    protected function successNotification($data, $settlements, $traceCode)
    {
        $this->trace->info($traceCode, $data);

        (new SlackNotification)->send('setl_initiate', $data);
    }

    protected function settlementFailure($channel, $e, $traceCode)
    {
        $e = new SettlementFailureException($channel, $e->getMessage(), null, $e);
        $this->failureNotification($e);

        throw $e;
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->send('setl_initiate', [], $exception);
    }

    /**
     * Returns the list of all channels for which settlements needs to be done
     *
     * @param string|null $channel
     *
     * @return array
     */
    protected function getArrayedChannels($channel = null)
    {
        if ($channel === null)
        {
            $channels = Channel::getChannels();
        }
        else
        {
            $channels = [$channel];
        }

        return $channels;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('6144M');

        // Time limit of 30 mins
        RuntimeManager::setTimeLimit(1500);
    }

    /**
     * Early settlement timing check added
     *
     * @param $txn
     * @return bool
     */
    protected function skipForKarvy($txn): bool
    {
        $merchant = $this->merchants[$txn->getMerchantId()];

        // Karvy wants settlements only at 1 pm and 3 pm ¯\_(ツ)_/¯
        if ($merchant->getParentId() === Preferences::MID_KARVY)
        {
            $now = Carbon::now(Timezone::IST)->getTimestamp();

            $onePm = Carbon::today(Timezone::IST)->hour(13)->getTimestamp();

            $threePm = Carbon::today(Timezone::IST)->hour(15)->getTimestamp();

            $yesterdayThreePm = Carbon::yesterday(Timezone::IST)->hour(15)->getTimestamp();

            //if settlement for a previous day transaction with
            // settled_at of 3pm was not created then intiate it asap next day
            if ($txn->getSettledAt() <= $yesterdayThreePm)
            {
              return false;
            }

            if (($now > $threePm) and ($txn->getSettledAt() <= $threePm))
            {
                return false;
            }

            if (($now > $onePm) and ($txn->getSettledAt() <= $onePm))
            {
                return false;
            }

            $this->trace->count(
                Metric::TRANSACTIONS_SKIPPED_FOR_SETTLEMENT_TOTAL,
                [
                    Metric::SKIP_REASON => Metric::BLOCK_KARVY_OUTSIDE_TIME_PERIOD
                ],
                1);

            $this->trace->info(
                TraceCode::SETTLEMENT_SKIPPED,
                [
                    'merchant_id'       => $txn->getMerchantId(),
                    'transaction_id'    => $txn->getId(),
                    'source_id'         => $txn->getEntityId(),
                    'reason'            => Metric::BLOCK_KARVY_OUTSIDE_TIME_PERIOD
                ]);

            return true;

        }

        return false;
    }

    /**
     * Scripbox wants the settlement only at 10AM and 1PM
     *
     * @param $txn
     * @return bool
     */
    protected function skipForScripBox($txn): bool
    {
        $merchant = $this->merchants[$txn->getMerchantId()];

        if (($merchant->getId() === Preferences::MID_SCRIP_BOX) or
            ($merchant->getParentId() === Preferences::MID_SCRIP_BOX))
        {
            $hour = Carbon::now(Timezone::IST)->hour;

            if (($hour < Constants::ELEVEN_AM) or ($hour > Constants::ONE_PM))
            {
                return true;
            }
        }

        return false;
    }

    protected function traceMemoryUsage(string $traceCode)
    {
        if ($this->isDebugEnabled() === true)
        {
            $memoryAllocated = get_human_readable_size(memory_get_usage(true));
            $memoryUsed = get_human_readable_size(memory_get_usage());
            $memoryPeakUsage = get_human_readable_size(memory_get_peak_usage());
            $memoryPeakUsageAllocated = get_human_readable_size(memory_get_peak_usage(true));

            $this->trace->info(
                $traceCode,
                [
                    'memory_allocated' => $memoryAllocated,
                    'memory_used' => $memoryUsed,
                    'memory_peak_usage' => $memoryPeakUsage,
                    'memory_peak_usage_allocated' => $memoryPeakUsageAllocated,
                ]);
        }
    }

    /**
     * @param $txns
     * @param $merchantId
     * @return array
     */
    protected function processMerchantSettlement($txns, $merchantId): array
    {
        $filterGroupedTxns       = [];

        $transactionSkipCount    = 0;

        $transactionsSettleCount = 0;

        foreach ($txns as $txn)
        {
            $skipForRefundAuthTxn = $this->skipForRefundAuthTxn($txn);

            if ($skipForRefundAuthTxn === true)
            {
                $transactionSkipCount++;

                continue;
            }

            $filterGroupedTxns[$merchantId] = ($filterGroupedTxns[$merchantId] ?? (new Base\PublicCollection));

            $filterGroupedTxns[$merchantId]->push($txn);
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_TRANSACTIONS_SKIPPED,
            [
                'transactions_skip_count'   => $transactionSkipCount,
                'transactions_settle_count' => $transactionsSettleCount
            ]
        );

        return $filterGroupedTxns;
    }

    /**
     * given partner Id if the settlement has to be aggregated at parent level
     * it'll give the merchant ID if there is any mapping found else will return null
     * In case of multiple partner map it'll give null
     *
     * @param string $merchantId
     *
     * @return string|null
     */
    public function settlementToPartner(string $merchantId)
    {
        $merchantList = $this->repo
                             ->merchant_access_map
                             ->fetchAffiliatedPartnersForSubmerchant($merchantId);

        //
        // if the list is empty then there is not partner to settle to
        // if there are multiple partners then we ignore this
        // currently this is specific to phonePe use case.
        //
        if (($merchantList->isEmpty() === true) or ($merchantList->count() > 1))
        {
            return null;
        }

        $parentMerchantID = $merchantList->first()
                                         ->getEntityOwnerId();

        //
        // check if the parent is enabled with aggregate settlement feature
        //
        $feature = $this->repo
                        ->feature
                        ->findByEntityTypeEntityIdAndName(
                            EntityConstant::MERCHANT,
                            $parentMerchantID,
                            Feature\Constants::AGGREGATE_SETTLEMENT);

        return ($feature === null) ? null : $parentMerchantID;
    }

    public function calculateSettlementDetailAmounts($txns): array
    {
        $setlDetailFetchStartTime = microtime(true);

        $entityTypes = SetlComponent::getAllComponents();

        $details = [];

        foreach ($entityTypes as $componentType)
        {
            $details[$componentType]['amount'] = 0;

            $details[$componentType]['count'] = 0;

            $details[$componentType][SetlComponent::TAX] = 0;
            $details[$componentType][SetlComponent::FEE] = 0;
        }

        foreach ($txns as $txn)
        {
            $componentType = $txn->getType();

            if($componentType !== Transaction\Type::PAYMENT and $componentType !== Transaction\Type::REFUND)
            {
                $details[$componentType]['count'] += 1;
            }

            switch ($componentType)
            {
                case Transaction\Type::PAYMENT:
                    $payment = $txn->source;
                    $paymentType = $payment->isInternational() === true ?
                        Details\Component::PAYMENT_INTERNATIONAL : Details\Component::PAYMENT_DOMESTIC;

                    $details[$paymentType]['count'] += 1;
                    $details[$paymentType]['amount'] += $txn->getAmount();

                    $componentType = $paymentType;

                    break;

                case Transaction\Type::REVERSAL:
                case Transaction\Type::SETTLEMENT_TRANSFER:
                    $details[$componentType]['amount'] += $txn->getAmount();
                    break;

                case Transaction\Type::REFUND:
                    $payment = $txn->source->payment;
                    $refundType = $payment->isInternational() === true ?
                        Details\Component::REFUND_INTERNATIONAL : Details\Component::REFUND_DOMESTIC;

                    $details[$refundType]['count'] += 1;
                    $details[$refundType]['amount'] -= $txn->getAmount();

                    $componentType = $refundType;

                    break;

                case Transaction\Type::PAYOUT:
                case Transaction\Type::TRANSFER:
                case Transaction\Type::DISPUTE:
                case Transaction\Type::FUND_ACCOUNT_VALIDATION:
                case Transaction\Type::SETTLEMENT_ONDEMAND:
                case Transaction\Type::CREDIT_REPAYMENT:
                    $details[$componentType]['amount'] -= $txn->getAmount();
                    break;

                case Transaction\Type::ADJUSTMENT:
                case Transaction\Type::COMMISSION:
                    $details[$componentType]['amount'] += $txn->getCredit();
                    $details[$componentType]['amount'] -= $txn->getDebit();
                    break;

                default:
                    throw new Exception\LogicException('Invalid Settlement-component-type:' . $componentType);
            }

            if(isset($details[$componentType][SetlComponent::FEE]) == false)
            {
                $details[$componentType][SetlComponent::FEE] = 0;
            }

            if(isset($details[$componentType][SetlComponent::TAX]) == false)
            {
                $details[$componentType][SetlComponent::TAX] = 0;
            }

            $details[$componentType][SetlComponent::TAX] += $txn->getTax();

            $details[$componentType][SetlComponent::FEE] += ($txn->getFee() - $txn->getTax());

            // Add credits if txn is of type fee credits.
            $details[SetlComponent::FEE_CREDITS]['amount'] += ($txn->isFeeCredits() ? $txn->getCredits() : 0);

            // Add credits if txn is of type refund credits.
            $details[SetlComponent::REFUND_CREDITS]['amount'] += ($txn->isRefundCredits() ? $txn->getCredits() : 0);
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_DETAIL_CREATE_TIME_TAKEN,
            [
                'time_taken'  => get_diff_in_millisecond($setlDetailFetchStartTime),
            ]);

        return $details;
    }
}
