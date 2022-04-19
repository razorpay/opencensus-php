<?php

namespace RZP\Models\BankingAccountStatement;

use Mail;
use File;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Payout\Status;
use RZP\Models\BankingAccount;
use RZP\Models\Payout\Purpose;
use RZP\Models\Admin\ConfigKey;
use RZP\Jobs\RblBankingAccountStatement;
use RZP\Jobs\IciciBankingAccountStatement;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as FeatureConstants;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;


class Core extends Base\Core
{
    const STORE_TYPE         = 'transactions';

    const FILE_ID            = 'file_id';

    const DASHBOARD_FILE_URL = '%sufh/file/%s';

    const DELAY = 'delay';

    const ATTEMPT_NUMBER = 'attempt_number';

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT = 6;

    const DEFAULT_RX_BAS_FORCED_FETCH_TIME_IN_HOURS = 8;

    // account numbers are selected for statement fetch based on these rules.
    const RBL_STATEMENT_FETCH_BALANCE_CHANGED_RULE  = "balance_changed_rule";

    const RBL_STATEMENT_FETCH_OTHERS_RULE           = "others";

    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT = 200;

    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL_DEFAULT = 100;

    // In Single payments api we append gateway ref no in description for IFT mode. This regex will be used to fetch
    // gateway ref no while recon.
    // ex: SAMPLE NARRATION RZPTESTIFT123
    const RBL_SINGLE_PAYMENTS_API_IFT_REGEX = "/\sRZP+[0-9A-Z]{10}$/";

    /**
     * Temporary hack. Should not set balance at a class level.
     * This restricts us from processing transactions from
     * multiple account statements at once.
     *
     * @var Merchant\Balance\Entity
     */
    protected $balance;

    /**
     * Recon for RBL IFT Transactions depends on whether the merchant is onboarded
     * to the single payments api offered by the bank or not.
     *
     * @var bool
     */
    protected $isRBLSinglePaymentsApiEnabled = false;

    protected $creditBeforeDebitUtrs = [];

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /** @var Details\Entity $basDetails  */
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

    /**
     * NOTE: This function should be used for a specific account number only. We cannot
     * call this function for processing transactions of multiple account numbers.
     * This is because we are setting the balance entity at a class level.
     * If you want to process transactions of multiple account numbers
     * in a single shot, the logic for fetching balance should be fixed.
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function processStatementForAccount(array $input)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        if ($this->checkReArchFlow($accountNumber, $channel) === true)
        {
            $input = [
                Entity::CHANNEL         => $channel,
                Entity::ACCOUNT_NUMBER  => $accountNumber,
            ];

            $this->fetchAccountStatementV2($input);

            $this->processStatementForAccountV2($input);

            return ['channel' => $channel, 'account_number' => $accountNumber];
        }

        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
                [
                    'channel'        => $channel,
                    'account_number' => $accountNumber,
                ]);

            $this->mutex->acquireAndRelease(
                'banking_account_statement_' . $accountNumber,
                function () use ($channel, $accountNumber, $input)
                {
                    $bankingAccount = (new BankingAccount\Repository)->findByAccountNumberAndChannel($accountNumber, $channel);

                    $currentTime = Carbon::now()->getTimestamp();

                    // updating LastStatementAttemptAt irrespective of success or fail so that new accounts are always fetched
                    // using LastStatementAttemptAt and failed accounts can be manually tried by sre also We are handling failure
                    // retry in job instead.
                    $bankingAccount->setLastStatementAttemptAt($currentTime);

                    $this->repo->saveOrFail($bankingAccount);

                    $basDetailEntity = $this->getBasDetails($accountNumber, $channel);

                    $basDetailEntity->setLastStatementAttemptAt();

                    $this->repo->saveOrFail($basDetailEntity);

                    $accountStatementApiVersion = $this->getAccountStatementApiVersion($basDetailEntity);

                    $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

                    $accountStatementDetails = $processor->fetchAccountStatementDetails($input);

                    $this->processAccountStatement($accountStatementDetails, $processor, $basDetailEntity);

                    $bankingAccount->balance->updateLastFetchedAt();
                },
                1800,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
            );
        }
        catch (Exception\BadRequestException $e)
        {
            // catching only BadRequestException exception to log and have noop for duplicate statement fetch request
            // Not considering the duplicate exception as success and needs to check for retries.
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
        $accountStatementApiVersion = Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_1;

        if ($basDetails->getchannel() === Channel::RBL)
        {
            // razorx experiment to decide the statement fetch flow to be old or new.
            $accStmtVariant = $this->app->razorx->getTreatment(
                $basDetails->merchant->getId(),
                Merchant\RazorxTreatment::RBL_V2_BAS_API_INTEGRATION,
                $this->mode
            );

            if (strtolower($accStmtVariant) === "on")
            {
                $accountStatementApiVersion = Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_2;
            }
        }

        return $accountStatementApiVersion;
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
                'banking_account_statement_fetch_' . $accountNumber . '_' . $channel,
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

                    $basDetailEntity->balance->updateLastFetchedAt();
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
    }

    public function processStatementForAccountV2(array $input)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_PROCESS_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT;
        }

        $saveLimit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL]);

        if (empty($saveLimit) == true)
        {
            $saveLimit = self::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL_DEFAULT;
        }

        $basDetails = $this->getBasDetails($accountNumber, $channel);

        $merchant = $basDetails->merchant;

        try
        {
            $this->mutex->acquireAndRelease(
                'banking_account_statement_process_' . $accountNumber . '_' . $channel,
                function () use ($channel, $accountNumber, $input, $limit, $saveLimit, $merchant)
                {
                    $this->setCreditBeforeDebitUtrsFromRedis($accountNumber);

                    while ($saveLimit > 0)
                    {
                        $basEntities = $this->repo->banking_account_statement->fetchUnlinkedBasRecords($accountNumber, $channel, $limit);

                        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ROWS_FETCHED, [
                            'count'             => count($basEntities),
                            'account_number'    => $accountNumber,
                        ]);

                        if (count($basEntities) == 0)
                            break;

                        $startTime = microtime(true);

                        $this->saveAccountStatementV2($basEntities, $merchant);

                        $endTime = microtime(true);

                        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BULK_LINKING_TIME,
                            [
                                'account_number'         => $accountNumber,
                                'time_to_link_records'   => $endTime - $startTime,
                            ]);

                        $saveLimit--;
                    }
                },
                1800,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
            );
        }
        catch (Exception\BadRequestException $e)
        {
            // catching only BadRequestException exception to log and have noop for duplicate statement fetch request
            // Ignoring the duplicate exception and treating it success and delete account number from sqs.
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
            else
            {
                throw $e;
            }
        }
    }

    protected function setCreditBeforeDebitUtrsFromRedis(string $accountNumber)
    {
        $creditBeforeDebitUtrsRedis = (new AdminService)->getConfigKey(['key' => ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS]);

        if (array_key_exists($accountNumber, $creditBeforeDebitUtrsRedis) === true)
        {
            $this->creditBeforeDebitUtrs += $creditBeforeDebitUtrsRedis[$accountNumber];
        }
    }

    protected function updateCreditBeforeDebitUtrsInRedis(string $accountNumber)
    {
        $this->mutex->acquireAndRelease(
            'bas_credit_before_debit_redis',
            function () use ($accountNumber)
            {
                $creditBeforeDebitUtrsRedis = (new AdminService)->getConfigKey(['key' => ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS]);

                $this->trace->info(
                    TraceCode::BAS_CREDIT_BEFORE_DEBIT_REDIS_KEY_UPDATE,
                    [
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        'current_redis_value'  => $creditBeforeDebitUtrsRedis,
                        'updated_utr_list'     => $this->creditBeforeDebitUtrs,
                    ]);

                $creditBeforeDebitUtrsRedis[$accountNumber] = $this->creditBeforeDebitUtrs;

                if (empty($creditBeforeDebitUtrsRedis[$accountNumber]) === true)
                {
                    unset($creditBeforeDebitUtrsRedis[$accountNumber]);
                }

                (new AdminService)->setConfigKeys([ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS => $creditBeforeDebitUtrsRedis]);
            },
            60,
            TraceCode::BAS_CREDIT_BEFORE_DEBIT_KEY_UPDATE_FAILED,
            3
        );
    }

    /**
     * @param array $bankTransactions
     * @param $merchant
     * @param string $channel
     * @param string $accountNumber
     * @throws Exception\BadRequestException
     * We will be saving the records in bulk and with a limit of 200 records in 1 go
     */
    public function saveAccountStatementDetails(array $bankTransactions,
                                                $merchant, string $channel,
                                                string $accountNumber,
                                                Processor\Base $processor,
                                                BASDetails\Entity  $basDetails)
    {
        $bankTransactions = $processor->checkForDuplicateTransactions(
                                        $bankTransactions,
                                        $channel,
                                        $accountNumber);

        $lastBankTxn = $this->repo->banking_account_statement->findLatestByAccountNumber($accountNumber);

        $previousClosingBalance = $lastBankTxn == null ? 0 : $lastBankTxn->getBalance();

        $this->checkAndUpdateBalanceForExistingAccounts($lastBankTxn, $bankTransactions, $previousClosingBalance, $merchant);

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
                Entity::ACCOUNT_NUMBER        => $basEntity->getAccountNumber(),
                Entity::CHANNEL               => $basEntity->getChannel(),
                Entity::ID                    => $basEntity->getId(),
                Entity::AMOUNT                => $basEntity->getAmount(),
                Entity::CURRENCY              => $basEntity->getCurrency(),
                Entity::TRANSACTION_DATE      => $basEntity->getTransactionDate(),
                Entity::UTR                   => $basEntity->getUtr(),
                Entity::BANK_SERIAL_NUMBER    => $basEntity->getSerialNumber(),
                Entity::TYPE                  => $basEntity->getType(),
                Entity::BALANCE               => $basEntity->getBalance(),
                Entity::MERCHANT_ID           => $basEntity->merchant->getId(),
                Entity::BANK_TRANSACTION_ID   => $basEntity->getBankTransactionId(),
                Entity::CREATED_AT            => Carbon::now()->getTimestamp(),
                Entity::UPDATED_AT            => Carbon::now()->getTimestamp(),
                Entity::POSTED_DATE           => $basEntity->getPostedDate(),
                Entity::DESCRIPTION           => $basEntity->getDescription(),
                Entity::BANK_INSTRUMENT_ID    => $basEntity->getBankInstrumentId(),
                Entity::CATEGORY              => $basEntity->getCategory(),
            ];

            $totalRecordCount++;
        }

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT;
        }

        $this->repo->transaction(function() use (
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

                Entity::insert($records);

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

    protected function checkAndUpdateBalanceForExistingAccounts($lastBankTxn, $bankTransactions, & $previousClosingBalance, $merchant)
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

            /** @var Merchant\Balance\Entity $balance */
            $balance = $this->basDetails->balance;

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_UPDATE_BALANCE_FOR_EXISTING_ACCOUNT,
                [
                    Entity::MERCHANT_ID => $this->basDetails->getMerchantId(),
                    'balance_id'        => $balance->getId(),
                    'previous_balance'  => $balance->getBalance(),
                    'new_balance'       => $previousClosingBalance,
                ]
            );

            $balance->setBalance($previousClosingBalance);
            $this->repo->balance->saveOrFail($balance);

            // Update opening balance on ledger when 1st statement fetch happens for Direct account
            if (($merchant->isFeatureEnabled(FeatureConstants::DA_LEDGER_JOURNAL_WRITES) === true) && ($previousClosingBalance !== 0))
            {
                (new Merchant\Balance\Ledger\Core)->updateXLedgerMerchantBalanceAccountForDirect($this->basDetails->getMerchantId(), $this->basDetails->getPublicId(), $previousClosingBalance);
            }
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

    public function requestAccountStatement($input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_STATEMENT_GENERATE, $input);

        $statementFileId = $this->generateBankAccountStatement($input);

        $sendEmail = filter_var($input[Entity::SEND_EMAIL], FILTER_VALIDATE_BOOLEAN);

        if ($sendEmail === true)
        {
            $this->sendBankAccountStatementEmail($input, $statementFileId);

            return $input;
        }

        $input[self::FILE_ID] = $statementFileId;

        return $input;
    }

    /**
     * Creates either a PDF/Excel File and returns the file handle to the calling function
     *
     * @param $input
     *
     * @return string
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function generateBankAccountStatement(array $input)
    {
        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $fromDate = $input[Entity::FROM_DATE];

        $toDate = $input[Entity::TO_DATE];

        $format = $input[Entity::FORMAT];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
            [
                'channel'        => $channel,
                'account_number' => $accountNumber,
                'from_date'      => $fromDate,
                'to_date'        => $toDate,
                'format'         => $format,
                'send_email'     => $input[Entity::SEND_EMAIL],
            ]);

        $statementGenerator = $this->getGenerator($accountNumber, $channel, $format, $fromDate, $toDate);

        $balance = $this->repo->balance->getBalanceByAccountNumberOrFail($accountNumber);

        $temporaryFilePath = $statementGenerator->getStatement();

        $this->trace->info(TraceCode::CA_STATEMENT_GENERATED,
                           [
                               'balance_id'          => $balance->getId(),
                               'account_number'      => $balance->getAccountNumber(),
                               'temporary_file_path' => $temporaryFilePath
                           ]);

        $ufhResponse = $this->uploadTemporaryFileToStore($temporaryFilePath, $balance);

        $fileId = $ufhResponse[self::FILE_ID] ?? null;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
            [
                'file_id' => $fileId
            ]);

        return $fileId;
    }

    protected function sendBankAccountStatementEmail(array $input, string $statementFileId = null)
    {
        $fileAccessUrl = $this->getDashboardFileAccessUrl($statementFileId);

        $merchant = $this->merchant;

        $toEmails = $input[Entity::TO_EMAIL_LIST];

        $fromDate = $input[Entity::FROM_DATE];

        $toDate = $input[Entity::TO_DATE];

        $email = new StatementMail($merchant,
                                   $toEmails,
                                   $fromDate,
                                   $toDate,
                                   $fileAccessUrl);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_EMAIL,
            [
                'merchant_id' => $this->merchant->getId(),
                'to_emails'   => $toEmails,
                'from_date'   => $fromDate,
                'to_date'     => $toDate,
            ]);

        Mail::queue($email);
    }

    protected function getDashboardFileAccessUrl(string $fileId = null)
    {
        return sprintf(self::DASHBOARD_FILE_URL, $this->config['applications.dashboard.url'], $fileId);
    }

    protected function uploadTemporaryFileToStore(string $pathToTemporaryFile, Merchant\Balance\Entity $entity)
    {
        $ufhService = $this->app['ufh.service'];

        $uploadedFileInstance = $this->getUploadedFileInstance($pathToTemporaryFile);

        $response = $ufhService->uploadFileAndGetUrl($uploadedFileInstance,
                                                     $name = File::name($pathToTemporaryFile),
                                                     self::STORE_TYPE,
                                                     $entity);
        $this->trace->info(
            TraceCode::UFH_RESPONSE,
            [
                'balance_id' => $entity->getId(),
                'response'   => $response,
            ]);

        return $response;
    }

    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.
        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $size, $error, $test);

        return $object;
    }

    protected function getGenerator(string $accountNumber, string $channel, string $format, int $fromDate, int $toDate)
    {
        $statementGeneratorNamespace = __NAMESPACE__ . '\\' . 'Generator\\Gateway\\' . studly_case($channel);

        $statementGenerator = $statementGeneratorNamespace . '\\' . studly_case($format);

        return new $statementGenerator($accountNumber, $channel, $fromDate, $toDate);
    }

    protected function getProcessor(string $channel, string $accountNumber, BASDetails\Entity $basDetailEntity = null, string $version = "v1"): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Gateway';

        return new $processor($channel, $accountNumber, $basDetailEntity, $version);
    }

    /**
     * @param array             $bankTransactions
     *
     * $processor is gateway depending on channel.
     * @param                   $processor
     * @param BASDetails\Entity $basDetails
     *
     * @throws Exception\BadRequestException
     */
    protected function processAccountStatement(
        array $bankTransactions,
        $processor,
        BASDetails\Entity $basDetails)
    {
        $merchant = $basDetails->merchant;
        $accountNumber = $basDetails->getAccountNumber();

        $bankTxnCount = count($bankTransactions);
        $skippedCount = 0;

        // This will be updated in BAS Details table as statement closing balance.
        // initializing to null so that if $closingBalance is null BAS details table update process will no trigger.
        $closingBalance = null;

        foreach ($bankTransactions as $bankTransaction)
        {
            $bankTxnId      = $bankTransaction[Entity::BANK_TRANSACTION_ID];
            $bankTxnSrlNo   = $bankTransaction[Entity::BANK_SERIAL_NUMBER];
            $bankTxnDate    = $bankTransaction[Entity::TRANSACTION_DATE];
            $bankTxnChannel = $bankTransaction[Entity::CHANNEL];

            // TODO: add check on serial number also before go live
            $txnExists = $this->repo->banking_account_statement->bankTransactionExists(
                $bankTxnId,
                $accountNumber,
                $bankTxnDate,
                $bankTxnChannel,
                $bankTxnSrlNo);

            if ($txnExists === true)
            {
                $skippedCount++;

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_INSERT_SKIP,
                    [
                        'bank_transaction_id'               => $bankTxnId,
                        'bank_transaction_serial_number'    => $bankTxnSrlNo,
                        'bank_transaction_date'             => $bankTxnDate,
                        'bank_transaction_channel'          => $bankTxnChannel,
                        'bank_account_number'               => $accountNumber,
                    ]);

                continue;
            }

            $this->saveAccountStatement($bankTransaction, $merchant, $processor);

            $closingBalance = $bankTransaction[Entity::BALANCE];
        }

        $processedCount = $bankTxnCount - $skippedCount;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE_SUMMARY,
            [
                'total'     => $bankTxnCount,
                'skipped'   => $skippedCount,
                'processed' => $processedCount,
            ]);

        if ($processedCount > 0)
        {
            $this->repo->saveOrFail($basDetails);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_DETAILS_UPDATE_PAGINATION_KEY,
                [
                    BASDetails\Entity::PAGINATION_KEY => $basDetails->getPaginationKey(),
                    BASDetails\Entity::ACCOUNT_NUMBER => $basDetails->getAccountNumber(),
                ]);
        }

        $this->checkAndTraceForStaleResponse($bankTxnCount, $processedCount, $accountNumber);

        // This data will be required to create an entry in BAS Details table.
        // Once statement is fetched, closing balance has to be updated in BAS Details table as well. Statement fetch will be initiated based on this table.
        if ($closingBalance !== null)
        {
            $basDetailInput = [
                BASDetails\Entity::MERCHANT_ID               => $merchant->getId(),
                BASDetails\Entity::ACCOUNT_NUMBER            => $accountNumber,
                BASDetails\Entity::BALANCE_ID                => $this->balance->getId(),
                BASDetails\Entity::CHANNEL                   => $this->balance->getChannel(),
                BASDetails\Entity::STATEMENT_CLOSING_BALANCE => $closingBalance
            ];
            (new BASDetails\Core)->createOrUpdate($basDetailInput);
        }
    }

    /**
     * @param array           $bankTransaction
     * @param Merchant\Entity $merchant
     * $processor is gateway depending on channel.
     * @param                 $processor
     */
    protected function saveAccountStatement(array $bankTransaction, Merchant\Entity $merchant, $processor)
    {
        $bankPostedDate = $bankTransaction[Entity::POSTED_DATE];
        $bankTxnChannel = $bankTransaction[Entity::CHANNEL];
        $bankTxnId      = $bankTransaction[Entity::BANK_TRANSACTION_ID];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_BEING_SAVED,
            [
                'bank_txn_id'           => $bankTxnId,
                'bank_txn_posted_date'  => $bankPostedDate,
                'bank_txn_channel'      => $bankTxnChannel,
            ]);

        list($sourceEntity, $isSourceAlreadyCreated, $basEntity) = $this->repo->transaction(function () use ($bankTransaction, $merchant, $processor) {

            $basEntity = (new Entity)->build($bankTransaction);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ENTITY_BUILT,
                [
                    'bank_txn_id'           => $bankTransaction[Entity::BANK_TRANSACTION_ID],
                    'bank_txn_posted_date'  => $bankTransaction[Entity::POSTED_DATE],
                    'bank_txn_channel'      => $bankTransaction[Entity::CHANNEL],
                    'bas_id'                => $basEntity->getId(),
                    'account_no'            => $basEntity->getAccountNumber()
                ]);

            //
            // This should be done after build since `setUtr` fetches things from the entity.
            // Can be refactored if required, as long as properly tested.
            //
            if (empty($basEntity->getUtr()) === true)
            {
                $utr = $processor->getUtrForChannel($basEntity);

                $basEntity->setUtr($utr);
            }

            $basEntity->merchant()->associate($merchant);

            $startTime = microtime(true);

            list($sourceEntity, $isSourceAlreadyCreated) = $this->processSourceEntity($basEntity);

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            (new Transaction\Core)->updatePostedDate($sourceEntity, $basEntity->getPostedDate());

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE, $basEntity->toArray());

            $this->repo->saveOrFail($basEntity);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_CREATION,
                [
                    'source_entity'         => $sourceEntity->toArray(),
                    'bas_id'                => $basEntity->getId(),
                    'account_no'            => $basEntity->getAccountNumber(),
                    'entity_linking_time'   => (microtime(true) - $startTime) * 1000,
                    'entity_id'             => $sourceEntity->getId(),
                    'entity_type'           => $basEntity->getEntityType(),
                ]);

            return [$sourceEntity, $isSourceAlreadyCreated, $basEntity];
        });

        // send event to ledger in shadow mode
        $this->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity);

        $this->fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated);
    }

    protected function saveAccountStatementV2(Base\PublicCollection $basEntities, Merchant\Entity $merchant)
    {
        foreach ($basEntities as $basEntity)
        {
            try
            {
                list($sourceEntity, $isSourceAlreadyCreated) = $this->linkAccountStatementRecord($basEntity, $merchant);

                // send event to ledger in shadow mode
                $this->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::BANKING_ACCOUNT_STATEMENT_LINKING_FAILED,
                    [
                        'bas_id'         => $basEntity->getId(),
                        'utr'            => $basEntity->getUtr(),
                        'account_number' => $basEntity->getAccountNumber(),
                        'message'        => $e->getMessage(),
                    ]);

                if (($e->getMessage() == "Call to a member function isPostpaid() on null") and
                    ($basEntity->getUtr() !== null))
                {
                    array_push($this->creditBeforeDebitUtrs, $basEntity->getUtr());

                    $this->updateCreditBeforeDebitUtrsInRedis($basEntity->getAccountNumber());

                    list($sourceEntity, $isSourceAlreadyCreated) = $this->linkAccountStatementRecord($basEntity, $merchant);

                    // send event to ledger in shadow mode
                    $this->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity);
                }
                else
                {
                    throw $e;
                }
            }

            if (($basEntity->getType() === Type::DEBIT) and
                (in_array($basEntity->getUtr(), $this->creditBeforeDebitUtrs) === true))
            {
                $this->reversePayoutForCreditBeforeDebit($basEntity);
            }
            else
            {
                $this->fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated);
            }
        }
    }

    protected function linkAccountStatementRecord($basEntity, Merchant\Entity $merchant)
    {
        return $this->repo->transaction(function() use ($basEntity, $merchant)
        {
            $startTime = microtime(true);

            list($sourceEntity, $isSourceAlreadyCreated) = $this->processSourceEntity($basEntity);

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            (new Transaction\Core)->updatePostedDate($sourceEntity, $basEntity->getPostedDate());

            $this->repo->saveOrFail($basEntity);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ENTITY_LINKED, $basEntity->toArray());

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_CREATION_V2,
                               [
                                   'source_entity'       => $sourceEntity->toArray(),
                                   'bas_id'              => $basEntity->getId(),
                                   'account_number'      => $basEntity->getAccountNumber(),
                                   'entity_linking_time' => (microtime(true) - $startTime) * 1000,
                                   'entity_id'           => $sourceEntity->getId(),
                                   'entity_type'         => $basEntity->getEntityType(),
                               ]);

            return [$sourceEntity, $isSourceAlreadyCreated];
        });
    }

    protected function reversePayoutForCreditBeforeDebit(Entity $basEntity)
    {
        // reversal based on credit before debit should happen on getting debit entry in account statement.
        if ($basEntity->getType() !== Type::DEBIT)
        {
            return;
        }

        $creditBasTemp = $this->repo->banking_account_statement->fetchByUtrAndType($basEntity->getUtr(),
                                                                                   Type::CREDIT,
                                                                                   $basEntity->getAccountNumber(),
                                                                                   $basEntity->getChannel());

        if (count($creditBasTemp) !== 1)
        {
            $this->trace->error(
                TraceCode::BAS_CREDIT_BEFORE_DEBIT_REVERSE_PAYOUT_LOGIC_ERROR,
                [
                    'bas_id'         => $basEntity->getId(),
                    'utr'            => $basEntity->getUtr(),
                    'account_number' => $basEntity->getAccountNumber(),
                    'credit_bas_count' => count($creditBasTemp),
                ]);

            return;
        }

        $creditBas = $creditBasTemp[0];

        $payout = $this->fetchExistingPayoutForAccountStatement($basEntity,
                                                                $temp,
                                                                $temp,
                                                                false);

        if ($payout !== null)
        {
            $this->trace->info(
                TraceCode::BAS_CREDIT_BEFORE_DEBIT_REVERSE_PAYOUT,
                [
                    [
                        'bas_id'         => $basEntity->getId(),
                        'utr'            => $basEntity->getUtr(),
                        'account_number' => $basEntity->getAccountNumber(),
                        'credit_bas_id'  => $creditBas->getId(),
                        'payout_id'      => $payout->getId(),
                        'payout_status'  => $payout->getStatus()
                    ]
                ]);

            if ($payout->getStatus() !== Status::FAILED)
            {
                return;
            }

            (new Payout\Core)->handlePayoutReversed($payout,
                                                    null,
                                                    null,
                                                    $creditBas);

            if (($key = array_search($basEntity->getUtr(), $this->creditBeforeDebitUtrs)) !== false)
            {
                unset($this->creditBeforeDebitUtrs[$key]);

                $this->updateCreditBeforeDebitUtrsInRedis($basEntity->getAccountNumber());
            }
        }
    }

    public function fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated)
    {
        $sourceTransaction = $sourceEntity->transaction;

        $sourceTransaction->load('source');

        if ($sourceEntity->getEntityName() === Constants\Entity::EXTERNAL)
        {
            // transaction.created webhook for external transactions
            $variant = $this->app->razorx->getTreatment($sourceEntity->merchant->getId(),
                                                        Merchant\RazorxTreatment::BLOCK_EXTERNAL_TRANSACTION_CREATED_WEBHOOK_RBL,
                                                        $this->mode);

            // by default webhook will be sent for all merchants , if experiment is on for that merchant then webhook
            // will not be sent. In case razorx is down webhook will be sent.
            if ($variant !== 'on')
            {
                (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification(
                    $sourceEntity->transaction);
            }
        }
        else
        {
            // TODO: refactor using wasRecentlyCreated
            // https://razorpay.atlassian.net/browse/RX-2630
            if (($sourceEntity->getEntityName() === Constants\Entity::REVERSAL) and
                ($isSourceAlreadyCreated === false))
            {
                $this->app->events->dispatch('api.payout.reversed', $sourceEntity->entity);
            }

            (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification(
                $sourceEntity->transaction);
        }
    }

    protected function getBalance(Entity $basEntity)
    {
        if (empty($this->balance) === true)
        {
            $this->balance = $this->repo
                                  ->balance
                                  ->getBalanceByMerchantIdAccountNumberAndChannelOrFail($basEntity->getMerchantId(),
                                                                                        $basEntity->getAccountNumber(),
                                                                                        $basEntity->getChannel());
        }

        return $this->balance;
    }

    protected function processSourceEntity(Entity $basEntity)
    {
        // this flag helps to identify whether we found an existing reversal or
        // we created a reversal while processing reversal. Based on this flag we would
        // decide whether to send payout.reversed webhook or not
        // ensuring duplicate webhook doesn't get fired
        $isSourceAlreadyCreated = true;

        // when we are not able to map source as payout or reversal due to some reason like
        // duplicate utr found , that time we create external entity with remarks with reason of
        // failure and map this external with bas record
        $remarks = null;

        if (in_array($basEntity->getUtr(), $this->creditBeforeDebitUtrs) === true)
        {
            $sourceEntity = $this->processExternal($basEntity, $remarks);
        }
        else
        {
            if ($basEntity->isTypeCredit() === true)
            {
                list($sourceEntity, $isSourceAlreadyCreated) = $this->processReversal($basEntity, $remarks);
            }
            else
            {
                $sourceEntity = $this->processPayout($basEntity, $remarks);
            }

            if (($remarks != null) or
                ($sourceEntity === null))
            {
                $sourceEntity = $this->processExternal($basEntity, $remarks);
            }
        }

        $this->trace->info(TraceCode::BAS_ENTRY_SOURCE_MAPPING_DETAILS,
                   [
                       'source_id'   => $sourceEntity->getPublicId(),
                       'source_type' => $sourceEntity->getEntityName(),
                       'bas_id'      => $basEntity->getId(),
                       'account_no'  => $basEntity->getAccountNumber(),
                       'remarks'     => $remarks
                   ]);

        $this->validateBalance($basEntity, $sourceEntity);

        return [$sourceEntity, $isSourceAlreadyCreated];
    }

    /**
     * @param Entity $basEntity
     * @param        $remarks
     * This function tries to map given bas record with reversal in our system or creates a reversal in case we
     * are not able to find the reversal but found a corresponding payout
     *
     * if while processing reversal we are unable to uniquely identify source , instead of failing the
     * account statement we will create external entity with remarks as failure reason and
     * map this record to given basEntity and raise a slack alert
     *
     * @return array|null
     * first variable in array is reversal entity and second is a flag which helps to identify whether we
     * found an existing reversal or we created a reversal while processing . Based on this flag we would
     * decide whether to send payout.reversed webhook or not
     * @throws Exception\LogicException
     */
    protected function processReversal(Entity $basEntity, & $remarks)
    {
        // order of fetching:
        // 1. try to find existing reversal using utr.
        // 2. if not found , then try to find payout with given utr
        // 3. if no payout is found using utr , then try finding payout with given cms ref no
        // 4. if no payout is found using cms ref no, then try finding payout using return utr.

        // Cases for mapping bas record:
        // *  No existing reversal with the UTR
        //    1.Search for payout ((SEARCHING IN PAYOUT TABLE)with same UTR/CMS REF NO/RETURN UTR
        //      If more than 1 payout with the same UTR ,then raise alert and link with external.
        //      Same for CMS REF NO  and IFT cases
        //    2.If single payout is found then use this payout and create reversal and reversal transaction
        // * Found Only 1 existing reversal with the UTR(SEARCHING IN REVERSAL TABLE)
        //    1.Create a credit transaction and link with reversal. (use $isalreadyCreated)
        // * Found More than 1 existing reversal with same UTR(SEARCHING IN REVERSAL TABLE)
        //    1.With same UTR, if we get more than 1 unlinked reversal(it does not have a txn yet),
        //      then raise alert on slack and create external entity with remarks -
        //      More than one reversal with same UTR - UTR’s value

        // create external source is a flag that is used by caller to identify whether to create an external
        // source or not. remarks is set when external entity is to be created and is also passed to caller
        // as reference
        $createExternalSource = false;

        $reversal = $this->fetchExistingReversalIfPresent($basEntity, $createExternalSource, $remarks);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_REVERSAL,
                        [
                            'bas_id'        => $basEntity->getId(),
                            'account_no'    => $basEntity->getAccountNumber(),
                            'reversal'      => $reversal,
                        ]);

        if ($createExternalSource === true)
        {
            return [null, true];
        }

        // this is to ensure that payout.reversed webhook does not get fired twice. i.e
        // it only gets fired in this flow if a new reversal entity is created and we are not
        // able to map existing reversal to bas. Because if we were able to map existing reversal,
        // that means that webhook would have been fired already when that reversal entity was
        // created
        $isReversalAlreadyCreated = true;

        if ($reversal === null)
        {
            $isReversalAlreadyCreated = false;

            // There can be a possibilty that a payout is not marked reversed due
            // to some code-or-mapping miss at Mozart layer or if the webhook from
            // FTS to API is missed. In that case, we will not be able to
            // find any reversal for the credit row. So we will check if there
            // is a payout in the system for which this is the credit row.
            // If a payout is found, then we will also check if it has is marked
            // reversed, if not we will update the payout as reversed
            //
            /** @var Payout\Entity $existingPayout */
            $existingPayout = $this->fetchExistingPayoutForAccountStatementForMappingCredits($basEntity,
                                                                                             $createExternalSource,
                                                                                             $remarks);

            if ($createExternalSource === true)
            {
                return [null, true];
            }

            if ($existingPayout === null)
            {
                return null;
            }

            $this->trace->info(TraceCode::AUTO_RECON_PAYOUT_REVERSAL_CREATE_REQUEST,
                            [
                                'payout_id'         => $existingPayout->getId(),
                                'bas_id'            => $basEntity->getId(),
                                'account_number'    => $basEntity->getAccountNumber(),
                            ]);

            $reverseReason = $existingPayout->getFailureReason() ?? 'REVERSAL';

            (new Payout\Core)->reversePayout($existingPayout, $reverseReason);

            $reversal = $existingPayout->reversal;

            $this->trace->info(TraceCode::AUTO_RECON_PAYOUT_REVERSAL_CREATED,
                [
                    'payout_id'     => $existingPayout->getId(),
                    'reversal_id'   => $reversal->getId(),
                    'bas_id'        => $basEntity->getId(),
                    'account_no'    => $basEntity->getAccountNumber(),
                ]);

            $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);

            $this->trace->info(TraceCode::REVERSAL_TRANSACTION_CREATED,
                               [
                                   'reversal_id'       => $reversal->getId(),
                                   'transaction_id'    => $reversal->transaction->getId(),
                                   'bas_id'            => $basEntity->getId(),
                                   'account_no'        => $basEntity->getAccountNumber(),
                               ]);
        }

        if (($isReversalAlreadyCreated === true) and
            ($reversal !== null) and
            ($reversal->transaction === null))
        {
            $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);

            $this->trace->info(TraceCode::REVERSAL_TRANSACTION_CREATED,
                [
                    'reversal_id'       => $reversal->getId(),
                    'transaction_id'    => $reversal->transaction->getId(),
                    'bas_id'            => $basEntity->getId(),
                    'account_no'        => $basEntity->getAccountNumber(),
                ]);
        }

        return [$reversal, $isReversalAlreadyCreated];
    }

    protected function processPayout(Entity $basEntity, & $remarks)
    {
        $createExternalSource = false;

        $payout = $this->fetchExistingPayoutForAccountStatement($basEntity, $createExternalSource, $remarks);

        if ($createExternalSource === true)
        {
            return null;
        }

        if ($payout === null)
        {
            return null;
        }

        // We are checking for $payout->isStatusFailed(), because its possible that due to a code-miss,
        // a 'reversed' payout is marked as a 'failed' payout, in which case we will get a 'failed' payout
        // matching a BAS, which should be impossible ideally, because a Failed payout, means no debit
        // ever happened. So to ensure that error case is handled we are checking for 'failed' payouts too
        if ($payout->isStatusFailed() === true)
        {
            $this->trace->error(
                TraceCode::BAS_ENTRY_FOR_A_FAILED_PAYOUT,
                 [
                     'bas_id'    => $basEntity->getId(),
                     'payout_id' => $payout->getId()
                 ]);

            return null;
        }

        (new DownstreamProcessor('fund_account_payout', $payout, $this->mode))->processTransaction();

        $transactionId = $payout->transaction ? $payout->transaction->getID() : null;

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_PAYOUT_TRANSACTION,
            [
                'bas_id'            => $basEntity->getId(),
                'account_no'        => $basEntity->getAccountNumber(),
                'payout'            => $payout,
                'transaction_id'    => $transactionId
            ]);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    protected function processExternal(Entity $basEntity, $remarks)
    {
        $external = (new External\Core)->create($basEntity);

        if ($remarks != null)
        {
            $external->setRemarks($remarks);

            $this->trace->info(TraceCode::EXTERNAL_SAVE_WITH_REMARKS_NOT_NULL,
                            [
                                'external'      => $external->toArray(),
                                'bas_id'        => $basEntity->getId()
                            ]);

            $this->repo->saveOrFail($external);
        }

        return $external;
    }

    /**
     * @param Entity $basEntity
     * @param false  $createExternalSource
     * @param null   $remarks
     *
     * if while fetching reversal we get multiple reversals with duplicate utr , instead of failing the
     * account statement we will create external entity with remarks as multiple unlinked reversals with
     * same utr and map this record to given basEntity and raise a slack alert
     *
     * Usage of create_external_source and remarks:
     * create external source is a flag that is used by caller to identify whether to create an external
     * source or not. remarks is set when external entity is to be created and is also passed to caller
     * as reference
     *
     *
     * @return mixed|null
     */
    public function fetchExistingReversalIfPresent(Entity $basEntity,
                                                      & $createExternalSource = false,
                                                      & $remarks = null)
    {
        $utr = $basEntity->getUtr();

        $unlinkedReversals = [];

        if (empty($utr) === true)
        {
            return null;
        }

        $balance = $this->getBalance($basEntity);

        $startTime = microtime(true);

        $reversals = $this->repo
                         ->reversal
                         ->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());

        $this->trace->info(TraceCode::BAS_REVERSALS_FETCHED_VIA_UTR,
                           [
                               'utr'                                    => $utr,
                               'reversal_ids'                           => $reversals->getQueueableIds(),
                               'bas_id'                                 => $basEntity->getId(),
                               'account_no'                             => $basEntity->getAccountNumber(),
                               'reversals_fetched_via_utr_mapping_time' => (microtime(true) - $startTime) * 1000
                           ]);

        foreach ($reversals as $key => $reversal)
        {
            if ($reversal->getTransactionId() !== null)
            {
                $data = [
                    'channel'                    => $basEntity->getChannel(),
                    'amount'                     => $basEntity->getAmount(),
                    'current_reversal_id'        => $reversal->getId(),
                    'reversal_ids_with_same_utr' => $reversals->getQueueableIds(),
                    'utr'                        => $utr
                ];

                $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_UTR_FOR_REVERSAL, [
                    'data' => $data,
                ]);

                $operation = 'duplicate UTR in account statement fetch for a linked reversal';

                (new SlackNotification)->send(
                    $operation,
                    $data,
                    null,
                    1,
                    'rx_ca_rbl_alerts');

                unset($reversals[$key]);
            }
            else
            {
                $unlinkedReversals[] = $reversal;
            }
        }

        if (count($unlinkedReversals) === 1)
        {
            return $unlinkedReversals[0];
        }
        // RBL has confirmed that UTR will be unique across all transactions
        // of RBL and so we not process this account statement record
        if (count($unlinkedReversals) > 1)
        {
            $createExternalSource = true;
            $remarks              = 'multiple unlinked reversals with same utr ' . $utr;

            $data = [
                'channel'      => $basEntity->getChannel(),
                'amount'       => $basEntity->getAmount(),
                'reversal_ids' => $reversals->getQueueableIds(),
                'utr'          => $utr
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_MULTIPLE_UNLINKED_REVERSALS_WITH_SAME_UTR, [
                'data' => $data,
            ]);

            $operation = 'multiple unlinked reversals found with same utr ' . $utr .
                         ' in account statement fetch for credit mapping';

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            return null;
        }

        return null;
    }

    /**
     * TODO: The logic would be different based on the channel.
     * Refactor this when adding more banks here.
     *
     * @param Entity $basEntity
     *
     * @return mixed
     * @throws Exception\LogicException
     */

    protected function fetchExistingPayoutForAccountStatementForMappingCredits(Entity $basEntity,
                                                                               & $createExternalSource = false,
                                                                               & $remarks = null)
    {
        $payouts = new Base\Collection;

        $balance = $this->getBalance($basEntity);

        $utr = $basEntity->getUtr();

        if (empty($utr) === false)
        {
            $startTime = microtime(true);

            $payouts = $this->repo->payout->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());

            $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_UTR_FOR_CREDIT_MAPPING,
                               [
                                   'utr'                                   => $utr,
                                   'payout_ids'                            => $payouts->getQueueableIds(),
                                   'bas_id'                                => $basEntity->getId(),
                                   'account_no'                            => $basEntity->getAccountNumber(),
                                   'payouts_fetched_via_utr_mapping_time'  => (microtime(true) - $startTime) * 1000
                               ]);

            if ($basEntity->getType() === Type::CREDIT)
            {
                if ($payouts->count() === 1)
                {
                    return $payouts->first();
                }
                else
                {
                    if ($payouts->count() > 1)
                    {
                        $createExternalSource   = true;
                        $remarks                = 'multiple payouts found with same utr for credit mapping';

                        $data = [
                            'channel'    => $basEntity->getChannel(),
                            'amount'     => $basEntity->getAmount(),
                            'payout_ids' => $payouts->getQueueableIds(),
                            'utr'        => $utr
                        ];

                        $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_PAYOUT_UTR_FOR_CREDIT_MAPPING, [
                            'data' => $data,
                        ]);

                        $operation = 'multiple payouts found with same utr for credit mapping in account statement fetch';

                        (new SlackNotification)->send(
                            $operation,
                            $data,
                            null,
                            1,
                            'rx_ca_rbl_alerts');

                        return null;
                    }
                }
            }
        }

        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $bankTimeBeforePostedDate = Carbon::createFromTimestamp(
                                              $basEntity->getPostedDate(),
                                          Timezone::IST)
                                              ->subHours(4)
                                              ->getTimestamp();

            $bankTimeBeforePostedDateForNonIFT = Carbon::createFromTimestamp(
                                                       $basEntity->getPostedDate(),
                                                   Timezone::IST)
                                                       ->subWeek()
                                                       ->getTimestamp();

            $startTime = microtime(true);

            /**
             * Bank is not sending cms ref number in the single payments api response for IFT mode. Hence FTS is appending
             * gateway reference number with 'RZP' as delimiter at the end of description of IFT transactions. Recon
             * needs to happen by picking the end 10 characters and match with gateway ref no. in fta table.
             * example description: SAMPLE NARRATION RZPTESTIFT123
             *
             * slack link: https://razorpay.slack.com/archives/C019AKLLQAH/p1616757629029200
             *
             * Only for IFT mode.
             */

            $description = $basEntity->getDescription() ? trim($basEntity->getDescription()) : '';

            $matches = [];

            if (($this->checkForRblSinglePaymentsApi($description, $matches) === true) and
                ($basEntity->getChannel() === Channel::RBL))
            {
                $gatewayRefNo = substr($matches[0], 4, 10);

                // this will be used to send slack notifications if required.
                $identifier = 'gateway ref no';

                // we are checking both linked and unlinked payouts because debit row might have already been
                // processed.
                $payouts = $this->repo->payout->fetchPayoutsFromGatewayRefNumberWithinTimeRangeForIFT(
                    $gatewayRefNo,
                    $basEntity->getPostedDate(),
                    $bankTimeBeforePostedDate,
                    $basEntity->getAmount(),
                    $balance->getId());

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_GATEWAY_REF_NO_FOR_IFT_FOR_CREDIT_MAPPING,
                                   [
                                       'cms_ref_no'                                              => $bankTxnId,
                                       'gateway_ref_no'                                          => $gatewayRefNo,
                                       'payout_ids'                                              => $payouts->getQueueableIds(),
                                       'bas_id'                                                  => $basEntity->getId(),
                                       'account_no'                                              => $basEntity->getAccountNumber(),
                                       'payouts_fetched_via_gateway_ref_no_for_ift_mapping_time' => (microtime(true) - $startTime) * 1000,
                                   ]);
            }
            else
            {
                // this will be used to send slack notifications if required.
                $identifier = 'cms ref no';

                // we are checking both linked and unlinked payouts because debit row might have already been
                // processed.
                $payouts = $this->repo->payout->fetchPayoutsFromCmsRefNumberWithinTimeRangeForIFT(
                    $bankTxnId,
                    $basEntity->getPostedDate(),
                    $bankTimeBeforePostedDate,
                    $basEntity->getAmount(),
                    $balance->getId());

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_IFT_FOR_CREDIT_MAPPING,
                                   [
                                       'cms_ref_no'                                          => $bankTxnId,
                                       'payout_ids'                                          => $payouts->getQueueableIds(),
                                       'bas_id'                                              => $basEntity->getId(),
                                       'account_no'                                          => $basEntity->getAccountNumber(),
                                       'payouts_fetched_via_cms_ref_no_for_ift_mapping_time' => (microtime(true) - $startTime) * 1000,
                                   ]);
            }
        }

        if ($payouts->count() === 1)
        {
            return $payouts->first();
        }

        if ($payouts->count() > 1)
        {
            $createExternalSource = true;
            $remarks                = 'multiple payouts found with same ' . $identifier . ' for IFT for credit mapping';

            $data = [
                'channel'    => $basEntity->getChannel(),
                'amount'     => $basEntity->getAmount(),
                'payout_ids' => $payouts->getQueueableIds(),
                'cms_ref_no' => $bankTxnId
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_PAYOUT_CMS_REF_NO_FOR_IFT_FOR_CREDIT_MAPPING, [
                'data' => $data,
            ]);

            $operation = 'multiple payouts found with same ' . $identifier . ' for IFT for credit mapping in account statement fetch';

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            return null;
        }

        $startTime = microtime(true);

        // we are checking both linked and unlinked payouts because debit row might have already been
        // processed.
        $payouts = $this->repo->payout->fetchPayoutsFromCmsRefNumberinTimeRange(
            $bankTxnId,
            $basEntity->getPostedDate(),
            $bankTimeBeforePostedDateForNonIFT,
            $basEntity->getAmount(),
            $balance->getId());

        $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_NON_IFT_FOR_CREDIT_MAPPING,
                           [
                               'cms_ref_no' => $bankTxnId,
                               'payout_ids' => $payouts->getQueueableIds(),
                               'bas_id'     => $basEntity->getId(),
                               'account_no' => $basEntity->getAccountNumber(),
                               'payouts_fetched_via_cms_ref_no_for_non_ift_mapping_time' => (microtime(true) - $startTime ) * 1000,
                           ]);

        if ($payouts->count() === 1)
        {
            return $payouts->first();
        }

        if ($payouts->count() > 1)
        {
            $createExternalSource = true;

            $remarks = 'multiple payouts found with same cms ref no for non IFT for credit mapping';

            $data = [
                'channel'    => $basEntity->getChannel(),
                'amount'     => $basEntity->getAmount(),
                'payout_ids' => $payouts->getQueueableIds(),
                'cms_ref_no' => $bankTxnId
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_PAYOUT_CMS_REF_NO_FOR_NON_IFT_FOR_CREDIT_MAPPING, [
                'data' => $data,
            ]);

            $operation = 'multiple payouts found with same cms ref no for non IFT for credit mapping in account statement fetch';

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            return null;

        }
        // we are removing the search on return_utr. The detailed reasoning is present here
        // https://razorpay.slack.com/archives/C01CX0EC34M/p1613643176033700
    }

    /**
     * TODO: The logic would be different based on the channel.
     * Refactor this when adding more banks here.
     *
     * @param Entity $basEntity
     *
     *  order of fetching:
     * 1. try to find existing Payout using utr.
     * 2. if not found , then try to find unlinked payout using CmsRefNumber Within some time range for ift
     * 3. if still no payout is found, then try to find unlinked payout using CmsRefNumber
     *
     * Cases for mapping bas record:
     *    1.Search for payout ((SEARCHING IN PAYOUT TABLE)with same UTR/CMS REF NO
     *      If more than 1 unlinked payout with the same UTR is found ,then raise alert
     *      and link with external with remarks as reason of failure (duplicate utr in this case).
     *      Same for CMS REF NO  and IFT cases
     *    2.If single payout is found then use this payout and return it
     *
     * @return mixed
     * @throws Exception\LogicException
     */

    public function fetchExistingPayoutForAccountStatement(Entity $basEntity,
                                                              &$createExternalSource,
                                                              &$remarks,
                                                              bool $checkCmsRefNo = true)
    {
        $payouts = new Base\Collection;

        $balance = $this->getBalance($basEntity);

        $utr = $basEntity->getUtr();

        $unlinkedPayouts = [];

        if (empty($utr) === false)
        {
            $startTime = microtime(true);

            $payouts = $this->repo->payout->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());

            $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_UTR_FOR_DEBIT_MAPPING,
                               [
                                   'utr'                        => $utr,
                                   'payout_ids'                 => $payouts->getQueueableIds(),
                                   'bas_id'                     => $basEntity->getId(),
                                   'account_no'                 => $basEntity->getAccountNumber(),
                                   'payouts_fetched_via_utr_mapping_time' => (microtime(true) - $startTime) * 1000
                               ]);

            foreach ($payouts as $key => $payout)
            {
                if ($payout->getTransactionId() !== null)
                {
                    $data = [
                        'channel'                  => $basEntity->getChannel(),
                        'amount'                   => $basEntity->getAmount(),
                        'current_payout_id'        => $payout->getId(),
                        'payout_ids_with_same_utr' => $payouts->getQueueableIds(),
                    ];

                    $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_UTR_FOR_PAYOUT, [
                        'data' => $data,
                    ]);

                    $operation = 'duplicate UTR in account statement fetch for a linked payout';

                    (new SlackNotification)->send(
                                                $operation,
                                                $data,
                                                null,
                                                1,
                                                'rx_ca_rbl_alerts');

                    unset($payouts[$key]);
                }
                else
                {
                    $unlinkedPayouts[] = $payout;
                }
            }

            if (count($unlinkedPayouts) === 1)
            {
                return $unlinkedPayouts[0];
            }
            // RBL has confirmed that UTR will be unique across all transactions
            // of RBL and so we not process this account statement record
            if (count($unlinkedPayouts) > 1)
            {
                $createExternalSource = true;

                $remarks = 'multiple unlinked payouts found with same utr for debit mapping';

                $data = [
                    'channel'    => $basEntity->getChannel(),
                    'amount'     => $basEntity->getAmount(),
                    'payout_ids' => $payouts->getQueueableIds(),
                    'utr'        => $utr
                ];

                $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_MULTIPLE_UNLINKED_PAYOUTS_WITH_SAME_UTR, [
                    'data' => $data,
                ]);

                $operation = 'multiple unlinked payouts found with same utr for debit mapping in account statement fetch';

                (new SlackNotification)->send(
                    $operation,
                    $data,
                    null,
                    1,
                    'rx_ca_rbl_alerts');

                return null;
            }
        }

        if ($checkCmsRefNo === false)
        {
            return null;
        }

        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $bankTimeBeforePostedDate = Carbon::createFromTimestamp(
                                                        $basEntity->getPostedDate(), Timezone::IST)
                                                        ->subHours(4)
                                                        ->getTimestamp();
            $startTime = microtime(true);

            /**
             * Bank is not sending cms ref number in the single payments api response for IFT mode. Hence FTS is appending
             * gateway reference number with 'RZP' as delimiter at the end of description of IFT transactions. Recon
             * needs to happen by picking the end 10 characters and match with gateway ref no. in fta table.
             * example description: SAMPLE NARRATION RZPTESTIFT123
             *
             * slack link: https://razorpay.slack.com/archives/C019AKLLQAH/p1616757629029200
             *
             * Only for IFT mode.
             */

            $description = $basEntity->getDescription() ? trim($basEntity->getDescription()) : '';

            $matches = [];

            if (($this->checkForRblSinglePaymentsApi($description, $matches) === true) and
                ($basEntity->getChannel() === Channel::RBL))
            {
                $gatewayRefNo = substr($matches[0], 4, 10);

                // this will be used to send slack notifications if required.
                $identifier = 'gateway ref no';

                // fetch only unlinked payouts i.e which do not have txn_id, since we are trying to map given bas
                // record with payout.
                $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromGatewayRefNumberWithinTimeRangeForIFT(
                    $gatewayRefNo,
                    $basEntity->getPostedDate(),
                    $bankTimeBeforePostedDate,
                    $basEntity->getAmount(),
                    $balance->getId());

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_GATEWAY_REF_NO_FOR_IFT_FOR_DEBIT_MAPPING,
                                   [
                                       'cms_ref_no'                                              => $bankTxnId,
                                       'gateway_ref_no'                                          => $gatewayRefNo,
                                       'payout_ids'                                              => $payouts->getQueueableIds(),
                                       'bas_id'                                                  => $basEntity->getId(),
                                       'account_no'                                              => $basEntity->getAccountNumber(),
                                       'payouts_fetched_via_gateway_ref_no_for_ift_mapping_time' => (microtime(true) - $startTime) * 1000
                                   ]);
            }
            else
            {
                // this will be used to send slack notifications if required.
                $identifier = 'cms ref no';

                // fetch only unlinked payouts i.e which do not have txn_id, since we are trying to map given bas
                // record with payout.
                $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromCmsRefNumberWithinTimeRangeForIFT(
                    $bankTxnId,
                    $basEntity->getPostedDate(),
                    $bankTimeBeforePostedDate,
                    $basEntity->getAmount(),
                    $balance->getId());

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_IFT_FOR_DEBIT_MAPPING,
                                   [
                                       'cms_ref_no'                                          => $bankTxnId,
                                       'payout_ids'                                          => $payouts->getQueueableIds(),
                                       'bas_id'                                              => $basEntity->getId(),
                                       'account_no'                                          => $basEntity->getAccountNumber(),
                                       'payouts_fetched_via_cms_ref_no_for_ift_mapping_time' => (microtime(true) - $startTime) * 1000
                                   ]);
            }
        }

        if ($payouts->count() === 1)
        {
            return $payouts->first();
        }

        if ($payouts->count() > 1)
        {
            $createExternalSource = true;
            $remarks                = 'multiple payouts found with same ' . $identifier . ' for IFT for debit mapping';

            $data = [
                'channel'    => $basEntity->getChannel(),
                'amount'     => $basEntity->getAmount(),
                'payout_ids' => $payouts->getQueueableIds(),
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_PAYOUT_CMS_REF_NO_FOR_IFT_FOR_DEBIT_MAPPING, [
                'data' => $data,
            ]);

            $operation = 'multiple payouts found with same ' . $identifier . ' for IFT for debit mapping in account statement fetch';

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            return null;
        }

        $startTime = microtime(true);

        // fetch only unlinked payouts i.e which do not have txn_id, since we are trying to map given bas
        // record with payout.
        $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromCmsRefNumber(
                                                                        $bankTxnId,
                                                                        $basEntity->getAmount(),
                                                                        $balance->getId());

        $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_NON_IFT_FOR_DEBIT_MAPPING,
                           [
                               'cms_ref_no'                                => $bankTxnId,
                               'payout_ids'                                => $payouts->getQueueableIds(),
                               'bas_id'                                    => $basEntity->getId(),
                               'account_no'                                => $basEntity->getAccountNumber(),
                               'payouts_fetched_via_cms_ref_no_for_non_ift_mapping_time' => (microtime(true) - $startTime) * 1000
                           ]);

        if ($payouts->count() > 1)
        {
            $createExternalSource = true;
            $remarks                = 'multiple payouts found with same cms ref no for non IFT for debit mapping';

            $data = [
                'channel'    => $basEntity->getChannel(),
                'amount'     => $basEntity->getAmount(),
                'payout_ids' => $payouts->getQueueableIds(),
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_PAYOUT_CMS_REF_NO_FOR_NON_IFT_FOR_CREDIT_MAPPING, [
                'data' => $data,
            ]);

            $operation = 'multiple payouts found with same cms ref no for non IFT for debit mapping in account statement fetch';

            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');

            return null;
        }

        return $payouts->first();
    }

    protected function checkForRblSinglePaymentsApi(string $statementDescription, array & $matches)
    {
        $regex = self::RBL_SINGLE_PAYMENTS_API_IFT_REGEX;

        $match = preg_match($regex, $statementDescription, $matches);

        if ($match === 1)
        {
            return true;
        }

        return false;
    }

    protected function validateBalance(Entity $basEntity, Base\PublicEntity $sourceEntity)
    {
        $balanceCalculated = $sourceEntity->transaction->getBalance();

        $balanceAtBankSide = $basEntity->getBalance();

        if ($balanceCalculated !== $balanceAtBankSide)
        {
            throw new Exception\LogicException(
                'Balance at channel does not match with our balance',
                ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                [
                    'rzp_balance'     => $balanceCalculated,
                    'channel_balance' => $balanceAtBankSide,
                    'account_number'  => $basEntity->getAccountNumber(),
                    'bank_txn_id'     => $basEntity->getBankTransactionId(),
                ]
            );
        }
    }

    protected function checkAndTraceForStaleResponse(int $bankTxnCount, int $processedCount, string $accountNumber)
    {
        //
        // Check for stale response from the channel,
        // This happens when we have stored the txns already,
        // but the bank is still sending us the same txns again
        // Since we are using the last txn for pagination, we
        // should never be receiving the same txns again
        //
        if (($bankTxnCount > 0) and ($processedCount === 0))
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_STATEMENT_STALE_RESPONSE,
                [
                    'account_number'    => $accountNumber,
                    'total'             => $bankTxnCount,
                    'processed'         => $processedCount,
                ]);
        }
    }

    //
    // 0. Trace the request here.
    //
    // 1. Fetch accountNumbers to process for that channel
    // We will fetch accountNumbers per channel ascending order by last_statement_fetch_at and pass the details through
    // a filter which would select based on following criteria.
    //
    // Criterion:
    // 1. When GATEWAY_BALANCE != STATEMENT_CLOSING_BALANCE, merchant is clearly a transacting merchant
    // 2. GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT > both STATEMENT_CLOSING_BALANCE_CHANGE_AT
    //    and LAST_STATEMENT_ATTEMPT_AT merchant is still a transacting merchant as this means merchant did credit and
    //    debit of equal amount after statement was fetched.
    // 3. when GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT is less than
    //    STATEMENT_CLOSING_BALANCE_CHANGE_AT or LAST_STATEMENT_ATTEMPT_AT, means we have fetched full statement of the
    //    merchant. Hence merchant is non-transacting.
    // 4. when GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT is greater than
    //    STATEMENT_CLOSING_BALANCE_CHANGE_AT but less than LAST_STATEMENT_ATTEMPT_AT, means we have fetched full statement
    //    of the merchant. This case arises when gateway balance cron gets delayed. Hence merchant is non-transacting.
    //
    // Create and dispatch jobs to pull data for those MIDs
    // Return accountNumbers dispatched for processing for the route response
    //
    public function dispatchAccountNumberForChannel(string $channel, array $input)
    {
        $limit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT]);

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON_INITIATED,
            [
                'channel'                              => $channel,
                'banking_account_statement_rate_limit' => $limit,
                'input'                                => $input
            ]);

        $accountType = array_pull($input, BASDetails\Entity::ACCOUNT_TYPE, BASDetails\AccountType::DIRECT);

        $bankingAccountDetails = $this->repo->banking_account_statement_details->fetchAccountNumbersByChannelOrderByLastStatementAttemptAt($channel, $accountType);

        $accountNumbersToDispatch = [];

        $otherAccounts = [];

        $numberOfAccountsSelected = 0;

        $currentTime = Carbon::now()->getTimestamp();

        foreach ($bankingAccountDetails as $bankingAccountDetail)
        {
            if ($this->checkIfBlackListedMerchant($bankingAccountDetail->merchant->getId()) === true)
            {
                continue;
            }

            $gatewayBalance = $bankingAccountDetail->getGatewayBalance();

            $statementClosingBalance = $bankingAccountDetail->getStatementClosingBalance();

            $gatewayBalanceChangeAt = $bankingAccountDetail->getGatewayBalanceChangeAt();

            $statementClosingBalanceChangeAt = $bankingAccountDetail->getStatementClosingBalanceChangeAt();

            $lastStatementAttemptAt = $bankingAccountDetail->getLastStatementAttemptAt();

            if (($gatewayBalance !== $statementClosingBalance) or
                ($gatewayBalance === $statementClosingBalance and
                 $gatewayBalanceChangeAt > $statementClosingBalanceChangeAt and
                 $gatewayBalanceChangeAt > $lastStatementAttemptAt))
            {
                $accountNumbersToDispatch[$numberOfAccountsSelected] = [
                    BASDetails\Entity::ACCOUNT_NUMBER => $bankingAccountDetail->getAccountNumber(),
                    BASDetails\Entity::BALANCE_ID     => $bankingAccountDetail->getBalanceId(),
                    BASDetails\Entity::CHANNEL        => $bankingAccountDetail->getChannel(),
                    'rule'                            => self::RBL_STATEMENT_FETCH_BALANCE_CHANGED_RULE,
                ];

                $numberOfAccountsSelected++;
            }
            else
            {
                array_push($otherAccounts, [
                    BASDetails\Entity::ACCOUNT_NUMBER => $bankingAccountDetail->getAccountNumber(),
                    BASDetails\Entity::BALANCE_ID     => $bankingAccountDetail->getBalanceId(),
                    BASDetails\Entity::CHANNEL        => $bankingAccountDetail->getChannel(),
                    'rule'                            => self::RBL_STATEMENT_FETCH_OTHERS_RULE
                ]);
            }

            if ($numberOfAccountsSelected >= $limit)
            {
                break;
            }
        }

        if ($numberOfAccountsSelected < $limit)
        {
            foreach ($otherAccounts as $otherAccount)
            {
                $accountNumbersToDispatch[$numberOfAccountsSelected] = $otherAccount;

                $numberOfAccountsSelected++;

                if ($numberOfAccountsSelected >= $limit)
                {
                    break;
                }
            }
        }

        $accountNumbersDispatched = [];

        $cronDispatchDelay = $this->getCronDispatchDelayByChannel($channel);

        foreach ($accountNumbersToDispatch as $accountNumberDetails)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON,
                [
                    'currentTime'         => $currentTime,
                    'channel'             => $channel,
                    'balanceId'           => $accountNumberDetails['balance_id'],
                    'rule'                => $accountNumberDetails['rule'],
                ]);

            array_push($accountNumbersDispatched, $accountNumberDetails['account_number']);

            $accountNumberDetails[self::DELAY] = $cronDispatchDelay;

            $this->dispatchBankingAccountStatementJob($accountNumberDetails, $accountType);
        }

        return ['accounts_processed' => $accountNumbersDispatched];
    }

    public function checkIfBlackListedMerchant($merchantId)
    {
        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::DISABLE_STATEMENT_FETCH,
            $this->mode
        );

        if ($variant === 'on')
        {
            return true;
        }

        return false;
    }

    // Adding a delay in dispatch and default is 0 min delay.
    // This delay can be made channel specific and can be kept in redis
    public function dispatchBankingAccountStatementJob(array $accountDetails, string $accountType = BASDetails\AccountType::DIRECT)
    {
        $channel       = array_pull($accountDetails, BASDetails\Entity::CHANNEL);
        $accountNumber = array_pull($accountDetails, BASDetails\Entity::ACCOUNT_NUMBER);
        $balanceId     = array_pull($accountDetails, BASDetails\Entity::BALANCE_ID);
        $delay         = array_pull($accountDetails, self::DELAY, 0);
        $attemptNumber = array_pull($accountDetails, self::ATTEMPT_NUMBER, 0);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_REQUEST,
            [
                BASDetails\Entity::CHANNEL        => $channel,
                BASDetails\Entity::ACCOUNT_NUMBER => $accountNumber,
                BASDetails\Entity::BALANCE_ID     => $balanceId,
                self::DELAY                       => $delay,
                self::ATTEMPT_NUMBER              => $attemptNumber
            ]);

        $job = $this->getAccountStatementJobForChannel($channel, $accountNumber, $accountType);

        $job::dispatch($this->mode,
                       [
                           BASDetails\Entity::CHANNEL        => $channel,
                           BASDetails\Entity::ACCOUNT_NUMBER => $accountNumber,
                           BASDetails\Entity::BALANCE_ID     => $balanceId,
                           self::ATTEMPT_NUMBER              => $attemptNumber
                       ])->delay($delay);
    }

    // Channel wise Queues are available for fetching direct accounts statement only. For Pool accounts we want to use BankingAccountStatementJob only.
    protected function getAccountStatementJobForChannel(string $channel, string $accountNumber, string $accountType)
    {
        if (($accountType === BASDetails\AccountType::DIRECT) and
            ($this->checkReArchFlow($accountNumber, $channel) === true))
        {
            $job = 'RZP\Jobs' . '\\' . studly_case($channel) . 'BankingAccountStatement';

            if (class_exists($job) === true)
            {
                return $job;
            }
        }

        $job = BankingAccountStatementJob::class;

        return $job;
    }

    public function getCronDispatchDelayByChannel(string $channel)
    {
        switch ($channel)
        {
            case Channel::RBL:
                $delay = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY]);
                break;

            default:
                $delay = 0;
        }

        if (empty($delay) === true)
        {
            $delay = 0;
        }

        return $delay;
    }

    protected function linkPayoutToDebitBas($payout, $debit_bas)
    {
        if (($payout->getId() === $debit_bas->source->getId()) and
            ($debit_bas->source->getEntity() === 'payout'))
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_ALREADY_HAS_PAYOUT_LINKED,
                [
                    'payout_id'    => $payout->getId(),
                    'debit_bas_id' => $debit_bas->getId(),
                ]);

            return;
        }

        if ($payout->getStatus() === Status::PROCESSED)
        {
            (new Payout\Core)->handlePayoutTransactionForDirectBanking($payout, $debit_bas);
        }
        else
        {
            (new Payout\Core)->handlePayoutProcessed($payout, $debit_bas);
        }
    }

    protected function linkCreditBas($payout, $credit_bas)
    {
        if ($payout->getStatus() === Status::REVERSED)
        {
            $reversal = $payout->reversal;

            (new Payout\Core)->handleReversalTransactionForDirectBanking($reversal, $credit_bas);
        }
        else
        {
            (new Payout\Core)->handlePayoutReversed($payout, null, null, $credit_bas);
        }
    }

    public function updateSourceLinking(array $input)
    {
        // TODO: add validation for input
        $validator = new Validator;

        $validator->validateInput(Validator::SOURCE_UPDATE, $input);

        /* @var \RZP\Models\Payout\Entity $payout */
        $payout = $this->repo->payout->findOrFail($input['payout_id']);

        $current_status = $payout->getStatus();

        $validator->validateCreditBas($current_status, $input);

        $debit_bas = $this->repo->banking_account_statement->findOrFail($input['debit_bas_id']);

        $this->linkPayoutToDebitBas($payout, $debit_bas);

        if (isset($input['credit_bas_id']) === true)
        {
            $credit_bas = $this->repo->banking_account_statement->findOrFail($input['credit_bas_id']);

            $this->linkCreditBas($payout, $credit_bas);
        }

        $payout->reload();

        $reversal = $payout->reversal;

        $response = [
            'payout'               => $payout->toArrayPublic(),
            'reversal'             => optional($reversal)->toArrayPublic(),
            'payout_transaction'   => $payout->transaction->toArrayPublic(),
            'reversal_transaction' => optional(optional($reversal)->transaction)->toArrayPublic()
        ];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_LIKING_UPDATE_RESPONSE,
            $response);

        return $response;
    }

    public function checkReArchFlow(string $accountNumber, string $channel)
    {
        if ($channel === Channel::ICICI)
        {
            return true;
        }

        // Some accounts are already onboarded to re-arch flow using this config key. Hence this is required for backward compatibility.
        $newStatementFetchFlowFeature = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW]);

        if (in_array($accountNumber, $newStatementFetchFlowFeature) === true)
        {
            return true;
        }

        /** @var BASDetails\Entity $basDetailEntity */
        $basDetailEntity = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

        //s($basDetailEntity->toArray());

        if ($basDetailEntity === null)
        {
            $id = (new Entity)->generateId()->getId();
            s($id);

            (new Details\Core)->createOrUpdate([
                                                   BASDetails\Entity::MERCHANT_ID               => $id,
                                                   BASDetails\Entity::ACCOUNT_NUMBER            => $accountNumber,
                                                   BASDetails\Entity::CHANNEL                   => $channel,
                                                   BASDetails\Entity::BALANCE_ID => $id
                                               ]);

            $basDetailEntity = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel);

            s($basDetailEntity->toArray());
        }
        // roll out via razorx.
        $variant = $this->app->razorx->getTreatment(
            $basDetailEntity->getMerchantId(),
            Merchant\RazorxTreatment::BAS_FETCH_RE_ARCH,
            $this->mode
        );

        return (strtolower($variant) == 'on');
    }

    /**
     * This function handles the logic to decide what events to send to ledger post the processing of source entity on statement fetch
     * @param $merchant
     * @param $sourceEntity
     * @param $basEntity
     */
    public function sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FLOW_LEDGER_SHADOW,
            [
                'source_entity_id'      => $sourceEntity->getPublicId(),
                'source_entity_name'    => $sourceEntity->getEntityName(),
                'bas_id'                => $basEntity->getId(),
                'account_no'            => $basEntity->getAccountNumber(),
                'entity_type'           => $basEntity->getEntityType(),
            ]);
        if ($sourceEntity->getEntityName() === Constants\Entity::EXTERNAL)
        {
            $ledgerEvent = Transaction\Processor\Ledger\Payout::DA_EXT_CREDIT;
            if ($basEntity->isTypeDebit() === true)
            {
                $ledgerEvent = Transaction\Processor\Ledger\Payout::DA_EXT_DEBIT;
            }
            $this->processLedgerPayoutForDirect($merchant, $ledgerEvent, null, null, $sourceEntity, $basEntity);

        }
        else if ($sourceEntity->getEntityName() === Constants\Entity::PAYOUT)
        {
            if ($sourceEntity->getPurpose() === Purpose::RZP_FEES)
            {
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::DA_FEE_PAYOUT_PROCESSED, $sourceEntity);
            }
            else
            {
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::DA_PAYOUT_PROCESSED, $sourceEntity);
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::DA_PAYOUT_PROCESSED_RECON, $sourceEntity, null, null, $basEntity);
            }
        }
        else if ($sourceEntity->getEntityName() === Constants\Entity::REVERSAL)
        {
            /** @var Payout\Entity $payout */
            $payout = $sourceEntity->entity;
            if ($payout->getPurpose() === Purpose::RZP_FEES)
            {
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::DA_FEE_PAYOUT_REVERSED, $payout, $sourceEntity);
            }
            else {
                $this->processLedgerPayoutForDirect($merchant,Transaction\Processor\Ledger\Payout::DA_PAYOUT_REVERSED, $payout, $sourceEntity);
                $this->processLedgerPayoutForDirect($merchant,Transaction\Processor\Ledger\Payout::DA_PAYOUT_REVERSED_RECON, $payout, $sourceEntity, null, $basEntity);
            }
        }
    }

    /**
     * @param string               $event
     * @param Payout\Entity|null   $payout
     * @param Reversal\Entity|null $reversal
     * @param External\Entity|null $external
     * @param Entity|null          $bas
     * Push event to ledger sns when
     * - an external record is identified as payout/reversal.
     * - a external record is not identified as payout/reversal
     * This will create the required journal in ledger DB.
     * Since ledger keeps different records for all payout states, these events are triggered.
     */
    public function processLedgerPayoutForDirect($merchant,
                                                 string $event,
                                                 Payout\Entity $payout = null,
                                                 Reversal\Entity $reversal = null,
                                                 External\Entity $external = null,
                                                 Entity $bas = null)
    {
        // Here only direct payout is pushed to ledger. So in case of shared, return.
        // In case env variable ledger.enabled is false, return.
        if ($this->app['config']->get('applications.ledger.enabled') === false)
        {
            return;
        }

        if (($payout !== null) and (($payout->getBalanceAccountType() === Merchant\Balance\AccountType::SHARED) or ($payout->isBalanceTypePrimary() === true)))
        {
            return;
        }

        // Skip ledger shadow mode for high TPS merchant
        if ($merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true)
        {
            return;
        }

        // If the mode is not live OR the merchant does not have the DA's ledger journal write feature, we return.
        if ($merchant->isFeatureEnabled(Feature\Constants::DA_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        (new Transaction\Processor\Ledger\Payout)
            ->pushTransactionToLedgerForDirect($event, $payout, $reversal, $external, $bas);
    }
}
