<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Type;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Merchant\Balance;
use RZP\Models\Feature\Constants;
use RZP\Jobs\Settlement\migration;
use RZP\Models\Merchant\Preferences;
use RZP\Models\FundTransfer\Attempt;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant as MerchantModel;
use RZP\Models\Settlement\Bucket\Preference;
use RZP\Models\Schedule\Task as scheduleTask;
use RZP\Jobs\Settlement\TransactionMigration;
use RZP\Models\Settlement\Bucket as BucketModel;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }

    public function postInitiateTransfer(array $input): array
    {
        (new Validator)->validateInput('nodal_transfer', $input);

        if (isset($input[Payment\Entity::GATEWAY]) === true)
        {
            $gateway = $input[Entity::GATEWAY];

            $channel = Channel::getChannelFromGateway($gateway);

            $amount = $this->getAmountFromPaymentsForLastDay($gateway);
        }
        else
        {
            $amount = $input[Entity::AMOUNT];

            $channel = $input[Entity::CHANNEL];
        }

        $response = [
            'message' => 'Amount to be transferred is zero or negative'
        ];

        if ($amount > 0)
        {
            $destination = $input[Entity::DESTINATION];

            $merchantId = NodalAccount::ACCOUNT_MAP[$this->mode][$destination];

            $adjInput = [
                Adjustment\Entity::MERCHANT_ID  => $merchantId,
                Adjustment\Entity::AMOUNT       => $amount,
                Adjustment\Entity::CHANNEL      => $channel,
                Adjustment\Entity::DESCRIPTION  => 'Nodal Nodal Transfer',
                Adjustment\Entity::CURRENCY     => 'INR'
            ];

            $adjustment = (new Adjustment\Service)->addAdjustment($adjInput);

            return $adjustment;
        }

        return $response;
    }

    public function addBeneficiary(string $channel, array $input)
    {
        (new Validator)->validateInput($channel . '_add_beneficiary', $input);

        return $this->getNodalAccount($channel)->addBeneficiary($input);
    }

    public function updateChannel(array $input): array
    {
        (new Validator)->validateInput('update_channel', $input);

        $settlementIds = $input['settlement_ids'];

        Entity::verifyIdAndStripSignMultiple($settlementIds);

        $channel = $input['channel'];

        $failedIds = [];

        $successIds = [];

        foreach ($settlementIds as $settlementId)
        {
            try
            {
                $transactionIds = $this->repo
                                       ->transaction
                                       ->fetch([Transaction\Entity::SETTLEMENT_ID => $settlementId])
                                       ->pluck(Transaction\Entity::ID)
                                       ->toArray();

                $this->repo->transaction(function () use ($settlementId, $transactionIds, $channel)
                {
                    $this->repo
                         ->settlement
                         ->updateChannel($settlementId, $channel);

                    $this->repo
                         ->transaction
                         ->updateChannelForSettlement($settlementId, $transactionIds, $channel);
                });

                $successIds[] = $settlementId;
            }
            catch (\Throwable $ex)
            {
                $failedIds[] = $settlementId;

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SETTLEMENTS_CHANNEL_UPDATE_FAILED,
                    [
                        'settlement_id' => $settlementId,
                        'channel'       => $channel,
                    ]);
            }
        }

        $response = [
            'channel'       => $channel,
            'total'         => count($settlementIds),
            'success'       => count($successIds),
            'failed'        => count($failedIds),
            'failed_ids'    => $failedIds,
        ];

        $this->trace->info(
            TraceCode::SETTLEMENTS_CHANNEL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    /**
     * @param Entity $entity
     * @param array  $ftaData
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaRecon(Entity $entity, array $ftaData)
    {
        $attemptStatus = $ftaData[Attempt\Constants::FTA_STATUS];

        $attemptFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON];

        $status = $this->getDerivedStatus($entity, $attemptStatus);

        $entity->setStatus($status);

        $entity->setUtr($ftaData[Attempt\Constants::UTR]);

        $entity->setFailureReason($attemptFailureReason);

        $this->repo->saveOrFail($entity);

        $batchFta = $entity->batchFundTransfer;

        $batchFtaId = null;

        //BatchFTA can be null in test mode
        if (empty($batchFta) === false)
        {
            $batchFtaId = $batchFta->getId();
        }

        $customProperties = [
            'merchant_id'                       => $ftaData['merchant_id'],
            'channel'                           => $entity->getChannel(),
            'fund_transfer_attempt_id'          => $ftaData['fta_id'],
            'batch_fund_transfer_attempt_id'    => $batchFtaId,
            'fund_transfer_attempt_mode'        => $ftaData['mode'],
            'fund_transfer_attempt_amount'      => $entity->getAmount(),
            'settlement_id'                     => $entity->getId(),
            'error_message'                     => $attemptFailureReason,
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::SETTLEMENT_STATUS_UPDATED,
            null,
            null,
            $customProperties);
    }

    public function updateStatusAfterFtaInitiated(Entity $entity, Attempt\Entity $fta)
    {
        $entity->batchFundTransfer()->associate($fta->batchFundTransfer);

        $entity->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($entity);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $entity, array $ftaData)
    {
        $entity->setUtr($ftaData[Attempt\Constants::UTR]);

        $entity->setRemarks($ftaData[Attempt\Constants::REMARKS]);

        $this->trace->info(
            TraceCode::FTA_RECON_SOURCE_UPDATED,
            [
                'source_id'         => $entity->getId(),
                'fta_id'            => $ftaData[Attempt\Constants::FTA_ID],
                'source_original'   => $entity->getOriginalAttributesAgainstDirty(),
                'source_dirty'      => $entity->getDirty(),
            ]);

        $this->repo->saveOrFail($entity);
    }

    public function getAccountBalance(string $channel): array
    {
        return $this->getNodalAccount($channel)->getAccountBalance();
    }

    /**
     * @param Entity $entity
     * @param string $ftaStatus
     * @return mixed|string
     * @throws Exception\LogicException
     */
    protected function getDerivedStatus(Entity $entity, string $ftaStatus)
    {
        switch ($ftaStatus)
        {
            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                return $entity->getStatus();

            case Attempt\Status::FAILED:
                return Status::FAILED;

            case Attempt\Status::PROCESSED:
                return Status::PROCESSED;

            default:
                throw new Exception\LogicException('Unrecognized attempt status: ' . $ftaStatus);
        }
    }

    protected function getAmountFromPaymentsForLastDay(string $gateway) : int
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        // Get the amount for captured payments on gateway for last day
        $paymentAmount = $this->repo->payment->getCapturedAmountByGateway($gateway, $from, $to);

        // Get the amount for refunds on gateway for last day
        $refundAmount = $this->repo->refund->getRefundedAmountByGateway($gateway, $from, $to);

        // amount to be transferred in paisa
        $amount = $paymentAmount - $refundAmount;

        // Transfer 99% of the derived amount
        $amount = 0.99 * $amount;

        return (int) $amount;
    }

    /**
     * Sends a webhook to the merchant for successfully settled payments
     *
     * @param Entity $settlement
     */
    public function triggerSettlementWebhook(Entity $settlement)
    {
        if ($this->shouldSendWebhook($settlement) === false)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $settlement
        ];

        $this->app['events']->fire('api.settlement.processed', $eventPayload);

    }

    /**
     * Returns false,
     *   if the settlement was not processed, or,
     *   if the settlement was not made for a linked account.
     *
     * @param Entity $settlement
     *
     * @return bool
     */
    protected function shouldSendWebhook(Entity $settlement): bool
    {
        // Proceed only if the settlement has successfully processed
        if ($settlement->isStatusProcessed() === false)
        {
            return false;
        }

        // Proceed only if the settlement was made to a linked account
        if ($settlement->merchant->isLinkedAccount() === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Creates the nodalAccount object for given channel
     *
     * @param string $channel channel name
     *
     * @return Object Nodal Account Object
     */
    public function getNodalAccount(string $channel)
    {
        $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

        return new $nodalClass();
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
    }

    /**
     * calculates the settlement amount fot given merchant and balanceType
     *
     * @param MerchantModel\Entity $merchant
     * @param Balance\Entity       $balance
     * @param int                  $timestamp
     * @return array
     */
    public function getMerchantSettlementAmount(
        MerchantModel\Entity $merchant,
        Balance\Entity $balance,
        int $timestamp = 0)
    {
        $isMerchantSettlementScheduled = ($timestamp !== 0);

        $timestamp = $this->getValidSettlementTime($timestamp);

        $amount = $this->repo
                       ->transaction
                       ->getMerchantSettlementAmount(
                            $merchant->getId(),
                            $balance,
                            $timestamp->getTimestamp())
                       ->toArray();

        $settlementAmount = (int) $amount['settlement_amount'];

        //
        // In case merchant is not bucketed and has valid settlement amount
        // then enqueue him for bucketing so the settlement can go as expected
        //
        if (($isMerchantSettlementScheduled === false) and
            ($settlementAmount <= $balance->getBalance()) and
            ($settlementAmount >= 100))
        {
            Bucket::dispatch($this->mode, '', $merchant->getId(), $timestamp->getTimestamp());
        }

        return [
            'settlement_amount'    => $settlementAmount,
            'next_settlement_time' => $timestamp->getTimestamp(),
        ];
    }

    /**
     * Gives the valid timestamp when the settlement will be processed
     * This takes are of holidays in case the bucket timestamp fell under holiday
     *
     * @param int $timestamp
     * @return Carbon
     */
    public function getValidSettlementTime(int $timestamp): Carbon
    {
        //
        // If there is not future bucket for settlement for the merchant then consider the current timestamp
        //
        $timestamp = ($timestamp === 0) ? Preference::getCeilTimestamp(Carbon::now(Timezone::IST)) :
                                            Carbon::createFromTimestamp($timestamp, Timezone::IST);

        //
        // If the timestamp given is a holiday then calculate the next working day
        // This situation can come up if the holiday is marked at last moment
        // also set the hour anchor to 9 AM as in that settlement cycle
        // we'll be settling the amount for this merchant
        //
        if (Holidays::isWorkingDay($timestamp) === false)
        {
            $timestamp = Holidays::getNthWorkingDayFrom($timestamp, 1)->addHours(9);
        }

        return $timestamp;
    }

    public function MigrateMerchantConfiguration($merchantId, $mode)
    {
        $merchant = $this->repo->merchant->fetchMerchantOnConnection($merchantId, $mode);

        $req = [
            'merchant_id' => $merchant->getId(),
        ];

        $response = app('settlements_api')->migrateMerchantConfigCreate($req, $mode);

        unset($response['config']['active']);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_DEFAULT_CREATE_SUCCESS,
            [
                'merchant_id' => $merchant->getId(),
                'response'    => $response,
                'mode'        => $mode,
            ]);

        $featureResult = $this->repo
                              ->feature
                              ->findByEntityIdAndNameOnConnection($merchant->getId(), Constants::BLOCK_SETTLEMENTS, $mode);

        if ($featureResult !== null)
        {
            $response['config']['features']['block']['status'] = true;
            $response['config']['features']['block']['reason'] = 'merchants has blocked settlement feature';
        }

        if (in_array($merchant->getId(), Preferences::NO_SETTLEMENT_MIDS, true) === true)
        {
            $response['config']['features']['block']['status'] = true;
            $response['config']['features']['block']['reason'] = 'merchants opted out on settlement';
        }

        if ($merchant->isFundsOnHold() === true)
        {
            $response['config']['features']['disable']['status'] = true;
            $response['config']['features']['disable']['reason'] =
                $merchant->getHoldFundsReason() != null ? $merchant->getHoldFundsReason() : 'funds are on hold';
        }

        $settlementServiceSupportedChannels = [
            Channel::AXIS,
            Channel::CITI,
            Channel::RBL,
            Channel::ICICI,
            Channel::YESBANK,
        ];

        if (in_array($merchant->getChannel(), $settlementServiceSupportedChannels) === true)
        {
            $response['config']['preferences']['channel'] = strtoupper($merchant->getChannel());
        }

        if (in_array($merchant->getId(), Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $response['config']['preferences']['mode'] = 'NEFT';
        }

        $scheduleMapping = $this->getScheduleMappingForMethodNewService($response['config']['schedules'], $merchant, $mode);

        $response['config']['schedules'] = $scheduleMapping;

        $destinationMerchantId = (new Processor)->settlementToPartner($merchant->getId());

        $isAggregateSettlement = (bool) $destinationMerchantId;

        if ($isAggregateSettlement === true)
        {
            $response['config']['types']['aggregate']['enable'] = true;
            $response['config']['types']['default']['enable'] = false;
        }

        $request = array_merge($req, $response);

        $result = app('settlements_api')->migrateMerchantConfigUpdate($request, $mode);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_UPDATE_SUCCESS,
            [
                'merchant' => $merchant->getId(),
                'request'  => $request,
                'mode'     => $mode,
                'result'   => $result,
            ]);
    }

    public function getScheduleMappingForMethodNewService($newSettlementSchedules, $merchant, $mode)
    {
        $schedules = $this->repo
                          ->schedule_task
                          ->fetchByMerchantOnConnection($merchant, scheduleTask\Type::SETTLEMENT, $mode);

        // todo add the schedule mappings from settlement service and existing schedules
        $scheduleIdMapping = [
            'live' => [
                'Exelo4dBIBNb7w' =>	'FaBOwnO4AVhpQP',
                'EkMPag0vPhEoII' =>	'FaBSNZYCXUd3Je',
                'EbDIK1BCsdChRO' =>	'FaBX2rsdiIdGKK',
                'E199S87u5emrhc' =>	'FaBXxd1MOLqvuz',
                'D9OYLuzMqpixEN' =>	'FaBc7ypWPhaQ6B',
                'D9M7aRrlKklxeA' =>	'FaBdBtGZksK7Ig',
                'CopOjZuuZlVJQF' =>	'FaBeG4Y339hE8v',
                'C6RlMskzOd4P1f' =>	'FaBfuI7GmFfmVR',
                'Bxn1GzzaOXYiUH' =>	'FaBgrBufcq4kVt',
                'BoHfGJokmCajnV' =>	'FaBhcVSqEVRk4L',
                'Bn2WcETPmn44M4' =>	'FaBjQdy3ZmFl8V',
                'Bn2TDNApLgprBN' =>	'FaBdJpUChkiTjd',
                'BU3qfzAjT3xfI8' =>	'FaBeXhqRsbtVOx',
                'BOqaXQX7kGvZAw' =>	'FaBg1IijNzE1lL',
                'BOqZw6mMPCiAZ6' =>	'FaBgvG6J2VGTTP',
                'BOqHxJ2begGv3h' =>	'FaBhmmTl1dUDF1',
                'BNSX7DllPSd6FH' =>	'FaBlAA8ZgWgBYP',
                'BEEgsA9DoDOtMR' =>	'FaBmLJbf2mSleI',
                'BEEgUzZZhUEEx0' =>	'FaBnf4IhuO3xHD',
                'B2j5vKmxqwkNsb' =>	'FaBpNAUudchIHL',
                'B0MUVJul984k1k' =>	'FaBqDl03OzGRzS',
                'AMrWBvk7AWHEb1' =>	'FaBquiH6tKr0x3',
                'AHeF0Ljio2Ertp' =>	'FaBrkLnGNeX9Tq',
                '9qP0GhZzHqJAJZ' =>	'FaBZt8dKpqHlst',
                '9gDcKNbZsdka2i' =>	'FaBbsMRRZrZq3V',
                '9WDh2pkY3h9HWX' =>	'FaBd80qi8NGBgq',
                '9JBZK3HBwiECrd' =>	'FaBe8lNn6afgKa',
                '81yazpHIGJCPKQ' =>	'FaBexWYwaitTew',
                '7y2tOBpciGUxKA' =>	'FaBfuhX4epaofT',
                '7xc78ePv15g3bz' =>	'FaBghFc4LrdShI',
                '7s3Je6PYgxT2s1' =>	'FaBhWvWUHWCULn',
                '7eNCPavacsWE5D' =>	'FaBjPRxE2l1NjC',
                '7NcC6RxVACi5K7' =>	'FaBkQiywSGGXL0',
                '70cLLZOrU1rda6' =>	'FaBl4BrK2sjfYq',
                '70cFKcUYGQ7z0b' =>	'FaBmZDTyhYFfX2',
                '6iSiMdFzj16vMz' =>	'FaBcIvVpHXwSRI',
                '6iSiKg3whz8vTD' =>	'FaBdHQx131SzsD',
                '6iSiLM8shHpTub' =>	'FaBeR4RciLLr0s',
                '6iSiL3rghEV5qm' =>	'FaBf642uym87EV',
                '6iSiKBFKiFewWp' =>	'FaBgAT22diKMlo',
                '6iSiKJJojOtOQl' =>	'FaBguK2nuCvJNI',
                '6iSiK02cEdsncf' =>	'FaBhiWz7gMvhkW',
                '6iSiKMoPSRYJ2o' =>	'FaBiWgj7VcwsY2',
                '6iSiK54y6I6K75' =>	'FaBjHMtZopcUPE',
                '6i9KXrnqHXFHk9' =>	'FaBkAsTsDtrlKG',
                '6aAnMAFmYpY8Ps' =>	'FaBmFOVrSxuon3',
                'F8rIlU86u40T5U' =>	'FaBo58vbIwuJbq',
                'FaaE8UTF0BkMjX' =>	'FZiLhQXkTuUkIi',
            ],
            'test' => [
                'Exelo4dBIBNb7w' =>	'FVW4076gpnontA',
                'EkMPag0vPhEoII' =>	'FaBSNbVAtgir4H',
                'EbDIK1BCsdChRO' =>	'FaBX2xyyaTDBL8',
                'E199S87u5emrhc' =>	'FaBXxh61agAieq',
                'D9OYLuzMqpixEN' =>	'FaBc8jORXUmwcC',
                'D9M7aRrlKklxeA' =>	'FaBdBwPaxBNINY',
                'CopOjZuuZlVJQF' =>	'FaBeG38aUsofbV',
                'C6RlMskzOd4P1f' =>	'FaBfuGVb8EsbtV',
                'Bxn1GzzaOXYiUH' =>	'FaBgrESHeEzwm2',
                'BoHfGJokmCajnV' =>	'FaBhcXv7cmBc8U',
                'Bn2WcETPmn44M4' =>	'FaBjQgiMnWOm1P',
                'Bn2TDNApLgprBN' =>	'FaBdKAJbVg5aZD',
                'BU3qfzAjT3xfI8' =>	'FaBeXgBm4eSgZH',
                'BOqaXQX7kGvZAw' =>	'FaBg1JTJTtVkjf',
                'BOqZw6mMPCiAZ6' =>	'FaBgvGhXj7IRvg',
                'BOqHxJ2begGv3h' =>	'FaBkZdShIHHyu7',
                'BNSX7DllPSd6FH' =>	'FaBlANZjw4DtcG',
                'BEEgsA9DoDOtMR' =>	'FaBmLIQJOs0KuG',
                'BEEgUzZZhUEEx0' =>	'FaBnf7c1vDcYzU',
                'B2j5vKmxqwkNsb' =>	'FaBpNBPetoVpaO',
                'B0MUVJul984k1k' =>	'FaBqDmtX8JyHjx',
                'AMrWBvk7AWHEb1' =>	'FaBqulIXo5OLCb',
                'AHeF0Ljio2Ertp' =>	'FaBrkSOAjH3ryZ',
                '9qP0GhZzHqJAJZ' =>	'FaBZt8Q4i5oAIj',
                '9gDcKNbZsdka2i' =>	'FaBbsPVSg8BFWB',
                '9WDh2pkY3h9HWX' =>	'FaqsdxLvy4rvkG',
                '9JBZK3HBwiECrd' =>	'FaBe8msbMVhw9J',
                '81yazpHIGJCPKQ' =>	'FaBexYbyF3VWLL',
                '7y2tOBpciGUxKA' =>	'FaBfuiZK7dgvKb',
                '7xc78ePv15g3bz' =>	'Far1aT7zlilIIk',
                '7s3Je6PYgxT2s1' =>	'FaBhxVgYQ4Nqnn',
                '7eNCPavacsWE5D' =>	'FaBjPTCsyVDDbr',
                '7NcC6RxVACi5K7' =>	'FaBkQhRTMyLswv',
                '70cLLZOrU1rda6' =>	'FaBl4A7wqB29QE',
                '70cFKcUYGQ7z0b' =>	'FaBmZ3XQon8BzW',
                '6iSiMdFzj16vMz' =>	'FaBcIzd6EBA6Ba',
                '6iSiKg3whz8vTD' =>	'Faqvn3ijaVS61H',
                '6iSiLM8shHpTub' =>	'FaBeR4dhqae2dz',
                '6iSiL3rghEV5qm' =>	'FaBf64a8QX5ACD',
                '6iSiKBFKiFewWp' =>	'FaBgAQ27WQAl0q',
                '6iSiKJJojOtOQl' =>	'FaBguLLEQUQ47I',
                '6iSiK02cEdsncf' =>	'FaBhiWyIvME9Hk',
                '6iSiKMoPSRYJ2o' =>	'FaBiWhCCCX788p',
                '6iSiK54y6I6K75' =>	'FaBjHMKYWAoxHG',
                '6i9KXrnqHXFHk9' =>	'FaBkAsiZBb5yAM',
                '6aAnMAFmYpY8Ps' =>	'FaBmFN7468bc0m',
                'F8rIlU86u40T5U' =>	'FaBo58ziGxpCpw',
                'FaaE8UTF0BkMjX' =>	'FZiI5V59gdLg3r',
            ]
        ];

        $methodOfPayments = [
            null,
            Payment\Method::EMANDATE,
            Payment\Method::EMI,
            Payment\Method::CARD,
            Payment\Method::UPI,
            Payment\Method::BANK_TRANSFER,
            Payment\Method::WALLET,
            Payment\Method::NETBANKING,
        ];

        foreach ($schedules as $schedule)
        {
            if ($schedule->schedule->getType() !== Type::SETTLEMENT)
            {
                continue;
            }

            if(array_key_exists($schedule['schedule_id'], $scheduleIdMapping[$mode]) === false)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_NO_MAPPING_PRESENT,
                    [
                       'merchant_id' => $merchant->getId(),
                       'schedule_id' => $schedule['schedule_id'],
                       'mode'       => $mode,
                    ]);

                continue;
            }

            $scheduleMethod = $schedule['method'];

            if(in_array($scheduleMethod, $methodOfPayments) === true)
            {
                if($schedule['international'] === 0)
                {
                    if($scheduleMethod === null)
                    {
                        $method = 'domestic:default';
                    }
                    else
                    {
                        $method = 'domestic:' . $scheduleMethod ;
                    }

                    $newSettlementSchedules['payment'][$method] = $scheduleIdMapping[$mode][$schedule['schedule_id']];
                }
                else
                {
                    if($scheduleMethod === null)
                    {
                        $method = 'international:default';
                    }
                    else
                    {
                        $method = 'international:' . $scheduleMethod ;
                    }

                    $newSettlementSchedules['payment'][$method] = $scheduleIdMapping[$mode][$schedule['schedule_id']];
                }
                continue;
            }

            // this is if the settlement_transfer_schedule is there then
            $newSettlementSchedules[$scheduleMethod]['default'] = $scheduleIdMapping[$mode][$schedule['schedule_id']];
        }

        return $newSettlementSchedules;
    }

    public function migrateConfigurations(array $input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MIGRATION_REQUEST,
            [
                'input' => $input
            ]);

        $response = [
            'total'         => count($input['merchant_ids']),
            'skipped_count' => 0,
            'failed_count'  => 0,
        ];

        foreach ($input['merchant_ids'] as $merchantId)
        {
            try
            {
                if ((new BucketModel\Core)->shouldProcessViaNewService($merchantId) === true)
                {
                    migration::dispatch($this->mode, $merchantId);
                }
                else
                {
                    $this->trace->info(
                        TraceCode::SETTLEMENT_SERVICE_MIGRATION_SKIPPED,
                        [
                            'merchant_id' => $merchantId,
                        ]);

                    $response['skipped_count'] += 1;
                }
            }
            catch (\Throwable $e)
            {
                $response['failed_count'] += 1;

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SETTLEMENT_SERVICE_QUEUE_DISPATCH_FAILED,
                    [
                        'merchant_id' => $merchantId
                    ]);
            }
        }

        return $response;
    }

    public function enqueueForReplay(array $input)
    {
        $opt = [
            'from'                => $input['from'] ?? null,
            'to'                  => $input['to'] ?? null,
            'balance_type'        => $input['balance_type'],
            'transaction_ids'     => $input['transaction_ids'] ?? [],
            'initial_ramp'        => $input['initial_ramp'] ?? false,
            'source_type'         => $input['source_type'] ?? null,
        ];

        foreach ($input['merchant_ids'] as $mid)
        {
            TransactionMigration::dispatch($this->mode, $mid, $opt);
        }
    }
}
