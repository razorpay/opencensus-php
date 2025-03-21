<?php

namespace RZP\Models\FeeRecovery;

use Carbon\Carbon;

use RZP\Exception\InvalidArgumentException;
use RZP\Exception\LogicException;
use RZP\Jobs;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Task;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Service;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Settlement\SlackNotification;

class Core extends Base\Core
{
    const BATCH_SIZE = 50000;
    const OVERRIDDEN_BATCH_SIZE = 5000;

    const BULK_INSERT_SIZE = 1000;

    const INITIATE = 'initiate';

    const INTERMEDIATE = 'intermediate';

    const FETCH = 'fetch';

    const INSERT = 'insert';

    const UPDATE = 'update';

    const OLD_IFSC_FOR_YBL_RZP_FEES = 'HDFC0000053';

    /** @var \RZP\Services\Mutex $mutex */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Creates an entry into the fee_recovery table corresponding to a Payout/Reversal.
     * This function gets invoked at payout initiation and reversal creation.
     * skipDedupe is true for debit fee recovery entry and calling function ensures that the debit entry creation is called only once
     * @param PublicEntity $entity
     * @param bool $skipDedupe
     * @return Entity
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function createFeeRecoveryEntityForSource(Base\PublicEntity $entity, bool $skipDedupe = false)
    {
        Validator::validateSourceEntity($entity);
        $type = (new Type)->getTypeFromSourceEntity($entity);
        return $this->createFeeRecoveryEntityForSourceAndType($entity, $type, $skipDedupe);
    }

    /**
     * Creates an entry into the fee_recovery table corresponding to a Payout/Reversal.
     * This function gets invoked at payout initiation and reversal creation.
     * skipDedupe is true for debit fee recovery entry and calling function ensures that the debit entry creation is called only once
     * @param PublicEntity $entity
     * @param string $type
     * @param bool $skipDedupe
     * @return Entity
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    public function createFeeRecoveryEntityForSourceAndType(Base\PublicEntity $entity, string $type, bool $skipDedupe = false)
    {
        Validator::validateSourceEntity($entity);

        return $this->mutex->acquireAndRelease(
            'fee_recovery_' . $entity->getId(),
            function () use ($entity, $type, $skipDedupe)
            {
                $feeRecoveryEntity = (new Entity)->build();

                $feeRecoveryEntity->setType($type);

                $feeRecoveryEntity->setStatus(Status::UNRECOVERED);

                $feeRecoveryEntity->entity()->associate($entity);

                $feeRecoveryEntity->setIgnoreRelations();

                if ($skipDedupe === false)
                {
                    [$skipCreation, $existingFeeRecoveryEntity] = $this->skipIfExistingFeeRecoveryDataExists($feeRecoveryEntity);

                    if ($skipCreation === true)
                    {
                        return $existingFeeRecoveryEntity;
                    }
                }

                $this->repo->saveOrFail($feeRecoveryEntity);

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_ENTITY_CREATED,
                    [
                        'source_id'       => $entity->getId(),
                        'source_type'     => $entity->getEntityName(),
                        'fee_recovery_id' => $feeRecoveryEntity->getId()
                    ]);

                return $feeRecoveryEntity;
            },
            60,
            ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function recoverFeeRecoveryEntryViaFeeCredit($payout, $feeRecovery, $reversalId = null)
    {
        try
        {
            $this->repo->transaction(
                function () use($payout, $feeRecovery)
                {
                    (new Merchant\Credits\Transaction\Core())->reverseCreditsForSource($payout->getId(),
                        Entity::PAYOUT,
                        $payout);

                    $this->updateFeeRecoveryStatusForFeeCredit($feeRecovery, Status::RECOVERED);
                });
        }
        catch(\throwable $ex)
        {
            $this->trace->error(TraceCode::FEE_RECOVERY_REVERSE_CREDITS_FAILED, [
                'reverse_credits_error_message' => $ex->getMessage(),
                'entity_id'                     => $reversalId === null ? $payout->getId() : $reversalId,
                'fees'                          => $payout->getFees(),
                'entity_type'                   => $reversalId === null ? Entity::PAYOUT : Entity::REVERSAL,
            ]);
        }
    }

    /**
     * This function is called every time a payout changes status.
     * It creates a new entry, in case a payout failed/reversed
     * It also updates all the corresponding entries if the state change occurred for a 'rzp_fees' payout
     *
     * @param Payout\Entity        $payout
     * @param $previousStatus
     * @param Reversal\Entity|null $reversal
     */
    public function handlePayoutStatusUpdate(Payout\Entity $payout,
                                             $previousStatus = null,
                                             Reversal\Entity $reversal = null)
    {
        if (($payout->getFeeType() !== null) and
            ($payout->getFeeType() === CreditType::REWARD_FEE))
        {
            return;
        }

        if ($payout->getPurpose() === Purpose::RZP_CHARGE_COLLECTIONS) {
            return;
        }

        $payoutStatus = $payout->getStatus();

        // We shall make a new entry in the fee_recovery table of type credit when payout status is either failed
        // or reversed, and initiated_at is not null
        if ($payoutStatus === Payout\Status::FAILED)
        {
            if($payout->getInitiatedAt() !== null){
                $feeRecovery = $this->createFeeRecoveryEntityForSource($payout);

                $featureEnabled = (new \RZP\Models\Merchant\Credits\Service())->isRzpxFeeCreditEnabledForMerchant($payout->merchant);

                if($featureEnabled === true and $payout->getFeeType() === null)
                {
                    $this->recoverFeeRecoveryEntryViaFeeCredit($payout, $feeRecovery);
                }
            }
            else{
                $this->trace->info(
                    TraceCode::FEE_RECOVERY_NON_INITIATED_FAILED_PAYOUT,
                    [
                        'payout_id'        => $payout->getId(),

                    ]);
            }
        }
        else if ($payoutStatus === Payout\Status::REVERSED)
        {
            $feeRecovery = $this->createFeeRecoveryEntityForSource($reversal);

            $featureEnabled = (new \RZP\Models\Merchant\Credits\Service())->isRzpxFeeCreditEnabledForMerchant($payout->merchant);

            if($featureEnabled === true and $payout->getFeeType() === null)
            {
                $this->recoverFeeRecoveryEntryViaFeeCredit($payout, $feeRecovery, $reversal->getId());
            }
        }

        // If the payout is a fee_recovery payout, we need to update all the fee_recovery entries
        // corresponding to this fee_recovery payout
        if ($payout->getPurpose() === Payout\Purpose::RZP_FEES)
        {
            $feeRecoveryPayoutId = $payout->getId();

            $feeRecoveryStatus = Status::getFeeRecoveryStatusFromPayoutStatus($payoutStatus);

            $this->repo->fee_recovery->updateFeeRecoveryOnPayoutStatusUpdate($feeRecoveryPayoutId,
                                                                             $feeRecoveryStatus);

            $payoutStatusFailedForInitiatedPayout = (($payoutStatus === Payout\Status::FAILED) and
                                                     ($previousStatus === Payout\Status::INITIATED));

            $payoutStatusReversedForInitiatedOrProcessedPayout = (($payoutStatus === Payout\Status::REVERSED) and
                                                                  (($previousStatus === Payout\Status::INITIATED) or
                                                                   ($previousStatus === Payout\Status::PROCESSED)));

            if ($payoutStatusFailedForInitiatedPayout or
                $payoutStatusReversedForInitiatedOrProcessedPayout)
            {
                Jobs\FeeRecovery::dispatch($this->mode, $feeRecoveryPayoutId);
            }

            $this->trace->info(
                TraceCode::FEE_RECOVERY_UPDATE_AFTER_RZP_FEES_PAYOUT_STATUS_UPDATE,
                [
                    'fee_recovery_payout_id'        => $feeRecoveryPayoutId,
                    'fee_recovery_status'           => $feeRecoveryStatus,
                    'fee_recovery_payout_status'    => $payout->getStatus(),
                ]);
        }
    }

    /**
     * This function picks up all payouts, failed payouts and reversals between a certain period,
     * calculates fees that needs to be recovered for these entities (positive for debit, negative for credit)
     * and makes a payout to a designated rzp_fees fund account with the calculated amount
     *
     * @param array $input
     *
     * @return Payout\Entity
     *
     * @throws Exception\BadRequestException
     */
    public function createFeeRecoveryPayout(array $input)
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_INITIATED,
            [
                'input' => $input,
            ]);

        (new Validator)->validateInput(Validator::CREATE_FEE_RECOVERY_PAYOUT, $input);

        $balanceId = $input[Entity::BALANCE_ID];

        $startTimeStamp = $input[Entity::FROM];

        $endTimeStamp = $input[Entity::TO];

        $balance = $this->repo->balance->findOrFailById($balanceId);

        (new Validator)->validateBalanceTypeAndTimeStamps($balance, $startTimeStamp, $endTimeStamp);

        $response = $this->processFeeRecovery($balance, $startTimeStamp, $endTimeStamp);

        return $response;
    }

    /**
     * This function picks up all payouts, failed payouts and reversals from a failed recovery payout Id,
     * and recreate them in fee recovery
     * and makes a payout to a designated rzp_fees fund account with the calculated amount
     *
     * @param $previousRecoveryPayoutId
     * @param bool $manualRetry
     * @return Payout\Entity
     */
    public function recreateFeeRecoveryPayout($previousRecoveryPayoutId, $manualRetry = false)
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_RETRY_INITIATED,
            [
                Entity::PREVIOUS_RECOVERY_PAYOUT_ID => $previousRecoveryPayoutId,
            ]
        );

        $previousRecoveryPayout = $this->repo->payout->findOrFail($previousRecoveryPayoutId);

        $balance = $previousRecoveryPayout->balance;

        $amount = $previousRecoveryPayout->getAmount();

        return $this->recreateFeeRecoveryEntityForSourceAndFeeRecoveryPayout($previousRecoveryPayout, $balance, $amount, $manualRetry);
    }

    public function recreateFeeRecoveryEntityForSourceAndFeeRecoveryPayout(Payout\Entity $previousRecoveryPayout, $balance, $amount, $manualRetry = false)
    {
        $previousFeeRecoveryEntities = $this->repo->fee_recovery->getFeeRecoveryByRecoveryPayoutId($previousRecoveryPayout->getId(), $manualRetry);

        if (count($previousFeeRecoveryEntities) === 0)
        {
            // This slack notification is required to notify for manual recovery alert.
            $operation = 'Fee Recovery Retry failed';

            if ($manualRetry == false)
            {
                $data = [
                    'fee_recovery_payout_id' => $previousRecoveryPayout->getId()
                ];

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_RETRY_FAILED_PROCEED_MANUAL,
                    $data
                );

                (new SlackNotification)->send($operation, $data, null, 1, 'rx_ca_rbl_alerts');

                return null;
            }
            else
            {
                return [
                    'message' => $operation
                ];
            }
        }

        return $this->repo->transaction(
            function () use($previousFeeRecoveryEntities, $balance, $amount, $previousRecoveryPayout)
            {
                $merchant = $balance->merchant;

                $payoutPayload = $this->getPayloadForFeeRecoveryPayout($balance, $amount);

                $this->trace->info(
                                   TraceCode::FEE_RECOVERY_PAYOUT_CREATE_REQUEST,
                                   [
                                       'payload' => $payoutPayload
                                   ]
                );

                $newFeeRecoveryPayout = (new Payout\Core)->createPayoutToFundAccount($payoutPayload,
                                                                                     $merchant,
                                                                                     null,
                                                                                     true);

                $this->trace->info(
                                   TraceCode::FEE_RECOVERY_PAYOUT_CREATED,
                                   [
                                       'fee_recovery_payout_id'     =>  $newFeeRecoveryPayout->getPublicId(),
                                       'fee_recovery_payout_amount' =>  $newFeeRecoveryPayout->getAmount()
                                   ]
                );
                $this->mutex->acquireAndRelease(
                    'recreate_fee_recovery_' . $previousRecoveryPayout->getId(),
                    function () use ($previousFeeRecoveryEntities, $newFeeRecoveryPayout)
                    {
                        $newFeeRecoveryEntities = $this->getNewFeeRecoveryEntities($previousFeeRecoveryEntities, $newFeeRecoveryPayout);

                        $this->insertBulkFeeRecoveryEntitiesViaBatching($newFeeRecoveryEntities);
                        $this->updateBulkStatusViaBatching($previousFeeRecoveryEntities, Status::FAILED);
                    },
                    600,
                    ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);

                return $newFeeRecoveryPayout;
            });
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function recoveryPayoutCron(array $input)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $pendingTasks = $this->repo->schedule_task->fetchDueScheduleTasks(Task\Type::FEE_RECOVERY, $currentTimeStamp);

        foreach ($pendingTasks as $task)
        {
            $balanceId = $task->getEntityId();

            $balance = $this->repo->balance->find($balanceId);

            //
            // We are going to run the fee recovery payout for payouts created between lastRunAt and nextRunAt of a
            // schedule task.
            //
            // IMPORTANT : If the task runs 23 min post it's current nextRunAt, lastRunAt is still updated to
            // the current value of nextRunAt. Hence, we don't have to consider any actual delay that happen
            // when we run the cron.
            //
            // Also, we have manually added 1 here because the query is inclusive on both ends. The same route is also
            // called via admin auth, and keeping the timestamps inclusive makes it less prone to human error.
            //
            $lastRunAt = ($task->getLastRunAt() + 1) ?? $balance->getCreatedAt();
            $nextRunAt = $task->getNextRunAt();

            Jobs\FeeRecovery::dispatch($this->mode, null, $balanceId, $lastRunAt, $nextRunAt, $task);
        }

        return ['success' => true];
    }

    public function updateNextRunAndLastRunForFeeRecoveryTasks(Task\Entity $task)
    {
        // compute nextRunAt and lastRunAt based on custom logic
        $nextRunAt = $task->getNextRunAt();
        $task->setLastRunAt($nextRunAt); // +1 is done in the job execution

        $refTime = Carbon::createFromTimestamp($nextRunAt, Timezone::IST);

        [$startTime, $endTime] = $this->getNextInterval($refTime);
        $task->setNextRunAt($endTime->timestamp);
    }

    public function updateNextRunAtForNegativeFees(Task\Entity $task, string $balanceId)
    {
        $balance = $this->repo->balance->findOrFailById($balanceId);

        $lastRunAt = $task->getLastRunAt();

        $nextRunAt = $task->getNextRunAt();

        $currentTimeStamp = Carbon::now(Timezone::IST)->timestamp;

        do
        {
            $refTime = Carbon::createFromTimestamp($nextRunAt, Timezone::IST);

            $nextRunAt = $refTime->copy()->addDay()->timestamp;

            list ($payouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecovery($balance,
                $lastRunAt + 1,
                $nextRunAt);

            $amount = $this->getFeesForFeeRecovery($payouts, $failedPayouts, $reversals);
        }
        while($nextRunAt < $currentTimeStamp and $amount < 0);

        $task->setNextRunAt($nextRunAt);

        $task->saveOrFail();
    }

    // refTime will be the current nextRunAt - end time of the current interval
    public function getNextInterval(Carbon $refTime)
    {
        $refTime = $refTime->copy();

        $startTime = $refTime->copy();

        $startTime = $refTime->copy()->addSeconds(1);

        $endTime = $startTime->copy()->endOfDay();

        return [$startTime, $endTime];
    }

    protected function skipIfExistingFeeRecoveryDataExists(Entity $feeRecovery)
    {
        /** @var Base\PublicEntity $source */
        $source = $feeRecovery->entity;

        $params = [
            Entity::ENTITY_ID       => $source->getId(),
            Entity::TYPE            => $feeRecovery->getType(),
        ];

        $this->repo->fee_recovery->setMerchantIdRequiredForMultipleFetch(false);

        $existingData = $this->repo->fee_recovery->fetch($params);

        if ($existingData->count() > 1)
        {
            $errorData = [
                'source_id'      => $source->getId(),
                'source_type'    => $source->getEntityName(),
                'count'          => $existingData->count(),
            ];

            $errorMessage = 'More than one entry in fee_recovery for given source entity';

            $this->sendSlackAlert($errorMessage, $errorData);

            throw new Exception\LogicException($errorMessage,
                                               ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_DUPLICATE_DATA,
                                               $errorData);
        }

        if ($existingData->count() === 1)
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_FOR_GIVEN_ENTITY_ALREADY_EXISTS,
                [
                    'source_id'                 => $feeRecovery->getEntityId(),
                    'existing_fee_recovery_id'  => $existingData->first->getEntityId()
                ]);

            return [true, $existingData->first];
        }

        // In case of reversals, we also need to check for existing credit entry for a payout.
        // This is because we are now allowing transition of payout status from FAILED -> REVERSED
        // In case of failed payouts, we already have a credit entry corresponding to this failed payout
        // When the payout gets reversed, we shall not create another credit entry and will simply skip it
        if ($feeRecovery->getEntityType() === Entity::REVERSAL)
        {
            $fetchParams = [
                Entity::ENTITY_ID => $feeRecovery->reversal->getEntityId(),
                Entity::TYPE      => Type::CREDIT
            ];

            $existingEntry = $this->repo->fee_recovery->fetch($fetchParams);

            if($existingEntry->count() > 0)
            {
                $this->trace->info(
                    TraceCode::FEE_RECOVERY_FAILED_PAYOUT_TO_REVERSAL,
                    [
                        'reversal_id' => $feeRecovery->getEntityId()
                    ]);

                return [true, $existingData->first];
            }
        }

        return [false, null];
    }

    protected function processFeeRecovery(Balance\Entity $balance,
                                                 int $startTimestamp,
                                                 int $endTimestamp)
    {
        $response = $this->mutex->acquireAndRelease(
            'process_fee_recovery_' . $balance->getId(),
            function() use ($balance, $startTimestamp, $endTimestamp)
        {
            list ($payouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecovery($balance,
                                                                                                            $startTimestamp,
                                                                                                            $endTimestamp);

            $amount = $this->getFeesForFeeRecovery($payouts, $failedPayouts, $reversals);

            if ($amount < 0)
            {
                $errorData = [
                    'balance_id'            => $balance->getId(),
                    'start_timestamp'       => $startTimestamp,
                    'end_timestamp'         => $endTimestamp,
                    'fee_recovery_amount'   => $amount
                ];

                $errorMessage = 'Amount is insufficient to make a fee recovery payout';

                $this->sendSlackAlert($errorMessage, $errorData);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FEE_RECOVERY_AMOUNT_INSUFFICIENT,
                    null,
                    $errorData
                    );
            }
            if ($amount == 0)
            {
                return [
                    'message'  => "The total amount to be recovered is zero and hence we are not creating a fee recovery payout for the current week"
                ];
            }

            $feeRecoveryPayout =  $this->processAndGetFeeRecoveryPayout($payouts,
                                                                        $failedPayouts,
                                                                        $reversals,
                                                                        $balance,
                                                                        $amount);

            return $feeRecoveryPayout->toArrayPublic();

        },
        600,
        ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);

        return $response;
    }

    protected function getPayoutAndReversalEntitiesForFeeRecovery(Balance\Entity $balance,
                                                                  int $startTimestamp,
                                                                  int $endTimestamp)
    {
        $merchant = $balance->merchant;
        $merchantId = $merchant->getId();
        $balanceId = $balance->getId();

        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUTS_AND_REVERSALS_FETCH_INITIATED,
            [
                'merchant_id'   => $merchantId,
                'balance_id'    => $balanceId,
                'start_time'    => $startTimestamp,
                'end_time'      => $endTimestamp
            ]);

        // TODO : Add a limit to make sure that these fetch statements don't choke the network

        $properties = [
            'id' => $balanceId,
            'experiment_id' => 'fee_recovery_datalake_migration',
            'request_data'  => json_encode(['balance_id' => $balanceId]),
        ];

        $expResult = $this->isSplitzExperimentEnable($properties, 'enabled');

        if ($expResult === true)
        {
            $formatStringPayouts = "select p.id, p.fees from realtime_hudi_api.payouts p where p.merchant_id='%s' and p.balance_id='%s' and p.initiated_at is not null and p.initiated_at between %d and %d and coalesce(p.fee_type, '') != 'reward_fee'";
            $queryPayouts = sprintf($formatStringPayouts, $merchantId, $balanceId, $startTimestamp, $endTimestamp);
            $payoutsArray = $this->app['datalake.presto']->getDataFromDatalake($queryPayouts);
            $payouts = $this->getPublicCollectionFromArrayWithType($payoutsArray, 'payout');

            $formatStringFailedPayouts = "select id, fees from realtime_hudi_api.payouts p where p.merchant_id='%s' and p.balance_id='%s' and p.initiated_at is not null and p.failed_at between %d and %d and coalesce(p.fee_type, '') != 'reward_fee'";
            $queryFailedPayouts = sprintf($formatStringFailedPayouts, $merchantId, $balanceId, $startTimestamp, $endTimestamp);
            $failedPayoutsArray = $this->app['datalake.presto']->getDataFromDatalake($queryFailedPayouts);
            $failedPayouts = $this->getPublicCollectionFromArrayWithType($failedPayoutsArray, 'payout');

            $formatStringReversals = "select r.id, p.fees from realtime_hudi_api.reversals r join realtime_hudi_api.payouts p on r.entity_id=p.id where r.merchant_id='%s' and r.entity_type='payout' and r.balance_id='%s' and r.created_at between %d and %d and p.failed_at is null and coalesce(p.fee_type, '') != 'reward_fee'";
            $query = sprintf($formatStringReversals, $merchantId, $balanceId, $startTimestamp, $endTimestamp);
            $reversalsArray = $this->app['datalake.presto']->getDataFromDatalake($query);
            $reversals = $this->getPublicCollectionFromArrayWithType($reversalsArray, 'reversal');

            $this->trace->info(TraceCode::FEE_RECOVERY_PAYOUTS_AND_REVERSALS_TRINO_FETCH_COMPLETED, [
                'merchant_id' => $merchantId,
                'balance_id' => $balanceId,
            ]);
        }

        else
        {
            $payouts = $this->repo->payout->fetchFeesAndIdOfPayoutsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $startTimestamp,
                $endTimestamp
            );

            $failedPayouts = $this->repo->payout->fetchFeesAndIdOfFailedPayoutsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $startTimestamp,
                $endTimestamp
            );

            $reversals = $this->repo->reversal->fetchFeesAndIdOfReversalsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $startTimestamp,
                $endTimestamp
            );
        }

        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUTS_AND_REVERSALS_FETCH_COMPLETED,
            [
                'merchant_id'           => $merchantId,
                'balance_id'            => $balanceId,
                'payout_count'          => $payouts->count(),
                'failed_payout_count'   => $failedPayouts->count(),
                'reversal_count'        => $reversals->count()
            ]);

        return [$payouts, $failedPayouts, $reversals];
    }


    protected function getPayoutAndReversalEntitiesForFeeRecoveryViaBatching(Balance\Entity $balance,
                                                                  int $startTimestamp,
                                                                  int $endTimestamp) : array
    {
        $merchant = $balance->merchant;
        $merchantId = $merchant->getId();
        $balanceId = $balance->getId();
        $batchSize = 3600; // 1 hour in seconds

        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUTS_AND_REVERSALS_FETCH_INITIATED,
            [
                'merchant_id'   => $merchantId,
                'balance_id'    => $balanceId,
                'start_time'    => $startTimestamp,
                'end_time'      => $endTimestamp
            ]);

        $allPayouts = new Base\PublicCollection();
        $allFailedPayouts = new Base\PublicCollection();
        $allReversals = new Base\PublicCollection();


        for ($batchStart = $startTimestamp; $batchStart < $endTimestamp; $batchStart += $batchSize) {
            $batchEnd = min($batchStart + $batchSize, $endTimestamp);

            // Fetch payouts for the current batch
            $payouts = $this->repo->payout->fetchFeesAndIdOfPayoutsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $batchStart,
                $batchEnd
            );

            // Fetch failed payouts for the current batch
            $failedPayouts = $this->repo->payout->fetchFeesAndIdOfFailedPayoutsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $batchStart,
                $batchEnd
            );

            // Fetch reversals for the current batch
            $reversals = $this->repo->reversal->fetchFeesAndIdOfReversalsForGivenBalanceIdForPeriod(
                $merchantId,
                $balanceId,
                $batchStart,
                $batchEnd
            );

            // Merge the results into the main collections
            $allPayouts = $allPayouts->merge($payouts->all());
            $allFailedPayouts = $allFailedPayouts->merge($failedPayouts->all());
            $allReversals = $allReversals->merge($reversals->all());
        }


        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUTS_AND_REVERSALS_FETCH_COMPLETED,
            [
                'merchant_id'           => $merchantId,
                'balance_id'            => $balanceId,
                'payout_count'          => $allPayouts->count(),
                'failed_payout_count'   => $allFailedPayouts->count(),
                'reversal_count'        => $allReversals->count()
            ]);

        return [$allPayouts, $allFailedPayouts, $allReversals];
    }

    protected function getFeesForFeeRecovery(Base\PublicCollection $payouts,
                                             Base\PublicCollection $failedPayouts,
                                             Base\PublicCollection $reversals)
    {
        $payoutsFees = 0;
        $reversalsFees = 0;
        $failedPayoutsFees = 0;

        foreach ($payouts as $payout)
        {
            $payoutsFees += $payout[Payout\Entity::FEES];
        }

        foreach ($failedPayouts as $failedPayout)
        {
            $failedPayoutsFees += $failedPayout[Payout\Entity::FEES];
        }

        foreach ($reversals as $reversal)
        {
            $reversalsFees += $reversal[Payout\Entity::FEES];
        }

        $amount = $payoutsFees - $failedPayoutsFees - $reversalsFees;

        return $amount;
    }

    /**
     * @param Base\PublicCollection $payouts
     * @param Base\PublicCollection $failedPayouts
     * @param Base\PublicCollection $reversals
     * @param Balance\Entity        $balance
     * @param                       $amount
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    protected function processAndGetFeeRecoveryPayout(Base\PublicCollection $payouts,
                                                      Base\PublicCollection $failedPayouts,
                                                      Base\PublicCollection $reversals,
                                                      Balance\Entity $balance,
                                                      $amount)
    {
        $payoutIds          = $payouts->getIds();
        $failedPayoutIds    = $failedPayouts->getIds();
        $reversalIds        = $reversals->getIds();

        $this->validateNoExistingFeeRecoveryInProcess($payoutIds, $failedPayoutIds, $reversalIds);

        return $this->repo->transaction(
            function() use ($balance, $payoutIds, $failedPayoutIds, $reversalIds, $amount)
            {
                $merchant = $balance->merchant;

                $payoutPayload = $this->getPayloadForFeeRecoveryPayout($balance, $amount);

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_PAYOUT_CREATE_REQUEST,
                    [
                        'payload' => $payoutPayload
                    ]);

                $feeRecoveryPayout = (new Payout\Core)->createPayoutToFundAccount($payoutPayload,
                                                                                  $merchant,
                                                                                  null,
                                                                                  true);

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_PAYOUT_CREATED,
                    [
                        'payout_data' => $feeRecoveryPayout->toArrayPublic()
                    ]);

                $this->updateFeesRecoveryStatus($payoutIds, $failedPayoutIds, $reversalIds, $feeRecoveryPayout);

                return $feeRecoveryPayout;
            });
    }

    public function fetchUnrecoveredFeeRecoveryCountViaBatching($entityIdList,
                                                                 $entityType,
                                                                 $type)
    {
        $left = 0;

        $batch = self::BATCH_SIZE;

        $unrecoveredEntityCount = 0;

        $this->trace->info(
            TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
            [
                'step'                   => self::INITIATE,
                'operation'              => self::FETCH,
                'start'                  => $left,
                'batch_size'             => $batch,
                'unrecovered_count'      => $unrecoveredEntityCount,
                'total_entity_count'     => count($entityIdList),
                'entity_type'            => $entityType,
                'type'                   => $type,
            ]);

        while ($left < count($entityIdList))
        {
            $currentSlice = array_slice($entityIdList, $left, $batch, true);

            $left += $batch;

            $unrecoveredEntityCount += $this->repo->fee_recovery->fetchUnrecoveredFeeRecoveryCount($currentSlice, $entityType, $type);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
                [
                    'step'                   => self::INTERMEDIATE,
                    'operation'              => self::FETCH,
                    'start'                  => $left,
                    'batch_size'             => $batch,
                    'unrecovered_count'      => $unrecoveredEntityCount,
                    'total_entity_count'     => count($entityIdList),
                    'current_slice_count'    => count($currentSlice),
                ]);

        }

        return $unrecoveredEntityCount;
    }

    /**
     * * This function matches the count of payoutIds, failedPayoutIds and reversalIds to their corresponding entries
     * in the fee_recovery table. Ideally we would want to match every Id to its corresponding entry.
     * Doing that is very costly. By matching every Id, we could throw error for only certain payouts/reversals
     * but in this case, we shall throw an error for an entire range of payouts. This would only occur if there is
     * some sort of data inconsistency. In both cases, corresponding fee_recovery payout should fail.
     *
     * @param $payoutIds
     * @param $failedPayoutIds
     * @param $reversalIds
     *
     * @throws Exception\BadRequestException
     */
    protected function validateNoExistingFeeRecoveryInProcess($payoutIds,
                                                              $failedPayoutIds,
                                                              $reversalIds)
    {
        $unRecoveredPayoutCount = $this->fetchUnrecoveredFeeRecoveryCountViaBatching($payoutIds,
                                                                                    Entity::PAYOUT,
                                                                                    Type::DEBIT);

        $unRecoveredFailedPayoutCount = $this->fetchUnrecoveredFeeRecoveryCountViaBatching($failedPayoutIds,
                                                                                          Entity::PAYOUT,
                                                                                          Type::CREDIT);

        $unRecoveredReversalCount = $this->fetchUnrecoveredFeeRecoveryCountViaBatching($reversalIds,
                                                                                      Entity::REVERSAL,
                                                                                      Type::CREDIT);

        $payoutIdsCount = count($payoutIds);
        $reversalIdsCount = count($reversalIds);
        $failedPayoutIdsCount = count($failedPayoutIds);

        if (($unRecoveredPayoutCount !== $payoutIdsCount) or
            ($unRecoveredFailedPayoutCount !== $failedPayoutIdsCount) or
            ($unRecoveredReversalCount !== $reversalIdsCount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_ALREADY_INITIATED,
                null,
                [
                    'payout_id_count'                   => $payoutIdsCount,
                    'unrecovered_payout_count'          => $unRecoveredPayoutCount,
                    'failed_payout_id_list'             => $failedPayoutIdsCount,
                    'unrecovered_failed_payout_count'   => $unRecoveredFailedPayoutCount,
                    'reversal_id_list'                  => $reversalIdsCount,
                    'unrecovered_reversal_count'        => $unRecoveredReversalCount,
                ]);
        }
    }

    protected function fetchIfscForFeeRecoveryForChannel(string $channel)
    {
        return $this->config['banking_account']['razorpayx_fee_details'][$channel]['ifsc'];
    }

    /**
     * This function also ends up creating a new rzp_fees type contact and fund account if none currently exist
     * Ideally, this should never occur but may occur if someone manually activates a merchant for business banking.
     *
     * @param Balance\Entity $balance
     * @param $amount
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\LogicException
     */
    protected function getPayloadForFeeRecoveryPayout(Balance\Entity $balance, $amount)
    {
        $merchant = $balance->merchant;

        $feeRecoveryContact = $this->fetchOrCreateRzpFeesTypeContact($merchant, $balance);

        $ifscForFeeRecovery = $this->fetchIfscForFeeRecoveryForChannel($balance->getChannel());

        $feeRecoveryFundAccount = $this->repo->fund_account->fetchRzpFeesFundAccount($merchant->getId(),
                                                                                     $feeRecoveryContact->getId(),
                                                                                     $ifscForFeeRecovery);

        // fetching for migrated fund_accounts
        if($balance->getChannel() === Balance\Channel::YESBANK and $feeRecoveryFundAccount === null)
        {
            $ifscForFeeRecovery = self::OLD_IFSC_FOR_YBL_RZP_FEES;

            $feeRecoveryFundAccount = $this->repo->fund_account->fetchRzpFeesFundAccount($merchant->getId(),
                $feeRecoveryContact->getId(),
                $ifscForFeeRecovery);
        }

        $fundAccountId = $feeRecoveryFundAccount->getPublicId();

        switch($balance->getChannel())
        {
            case Channel::AXIS:
            case Channel::IDFC:
                $payoutMode = Payout\Mode::NEFT;
                break;

            default:
                $payoutMode = Payout\Mode::IFT;
                break;
        }

        // We will have to keep this check until we migrate account_number in all YBL rzp_fees fund account.
        if ($balance->getChannel() === Balance\Channel::YESBANK and str_starts_with($ifscForFeeRecovery, 'HDFC'))
        {
            $payoutMode = Payout\Mode::NEFT;
        }

        $payoutPayload = [
            Payout\Entity::FUND_ACCOUNT_ID      => $fundAccountId,
            Payout\Entity::MODE                 => $payoutMode,
            Payout\Entity::CURRENCY             => Currency::INR,
            Payout\Entity::BALANCE_ID           => $balance->getId(),
            Payout\Entity::PURPOSE              => Payout\Purpose::RZP_FEES,
            Payout\Entity::QUEUE_IF_LOW_BALANCE => true,
            Payout\Entity::AMOUNT               => $amount,
        ];

        return $payoutPayload;
    }

    public function updateBulkStatusAndRecoveryPayoutIdViaBatching($entityIds,
                                                                   $entityType,
                                                                   $type,
                                                                   $feeRecoveryPayoutId,
                                                                   $status,
                                                                   $currentAttemptNumber)
    {
        $left = 0;

        $batch = self::BATCH_SIZE;

        $updatedEntitiesCount = 0;

        $this->trace->info(
            TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
            [
                'step'                   => self::INITIATE,
                'operation'              => self::UPDATE,
                'start'                  => $left,
                'batch_size'             => $batch,
                'updated_entities_count' => $updatedEntitiesCount,
                'total_entity_count'     => count($entityIds),
                'entity_type'            => $entityType,
                'type'                   => $type,
            ]);

        while ($left < count($entityIds))
        {
            $currentSlice = array_slice($entityIds, $left, $batch, true);

            $left += $batch;

            $updatedEntitiesCount += $this->repo->fee_recovery->updateBulkStatusAndRecoveryPayoutId($currentSlice,
                $entityType,
                $type,
                $feeRecoveryPayoutId,
                $status,
                $currentAttemptNumber);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
                [
                    'step'                   => self::INITIATE,
                    'operation'              => self::UPDATE,
                    'start'                  => $left,
                    'batch_size'             => $batch,
                    'updated_entities_count' => $updatedEntitiesCount,
                    'total_entity_count'     => count($entityIds),
                    'current_slice_count'    => count($currentSlice),
                ]);
        }
        return $updatedEntitiesCount;
    }

    protected function updateFeesRecoveryStatus($payoutIds,
                                                $failedPayoutIds,
                                                $reversalIds,
                                                $feeRecoveryPayout,
                                                $currentAttemptNumber = 0)
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_STATUS_UPDATE,
            [
                'fee_recovery_payout_id' => $feeRecoveryPayout->getPublicId(),
            ]);

        $updatedPayoutsCount = $this->updateBulkStatusAndRecoveryPayoutIdViaBatching($payoutIds,
                                                                                Entity::PAYOUT,
                                                                                Type::DEBIT,
                                                                                $feeRecoveryPayout->getId(),
                                                                                Status::PROCESSING,
                                                                                $currentAttemptNumber);

        $updatedFailedPayoutsCount = $this->updateBulkStatusAndRecoveryPayoutIdViaBatching($failedPayoutIds,
                                                                                      Entity::PAYOUT,
                                                                                      Type::CREDIT,
                                                                                      $feeRecoveryPayout->getId(),
                                                                                      Status::PROCESSING,
                                                                                      $currentAttemptNumber);

        $updatedReversalsCount = $this->updateBulkStatusAndRecoveryPayoutIdViaBatching($reversalIds,
                                                                                  Entity::REVERSAL,
                                                                                  Type::CREDIT,
                                                                                  $feeRecoveryPayout->getId(),
                                                                                  Status::PROCESSING,
                                                                                  $currentAttemptNumber);

        if (($updatedPayoutsCount !== count($payoutIds)) or
            ($updatedFailedPayoutsCount !== count($failedPayoutIds)) or
            ($updatedReversalsCount !== count($reversalIds)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_BULK_UPDATE_ERROR,
                null,
                [
                    'payouts_count'                 => count($payoutIds),
                    'updated_payouts_count'         => $updatedPayoutsCount,
                    'failed_payouts_count'          => count($failedPayoutIds),
                    'updated_failed_payouts_count'  => $updatedFailedPayoutsCount,
                    'reversals_count'               => count($reversalIds),
                    'updated_reversals_count'       => $updatedReversalsCount
                ]);
        }
    }

    /**
     * This function fetches the 'rzp_fees' type contact.
     * If it does not exist, it creates the contact and corresponding fund account.
     *
     * @param Merchant\Entity $merchant
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\LogicException
     */
    protected function fetchOrCreateRzpFeesTypeContact(Merchant\Entity $merchant, Balance\Entity $balance)
    {
        $rzpFeesContacts = $this->repo->contact->fetchContactFromTiDB($merchant->getId(), Contact\Type::RZP_FEES, 2);

        if ($rzpFeesContacts->count() > 1)
        {
            throw new Exception\LogicException('Merchant has more than one rzp_fees type contact',
                                               ErrorCode::BAD_REQUEST_LOGIC_ERROR_MULTIPLE_RZP_FEES_CONTACT,
                                               [
                                                   'merchant_id' => $merchant->getId(),
                                                   'count'       => $rzpFeesContacts->count(),
                                               ]);
        }

        if (count($rzpFeesContacts) == 0)
        {
            (new BankingAccount\Core)->createRZPFeesContactAndFundAccount($merchant, $balance->getChannel());

            $this->trace->error(TraceCode::RZP_FEES_CONTACT_FUND_ACCOUNT_DOES_NOT_EXIST,
                                [
                                    'merchant_id'           => $merchant->getId(),
                                    'balance_id'            => $balance->getId(),
                                ]);

            $rzpFeesContacts = $this->repo->contact->fetch([
                                                               Contact\Entity::TYPE => Contact\Type::RZP_FEES
                                                           ],
                                                           $merchant->getId(),
                                                           true);
        }

        $feeRecoveryContact = $rzpFeesContacts->first();

        return $feeRecoveryContact;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function createManualRecovery(array $input)
    {
        (new Validator)->validateInput('create_manual_fee_recovery_payout', $input);

        $payoutIds          = $input[Entity::PAYOUT_IDS] ?? [];
        $reversalIds        = $input[Entity::REVERSAL_IDS] ?? [];
        $failedPayoutIds    = $input[Entity::FAILED_PAYOUT_IDS] ?? [];
        $balanceId          = $input[Entity::BALANCE_ID];
        $merchantId         = $input[Entity::MERCHANT_ID];
        $amount             = (int) $input[Entity::AMOUNT];

        $manualRecoveryData = [
            Entity::DESCRIPTION         => $input[Entity::DESCRIPTION] ?? null,
            Entity::REFERENCE_NUMBER    => $input[Entity::REFERENCE_NUMBER] ?? null
        ];

        $this->repo->transaction(
            function() use ($payoutIds, $failedPayoutIds, $reversalIds, $merchantId, $balanceId, $manualRecoveryData, $amount)
            {

                $feesForPayouts = $this->repo->payout->fetchFeesForPayoutIds($payoutIds,
                                                                             $merchantId,
                                                                             $balanceId);

                $feesForFailedPayouts = $this->repo->payout->fetchFeesForFailedPayoutIds($failedPayoutIds,
                                                                                         $merchantId,
                                                                                         $balanceId);

                $feesForReversals = $this->repo->reversal->fetchFeesForReversalIds($reversalIds,
                                                                                   $merchantId,
                                                                                   $balanceId);

                $totalFeesAmount = $feesForPayouts['fees'] - $feesForFailedPayouts['fees'] - $feesForReversals['fees'];

                if ($totalFeesAmount !== $amount)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_AMOUNT_MISMATCH,
                        null,
                        [
                            'amount'            => $amount,
                            'fees_calculated'   => $totalFeesAmount,
                            'merchant_id'       => $merchantId,
                            'balance_id'        => $balanceId,
                            'reference_number'  => $manualRecoveryData[Entity::REFERENCE_NUMBER],
                            'description'       => $manualRecoveryData[Entity::DESCRIPTION],
                        ]);
                }

                foreach ($payoutIds as $payoutId)
                {
                    $this->createAndUpdateFeeRecoveryEntityForManualRecovery($payoutId,
                                                                             Entity::PAYOUT,
                                                                             $merchantId,
                                                                             $manualRecoveryData,
                                                                             Type::DEBIT);
                }
                foreach ($failedPayoutIds as $failedPayoutId)
                {
                    $this->createAndUpdateFeeRecoveryEntityForManualRecovery($failedPayoutId,
                                                                             Entity::PAYOUT,
                                                                             $merchantId,
                                                                             $manualRecoveryData,
                                                                             Type::CREDIT);
                }
                foreach ($reversalIds as $reversalId)
                {
                    $this->createAndUpdateFeeRecoveryEntityForManualRecovery($reversalId,
                                                                             Entity::REVERSAL,
                                                                             $merchantId,
                                                                             $manualRecoveryData,
                                                                             Type::CREDIT);
                }
            });

        return ['success' => true];
    }

    public function processFeeRecoveryBalanceCron()
    {
        $this->lowBalanceMerchantEmail();

        $this->automatedMerchantBlockingAndUnblocking(Constants::AUTOMATED_BLOCKING);

        $this->automatedMerchantBlockingAndUnblocking(Constants::AUTOMATED_UNBLOCKING);

        return [
            'success' => true,
        ];
    }

    protected function createAndUpdateFeeRecoveryEntityForManualRecovery($entityId,
                                                                         $entityType,
                                                                         $merchantId,
                                                                         $manualRecoveryData,
                                                                         $type)
    {
        $this->mutex->acquireAndRelease(
            'fee_recovery_' . $entityId,
            function () use ($entityId, $entityType, $merchantId, $manualRecoveryData, $type)
        {
            $feeRecovery = $this->repo->fee_recovery->getLastUnrecoveredFeeRecoveryEntityByEntityIdType(
                $entityId,
                $entityType,
                $type
            );

            if ($feeRecovery === null)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
                    null,
                    [
                        'entity_id'     => $entityId,
                        'entity_type'   => $entityType
                    ]);
            }

            $newFeeRecovery = $feeRecovery->replicate();

            $dataToUpdate = [
                Entity::RECOVERY_PAYOUT_ID  => null,
                Entity::REFERENCE_NUMBER    => $manualRecoveryData[Entity::REFERENCE_NUMBER],
                Entity::DESCRIPTION         => $manualRecoveryData[Entity::DESCRIPTION],
                Entity::ATTEMPT_NUMBER      => $feeRecovery->getAttemptNumber() + 1,
            ];

            $newFeeRecovery->edit($dataToUpdate);

            $newFeeRecovery->setStatus(Status::MANUALLY_RECOVERED);

            if ($entityType === Entity::PAYOUT)
            {
                $sourceEntity = $feeRecovery->payout;

                if ($sourceEntity->getPurpose() === Payout\Purpose::RZP_FEES)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_FOR_RZP_FEES_PAYOUT_NOT_SUPPORTED,
                        null,
                        [
                            'entity_id'     => $entityId,
                            'entity_type'   => $entityType
                        ]);
                }
            }
            else if ($entityType === Entity::REVERSAL)
            {
                $sourceEntity = $feeRecovery->reversal;
            }

            $newFeeRecovery->entity()->associate($sourceEntity);

            $this->repo->saveOrFail($newFeeRecovery);

            // Set status of earlier attempt to failed
            $feeRecovery->setStatus(Status::FAILED);

            $this->repo->saveOrFail($feeRecovery);
        },
        60,
        ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);
    }

    protected function getNewFeeRecoveryEntities($previousFeeRecoveryEntities, $newFeeRecoveryPayout)
    {
        $newFeeRecoveryEntities = [];

        foreach ($previousFeeRecoveryEntities as $feeRecoverySourceEntity)
        {
            $newFeeRecovery = $feeRecoverySourceEntity->replicate();

            $newFeeRecovery->generateId();

            $dataToUpdate = [
                Entity::RECOVERY_PAYOUT_ID  => $newFeeRecoveryPayout->getId(),
                Entity::ATTEMPT_NUMBER      => $feeRecoverySourceEntity->getAttemptNumber() + 1,
            ];

            $newFeeRecovery->edit($dataToUpdate);

            $newFeeRecovery->setStatus(Status::PROCESSING);

            $newFeeRecovery->setCreatedAt(Carbon::now()->getTimestamp());

            $newFeeRecovery->setUpdatedAt(Carbon::now()->getTimestamp());

            if ($feeRecoverySourceEntity->getEntityType() === Entity::REVERSAL)
            {
                $sourceEntity = $feeRecoverySourceEntity->reversal;
            }
            else
            {
                $sourceEntity = $feeRecoverySourceEntity->payout;
            }

            $newFeeRecovery->setEntityType($feeRecoverySourceEntity->getEntityType());

            $newFeeRecovery->setEntityId($sourceEntity->getId());

            $newFeeRecoveryEntities[] = $newFeeRecovery->toArray();
        }

        return $newFeeRecoveryEntities;
    }

    protected function insertBulkFeeRecoveryEntitiesViaBatching($entityList)
    {
        $left = 0;

        $batch = self::BULK_INSERT_SIZE;

        $insertedEntityCount = 0;

        $this->trace->info(
            TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
            [
                'step'               => self::INITIATE,
                'operation'          => self::INSERT,
                'start'              => $left,
                'batch_size'         => $batch,
                'inserted_count'     => $insertedEntityCount,
                'total_count'        => count($entityList),
            ]);

        while ($left < count($entityList))
        {
            $currentSlice = array_slice($entityList, $left, $batch, true);

            $left += $batch;

            $this->repo->fee_recovery->bulkInsert($currentSlice);

            $insertedEntityCount += count($currentSlice);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
                [
                    'step'                => self::INTERMEDIATE,
                    'operation'           => self::INSERT,
                    'start'               => $left,
                    'batch_size'          => $batch,
                    'inserted_count'      => $insertedEntityCount,
                    'total_count'         => count($entityList),
                    'current_slice_count' => count($currentSlice),
                ]);
        }
    }

    protected function updateBulkStatusViaBatching($entityList, $status)
    {
        $left = 0;

        $batch = self::BULK_INSERT_SIZE;

        $updatedEntityCount = 0;

        $this->trace->info(
            TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
            [
                'step'               => self::INITIATE,
                'operation'          => self::UPDATE,
                'start'              => $left,
                'batch_size'         => $batch,
                'updated_count'      => $updatedEntityCount,
                'total_count'        => count($entityList),
            ]);

        while ($left < count($entityList))
        {
            $currentSlice = array_slice($entityList->toArray(), $left, $batch, true);

            $left += $batch;

            $this->repo->fee_recovery->updateBulkStatus(array_pluck($currentSlice, Entity::ID), $status);

            $updatedEntityCount += count($currentSlice);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
                [
                    'step'                => self::INTERMEDIATE,
                    'operation'           => self::UPDATE,
                    'start'               => $left,
                    'batch_size'          => $batch,
                    'updated_count'       => $updatedEntityCount,
                    'total_count'         => count($entityList),
                    'current_slice_count' => count($currentSlice),
                ]);
        }
    }

    protected function sendSlackAlert($operation, $data)
    {
        (new SlackNotification)->send($operation, $data, null, 1, Entity::RX_CA_RBL_ALERTS);
    }

    public function updateFeeRecoveryStatusForFeeCredit($feeRecovery, string $status)
    {
        $this->repo->fee_recovery->updateFeeRecoveryStatusById($feeRecovery->getId(), $status);
    }

    private function lowBalanceMerchantEmail(): void
    {
        $eligibleList = $this->repo->payout->fetchEligibleBalancesForLowBalanceAlertAndBlocking(Constants::MIN_BALANCE_AMOUNT);

        $accountNumberMap = [];

        foreach($eligibleList as $item)
        {
            $accountNumberMap[$item->getAttribute(Constants::ACCOUNT_NUMBER)] = [
                Entity::MERCHANT_ID => $item->getMerchantId(),
                Entity::BALANCE_ID  => $item->getBalanceId(),
            ];
        }

        // explicitly converting to strings since php converts numeric keys to int when possible
        $accountNumbers = array_map('strval', array_keys($accountNumberMap));

        if (empty($accountNumbers))
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_LOW_BALANCE_PROCESS_SKIPPED,
                [
                    'flow'      => Constants::LOW_BALANCE_ALERT,
                    'reason'    => 'No eligible Account Numbers found'
                ]);

            return;
        }

        $basResponse = (new \RZP\Models\BankingAccountService\Service())->fetchFeeRecoveryMetadata([
            Constants::ACCOUNT_NUMBERS => $accountNumbers
        ]);

        // sanity check: if there is some diff between account_number list sent back by BAS & original list, log here
        // this can happen if some accounts are not yet migrated to BAS
        if(!empty(array_diff($accountNumbers, array_pluck($basResponse, Constants::ACCOUNT_NUMBER))))
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_LOW_BALANCE_PROCESS_SKIPPED,
                [
                    'reason'    => 'There is a diff in original account_numbers & banking_account_service response'
                ]);
        }

        foreach ($basResponse as $item)
        {
            $accountNumber = $item[Constants::ACCOUNT_NUMBER];

            $feeRecoveryEmailLastSentAt = Carbon::createFromTimestamp($item[Constants::FEE_RECOVERY_EMAIL_SENT_AT] ?? 0);

            $currentTime = Carbon::now(Timezone::IST);

            if ($currentTime->diffInDays($feeRecoveryEmailLastSentAt) > 10)
            {
                Jobs\FeeRecoveryLowBalance::dispatch($this->mode, [
                    Constants::ACTION               => Constants::LOW_BALANCE_ALERT,
                    Constants::BUSINESS_ID          => $item[Constants::BUSINESS_ID],
                    Constants::BANKING_ACCOUNT_ID   => $item[Constants::BANKING_ACCOUNT_ID],
                    Constants::ACCOUNT_NUMBER       => $accountNumber,
                    Constants::BALANCE_IDS          => [$accountNumberMap[$accountNumber][Entity::BALANCE_ID]],
                    Entity::MERCHANT_ID             => $accountNumberMap[$accountNumber][Entity::MERCHANT_ID],
                ]);
            }
        }
    }

    private function automatedMerchantBlockingAndUnblocking($action): void
    {
        $eligibleList = [];

        if ($action === constants::AUTOMATED_BLOCKING)
        {
            $currentTime = Carbon::now(TimeZone::IST);

            if ($currentTime->day != 1 || $currentTime->hour !== 12 || $currentTime->minute > 30)
            {
                // Don't proceed further if current time is not between 12:00 PM and 12:30 PM on 1st of Month for automated blocking
                return;
            }

            $eligibleList = $this->repo->payout->fetchEligibleBalancesForLowBalanceAlertAndBlocking();
        }
        else
        {
            $eligibleList = $this->repo->balance->fetchMerchantsBlockedDueToLowBalanceWithBalanceId();
        }

        $merchantMap = [];

        foreach ($eligibleList as $item)
        {
            $merchantMap[$item->getMerchantId()][] = $item->getAttribute(Entity::BALANCE_ID);
        }

        if (empty($merchantMap))
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_LOW_BALANCE_PROCESS_SKIPPED,
                [
                    'flow'      => $action,
                    'reason'    => 'No eligible merchants found'
                ]);

            return;
        }

        $filteredMerchantIds = [];

        if ($action === Constants::AUTOMATED_BLOCKING)
        {
            // For initial release, the automated blocking should be applicable only to those merchants have the automated blocking feature enabled.
            // Once things are stabilized, this restriction will be lifted and made applicable to all merchants.
            $filteredMerchantIds = $this->repo->feature->getMerchantIdsHavingFeature(\RZP\Models\Feature\Constants::AUTO_DISABLE_PAYOUTS, array_keys($merchantMap));
        }
        else
        {
            $filteredMerchantIds = array_keys($merchantMap);
        }

        foreach ($filteredMerchantIds as $merchantId)
        {
            Jobs\FeeRecoveryLowBalance::dispatch($this->mode, [
                Constants::ACTION       => $action,
                Constants::BALANCE_IDS  => $merchantMap[$merchantId],
                Entity::MERCHANT_ID     => $merchantId
            ]);
        }
    }

    public function updateFeeRecoveryScheduleAdmin(array $input)
    {
        $action = $input['action'];

        $balanceId = $input[Entity::BALANCE_ID];

        $task = $this->repo->schedule_task->fetchByTypeAndEntityId(Task\Type::FEE_RECOVERY, $balanceId);

        if ($action === 'dry_run')
        {
            $res = [
                'balance_id'  => $balanceId,
                'last_run_at' => $task->getLastRunAt(),
                'next_run_at' => $task->getNextRunAt(),
                'last_run'    => Carbon::createFromTimestamp($task->getLastRunAt(), Timezone::IST),
                'next_run'    => Carbon::createFromTimestamp($task->getNextRunAt(), Timezone::IST),
            ];

            $this->trace->info(
                TraceCode::FEE_RECOVERY_SCHEDULE_FETCH_RESPONSE,
                [
                    'input' => $input,
                    'res'   => $res
                ]);

            return $res;
        }

        if ($action === 'update')
        {
            (new Validator)->validateInput(Validator::UPDATE_FEE_RECOVERY_SCHEDULE, $input);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_SCHEDULE_UPDATE_REQUEST,
                [
                    'input' => $input,
                ]);

            $newNextRun = $input['next_run_at'];
            $lastRunAt = $task->getLastRunAt();

            if ($newNextRun < $lastRunAt)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_FEE_RECOVERY_SCHEDULE_NOT_ELIGIBLE,
                    null,
                    [
                        'balance_id'        => $balanceId,
                        'new_next_run_at'   => $newNextRun,
                        'last_run_at'       => $lastRunAt
                    ]);
            }

            $task->setNextRunAt($newNextRun);

            $this->repo->saveOrFail($task);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_SCHEDULE_UPDATE_SUCCESS,
                [
                    'balance_id' => $balanceId,
                    'new_last_run_at' => $task->getLastRunAt(),
                    'new_next_run_at' => $task->getNextRunAt()
                ]);

            return [
                'balance_id'  => $balanceId,
                'last_run_at' => $task->getLastRunAt(),
                'next_run_at' => $task->getNextRunAt(),
                'last_run'    => Carbon::createFromTimestamp($task->getLastRunAt(), Timezone::IST),
                'next_run'    => Carbon::createFromTimestamp($task->getNextRunAt(), Timezone::IST),
            ];
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_WRONG_FEE_RECOVERY_SCHEDULE_ACTION_SPECIFIED,
            null,
            [
                'balance_id'    => $balanceId,
                'action'        => $action
            ]);
    }

    public function calculateFeeRecoveryAmountAdmin(string $balanceId, int $startTimestamp, int $endTimestamp)
    {
        $balance = $this->repo->balance->findOrFailById($balanceId);

        list ($payouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecovery($balance,
            $startTimestamp,
            $endTimestamp);

        $amount = $this->getFeesForFeeRecovery($payouts, $failedPayouts, $reversals);

        return [
            'amount'                => $amount,
            'start_time'            => $startTimestamp,
            'end_time'              => $endTimestamp,
            'payout_count'          => count($payouts),
            'failed_payout_count'   => count($failedPayouts),
            'reversal_count'        => count($reversals),
        ];
    }

    public function createRecoveryPayoutJobAdmin(array $input)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::FEE_RECOVERY_JOB_INITIATED,
            [
                'input' => $input
            ]);

        $balanceId = $input[Entity::BALANCE_ID];

        $balance = $this->repo->balance->find($balanceId);

        if($balance === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BALANCE_NOT_FOUND,
                null,
                [
                    'balance_id' => $balanceId
                ]);
        }

        $scheduleTask = $this->repo->schedule_task->fetchByTypeAndEntityId(Task\Type::FEE_RECOVERY, $balanceId);

        //
        // We are going to run the fee recovery payout for payouts created between lastRunAt and nextRunAt of a
        // schedule task.
        //
        // IMPORTANT : If the task runs 23 min post its current nextRunAt, lastRunAt is still updated to
        // the current value of nextRunAt. Hence, we don't have to consider any actual delay that happen
        // when we run the cron.
        //
        // Also, we have manually added 1 here because the query is inclusive on both ends. The same route is also
        // called via admin auth, and keeping the timestamps inclusive makes it less prone to human error.
        //
        $lastRunAt = empty($scheduleTask->getLastRunAt()) ? $balance->getCreatedAt() :  $scheduleTask->getLastRunAt() + 1;
        $nextRunAt = $scheduleTask->getNextRunAt();

        if($nextRunAt > $currentTimeStamp)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_SCHEDULE_NOT_ELIGIBLE,
                null,
                [
                    'balance_id'    => $balanceId,
                    'next_run_at'   => $nextRunAt,
                    'current_time'  => $currentTimeStamp
                ]);
        }

        Jobs\FeeRecovery::dispatch($this->mode, null, $balanceId, $lastRunAt, $nextRunAt, $scheduleTask);

        return ['success' => true];
    }

    protected function getPayloadForFeeRecoveryPayoutCustomAmount(Balance\Entity $balance, $amount, string $contactId = null)
    {
        $merchant = $balance->merchant;

        if ($contactId === null)
        {
            $feeRecoveryContact = $this->fetchOrCreateRzpFeesTypeContact($merchant, $balance);
        }
        else
        {
            $this->trace->info(
                TraceCode::FEE_RECOVERY_CUSTOM_AMOUNT_CONTACT_FETCH_SKIP_INITIATE,
                [
                    'input_contact_id' => $contactId,
                ]);

            $feeRecoveryContact = $this->repo->contact->findByIdAndMerchantId($contactId, $merchant->getId());

            $this->trace->info(
                TraceCode::FEE_RECOVERY_CUSTOM_AMOUNT_CONTACT_FETCH_SKIP_SUCCESS,
                [
                    'found_contact_id' => $feeRecoveryContact->getId(),
                ]);
        }

        $ifscForFeeRecovery = $this->fetchIfscForFeeRecoveryForChannel($balance->getChannel());

        $feeRecoveryFundAccount = $this->repo->fund_account->fetchRzpFeesFundAccount($merchant->getId(),
            $feeRecoveryContact->getId(),
            $ifscForFeeRecovery);

        $fundAccountId = $feeRecoveryFundAccount->getPublicId();

        switch($balance->getChannel())
        {
            case Channel::AXIS:

                $payoutMode = Payout\Mode::NEFT;
                break;

            default:
                $payoutMode = Payout\Mode::IFT;
                break;
        }

        $payoutPayload = [
            Payout\Entity::FUND_ACCOUNT_ID      => $fundAccountId,
            Payout\Entity::MODE                 => $payoutMode,
            Payout\Entity::CURRENCY             => Currency::INR,
            Payout\Entity::BALANCE_ID           => $balance->getId(),
            Payout\Entity::PURPOSE              => Payout\Purpose::RZP_FEES,
            Payout\Entity::QUEUE_IF_LOW_BALANCE => true,
            Payout\Entity::AMOUNT               => $amount,
        ];

        return $payoutPayload;
    }

    public function createRecoveryPayoutCustomAmountAdmin(array $input)
    {
        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUT_CUSTOM_AMOUNT_INITIATED,
            [
                'input' => $input,
            ]);

        $balanceId = $input[Entity::BALANCE_ID];

        $balance = $this->repo->balance->findOrFailById($balanceId);

        if (($balance->isTypeBanking() === false) or
            ($balance->isAccountTypeDirect() === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_BALANCE,
                null,
                [
                    Balance\Entity::TYPE          => $balance->getType(),
                    Balance\Entity::ACCOUNT_TYPE  => $balance->getAccountType(),
                    Balance\Entity::BALANCE_ID    => $balance->getId(),
                ]);
        }

        $merchant = $balance->merchant;

        $amount = $input[Entity::AMOUNT];

        $contactId = null;

        if (array_key_exists(Payout\Entity::CONTACT_ID, $input))
        {
            $contactId = $input[Payout\Entity::CONTACT_ID];
        }

        $payoutPayload = $this->getPayloadForFeeRecoveryPayoutCustomAmount($balance, $amount, $contactId);

        if (array_key_exists(Payout\Entity::NARRATION, $input))
        {
            $narration = $input[Payout\Entity::NARRATION];

            $payoutPayload[Payout\Entity::NARRATION] = $narration;
        }


        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUT_CREATE_REQUEST,
            [
                'payload' => $payoutPayload
            ]);

        $feeRecoveryPayout = (new Payout\Core)->createPayoutToFundAccount($payoutPayload,
            $merchant,
            null,
            true);

        $this->trace->info(
            TraceCode::FEE_RECOVERY_PAYOUT_CREATED,
            [
                'payout_data' => $feeRecoveryPayout->toArrayPublic()
            ]);

        return $feeRecoveryPayout->toArrayPublic();
    }

    protected function isSplitzExperimentEnable(array $properties, string $checkVariant, string $traceCode=null)
    {
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            if ($variant === $checkVariant)
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }
        return false;
    }

    protected function getPublicCollectionFromArrayWithType($array, $type)
    {
        $response = array();
        if ($type === 'payout')
        {
            foreach($array as $payout)
            {
                $payoutEntity = new Payout\Entity();
                $response[] = $payoutEntity->forceFill($payout);
            }
        }
        else if ($type === 'reversal')
        {
            foreach($array as $reversal)
            {
                $reversalEntity = new Reversal\Entity();
                $response[] = $reversalEntity->forceFill($reversal);
            }
        }
        return new Base\PublicCollection($response);
    }

    /**
     * @throws BadRequestException
     */
    public function getFeeRecoveryByEntityIdsAndType(array $entityIds, string $entityType, string $type)
    {
        Validator::validateFeeRecoveryType($type);
        return $this->fetchFeeRecoveryViaBatching($entityIds, $entityType, $type, self::OVERRIDDEN_BATCH_SIZE);
    }

    /**
     * This function picks up all payouts, failed payouts and reversals between a certain period,
     * checks for any discrepancies between the number of payouts and corresponding fee recovery entries.
     * For any discrepancies this method will do the data correction by creating the debit/credit entries in the fee recovery table.
     *
     * @param array $input
     * input contains - balance_id, from and to fields
     *
     * @throws Exception\BadRequestException
     */
    public function processFeeRecoveryDataCorrection(array $input): bool
    {
        $balanceId = $input[Entity::BALANCE_ID];
        $startTimeStamp = $input[Entity::FROM];
        $endTimeStamp = $input[Entity::TO];

        $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
            'balanceId' => $balanceId,
            'startTimeStamp' => $startTimeStamp,
            'endTimeStamp' => $endTimeStamp
        ]);

        $balance = $this->repo->balance->findOrFailById($balanceId);
        (new Validator)->validateBalanceTypeAndTimeStamps($balance, $startTimeStamp, $endTimeStamp);

        $response = $this->mutex->acquireAndRelease(
            'fee_recovery_data_correction_' . $balance->getId(),
            function() use ($balance, $startTimeStamp, $endTimeStamp)
            {
                $totalCorrections = 0;
                $totalCorrectionsFailed = 0;

                $properties = ['id' => $balance->getId(),
                    'experiment_id' => 'fee_recovery_fetch_batching',
                    'request_data'  => json_encode(['id' => $balance->getId()])
                ];
                $feeRecoveryBatchingEnabled = $this->isSplitzExperimentEnable($properties, 'enable');

                if($feeRecoveryBatchingEnabled){
                    list ($initiatedPayouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecoveryViaBatching($balance, $startTimeStamp, $endTimeStamp);
                    list ($feeRecoveryPayouts, $feeRecoveryFailedPayouts, $feeRecoveryReversals) = $this->getFeeRecoveryForPayouts($initiatedPayouts->getIds(), $failedPayouts->getIds(), $reversals->getIds(), self::OVERRIDDEN_BATCH_SIZE);
                } else {
                    list ($initiatedPayouts, $failedPayouts, $reversals) = $this->getPayoutAndReversalEntitiesForFeeRecovery($balance, $startTimeStamp, $endTimeStamp);
                    list ($feeRecoveryPayouts, $feeRecoveryFailedPayouts, $feeRecoveryReversals) = $this->getFeeRecoveryForPayouts($initiatedPayouts->getIds(), $failedPayouts->getIds(), $reversals->getIds());
                }

                $initiatedPayoutCountDiff = count($initiatedPayouts) - count($feeRecoveryPayouts);
                $failedCountDiff = count($failedPayouts) - count($feeRecoveryFailedPayouts);
                $reversalCountDiff = count($reversals) - count($feeRecoveryReversals);

                $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                    "balanceId" => $balance->getId(),
                    "initiatedPayouts" => count($initiatedPayouts),
                    "feeRecoveryPayouts" => count($feeRecoveryPayouts),
                    "failedPayouts" => count($failedPayouts),
                    "feeRecoveryFailedPayouts" => count($feeRecoveryFailedPayouts),
                    "reversals" => count($reversals),
                    "feeRecoveryReversals" => count($feeRecoveryReversals)
                ]);

                if($initiatedPayoutCountDiff == 0 && $failedCountDiff == 0 && $reversalCountDiff == 0){
                    $this->trace->count(Metric::FEE_RECOVERY_ISSUE, [Metric::STATUS => Metric::NO_ISSUE]);
                    return true;
                }

                $this->trace->count(Metric::FEE_RECOVERY_ISSUE, [Metric::STATUS => Metric::INITIATED]);
                if($initiatedPayoutCountDiff < 0 || $failedCountDiff < 0 || $reversalCountDiff < 0){
                    $this->trace->count(Metric::FEE_RECOVERY_ISSUE, [Metric::STATUS => Metric::NEGATIVE_COUNT]);
                    $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                        "balanceId" => $balance->getId(),
                        "startTime" => $startTimeStamp,
                        "endTime" => $endTimeStamp,
                        "initiatedPayoutCountDiff" => $initiatedPayoutCountDiff,
                        "failedCountDiff" => $failedCountDiff,
                        "reversalCountDiff" => $reversalCountDiff,
                    ]);
                }

                if($initiatedPayoutCountDiff > 0){
                    list($correctedPayoutIds, $uncorrectedPayoutIds) = $this->feeRecoveryDataCorrectionForInitiatedPayouts($initiatedPayouts, $feeRecoveryPayouts);
                    $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                        "balanceId" => $balance->getId(),
                        "initiatedPayoutCountDiff" => $initiatedPayoutCountDiff,
                        "correctedPayoutIds" => $correctedPayoutIds,
                        "uncorrectedPayoutIds" => $uncorrectedPayoutIds
                    ]);
                    $totalCorrections = $totalCorrections + count($correctedPayoutIds);
                    $totalCorrectionsFailed = $totalCorrectionsFailed + count($uncorrectedPayoutIds);
                }

                if($failedCountDiff > 0){
                    list($correctedPayoutIds, $uncorrectedPayoutIds) = $this->feeRecoveryDataCorrectionForFailedPayouts($failedPayouts, $feeRecoveryFailedPayouts);
                    $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                        "balanceId" => $balance->getId(),
                        "failedCountDiff" => $failedCountDiff,
                        "correctedPayoutIds" => $correctedPayoutIds,
                        "uncorrectedPayoutIds" => $uncorrectedPayoutIds
                    ]);
                    $totalCorrections = $totalCorrections + count($correctedPayoutIds);
                    $totalCorrectionsFailed = $totalCorrectionsFailed + count($uncorrectedPayoutIds);
                }

                if($reversalCountDiff > 0){
                    list($correctedReversalIds, $uncorrectedReversalsIds) = $this->feeRecoveryDataCorrectionForReversedPayouts($reversals, $feeRecoveryReversals);
                    $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                        "balanceId" => $balance->getId(),
                        "reversalCountDiff" => $reversalCountDiff,
                        "correctedReversalIds" => $correctedReversalIds,
                        "uncorrectedReversalsIds" => $uncorrectedReversalsIds
                    ]);
                    $totalCorrections = $totalCorrections + count($correctedReversalIds);
                    $totalCorrectionsFailed = $totalCorrectionsFailed + count($uncorrectedReversalsIds);
                }

                $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                    "balanceId" => $balance->getId(),
                    "startTimeStamp" => $startTimeStamp,
                    "endTimeStamp" => $endTimeStamp,
                    "totalCorrections" => $totalCorrections,
                    "totalCorrectionsFailed" => $totalCorrectionsFailed
                ]);

                return $this->isFeeRecoveryIssueResolved($balance->getId(), $initiatedPayouts, $failedPayouts, $reversals);
            },
            600,
            ErrorCode::BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS);

        return $response;
    }

    private function getFeeRecoveryForPayouts($initiatedPayoutIds, $failedPayoutIds, $reversalIds, $batchSize=self::BATCH_SIZE)
    {
        $initiatedPayoutFeeRecoveries = $this->fetchFeeRecoveryViaBatching($initiatedPayoutIds,
            Entity::PAYOUT,
            Type::DEBIT,
            $batchSize);

        $failedPayoutsFeeRecoveries = $this->fetchFeeRecoveryViaBatching($failedPayoutIds,
            Entity::PAYOUT,
            Type::CREDIT,
            $batchSize);

        $reversalPayoutsFeeRecoveries = $this->fetchFeeRecoveryViaBatching($reversalIds,
            Entity::REVERSAL,
            Type::CREDIT,
            $batchSize);

        return [$initiatedPayoutFeeRecoveries, $failedPayoutsFeeRecoveries, $reversalPayoutsFeeRecoveries];
    }


    public function fetchFeeRecoveryViaBatching($entityIdList, $entityType, $type, $batch=self::BATCH_SIZE)
    {
        $left = 0;

        $feeRecoveryEntities = [];

        $this->trace->info(
            TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
            [
                'step'                   => self::INITIATE,
                'operation'              => self::FETCH,
                'start'                  => $left,
                'batch_size'             => $batch,
                'fee_recovery_entities'      => $feeRecoveryEntities,
                'total_entity_count'     => count($entityIdList),
                'entity_type'            => $entityType,
                'type'                   => $type,
            ]);

        while ($left < count($entityIdList))
        {
            $currentSlice = array_slice($entityIdList, $left, $batch, true);

            $left += $batch;

            $feeRecoveryEntities += $this->repo->fee_recovery->fetchFeeRecoveries($currentSlice, $entityType, $type)->toArrayWithItems()["items"];

            $this->trace->info(
                TraceCode::FEE_RECOVERY_BATCHING_PROCESS,
                [
                    'step'                   => self::INTERMEDIATE,
                    'operation'              => self::FETCH,
                    'start'                  => $left,
                    'batch_size'             => $batch,
                    'fee_recovery_count'      => count($feeRecoveryEntities),
                    'total_entity_count'     => count($entityIdList),
                    'current_slice_count'    => count($currentSlice),
                ]);

        }

        return new Base\PublicCollection($feeRecoveryEntities);
    }

    private function feeRecoveryDataCorrectionForInitiatedPayouts(Base\PublicCollection $initiatedPayouts, Base\PublicCollection $feeRecoveryPayouts): array
    {
        $feeRecoveryMissingPayouts = $this->getFeeRecoveryMissingPayouts($initiatedPayouts->all(), $feeRecoveryPayouts->all());
        $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, ["feeRecoveryMissingPayouts" => $feeRecoveryMissingPayouts,]);
        return  $this->createFeeRecoveryForEntityWithType($feeRecoveryMissingPayouts, Type::DEBIT);
    }

    private function feeRecoveryDataCorrectionForFailedPayouts(Base\PublicCollection $failedPayouts, Base\PublicCollection $feeRecoveryFailedPayouts)
    {
        $feeRecoveryMissingFailedPayouts = new Base\PublicCollection($this->getFeeRecoveryMissingPayouts($failedPayouts->all(), $feeRecoveryFailedPayouts->all()));
        $payoutsWithDebitEntries = $this->getFeeRecoveryByEntityIdsAndType($feeRecoveryMissingFailedPayouts->getIds(), Entity::PAYOUT, Type::DEBIT);
        $payoutsWithDebitEntriesIds = array_column($payoutsWithDebitEntries->all(), 'entity_id');
        $feeRecoveryMissingFailedPayoutsWithDebitEntries =  array_filter($feeRecoveryMissingFailedPayouts->all(), function($payout) use ($payoutsWithDebitEntriesIds) {
            return in_array($payout->id, $payoutsWithDebitEntriesIds);
        });
        $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
            "feeRecoveryMissingFailedPayouts" => $feeRecoveryMissingFailedPayouts,
            "payoutsWithDebitEntries" => $payoutsWithDebitEntries,
            "payoutsWithDebitEntriesIds" => $payoutsWithDebitEntriesIds,
            "feeRecoveryMissingFailedPayoutsWithDebitEntries" => $feeRecoveryMissingFailedPayoutsWithDebitEntries
        ]);
        return $this->createFeeRecoveryForEntityWithType($feeRecoveryMissingFailedPayoutsWithDebitEntries, Type::CREDIT);
    }

    /**
     * @throws BadRequestException
     */
    private function feeRecoveryDataCorrectionForReversedPayouts(Base\PublicCollection $reversals, Base\PublicCollection $feeRecoveryReversals): array
    {
        $feeRecoveryMissingReversals = new Base\PublicCollection($this->getFeeRecoveryMissingPayouts($reversals->all(), $feeRecoveryReversals->all()));
        $feeRecoveryMissingReversalPayoutsIds = array_column($feeRecoveryMissingReversals->all(), 'entity_id');

        $payoutsWithDebitEntries = $this->getFeeRecoveryByEntityIdsAndType($feeRecoveryMissingReversalPayoutsIds, Entity::PAYOUT, Type::DEBIT);
        $payoutsWithDebitEntriesIds = array_column($payoutsWithDebitEntries->all(), 'entity_id');
        $feeRecoveryMissingReversalsWithDebitEntries =  array_filter($feeRecoveryMissingReversals->all(), function($reversal) use ($payoutsWithDebitEntriesIds) {
            return in_array($reversal->getEntityId(), $payoutsWithDebitEntriesIds);
        });
        $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
            "feeRecoveryMissingReversals" => $feeRecoveryMissingReversals,
            "feeRecoveryMissingReversalPayoutsIds" => $feeRecoveryMissingReversalPayoutsIds,
            "payoutsWithDebitEntries" => $payoutsWithDebitEntries,
            "payoutsWithDebitEntriesIds" => $payoutsWithDebitEntriesIds,
            "feeRecoveryMissingReversalsWithDebitEntries" => $feeRecoveryMissingReversalsWithDebitEntries
        ]);
        return $this->createFeeRecoveryForEntityWithType($feeRecoveryMissingReversalsWithDebitEntries, Type::CREDIT);
    }

    private function getFeeRecoveryMissingPayouts(array $payouts, array $feeRecoveryPayouts): array
    {
        $feeRecoveryIds = array_column($feeRecoveryPayouts, 'entity_id');
        $missingPayouts = array_filter($payouts, function ($payout) use ($feeRecoveryIds) {
            return !in_array($payout['id'], $feeRecoveryIds);
        });

        return array_values($missingPayouts);
    }

    /**
     * @param array $feeRecoveryMissingForEntityWithDebitEntries
     * @return array[]
     */
    public function createFeeRecoveryForEntityWithType(array $feeRecoveryMissingForEntityWithDebitEntries, string $type): array
    {
        $correctedEntityIds = [];
        $uncorrectedEntityIds = [];
        foreach ($feeRecoveryMissingForEntityWithDebitEntries as $entity) {
            try {
                $this->createFeeRecoveryEntityForSourceAndType($entity, $type);
                $correctedEntityIds[] = $entity->getId();
            } catch (\Throwable $e) {
                $uncorrectedEntityIds[] = $entity->getId();
                $this->trace->error(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
                    'entity' => $entity,
                    'type' => $type,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'details' => "Failed to create fee recovery entry"
                ]);
            }
        }
        return [$correctedEntityIds, $uncorrectedEntityIds];
    }

    /**
     * @param mixed $initiatedPayouts
     * @param mixed $failedPayouts
     * @param mixed $reversals
     * @return void
     */
    function isFeeRecoveryIssueResolved(string $balanceId, mixed $initiatedPayouts, mixed $failedPayouts, mixed $reversals): bool
    {
        list ($feeRecoveryPayouts, $feeRecoveryFailedPayouts, $feeRecoveryReversals) = $this->getFeeRecoveryForPayouts($initiatedPayouts->getIds(), $failedPayouts->getIds(), $reversals->getIds(), self::OVERRIDDEN_BATCH_SIZE);

        $initiatedPayoutCountDiff = count($initiatedPayouts) - count($feeRecoveryPayouts);
        $failedCountDiff = count($failedPayouts) - count($feeRecoveryFailedPayouts);
        $reversalCountDiff = count($reversals) - count($feeRecoveryReversals);

        $this->trace->info(TraceCode::FEE_RECOVERY_DATA_CORRECTION_PROCESS, [
            'balanceId' => $balanceId,
            'message' => "Post Data Correction",
            'initiatedPayoutCountDiff' => $initiatedPayoutCountDiff,
            'failedPayoutCountDiff' => $failedCountDiff,
            'reversalCountDiff' => $reversalCountDiff
        ]);
        if ($initiatedPayoutCountDiff == 0 && $failedCountDiff == 0 && $reversalCountDiff == 0) {
            $this->trace->count(Metric::FEE_RECOVERY_ISSUE, [Metric::STATUS => Metric::RESOLVED]);
            return true;
        } else {
            $this->trace->count(Metric::FEE_RECOVERY_ISSUE, [Metric::STATUS => Metric::UNRESOLVED]);
            return false;
        }
    }

}
