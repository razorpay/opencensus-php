<?php

namespace RZP\Models\Settlement;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
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
use RZP\Notifications\Settlement\Events;
use RZP\Models\Merchant as MerchantModel;
use RZP\Models\Settlement\Bucket\Preference;
use RZP\Models\Schedule\Task as scheduleTask;
use RZP\Models\Settlement\Bucket as BucketModel;
use RZP\Jobs\Transfers\TransferSettlementStatus;
use RZP\Models\Settlement\Details as SetlDetails;
use RZP\Jobs\Settlement\TransactionMigrationBatch;
use RZP\Mail\Merchant\SettlementBankAccountFailure;
use RZP\Mail\Merchant\SettlementsProcessedNotification;
use RZP\Notifications\Settlement\Handler as SettlementNotificationHandler;
use RZP\Models\Settlement\Constants as SettlementConstants;

class Core extends Base\Core
{
    const SETTLEMENT_DASHBOARD_URL = [
        'MY' => 'https://dashboard.curlec.com/app/settlements/%s',
        'IN' => 'https://dashboard.razorpay.com/app/settlements/%s'
    ];

    const DASHBOARD_URL = 'https://dashboard.razorpay.com/app/%s';

    const SETTLEMENT_PROCESSED_SMS_TEMPLATE = 'sms.settlements.processed';

    const SETTLEMENT_FAILED_SMS_TEMPLATE = 'sms.settlements.failed';

    const FTS = 'fts';
    const PAYOUT = 'payout';

    const DEFAULT_FTS_CHANNEL = Channel::ICICI;
    const DEFAULT_PAYOUT_CHANNEL = Channel::AXIS;

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
     * @param null $redactedBaNumber
     * @param bool $sendFailureSms
     * @param bool $sendFailureMail
     */
    public function triggerSettlementWebhook(Entity $settlement, $redactedBaNumber = null, $sendFailureSms = false, $sendFailureMail = false)
    {
        $this->updateSettlementStatusInTransfer($settlement);

        $this->triggerSettlementNotification($settlement, $redactedBaNumber, $sendFailureSms, $sendFailureMail);

        if ($this->shouldSendWebhook($settlement) === false)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $settlement
        ];

        $this->app['events']->dispatch('api.settlement.processed', $eventPayload);

    }

    /**
     * Sends an email to the merchant for processed settlement
     *
     * @param Entity $settlement
     * @param null $bankAccountNumber
     * @param null $merchant
     */
    public function triggerSettlementsMail(Entity $settlement, $bankAccountNumber = null, $merchant = null)
    {

        try
        {
            $setlDetails  = (new SetlDetails\Core)->getSettlementDetails($settlement->getId(), $merchant);
            $settlementTime = Carbon::createFromTimestamp(Carbon::now(Timezone::IST)->getTimestamp(), Timezone::IST)
                ->format('d/m/Y h:i A');

            $email = null;
            $txnReportEmail = null;
            $toMail = null;

            if ($merchant->isLinkedAccount() === true)
            {
                $email = $merchant->parent->getEmail()??'';
                $txnReportEmail = $merchant->parent->getTransactionReportEmail()??[];
            }
            else
            {
                $email = $merchant->getEmail()??'';
                $txnReportEmail = $merchant->getTransactionReportEmail()??[];
            }

            if($email!=='' && in_array($email,$txnReportEmail) === false)
            {
                $txnReportEmail[]=$email;
            }
            $toMail = $txnReportEmail;

            if(empty($toMail) === true)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                    [
                        'notification_mode' => 'email',
                        'reason'            => 'no email associated with merchant',
                        'merchant_id'       => $merchant->getId(),
                        'settlement_id'     => $settlement->getId(),
                        'status'            => $settlement->getStatus(),
                    ]);

                return;
            }

            $settlementMail = null;

            if ($settlement->isStatusProcessed() === true)
            {
                $data = [
                    'merchant' => [
                        MerchantModel\Entity::EMAIL      => $toMail,
                        MerchantModel\Entity::LOGO_URL   => $merchant->getLogoUrl(),
                        MerchantModel\Entity::COUNTRY_CODE => $settlement->merchant->getCountry(),
                    ],
                    'settlement' => [
                        'id'                      => $settlement->getPublicId(),
                        'amount'                  => $settlement->getAmount(),
                        'utr'                     => $settlement->getUtr(),
                        'breakup'                 => $setlDetails['setl_details'],
                        'has_aggregated_fee_tax'  => $setlDetails['has_aggregated_fee_tax'],
                        'ba_number'               => $bankAccountNumber,
                        'time'                    => $settlementTime,
                        'url'                     => sprintf(self::SETTLEMENT_DASHBOARD_URL[$settlement->merchant->getCountry()], $settlement->getPublicId()),
                    ],
                    'org_data' => SettlementConstants::ORG_DATA[$settlement->merchant->getCountry()]
                ];

                $settlementMail = new SettlementsProcessedNotification($data);
            }
            else if ($settlement->isStatusFailed() === true)
            {
                $data = [
                    'merchant' => [
                        MerchantModel\Entity::ID         => $settlement->getMerchantId(),
                        MerchantModel\Entity::EMAIL      => $toMail,
                        'profile_link'                   => sprintf(self::DASHBOARD_URL, "profile"),
                        'bank_account_update_link'       => sprintf(self::DASHBOARD_URL, "profile/update_bank_account"),
                    ],
                    'settlement' => [
                        'failure_reason'  => $settlement->getFailureReason(),
                        'ba_number'       => $bankAccountNumber,
                    ],
                ];

                $settlementMail = new SettlementBankAccountFailure($data);
            }

            if (empty($settlementMail) === false or $settlementMail !== "")
            {
                Mail::queue($settlementMail);

                $this->trace->info(
                    TraceCode::SETTLEMENT_MAIL_NOTIFICATION_ENQUEUED,
                    [
                        'merchant_id'   => $merchant->getId(),
                        'settlement_id' => $settlement->getId(),
                        'status'        => $settlement->getStatus(),
                    ]);
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_NOTIFICATION_FAILED,
                [
                    'notification_mode' => 'email',
                    'settlement_id'     => $settlement->getId(),
                    'merchant_id'       => $merchant->getId(),
                    'settlement_status' => $settlement->getStatus(),
                ]);
        }
    }

    /**
     * it is used to trigger the SMS on the status change of the settlements to failed or processed state
     * @param Entity $settlement
     * @param null $bankAccountNumber
     * @param null $merchant
     * @param bool $sendFailureSms
     */
    public function triggerSettlementsSms(Entity $settlement, $bankAccountNumber = null, $merchant = null, $sendFailureSms = false)
    {
        try
        {
            $notificationMerchant = ($merchant->isLinkedAccount() === true) ? $merchant->parent : $merchant;

            $status = (new Service)->getSettlementSmsNotificationStatus($notificationMerchant);

            if($status['enabled'] === false)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                    [
                        'notification_mode'  => 'sms',
                        'reason'             => 'merchant has settlement sms notify block feature',
                        'merchant_id'        => $merchant->getId(),
                        'settlement_id'      => $settlement->getId(),
                    ]);

                return;
            }

            if ($merchant->isLinkedAccount() === true)
            {
                $contactNo = $merchant->parent->merchantDetail->getContactMobile();
            }
            else
            {
                $contactNo = $merchant->merchantDetail->getContactMobile();
            }

            if (empty($contactNo) === true)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                    [
                        'notification_mode'  => 'sms',
                        'reason'             => 'no contact no associated with merchant account',
                        'merchant_id'        => $merchant->getId(),
                        'settlement_id'      => $settlement->getId(),
                    ]);

                return;
            }

            $request = [
                'receiver' => $contactNo,
                'source'   => 'api.'. $this->mode . '.settlements',
                'params'   => [
                    'merchant_id'     => $merchant->getId(),
                    'bank_account_id' => $bankAccountNumber,
                    'settlement_id'   => $settlement->getPublicId(),
                    'date'            => Carbon::now(timezone::IST)->format('j M Y, g A'),
                ]
            ];

            if ($sendFailureSms === false)
            {
                $request['template']         = self::SETTLEMENT_PROCESSED_SMS_TEMPLATE ;
                $request['params']['utr']    = $settlement->getUtr();
                $request['params']['amount'] = 'Rs.'.$settlement->getAmount()/100;
            }
            else if ($sendFailureSms === true)
            {
                $failureReason = $settlement->getRemarks();

                if (empty($failureReason) === true)
                {
                    $this->trace->info(
                        TraceCode::SETTLEMENT_NOTIFICATION_SKIPPED,
                        [
                            'notification_mode' => 'sms',
                            'merchant_id'       => $merchant->getId(),
                            'settlement_id'     => $settlement->getId(),
                            'reason'            => 'no failure reason provided',
                        ]);

                    return;
                }

                $request['template']                 = self::SETTLEMENT_FAILED_SMS_TEMPLATE;
                $request['params']['failure_reason'] = $failureReason;
            }

            $this->app['raven']->sendSms($request, true);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SETTLEMENT_NOTIFICATION_FAILED,
                [
                    'merchant_id'       => $merchant->getId(),
                    'settlement_id'     => $settlement->getId(),
                    'notification_mode' => 'sms',
                    'settlement_status' => $settlement->getStatus(),
                ]);
        }
    }
    /**
     * This will be used to trigger the settlement Notification via SMS and Email
     * @param Entity $settlement
     * @param null $redactedBaNumber
     * @param bool $sendFailureSms
     * @param bool $sendFailureMail
     */
    public function triggerSettlementNotification(Entity $settlement, $redactedBaNumber = null, $sendFailureSms = false, $sendFailureMail = false)
    {
        // do not trigger notification in test mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        try
        {
            $merchant = $settlement->merchant;

            $merchantId = $merchant->getId();

            if ($merchant->isLinkedAccount() === true)
            {
                $merchantId = $merchant->getParentId();
            }

            $variant = $this->app->razorx->getTreatment($merchantId,
                MerchantModel\RazorxTreatment::SETTLEMENT_NOTIFICATION_OPT_OUT,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                return;
            }

            $bankAccountNumber = ($settlement->bankAccount !== null) ?
                $settlement->bankAccount->getRedactedAccountNumber() : 'XXXX-XXXX-XXXX';

            if ($redactedBaNumber !== null)
            {
                $bankAccountNumber = $redactedBaNumber;
            }

            if ($settlement->isStatusProcessed() === true)
            {
                $this->triggerSettlementsMail($settlement, $bankAccountNumber, $merchant);

                $args = [
                    'settlement'          => $settlement,
                    'merchant'            => $merchant,
                    'bankAccountNumber'   => $bankAccountNumber
                ];

                (new SettlementNotificationHandler($args))->sendForEvent(Events::PROCESSED);
            }
            else if ($settlement->isStatusFailed() === true)
            {
                if ($sendFailureMail === true)
                {
                    $this->triggerSettlementsMail($settlement, $bankAccountNumber, $merchant);
                }

                if ($sendFailureSms === true)
                {
                    $args = [
                        'settlement'          => $settlement,
                        'merchant'            => $merchant,
                        'bankAccountNumber'   => $bankAccountNumber
                    ];

                    (new SettlementNotificationHandler($args))->sendForEvent(Events::FAILED);
                }
            }
            else
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_NOTIFICATION_INVALID_SETTLEMENT_STATUS,
                    [
                        'merchant_id'   => $merchant->getId(),
                        'settlement_id' => $settlement->getId(),
                        'status'        => $settlement->getStatus(),
                    ]);

            }

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SETTLEMENT_NOTIFICATION_FAILED,
                [
                    'merchant_id'       => $merchant->getId(),
                    'settlement_id'     => $settlement->getId(),
                ]);
        }
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

    public function MigrateMerchantConfiguration($merchantId, $via, $mode)
    {
        $merchant = $this->repo->merchant->fetchMerchantOnConnection($merchantId, $mode);

        $req = [
            'merchant_id' => $merchant->getId(),
        ];

        $featureResult = $this->repo
                              ->feature
                              ->findMerchantWithFeaturesOnConnection($merchant->getId(),
                                [
                                    Constants::BLOCK_SETTLEMENTS,
                                    Constants::ES_AUTOMATIC,
                                    Constants::ES_AUTOMATIC_THREE_PM,
                                ] , $mode);

        try
        {
            $response = app('settlements_api')->migrateMerchantConfigCreate($req, $mode);
        }
        catch (\Throwable $e){
            $response =  app('settlements_api')->merchantConfigGet($req, $mode);
        }

        $setSchedulesFromParentConfig = false;

        $parentMerchantID = $merchant->getParentId();

        if(empty($parentMerchantID) === false) {
            $setSchedulesFromParentConfig = true;
        }

        // if a parent is present use Parent's schedules
        if($setSchedulesFromParentConfig) {
            $parentReq = [
                'merchant_id' => $parentMerchantID
            ];
            try {
                $parentConfig = app('settlements_api')->merchantConfigGet($parentReq, $mode);
                $this->trace->debug(TraceCode::SETTING_SCHEDULES_FROM_PARENT, [
                    'merchant_id' => $merchant->getId(),
                    'parent_MID'  => $parentMerchantID,
                    'schedules'   => $parentConfig['config']['schedules'],
                    'mode'        => $mode
                ]);
                $response['config']['schedules'] = $parentConfig['config']['schedules'];
            } catch (\Throwable $e) {
                $this->trace->info(
                    TraceCode::FAILED_TO_FETCH_PARENT_MERCHANT_CONFIG,
                    [
                        'merchant_id' => $merchant->getId(),
                        'parent_id' => $parentMerchantID,
                        'mode' => $mode,
                    ]);
                throw new Exception\LogicException(SettlementServiceMigration::FAILED_TO_FETCH_PARENT_CONFIG);
            }

        } else {
            $scheduleMapping = $this->getScheduleMappingForMethodNewService($merchant, $mode, $featureResult);
            foreach ($scheduleMapping as $type => $methods) {
                foreach ($methods as $method => $scheduleId) {
                    $response['config']['schedules'][$type][$method] = $scheduleId;
                }
            }
        }

        unset($response['config']['active']);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_DEFAULT_CREATE_SUCCESS,
            [
                'merchant_id' => $merchant->getId(),
                'response'    => $response,
                'mode'        => $mode,
            ]);

        if (in_array(Constants::BLOCK_SETTLEMENTS, $featureResult) === true)
        {
            $response['config']['features']['block']['status'] = true;
            $response['config']['features']['block']['reason'] = 'merchants has blocked settlement feature';
        }

        if (in_array($merchant->getId(), Preferences::NO_SETTLEMENT_MIDS, true) === true)
        {
            $response['config']['features']['block']['status'] = true;
            $response['config']['features']['block']['reason'] = 'merchants opted out on settlement';
        }

        $payoutSupportedChannels = [
            Channel::AXIS,
            Channel::RBL,
        ];

        $ftsSupportedChannels = [
            Channel::ICICI,
        ];

        if($via === self::FTS)
        {
            $response['config']['preferences']['channel'] = (in_array($merchant->getChannel(), $ftsSupportedChannels) === true) ? strtoupper($merchant->getChannel()) : strtoupper(self::DEFAULT_FTS_CHANNEL);
        }

        if($via === self::PAYOUT)
        {
            $response['config']['preferences']['channel'] = (in_array($merchant->getChannel(), $payoutSupportedChannels) === true) ? strtoupper($merchant->getChannel()) : strtoupper(self::DEFAULT_PAYOUT_CHANNEL);
        }

        if (in_array($merchant->getId(), Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $response['config']['preferences']['mode'] = 'NEFT';
        }

        if (in_array($merchant->getParentId(), Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $response['config']['preferences']['mode'] = 'NEFT';
        }

        $destinationMerchantId= (new MerchantModel\Core())->fetchAggregateSettlementForNSSParent($merchant->getId());

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_PARENT_DETAILS,
            [
                'merchant'          => $merchant->getId(),
                'destination Mid'   => $destinationMerchantId,
                'message'           => 'partner details for submerchant',
            ]);

        $isAggregateSettlement = (bool) $destinationMerchantId;

        if ($isAggregateSettlement === true)
        {
            $response['config']['types']['aggregate']['enable'] = true;
            $response['config']['types']['aggregate']['settle_to'] = $destinationMerchantId;
            $response['config']['types']['default']['enable'] = false;
        }

        if((in_array($merchant->getId(), SettlementServiceMigration::MIGRATION_BLACKLISTED_MIDS_TO_AXIS3) === true) ||
           (in_array($merchant->getParentId(), SettlementServiceMigration::MIGRATION_BLACKLISTED_MIDS_TO_AXIS3) === true))
        {
            $response['config']['preferences']['channel']='AXIS3';
        }

        $request = array_merge($req, $response);

        try
        {
            $hasCustomSettlmentEnabled = $merchant->org->isFeatureEnabled(Constants::ORG_CUSTOM_SETTLEMENT_CONF);

            if ($hasCustomSettlmentEnabled === true)
            {
                $orgId = $merchant->getOrgId();

                $orgConfigReq['org_id'] = $orgId;

                $response = app('settlements_api')->orgConfigGet($orgConfigReq, $mode);

                $this->trace->info(
                    TraceCode::SETTLEMENT_SERVICE_FETCH_ORG_CONFIG_SUCCESS,
                    [
                        'org' => $orgId,
                        'request' => $orgConfigReq,
                        'mode' => $mode,
                        'response' => $response,
                    ]);

                if ((isset($response['config']) === true) && empty($response['config']['schedules'] === false)) {
                    $orgSchedules = $response['config']['schedules'];

                    $request['config']['schedules'] = $orgSchedules;
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_FETCH_ORG_CONFIG_FAILED
            );
        }

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

    public function updateMerchantSchedule($merchantId, $mode)
    {
        $merchant = $this->repo->merchant->fetchMerchantOnConnection($merchantId, $mode);

        $req = [
            'merchant_id' => $merchant->getId(),
        ];

        $featureResult = $this->repo
                              ->feature
                              ->findMerchantWithFeaturesOnConnection($merchant->getId(),
                                [
                                    Constants::BLOCK_SETTLEMENTS,
                                    Constants::ES_AUTOMATIC,
                                    Constants::ES_AUTOMATIC_THREE_PM,
                                ] , $mode);

        $scheduleMapping = $this->getScheduleMappingForMethodNewService($merchant, $mode, $featureResult);

        $response =  app('settlements_api')->merchantConfigGet($req, $mode);

        unset($response['config']['active']);

        foreach ($scheduleMapping as $type => $methods)
        {
            foreach ($methods as $method => $scheduleId)
            {
                $response['config']['schedules'][$type][$method] = $scheduleId;
            }
        }

        $request = array_merge($req, $response);

        $result = app('settlements_api')->migrateMerchantConfigUpdate($request, $mode);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_UPDATE_SUCCESS,
            [
                'merchant' => $merchant->getId(),
                'request'  => $request,
                'mode'     => $mode,
                'result'   => $result,
            ]);
    }

    public function getScheduleMappingForMethodNewService($merchant, $mode, $features)
    {
        $newSettlementSchedules = array();

        $schedules = $this->repo
                          ->schedule_task
                          ->fetchByMerchantOnConnection($merchant, scheduleTask\Type::SETTLEMENT, $mode);

        $scheduleIdMapping = SettlementServiceMigration::scheduleIdMapping;

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

            if(array_key_exists($schedule[scheduleTask\Entity::SCHEDULE_ID], $scheduleIdMapping[$mode]) === false)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_SERVICE_MC_MIGRATION_NO_MAPPING_PRESENT,
                    [
                       'merchant_id' => $merchant->getId(),
                       'schedule_id' => $schedule['schedule_id'],
                       'mode'        => $mode,
                    ]);

                throw new Exception\LogicException(SettlementServiceMigration::NO_SCHEDULE_MAPPING_PRESENT);
            }

            $scheduleMethod = $schedule[scheduleTask\Entity::METHOD];

            if(in_array($scheduleMethod, $methodOfPayments) === true)
            {
                if($schedule[scheduleTask\Entity::INTERNATIONAL] === 0)
                {
                    if($scheduleMethod === null)
                    {
                        $method = SettlementServiceMigration::PREFIX_DOMESTIC . SettlementServiceMigration::DEFAULT_CONST;
                    }
                    else
                    {
                        $method = SettlementServiceMigration::PREFIX_DOMESTIC . $scheduleMethod ;
                    }

                    list($status, $mapSchedule) = $this->getScheduleIdForSpecificMIds($mode, $features, $merchant, SettlementServiceMigration::DOMESTIC);

                    if ($status === false)
                    {
                        $mapSchedule = $scheduleIdMapping[$mode][$schedule[scheduleTask\Entity::SCHEDULE_ID]];
                    }

                    $newSettlementSchedules['payment'][$method] = $mapSchedule;
                }
                else
                {
                    if($scheduleMethod === null)
                    {
                        $method =  SettlementServiceMigration::PREFIX_INTERNATIONAL. SettlementServiceMigration::DEFAULT_CONST;
                    }
                    else
                    {
                        $method = SettlementServiceMigration::PREFIX_INTERNATIONAL . $scheduleMethod ;
                    }

                    list($status, $mapSchedule) = $this->getScheduleIdForSpecificMIds($mode, $features, $merchant, scheduleTask\Entity::INTERNATIONAL);

                    if ($status === false)
                    {
                        $mapSchedule = $scheduleIdMapping[$mode][$schedule[scheduleTask\Entity::SCHEDULE_ID]];
                    }

                    $newSettlementSchedules['payment'][$method] = $mapSchedule;
                }
                continue;
            }

            // this is if the settlement_transfer_schedule is there then
            $newSettlementSchedules[$scheduleMethod][SettlementServiceMigration::DEFAULT_CONST] =
                $scheduleIdMapping[$mode][$schedule[scheduleTask\Entity::SCHEDULE_ID]];
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
            'failed_count'  => 0,
        ];

        foreach ($input['merchant_ids'] as $merchantId)
        {
            try
            {
                migration::dispatch(
                    $this->mode,
                    $merchantId,
                    $input['migrate_bank_account'],
                    $input['migrate_merchant_config'],
                    $input['via']);
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

    public function migrateBlockedTransactions(array $input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_BLOCK_TXN_MIGRATE_REQUEST,
            [
                'input' => $input
            ]);

        $response = [
            'total' => count($input['merchant_ids']),
            'failed_count_live' => 0,
            'failed_count_test' => 0
        ];

        foreach ($input['merchant_ids'] as $merchantId) {
            $startTime = microtime(true);
            // check for balance type- Only Primary and Commision

            $balances = $this->repo->balance->getMerchantBalances($merchantId);

            foreach ($balances as $balance)
            {
                if (Balance\Type::isSettleableBalanceType($balance->getType()) === true) {
                    $opt = [
                        'from' => $input['from'],
                        'to' => $input['to'],
                        'balance_type' => $balance->getType(),
                        'transaction_ids' => [],
                        'initial_ramp' => true,
                        'source_type' => null,
                    ];

                    //$migrationResult this will store the migration results for a merchant in this job
                    $migrationResult = [
                        'SUCCESSFUL_STEPS' => [
                            Mode::LIVE => [
                                'TRANSACTION_MIGRATION_DISPATCH' => false,
                            ],
                            Mode::TEST => [
                                'TRANSACTION_MIGRATION_DISPATCH' => false,
                            ],
                        ],
                        'FAILED_STEPS' => [
                            Mode::LIVE => [
                                'TRANSACTION_MIGRATION_DISPATCH' => [
                                    'status' => false,
                                    'reason' => null,
                                ],
                            ],
                            Mode::TEST => [
                                'TRANSACTION_MIGRATION_DISPATCH' => [
                                    'status' => false,
                                    'reason' => null,
                                ],
                            ],
                        ],
                    ];

                    $isFailure = false;
                    try {
                        TransactionMigrationBatch::dispatch(Mode::LIVE, $merchantId, $opt);

                        $migrationResult['SUCCESSFUL_STEPS'][Mode::LIVE]['TRANSACTION_MIGRATION_DISPATCH'] = true;
                    } catch (\Throwable $e) {
                        $response['failed_count_live'] += 1;
                        $isFailure = true;
                        $migrationResult['FAILED_STEPS'][Mode::LIVE]['TRANSACTION_MIGRATION_DISPATCH']['status'] = true;
                        $migrationResult['FAILED_STEPS'][Mode::LIVE]['TRANSACTION_MIGRATION_DISPATCH']['reason'] = $e->getMessage();
                    }

                    try {
                        TransactionMigrationBatch::dispatch(Mode::TEST, $merchantId, $opt);

                        $migrationResult['SUCCESSFUL_STEPS'][Mode::TEST]['TRANSACTION_MIGRATION_DISPATCH'] = true;
                    } catch (\Throwable $e) {
                        $response['failed_count_test'] += 1;
                        $isFailure = true;
                        $migrationResult['FAILED_STEPS'][Mode::TEST]['TRANSACTION_MIGRATION_DISPATCH']['status'] = true;
                        $migrationResult['FAILED_STEPS'][Mode::TEST]['TRANSACTION_MIGRATION_DISPATCH']['reason'] = $e->getMessage();
                    }
                    $this->trace->info(
                        TraceCode::SETTLEMENT_SERVICE_MIGRATION_RESULT,
                        [
                            'is_failure'  => $isFailure,
                            'merchant_id' => $merchantId,
                            'input'       => $input,
                            'result'      => $migrationResult,
                            'time_taken'  => microtime(true) - $startTime,
                        ]);
            }
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
            TransactionMigrationBatch::dispatch($this->mode, $mid, $opt);
        }
    }


    function getScheduleIdForSpecificMIds($mode, $features, $merchant, $state)
    {
        //if the merchant is on hourly settlement and the feature is
        // ES_Automatic or ES_AUTO_3PM then assign him specific schedule
        if (in_array(Constants::ES_AUTOMATIC, $features) === true)
        {
            if (in_array(Constants::ES_AUTOMATIC_THREE_PM, $features) === true)
            {
                return [true, SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode][Constants::ES_AUTOMATIC_THREE_PM]];
            }

            return [true, SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode][Constants::ES_AUTOMATIC]];
        }


        if (array_key_exists($merchant->getId(), SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode]) === true)
        {
            return [true, SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode][$merchant->getId()]];
        }

        $parent = $merchant->getParentId();

        if (array_key_exists($parent, SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode]) === true)
        {
            return [true, SettlementServiceMigration::MID_SPECIFIC_MAPPINGS[$state][$mode][$parent]];
        }

        return [false, ''];
    }

    protected function updateSettlementStatusInTransfer(Entity $settlement)
    {
        if (($settlement->merchant->isLinkedAccount() === true)
            and ($settlement->getStatus() === Status::PROCESSED))
        {
            TransferSettlementStatus::dispatch($this->mode, $settlement->getId())->delay(
                TransferSettlementStatus::DISPATCH_DELAY_SECONDS);
        }
    }
}
