<?php

namespace RZP\Models\BankingAccountStatement\Pool\Base;

use Database\Connection;
use Mail;
use File;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\BankingAccountStatement\Processor;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccountStatement\Pool\Rbl\Repository as rblRepo;
use RZP\Models\BankingAccountStatement\Pool\Icici\Repository as iciciRepo;

class Core extends Base\Core
{
    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT = 200;

    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL_DEFAULT = 100;

    const ACCOUNT_STATEMENT_FETCH_API_VERSION_1 = 'v1';

    const ACCOUNT_STATEMENT_FETCH_API_VERSION_2 = 'v2';

    protected $mutex;

    /** @var rblRepo|iciciRepo $channelRepo */
    protected $channelRepo;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /** @var BASDetails\Entity $basDetails  */
    public $basDetails = null;

    public function getBasDetails(string $accountNumber = null, string $channel = null)
    {
        if (($this->basDetails === null) and
            ($accountNumber !== null) and
            ($channel !== null))
        {
            $this->basDetails = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);
        }

        return $this->basDetails;
    }

    public function fetchAccountStatementV2(array $input)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
                [
                    'channel'        => $channel,
                    'account_number' => $accountNumber,
                ]);

            $this->mutex->acquireAndRelease(
                'banking_account_statement_pool_fetch_' . $accountNumber . '_' . $channel,
                function () use ($channel, $accountNumber, $input)
                {
                    $basDetailEntity = $this->getBasDetails($accountNumber, $channel);

                    $basDetailEntity->setLastStatementAttemptAt();

                    $this->repo->saveOrFail($basDetailEntity);

                    $merchant = $basDetailEntity->merchant;

                    $accountStatementApiVersion = $this->getAccountStatementApiVersion($basDetailEntity);

                    $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

                    $input[Entity::MERCHANT_ID] = $merchant->getId();

                    $bankTransactions = $processor->fetchAccountStatementDetails($input);

                    $this->saveAccountStatementDetails($bankTransactions, $merchant, $channel, $accountNumber, $processor, $basDetailEntity);
                },
                300,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
            );
        }
        catch (Exception\BadRequestException $e)
        {
            if ($e->getCode() === ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS,
                    [
                        'channel'           => $channel,
                        'account_number'    => $accountNumber,
                        'message'           => $e->getMessage(),
                    ]);
            }

            throw $e;
        }

        return ['channel' => $channel, 'account_number' => $accountNumber];
    }

    // Select account statement api's version. This will be passed to gateway in constructor to choose required api.
    public function getAccountStatementApiVersion(BASDetails\Entity $basDetails)
    {
        $accountStatementApiVersion = self::ACCOUNT_STATEMENT_FETCH_API_VERSION_1;

        if ($basDetails->getchannel() === Channel::RBL)
        {
            if ($this->app['env'] === Environment::TESTING)
            {
                // razorx experiment to decide the statement fetch flow to be old or new.
                $accStmtVariant = $this->app->razorx->getTreatment(
                    $basDetails->merchant->getId(),
                    Merchant\RazorxTreatment::RBL_V2_BAS_API_INTEGRATION,
                    $this->mode
                );

                if (strtolower($accStmtVariant) === "on")
                {
                    $accountStatementApiVersion = self::ACCOUNT_STATEMENT_FETCH_API_VERSION_2;
                }
            }
            else
            {
                $accountStatementApiVersion = self::ACCOUNT_STATEMENT_FETCH_API_VERSION_2;
            }
        }

        return $accountStatementApiVersion;
    }

    protected function getProcessor(string $channel, string $accountNumber, BASDetails\Entity $basDetailEntity = null, string $version = "v1"): Processor\Base
    {
        $processor = substr(__NAMESPACE__, 0, -10) . '\\' . 'Processor';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Gateway';

        return new $processor($channel, $accountNumber, $basDetailEntity, $version);
    }


    /**
     * @param array             $bankTransactions
     * @param                   $merchant
     * @param string            $channel
     * @param string            $accountNumber
     * @param Processor\Base    $processor
     * @param BASDetails\Entity $basDetails
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     *
     * We will be saving the records in bulk and with a limit of 200 records in 1 go
     */
    public function saveAccountStatementDetails(array $bankTransactions,
                                                $merchant,
                                                string $channel,
                                                string $accountNumber,
                                                Processor\Base $processor,
                                                BASDetails\Entity $basDetails)
    {
        $bankTransactions = $processor->checkForDuplicateTransactions(
            $bankTransactions,
            $channel,
            $accountNumber,
            $merchant);

        $lastBankTxn = $this->channelRepo->findLatestByAccountNumber($accountNumber);

        $previousClosingBalance = $lastBankTxn == null ? 0 : $lastBankTxn->getBalance();

        $this->checkAndUpdateBalanceForExistingAccounts($lastBankTxn, $bankTransactions, $previousClosingBalance);

        $basEntitiesToSave = [];
        $totalRecordCount = 0;
        $initialOffset = 0;

        foreach ($bankTransactions as $bankTransaction)
        {
            $basEntity = (new Entity)->build($bankTransaction);

            if (empty($basEntity->getUtr()) === true)
            {
                $utr = $processor->getUtrForChannel($basEntity);

                $basEntity->setUtr($utr);
            }

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ENTITY_BUILT,
                               [
                                   'bank_txn_id'           => $bankTransaction[Entity::BANK_TRANSACTION_ID],
                                   'bank_txn_posted_date'  => $bankTransaction[Entity::POSTED_DATE],
                                   'bank_txn_channel'      => $bankTransaction[Entity::CHANNEL],
                                   'bas_id'                => $basEntity->getId(),
                                   'account_no'            => $basEntity->getAccountNumber(),
                                   'utr'                   => $basEntity->getUtr(),
                               ]);

            $basEntity->merchant()->associate($merchant);

            if ($this->validateRecordBalance($previousClosingBalance, $basEntity) == false)
            {
                throw new Exception\LogicException('Statement record balance is not in correct order',
                                                   ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                                                   [
                                                       'account_number'    => $accountNumber,
                                                       'channel'           => $channel,
                                                       'row_balance'       => $basEntity->getBalance(),
                                                       'previous_balance'  => $previousClosingBalance,
                                                       'bas_amount'        => $basEntity->getAmount(),
                                                       'bas_type'          => $basEntity->getType(),
                                                   ]);
            }

            $previousClosingBalance = $basEntity->getBalance();

            $basEntitiesToSave[] = [
                Entity::ACCOUNT_NUMBER      => $basEntity->getAccountNumber(),
                Entity::ID                  => $basEntity->getId(),
                Entity::AMOUNT              => $basEntity->getAmount(),
                Entity::CURRENCY            => $basEntity->getCurrency(),
                Entity::TRANSACTION_DATE    => $basEntity->getTransactionDate(),
                Entity::UTR                 => $basEntity->getUtr(),
                Entity::BANK_SERIAL_NUMBER  => $basEntity->getSerialNumber(),
                Entity::TYPE                => $basEntity->getType(),
                Entity::BALANCE             => $basEntity->getBalance(),
                Entity::MERCHANT_ID         => $basEntity->merchant->getId(),
                Entity::BANK_TRANSACTION_ID => $basEntity->getBankTransactionId(),
                Entity::CREATED_AT          => Carbon::now()->getTimestamp(),
                Entity::UPDATED_AT          => Carbon::now()->getTimestamp(),
                Entity::POSTED_DATE         => $basEntity->getPostedDate(),
                Entity::DESCRIPTION         => $basEntity->getDescription(),
                Entity::BANK_INSTRUMENT_ID  => $basEntity->getBankInstrumentId(),
                Entity::CATEGORY            => $basEntity->getCategory(),
            ];

            $totalRecordCount++;
        }

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT;
        }

        $this->channelRepo->transaction(function() use (
            $initialOffset,
            $totalRecordCount,
            $basEntitiesToSave,
            $limit,
            $accountNumber,
            $basDetails)
        {
            while ($initialOffset < $totalRecordCount)
            {
                $startTime = microtime(true);

                $records = array_slice(
                    $basEntitiesToSave,
                    $initialOffset,
                    $limit);

                $this->channelRepo->bulkInsert($records);

                $endTime = microtime(true);

                $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BULK_INSERT_TIME,
                                   [
                                       'account_number'         => $accountNumber,
                                       'time_to_save_records'   => $endTime - $startTime,
                                   ]);

                $initialOffset += $limit;
            }
        });

        if ($totalRecordCount > 0)
        {
            $this->repo->saveOrFail($basDetails);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_PAGINATION_KEY,
                [
                    BASDetails\Entity::PAGINATION_KEY => $basDetails->getPaginationKey(),
                    BASDetails\Entity::ACCOUNT_NUMBER => $basDetails->getAccountNumber(),
                ]);
        }

        // This data will be required to create an entry in BAS Details table.
        // Once statement is fetched, closing balance has to be updated in BAS Details table as well.
        // Statement fetch will be initiated based on this table.
        if (count($bankTransactions) > 0)
        {
            $basDetailInput = [
                BASDetails\Entity::MERCHANT_ID               => $merchant->getId(),
                BASDetails\Entity::ACCOUNT_NUMBER            => $accountNumber,
                BASDetails\Entity::CHANNEL                   => $channel,
                BASDetails\Entity::STATEMENT_CLOSING_BALANCE => $previousClosingBalance
            ];

            (new BASDetails\Core)->createOrUpdate($basDetailInput);
        }
    }

    protected function validateRecordBalance($previousClosingBalance, Entity $basEntity) : bool
    {
        $currentClosingBalance = $basEntity->getBalance();

        if ($basEntity->getType() == Type::CREDIT)
        {
            if ($currentClosingBalance == $previousClosingBalance + $basEntity->getAmount())
                return true;
        }
        else
        {
            if ($currentClosingBalance == $previousClosingBalance - $basEntity->getAmount())
                return true;
        }
        return false;
    }

    protected function checkAndUpdateBalanceForExistingAccounts($lastBankTxn, $bankTransactions, & $previousClosingBalance)
    {
        if (($lastBankTxn === null) and
            (empty($bankTransactions) === false))
        {
            $firstTransaction = $bankTransactions[0];

            switch ($firstTransaction[Entity::TYPE])
            {
                Case Type::DEBIT:
                    $previousClosingBalance = $firstTransaction[Entity::BALANCE] + $firstTransaction[Entity::AMOUNT];
                    break;

                Case Type::CREDIT:
                    $previousClosingBalance = $firstTransaction[Entity::BALANCE] - $firstTransaction[Entity::AMOUNT];
                    break;

                default:
                    $previousClosingBalance = 0;
            }
        }
    }
}
