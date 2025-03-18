<?php

namespace RZP\Models\BankingAccountStatement\Processor;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Admin;
use RZP\Trace\Tracer;
use Rzp\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\BankingAccountStatement\Pool;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Metric;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\BankingAccountStatement\Core as BasCore;
use RZP\Models\BankingAccountStatement\Details as BasDetails;

abstract class Base extends BaseCore
{
    const MOZART_NAMESPACE = 'razorpayx';

    const MOZART_ACTION    = 'account_statement';

    protected $accountNumber;

    protected $source;

    protected $version;

    protected $channel;

    /** @var BasDetails\Entity */
    protected $basDetails;

    abstract public function checkForDuplicateTransactions(array $bankTransactions,
                                                           string $channel,
                                                           string $accountNumber,
                                                           Merchant\Entity $merchant);

    abstract protected function sendRequestAndGetResponse(array $input);

    abstract public function sendRequestToFetchStatement(array $input);

    abstract public function getUtrForChannel(PublicEntity $basEntity);

    public function __construct(string $channel, string $accountNumber)
    {
        parent::__construct();

        $this->setChannel($channel)
             ->setAccountNumber($accountNumber);
    }

    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function fetchAccountStatementDetails(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_SOURCE,
            [
                'input'     => $input,
                'source'    => $this->source,
                'version'   => $this->version
            ]);

        if ($this->source === Source::FETCH_API)
        {
            return $this->fetchAccountStatementDetailsViaFetchApi($input);
        }
        else
        {
            throw new Exception\LogicException(
                'Unhandled source passed to fetch account statement details',
                null,
                [
                    'source' => $this->source,
                ]);
        }
    }

    protected function fetchAccountStatementDetailsViaFetchApi(array $input)
    {
        $formattedResponse = $this->sendRequestAndGetResponse($input);

        return $formattedResponse;
    }

    protected function validateInputForMozartRequest(array $input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('create', $input);
    }

    protected function getValidator()
    {
        $validator = __NAMESPACE__ . '\\' . studly_case($this->channel) . '\\' . 'Validator';

        return new $validator;
    }

    protected function getLastBankTransaction()
    {
        $isAccountTypeShared = ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED);

        switch (true)
        {
            case (($isAccountTypeShared === true) and
                  ($this->channel === Channel::RBL)):
                /** @var Pool\Rbl\Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement_pool_rbl
                    ->findLatestByAccountNumber($this->getAccountNumber());

                break;

            case (($isAccountTypeShared === true) and
                  ($this->channel === Channel::ICICI)):
                /** @var Pool\Icici\Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement_pool_icici
                    ->findLatestByAccountNumber($this->getAccountNumber());

                break;

            default:
                $accountNumber = $this->getAccountNumber();
                $properties = ['experiment_id' => 'x_bas_reads_from_slave', 'id'=>$accountNumber];

                $experimentResult = $this->isSplitzExperimentEnable($properties, 'enabled');

                /** @var Entity|null $bankTxn */
                $bankTxn = $this->repo->banking_account_statement
                    ->findLatestByAccountNumberAndChannel($accountNumber, $this->channel, $experimentResult);
        }

        return $bankTxn;
    }

    protected function alterStatementColumnsToMatch()
    {
        if ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED)
        {
            array_delete(Entity::CHANNEL, $this->statementRecordsToMatch);
        }
    }

    protected function arrangeColumnsToFindDuplicates($bankTransaction)
    {
        $record = [
            Entity::BANK_TRANSACTION_ID => $bankTransaction[Entity::BANK_TRANSACTION_ID],
            Entity::BANK_SERIAL_NUMBER  => $bankTransaction[Entity::BANK_SERIAL_NUMBER],
            Entity::TRANSACTION_DATE    => $bankTransaction[Entity::TRANSACTION_DATE],
            Entity::AMOUNT              => $bankTransaction[Entity::AMOUNT],
        ];

        if ($this->basDetails->getAccountType() === BasDetails\AccountType::DIRECT)
        {
            $record[Entity::CHANNEL] = $bankTransaction[Entity::CHANNEL];
        }

        $record[Entity::ACCOUNT_NUMBER] = $bankTransaction[Entity::ACCOUNT_NUMBER];

        return $record;
    }

    protected function getColumnsToFindDuplicates($bankTransaction)
    {
        $record = [
            $bankTransaction[Entity::BANK_TRANSACTION_ID],
            $bankTransaction[Entity::BANK_SERIAL_NUMBER],
            $bankTransaction[Entity::TRANSACTION_DATE],
            $bankTransaction[Entity::AMOUNT],
        ];

        if ($this->basDetails->getAccountType() === BasDetails\AccountType::DIRECT)
        {
            $record[] = $bankTransaction[Entity::CHANNEL];
        }

        $record[] = $bankTransaction[Entity::ACCOUNT_NUMBER];

        return $record;
    }

    protected function getTimestampFromDateString(string $dateStr, string $timezone = Timezone::IST): int
    {
        $date = Carbon::parse($dateStr, $timezone);

        $timestamp = $date->timestamp;

        return $timestamp;
    }

    protected function getDateTimeStringFromTimestamp(
        int $timestamp,
        string $format,
        string $timezone = Timezone::IST): string
    {
        $date = Carbon::createFromTimestamp($timestamp, $timezone)->format($format);

        return $date;
    }

    protected function setChannel(string $channel)
    {
        Channel::validate($channel);

        $this->channel = $channel;

        return $this;
    }

    protected function getChannel()
    {
        return $this->channel;
    }

    protected function setAccountNumber(string $accountNumber)
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    protected function getAccountNumber()
    {
        return $this->accountNumber;
    }

    protected function getStartOfFinancialYear($timestamp)
    {
        $currentTime = Carbon::createFromTimestamp($timestamp, Timezone::IST);
        $year = $currentTime->year;
        $month = $currentTime->month;

        if($month < Carbon::APRIL){
            $year = $year-1;
        }

        return Carbon::create($year, Carbon::APRIL , 1, 0, 0, 0, Timezone::IST);
    }

    protected function getStartOfMonth($timestamp)
    {
        $currentTime = Carbon::createFromTimestamp($timestamp, Timezone::IST);
        $year = $currentTime->year;
        $month = $currentTime->month;

        return Carbon::create($year, $month , 1, 0, 0, 0, Timezone::IST);
    }

    protected function getStartTime()
    {
        if ($this->basDetails->getAccountType() === BasDetails\AccountType::SHARED)
        {
            return $this->getStartOfMonth($this->basDetails->getCreatedAt());
        }

        return $this->getStartOfFinancialYear($this->basDetails->getCreatedAt());
    }

    public function storeMissingStatementsInRedis(array $missingStatements, $accountNumber, $merchantId)
    {
        $channel = $this->channel;

        try
        {
            $this->app['api.mutex']->acquireAndRelease(
                'update_redis_missing_statements_recon_' . $accountNumber . '_' . $merchantId,
                function() use ($accountNumber, $channel, $missingStatements, $merchantId) {

                    $startTime = microtime(true);

                    $redis = $this->app['redis'];

                    $redisKey = sprintf(BasCore::MISSING_STATEMENTS_REDIS_KEY, $merchantId, $accountNumber);

                    $getMissingStatements = $redis->get($redisKey);

                    $merchantMissingStatementList = (isset($getMissingStatements) === true) ?
                        json_decode($getMissingStatements, true) : null;

                    if (empty($merchantMissingStatementList) === true)
                    {
                        $merchantMissingStatementList = array_values($missingStatements);
                    }
                    else
                    {
                        $encodedMissingStatements = array_map('json_encode', $missingStatements);

                        $encodedExistingMissingStatements = array_map(
                            'json_encode',
                            $merchantMissingStatementList
                        );

                        $encodedUniqueMissingStatements = array_values(array_diff(
                                                                           $encodedMissingStatements,
                                                                           $encodedExistingMissingStatements
                                                                       ));

                        $uniqueMissingStatements = array_map(
                            'json_decode',
                            $encodedUniqueMissingStatements,
                            array_map('boolval', $encodedUniqueMissingStatements)
                        );

                        $existingMissingStatements = array_map(
                            'json_decode',
                            $encodedExistingMissingStatements,
                            array_map('boolval', $encodedExistingMissingStatements)
                        );

                        $merchantMissingStatementList = array_merge(
                            $existingMissingStatements,
                            $uniqueMissingStatements
                        );
                    }

                    $redis->set($redisKey, json_encode($merchantMissingStatementList));

                    $endTime = microtime(true);

                    $this->trace->info(TraceCode::MISSING_STATEMENTS_REDIS_UPDATE_SUCCESS, [
                        Entity::ACCOUNT_NUMBER   => $accountNumber,
                        Entity::MERCHANT_ID      => $merchantId,
                        'current_redis_count'    => count($merchantMissingStatementList),
                        'new_missing_statements' => count($missingStatements),
                        'total_time_taken'       => $endTime - $startTime,
                    ]);
                },
                60,
                TraceCode::MISSING_BAS_UPDATE_IN_PROGRESS,
                3
            );
        }
        catch (\Exception $exception)
        {
            $this->trace->count(Metric::MISSING_STATEMENT_REDIS_INSERT_FAILURES, [
                Metric::LABEL_CHANNEL => $channel,
                'action'              => 'insertion'
            ]);

            $this->trace->traceException(
                $exception,
                null,
                TraceCode::INSERTION_OF_MISSING_STATEMENTS_INTO_REDIS_FAILURE,
                [
                    Entity::MERCHANT_ID    => $merchantId,
                    Entity::ACCOUNT_NUMBER => $accountNumber,
                    Entity::CHANNEL        => $channel
                ]
            );

            Tracer::startSpanWithAttributes(HyperTrace::MISSING_STATEMENT_REDIS_INSERT_FAILURES);
        }
    }

    public function compareAndReturnMatchedBASFromFetchedStatements($fetchedStatements = []): array
    {
        $matchedBASFromBank = new Entity();

        $existingBAS = new Entity();

        $count = count($fetchedStatements);

        while ($count > 0)
        {
            $basEntityFromBank = (new Entity)->build($fetchedStatements[$count - 1]);

            if (empty($basEntityFromBank->getUtr()) === true)
            {
                $utr = $this->getUtrForChannel($basEntityFromBank);

                $basEntityFromBank->setUtr($utr);
            }

            if ($basEntityFromBank->getUtr() !== null)
            {
                $existingBASEntries = $this->repo->banking_account_statement->fetchByUtrAndType(
                    $basEntityFromBank->getUtr(),
                    $basEntityFromBank->getType(),
                    $basEntityFromBank->getAccountNumber(),
                    $basEntityFromBank->getChannel()
                );

                $existingBAS = $existingBASEntries[0];

                if (count($existingBASEntries) > 0)
                {
                    $matchedBASFromBank = $basEntityFromBank;

                    break;
                }
            }

            $count--;
        }

        return [$matchedBASFromBank, $existingBAS];
    }
    private function isSplitzExperimentEnable(array $properties, string $checkVariant, string $traceCode=null)
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
}
