<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class Core extends Base\Core
{
    protected $mutex;

    const DEFAULT_MUTEX_LOCK_RETRIES = 4;

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

    private function updateStatementDetailsStatus(Entity $basDetailObj, string $status): Entity
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::STATUS_UPDATE_RULES, array("status" => $status));
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_STATE,
                           array(Entity::STATUS => $status));
        $basDetailObj->setStatus($status);
        $this->repo->saveOrFail($basDetailObj);

        return $basDetailObj;
    }

    public function archiveStatementDetail(Entity $basDetailObj)
    {
        return $this->updateStatementDetailsStatus($basDetailObj, Status::ARCHIVED);
    }

    public function activateStatementDetail(Entity $basDetailObj)
    {
        return $this->updateStatementDetailsStatus($basDetailObj, Status::ACTIVE);
    }
}
