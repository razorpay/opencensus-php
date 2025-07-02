<?php

namespace RZP\Models\BankingAccountStatement\Details;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Constants\Entity as EntityConstants;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\BankingAccountStatement\Metric;
use RZP\Trace\TraceCode;
use Throwable;

class Core extends Base\Core
{
    protected $mutex;

    const DEFAULT_MUTEX_LOCK_RETRIES = 4;

    const PAYOUT_SERVICE_BAS_DETAILS_TABLE = 'banking_account_statement_details';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function createOrUpdate(array $input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_FETCH_RULES, $input);

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        // Todo: __pobo__ To be handled by payouts team
        $accountType = $input[Entity::ACCOUNT_TYPE];

        /* @var Entity $basDetailEntity */
        $basDetailEntity = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

        if ($basDetailEntity === null)
        {
            return $this->create($input);
        }
        else
        {
            $retries = self::DEFAULT_MUTEX_LOCK_RETRIES;

            try
            {
                $basDetailEntity = $this->mutex->acquireAndRelease(
                    'banking_account_statement_details_' . $basDetailEntity->getId(),
                    function() use ($basDetailEntity, $input) {

                        // update gateway balance is called each time gateway balance is fetched by cron. We save the
                        // value fetched by cron only if it is not equal to existing value.
                        if (array_key_exists(Entity::GATEWAY_BALANCE, $input) === true)
                        {
                            if ($input[Entity::GATEWAY_BALANCE] !== $basDetailEntity->getGatewayBalance())
                            {
                                $basDetailEntity = $this->updateGatewayBalance($basDetailEntity, $input[Entity::GATEWAY_BALANCE]);
                            }

                            $basDetailEntity->setBalanceLastFetchedAt(Carbon::now(Timezone::IST)->getTimestamp());

                            $this->repo->saveOrFail($basDetailEntity);

                            $this->updateGatewayBalanceInPS($basDetailEntity);
                        }

                        // update statement closing balance will be performed only when new records are fetched from bank
                        // which means merchant has transacted since last statement closing balance updation. Hence we
                        // don't check if the value is same as existing value.
                        if (array_key_exists(Entity::STATEMENT_CLOSING_BALANCE, $input) === true)
                        {
                            $this->updateStatementClosingBalance($basDetailEntity, $input[Entity::STATEMENT_CLOSING_BALANCE]);
                        }

                        return $basDetailEntity;
                    },
                    30,
                    ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_DETAILS_OPERATION_IN_PROGRESS,
                    $retries
                );

            }
            catch (Exception\BadRequestException $e)
            {
                if ($e->getCode() === ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_DETAILS_OPERATION_IN_PROGRESS)
                {
                    $this->trace->traceException(
                        $e,
                        null,
                        TraceCode::ANOTHER_BANKING_ACCOUNT_STATEMENT_DETAILS_OPERATION_IN_PROGRESS,
                        [
                            'channel'        => $channel,
                            'account_number' => $accountNumber,
                            'message'        => $e->getMessage(),
                        ]);
                }
                else
                {
                    throw $e;
                }
            }

            return $basDetailEntity;
        }
    }

    // create function to be called from createOrUpdate function only or check if a record already exists.
    public function create(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_CREATE_REQUEST, $input);

        $basDetailEntity = new Entity();

        $basDetailEntity->build($input);

        $this->repo->saveOrFail($basDetailEntity);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_CREATE_RESPONSE, $basDetailEntity->toArray());

        return $basDetailEntity;
    }

    public function updateGatewayBalance(Entity $basDetail, int $gatewayBalance)
    {
        $basDetail->setGatewayBalance($gatewayBalance);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_GATEWAY_BALANCE, $basDetail->toArray());

        return $basDetail;
    }

    public function updateStatementClosingBalance(Entity $basDetail, int $statementClosingBal)
    {
        $basDetail->setStatementClosingBalance($statementClosingBal);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_STATEMENT_CLOSING_BALANCE, $basDetail->toArray());

        $this->repo->saveOrFail($basDetail);

        return $basDetail;
    }

    public function archiveStatementDetail(Entity $basDetailObj, ?string $accountNumber = '')
    {
        $status = Status::ARCHIVED;

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_STATE, [
            Entity::STATUS  => $status
        ]);

        $basDetailObj->setStatus($status);

        if (!empty($accountNumber))
        {
            $basDetailObj->setAttribute(Entity::ACCOUNT_NUMBER, $accountNumber);
        }

        $this->repo->banking_account_statement_details->saveOrFail($basDetailObj);

        return $basDetailObj;
    }

    public function handleBasDetailsActions($input)
    {
        $action = array_pull($input, 'action');

        switch ($action)
        {
            case 'basd_status':
                $id = $input[Entity::ID];
                $status = $input[Entity::STATUS];

                /** @var Entity $basDetails */
                $basDetails = $this->repo->banking_account_statement_details->findOrFail($id);

               $basDetails->setStatus($status);

               $this->repo->banking_account_statement_details->saveOrFail($basDetails);

               break;

            case 'ps_basd_status':
                $id = array_pull($input, Entity::ID);

                $this->updateBasDetailsInPayoutService($id, $input);

                break;

            case 'ps_basd_create':
                $this->createBasDetailsInPayoutService($input['data']);

        }

        return ['success' => $input];
    }

    public function updateBasDetailsInPayoutService(string $basDetailsId, $data)
    {
        $tableName = self::PAYOUT_SERVICE_BAS_DETAILS_TABLE;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BAS_DETAILS_UPDATE,
            $data
        );

        $this->repo->payout->updateInPayoutServiceDB($tableName, $basDetailsId, $data);
    }

    public function createBasDetailsInPayoutService(array $data)
    {
        $tableName = self::PAYOUT_SERVICE_BAS_DETAILS_TABLE;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_BAS_DETAILS_CREATE,
            $data
        );

        $this->repo->payout->insertIntoPayoutServiceDB($tableName, $data);
    }

    public function updateGatewayBalanceInPS(Entity $basDetails)
    {
        $data = [
            Entity::GATEWAY_BALANCE => $basDetails->getGatewayBalance(),
            Entity::GATEWAY_BALANCE_CHANGE_AT => $basDetails->getGatewayBalanceChangeAt(),
            Entity::BALANCE_LAST_FETCHED_AT => $basDetails->getBalanceLastFetchedAt(),
        ];

        $this->updateBasDetailsInPayoutService($basDetails->getId(), $data);
    }

    public function updateStatementLastFetchedData(array $input)
    {
        (new Validator)->validateInput(Validator::UPDATE_STATEMENT_LAST_FETCHED_DATA_INPUT, $input);

        $statementUpdateInput = $input['input'];

        $id = $statementUpdateInput[Entity::ID];

        /* @var Entity $basDetailEntity */
        $basDetailEntity = $this->repo->banking_account_statement_details->find($id);

        if ($basDetailEntity != null &&
            $basDetailEntity->getLastStatementAttemptAt() > $statementUpdateInput['last_statement_attempt_at']
        )
        {
            $this->trace->info(
                TraceCode::UPDATE_STATEMENT_LAST_FETCHED_DATA_NO_ACTION,
                [
                    'input'         => $statementUpdateInput,
                    'current_value' => $basDetailEntity->getLastStatementAttemptAt()
                ]
            );

            return ['status' => 'success'];
        }

        // Update BASD table
        $basDetailEntity->updateLastStatementAttemptAt($statementUpdateInput['last_statement_attempt_at']);
        $this->repo->saveOrFail($basDetailEntity);

        // Update Settings table
        /* @var \RZP\Models\Merchant\Balance\Entity $balanceEntity */
        $balanceEntity = $basDetailEntity->balance;
        $balanceEntity->updateLastFetchedAtTo($statementUpdateInput['last_statement_attempt_at']);

        return ['status' => 'success'];
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws Throwable
     */
    public function handleDualWrite(array $input): ?array
    {
        if (empty($input)) {
            return null;
        }

        $balanceIds = [];
        $balanceIdToInputMap = [];
        $validationErrors = [];

        // Validate all items first and collect valid ones
        foreach ($input as $item) {
            try {
                (new Validator)->validateXBalanceUpdateDualWriteInput($item);
                $balanceId = $item[Entity::BALANCE_ID];
                $balanceIds[] = $balanceId;
                $balanceIdToInputMap[$balanceId] = $item;
            } catch (Throwable $e) {
                $validationErrors[] = [
                    'item' => $item,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Log validation errors if any
        if (!empty($validationErrors)) {
            $this->logValidationErrors($validationErrors);
        }

        if (empty($balanceIds)) {
            return ['success' => 'true'];
        }

        // Fetch all entities in one query
        $basDetailEntities = $this->repo->banking_account_statement_details->getAccountStatementDetailsByBalanceIds($balanceIds);

        // Process entities and collect updates
        $missingBalanceIds = array_diff($balanceIds, array_map(function($entity) {
            return $entity->getBalanceId();
        }, $basDetailEntities));

        // Log missing entities if any
        if (!empty($missingBalanceIds)) {
            $this->logMissingEntities($missingBalanceIds);
        }

        $updates = [];
        $violatingBalanceIds = [];
        foreach ($basDetailEntities as $basDetailEntity) {
            $balanceId = $basDetailEntity->getBalanceId();
            $inputData = $balanceIdToInputMap[$balanceId];
            $inputTimestamp = $inputData[Entity::BALANCE_LAST_FETCHED_AT];

            if ($basDetailEntity->getBalanceLastFetchedAt() <= $inputTimestamp) {
                $updates[] = [
                    'entity' => $basDetailEntity,
                    'inputData' => $inputData
                ];
            } else {
                $violatingBalanceIds[] = $balanceId;
            }
        }

        // Log order violations if any
        if (!empty($violatingBalanceIds)) {
            $this->logOrderViolations($violatingBalanceIds);
        }

        if (empty($updates)) {
            return ['success' => 'true'];
        }

        // Process updates in transaction
        return $this->processUpdates($updates);
    }

    private function processUpdates(array $updates): array
    {
        $this->repo->beginTransaction();
        try {
            foreach ($updates as $update) {
                $basDetailEntity = $update['entity'];
                $inputData = $update['inputData'];

                $basDetailEntity->setGatewayBalance($inputData[Entity::GATEWAY_BALANCE]);
                $basDetailEntity->setBalanceLastFetchedAt($inputData[Entity::BALANCE_LAST_FETCHED_AT]);
                if (!empty($inputData[Entity::GATEWAY_BALANCE_CHANGE_AT])) {
                    $basDetailEntity->setGatewayBalanceLastChangedAt($inputData[Entity::GATEWAY_BALANCE_CHANGE_AT]);
                }

                $this->repo->saveOrFail($basDetailEntity);
                $this->updateGatewayBalanceInPS($basDetailEntity);
            }

            $this->repo->commit();
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_DUAL_WRITE_UPDATED_SUCCESSFULLY);

            return ['success' => 'true'];
        } catch (Throwable $e) {
            $this->repo->rollBack();
            $this->logUpdateFailure($e);
            throw $e;
        }
    }

    private function logValidationErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->trace->error(
                TraceCode::X_BALANCE_DUAL_WRITE_INPUT_VALIDATION_FAILED,
                $error
            );

            $this->trace->count(Metric::BASD_DUAL_WRITE_ERROR_COUNT, [
                'code' => TraceCode::X_BALANCE_DUAL_WRITE_INPUT_VALIDATION_FAILED,
            ]);
        }
    }

    private function logMissingEntities(array $balanceIds): void
    {
        foreach ($balanceIds as $balanceId) {
            $this->trace->error(
                TraceCode::X_BALANCE_DUAL_WRITE_BASD_ENTITY_NOT_FOUND_ERROR,
                ['balance_id' => $balanceId]
            );

            $this->trace->count(Metric::BASD_DUAL_WRITE_ERROR_COUNT, [
                'code' => TraceCode::X_BALANCE_DUAL_WRITE_BASD_ENTITY_NOT_FOUND_ERROR,
            ]);
        }
    }

    private function logOrderViolations(array $violatingBalanceIds): void
    {
        $this->trace->error(
            TraceCode::X_BALANCE_DUAL_WRITE_MESSAGE_ORDER_VIOLATION,
            ['balance_ids' => $violatingBalanceIds]
        );

        $this->trace->count(Metric::BASD_DUAL_WRITE_ERROR_COUNT, [
            'code' => TraceCode::X_BALANCE_DUAL_WRITE_MESSAGE_ORDER_VIOLATION,
        ]);
    }

    private function logUpdateFailure(Throwable $e): void
    {
        $this->trace->error(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_DUAL_WRITE_UPDATE_FAILED_UPDATE,
            ['error' => $e->getMessage()]
        );

        $this->trace->count(Metric::BASD_DUAL_WRITE_ERROR_COUNT, [
            'error_code' => TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_DUAL_WRITE_UPDATE_FAILED_UPDATE,
        ]);
    }
}
