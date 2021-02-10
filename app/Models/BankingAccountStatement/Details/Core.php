<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    const DEFAULT_MUTEX_LOCK_RETRIES = 4;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    // create function to be called from createOrUpdate function only or check if a record already exists.
    protected function create(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_CREATE_REQUEST, $input);

        $basDetailEntity = new Entity();

        $basDetailEntity->build($input);

        $this->repo->saveOrFail($basDetailEntity);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_CREATE_RESPONSE, $basDetailEntity->toArray());

        return $basDetailEntity;
    }

    public function createOrUpdate(array $input)
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_FETCH_RULES, $input);

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $basDetailEntity = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

        if ($basDetailEntity === null)
        {
            $this->create($input);
        }
        else
        {
            $retries = self::DEFAULT_MUTEX_LOCK_RETRIES;

            do
            {
                $mutexLockAcquired = true;
                try
                {
                    $this->mutex->acquireAndRelease(
                        'banking_account_statement_details_' . $basDetailEntity->getId(),
                        function () use ($basDetailEntity, $input) {

                            if ((array_key_exists(Entity::GATEWAY_BALANCE, $input) === true) and
                                ($input[Entity::GATEWAY_BALANCE] !== $basDetailEntity->getGatewayBalance()))
                            {
                                $this->updateGatewayBalance($basDetailEntity, $input[Entity::GATEWAY_BALANCE]);
                            }

                            if (array_key_exists(Entity::STATEMENT_CLOSING_BALANCE, $input) === true)
                            {
                                $this->updateStatementClosingBalance($basDetailEntity, $input[Entity::STATEMENT_CLOSING_BALANCE]);
                            }
                        },
                        30,
                        ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_DETAILS_OPERATION_IN_PROGRESS
                    );
                }
                catch (Exception\BadRequestException $e)
                {
                    if ($e->getCode() === ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_DETAILS_OPERATION_IN_PROGRESS)
                    {
                        $mutexLockAcquired = false;

                        $retries--;
                    }
                }
            }
            while(($retries >= 0) and ($mutexLockAcquired === false));
        }
    }

    public function updateGatewayBalance(Entity $basDetail, int $gatewayBalance)
    {
        $basDetail->setGatewayBalance($gatewayBalance);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_GATEWAY_BALANCE, $basDetail->toArray());

        $this->repo->saveOrFail($basDetail);

        return $basDetail;
    }

    public function updateStatementClosingBalance(Entity $basDetail, int $statementClosingBal)
    {
        $basDetail->setStatementClosingBalance($statementClosingBal);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_STATEMENT_CLOSING_BALANCE, $basDetail->toArray());

        $this->repo->saveOrFail($basDetail);

        return $basDetail;
    }
}
