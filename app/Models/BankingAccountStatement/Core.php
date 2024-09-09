<?php

namespace RZP\Models\BankingAccountStatement;

use Mail;
use File;
use Cache;
use Carbon\Carbon;
use Razorpay\Trace\Logger;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Trace\Tracer;
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
use RZP\Constants\HyperTrace;
use RZP\Constants\Environment;
use RZP\Models\BankingAccount;
use RZP\Models\Payout\Purpose;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\PayoutService;
use RZP\Jobs\RblBankingAccountStatement;
use RZP\Jobs\IciciBankingAccountStatement;
use RZP\Jobs\BankingAccountStatementRecon;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Jobs\BankingAccountStatementUpdate;
use RZP\Jobs\BankingAccountStatementCleanUp;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Jobs\BankingAccountStatementReconNeo;
use RZP\Jobs\BankingAccountStatementProcessor;
use RZP\Models\Admin\Validator as AdminValidator;
use RZP\Jobs\BankingAccountMissingStatementInsert;
use RZP\Models\Feature\Constants as FeatureConstants;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\BankingAccountStatement\Processor\Source;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccountStatement\Constants as BASConstants;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Models\PayoutsStatusDetails\Core as PayoutsStatusDetailsCore;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;
use RZP\Models\BankingAccountStatement\Processor\Rbl\Gateway as RblGateway;
use RZP\Models\BankingAccountStatement\Processor\Icici\Gateway as IciciGateway;


class Core extends Base\Core
{
    const STORE_TYPE         = 'transactions';

    const FILE_ID            = 'file_id';

    const DASHBOARD_FILE_URL = '%sufh/file/%s';

    const DELAY = 'delay';

    const ATTEMPT_NUMBER = 'attempt_number';

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT = 6;

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_RESERVE_PERCENTAGE = 95;

    // Setting it to 1 hour in seconds
    const DEFAULT_BANKING_ACCOUNT_STATEMENT_INACTIVE_DURATION_RATE_LIMIT = 3600;

    const DEFAULT_RX_BAS_FORCED_FETCH_TIME_IN_HOURS = 8;

    // account numbers are selected for statement fetch based on these rules.
    const RBL_STATEMENT_FETCH_BALANCE_CHANGED_RULE  = "balance_changed_rule";

    const RBL_STATEMENT_FETCH_OTHERS_RULE           = "others";

    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE_DEFAULT = 200;

    const ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL_DEFAULT = 100;

    const ACCOUNT_STATEMENT_RECORDS_TO_FETCH_MISSING_STATEMENTS_FOR_ICICI = 8000;

    const ACCOUNT_STATEMENT_RECORDS_TO_FETCH_MISSING_STATEMENTS_FOR_RBL = 25000;

    const ACCOUNT_STATEMENT_BAS_ENTITIES_TO_UPDATE = 5000;

    // a default window of 3 days
    const POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH = 259200;

    const RETRY_COUNT_FOR_ID_GENERATION = 100;

    const DEFAULT_RX_MISSING_STATEMENTS_INSERTION_LIMIT = 100;

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY = 10;

    const MAX_RETRY_COUNT_FOR_PROCESSING_ACCOUNT_STATEMENT = 2;

    const MISSING_STATEMENTS_REDIS_KEY = "missing_statements_%s_%s";

    const PAYOUT_SERVICE_TEMPORARY_METADATA_TABLE = 'payout_meta_temporary';
    const PAYOUT_ID                               = 'payout_id';
    const DUAL_WRITE_META_NAME                    = 'bas_dual_write';

    /**
     * Constant containing regex for identifying gateway ref number pattern in a statement's description for every bank.
     * Here, we maintain an array for every bank because regex can be different for different modes. In case of RBL and ICICI,
     * its the same for every mode and hence we store it in a default key. When a use case arises for different modes, it will be
     * simple to add a new key based on the mode and store the regex for it.
     */
    const GATEWAY_REF_NUMBER_PATTERN = [
        Channel::RBL => [
            // In Single payments api we append gateway ref no in description for IFT mode. This regex will be used to fetch
            // gateway ref no while recon.
            // ex: SAMPLE NARRATION RZPTESTIFT123
            BASConstants::DEFAULT => "/\sRZP+[0-9A-Z]{10}$/"
        ],
        Channel::ICICI => [
            // ex: MMT/IMPS/307612641236/APILSCQOSeJ8123/TEST/SBIN0070663
            BASConstants::DEFAULT => "/API[a-zA-Z0-9]{12}/"
        ]
    ];

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

    /**
     * This is used to check if the current process is running for statement fix or not
     *
     * @var bool
     */
    protected $isStatementUnderFix = false;

    /**
     * This array helps in setting the transaction_id and created_at for missing statement transactions
     *
     * @var array
     */
    protected $insertedBasTransactionDetails = null;

     /**
      * This is used to check if the current process is just dry run or not
      *
      * @var bool
      */
    protected $isDryRunModeActiveForStatementFix = false;

    protected $creditBeforeDebitUtrs = [];

    protected $mutex;

    /** @var PayoutService\BankingAccountStatement */
    protected $payoutServiceBankingAccountStatementClient;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->payoutServiceBankingAccountStatementClient = $this->app[PayoutService\BankingAccountStatement::PAYOUT_SERVICE_BANKING_ACCOUNT_STATEMENT];
    }

    /** @var Details\Entity $basDetails  */
    public $basDetails = null;

    public function getBasDetails(string $accountNumber = null, string $channel = null, array $statuses = [BASDetails\Status::ACTIVE])
    {
        if (($this->basDetails === null) and
            ($accountNumber !== null) and
            ($channel !== null))
        {
            $this->basDetails = $this->repo->banking_account_statement_details->fetchByAccountNumberAndChannel($accountNumber, $channel, $statuses);
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
                    $migratedRblAccount = false;

                    try
                    {
                        $bankingAccount = $this->repo->banking_account->findByAccountNumberAndChannel($accountNumber, $channel);

                        $balance = $bankingAccount->balance;
                    }
                    catch(\Exception $ex)
                    {
                        $balance = $this->repo->balance->getBalanceEntityByAccountNumber($accountNumber, $this->app['rzp.mode']);

                        if (empty($balance))
                        {
                            throw $ex;
                        }

                        $migratedRblAccount = true;
                    }

                    $currentTime = Carbon::now()->getTimestamp();

                    // updating LastStatementAttemptAt irrespective of success or fail so that new accounts are always fetched
                    // using LastStatementAttemptAt and failed accounts can be manually tried by sre also We are handling failure
                    // retry in job instead.
                    if (!$migratedRblAccount)
                    {
                        // for migrated RBL CAs, last_statement_attempt_at is irrelevant
                        $bankingAccount->setLastStatementAttemptAt($currentTime);

                        $this->repo->saveOrFail($bankingAccount);
                    }

                    $basDetailEntity = $this->getBasDetails($accountNumber, $channel);

                    $basDetailEntity->setLastStatementAttemptAt();

                    $this->repo->saveOrFail($basDetailEntity);

                    $accountStatementApiVersion = $this->getAccountStatementApiVersion($basDetailEntity);

                    $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

                    $accountStatementDetails = $processor->fetchAccountStatementDetails($input);

                    $this->processAccountStatement($accountStatementDetails, $processor, $basDetailEntity);

                    $balance->updateLastFetchedAt();
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
                    $accountStatementApiVersion = Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_2;
                }
            }
            else
            {
                $v1Merchants = [
                    'E3QUiloUAmQJTD',
                    'D4au0GwLGp3SMY',
                    'Fmn65RDV0khueP',
                    'G64B7qHr2WGAJb',
                    'GE4OiRvbrcAckv',
                    'INQservugLb8Ag',
                ];

                if (in_array($basDetails->getMerchantId(), $v1Merchants) === false)
                {
                    $accountStatementApiVersion = Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_2;
                }
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

                    if (empty($basDetailEntity) === true)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_DETAILS_FOR_ACCOUNT_IS_NOT_ACTIVE, null, [
                            'account_number' => $accountNumber,
                            'channel'        => $channel,
                        ]);
                    }

                    $basDetailEntity->reload();

                    if (($basDetailEntity->getStatus() !== Details\Status::ACTIVE) or
                        ($basDetailEntity->getStatus() === Details\Status::UNDER_MAINTENANCE))
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_DETAILS_FOR_ACCOUNT_IS_NOT_ACTIVE, null, [
                            'account_number'     => $accountNumber,
                            'channel'            => $channel,
                            Entity::MERCHANT_ID  => optional($this->basDetails)->getMerchantId(),
                            'bas_details_id'     => $basDetailEntity->getId(),
                            'bas_details_status' => $basDetailEntity->getStatus(),
                        ]);
                    }

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

    public function fetchAccountStatementWithRange(array $input, $isMonitoring = false, $save = false, $checkDuplicates = true)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        try
        {
            $this->trace->info(
                TraceCode::FETCH_MISSING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
                [
                    'channel'        => $channel,
                    'account_number' => $accountNumber,
                ]);

            [$fetchMore, $paginationKey, $bankTransactions, $mismatchAmountFound] = $this->mutex->acquireAndRelease(
                'banking_account_statement_recon_' . $accountNumber . '_' . $channel,
                function () use ($channel, $accountNumber, $input, $isMonitoring, $save, $checkDuplicates)
                {
                    $basDetailEntity = $this->getBasDetails(
                        $accountNumber, $channel, [BASDetails\Status::ACTIVE, BASDetails\Status::UNDER_MAINTENANCE]);

                    $merchant = $basDetailEntity->merchant;

                    // There are 6 merchants excluded from v2
                    $accountStatementApiVersion = ($channel === Channel::RBL) ? Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_2 :
                                                                              Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_1;

                    $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

                    $input[Entity::MERCHANT_ID] = $merchant->getId();

                    $this->trace->info(
                        TraceCode::FETCH_MISSING_ACCOUNT_STATEMENT_REMOTE_FETCH_SOURCE,
                        [
                            'input'     => $input,
                            'source'    => Source::FETCH_API,
                            'version'   => $accountStatementApiVersion
                        ]);

                    [$bankTransactions, $fetchMore, $paginationKey] = $processor->sendRequestToFetchStatement($input);

                    if (boolval($save) === true)
                    {
                        $this->saveAccountStatementDetails($bankTransactions, $merchant, $channel, $accountNumber, $processor, $basDetailEntity);

                        $basDetailEntity->balance->updateLastFetchedAt();

                        return [null, null];
                    }

                    $missingTransactions = [];
                    $mismatchAmountFound = 0;

                    if ($checkDuplicates === true)
                    {
                        $missingTransactions = $processor->checkForDuplicateTransactions(
                            $bankTransactions,
                            $channel,
                            $accountNumber,
                            $merchant);
                    }

                    //get last statement in our db
                    $lastBankTxn = $this->repo->banking_account_statement->findLatestByAccountNumber($accountNumber);

                    $postedDateOfLastTransaction = isset($lastBankTxn) === true ? $lastBankTxn[Entity::POSTED_DATE] : null;

                    /**
                     * Removing statements not pertaining to the range
                     */
                    foreach ($missingTransactions as $key => $missingTransaction)
                    {
                        if (($missingTransaction[Entity::TRANSACTION_DATE] > $input[Entity::TO_DATE]) or
                            ((isset($postedDateOfLastTransaction) === true) and
                             ($missingTransaction[Entity::POSTED_DATE] > $postedDateOfLastTransaction)))
                        {
                            unset($missingTransactions[$key]);
                        }
                        else
                        {
                            if ($missingTransaction[Entity::TYPE] === Type::CREDIT)
                            {
                                $mismatchAmountFound += $missingTransaction[Entity::AMOUNT];
                            }
                            else
                            {
                                $mismatchAmountFound += (-1 * $missingTransaction[Entity::AMOUNT]);
                            }
                        }
                    }

                    if (count($missingTransactions) !== 0)
                    {
                        $missingTransactions = array_values($missingTransactions);

                        $traceData = [
                            'channel'               => $channel,
                            'merchant_id'           => $merchant->getId(),
                            'from_date'             => $input[Entity::FROM_DATE],
                            'to_date'               => $input[Entity::TO_DATE],
                            'missing_records_found' => count($missingTransactions),
                            'missing_records'       => $missingTransactions,
                        ];

                        $this->trace->info(TraceCode::MISSING_TRANSACTIONS_FOUND, $traceData);

                        //pushing the missing transaction metrics to vajra for monitoring
                        $this->trace->count(Metric::MISSING_STATEMENTS_FOUND, [
                            Metric::LABEL_CHANNEL => $channel,
                            'is_monitoring'       => $isMonitoring,
                        ]);

                        $this->trace->histogram(Metric::MISSING_STATEMENTS_COUNT, count($missingTransactions), [
                            Metric::LABEL_CHANNEL => $channel,
                            'is_monitoring'       => $isMonitoring,
                            'merchant_id'         => $merchant->getId(),
                        ]);

                        Tracer::startSpanWithAttributes(HyperTrace::MISSING_STATEMENTS_FOUND,
                            [
                                Metric::LABEL_CHANNEL => $channel
                            ]);

                        if (boolval($input[Entity::SAVE_IN_REDIS]) === true)
                        {
                            // Persisting in redis
                            $processor->storeMissingStatementsInRedis($missingTransactions, $accountNumber, $merchant->getId());
                        }
                    }

                    return [$fetchMore, $paginationKey, $bankTransactions, $mismatchAmountFound];
                },
                300,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS,
                1
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

        return [$fetchMore, $paginationKey, $bankTransactions, $mismatchAmountFound];
    }

    public function insertMissingStatementsNeo(array $input, array $missingStatements, array $updateParams)
    {
        $insertStartTime = microtime(true);

        $noOfStatementsInserted = 0;

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $response = [];

        $channel = $input[Entity::CHANNEL];

        $missingStatementsBeforeDedupe = $missingStatements;

        $basDetails = $this->getBasDetails($accountNumber, $channel,[Details\Status::UNDER_MAINTENANCE, Details\Status::ACTIVE]);

        if (isset($basDetails) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $merchantId = $basDetails->getMerchantId();

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::OPTIMISE_INSERTION_LOGIC,
            $this->mode ?? Constants\Mode::LIVE,
            2
        );

        [$hasMore, $updateParams] = $this->mutex->acquireAndRelease(
            'banking_account_statement_fetch_' . $accountNumber . '_' . $channel,
            function () use ($channel, $accountNumber, $missingStatements, $updateParams, &$noOfStatementsInserted, $variant)
            {
                $countOfMissingRecords = count($missingStatements);

                if ($countOfMissingRecords === 0)
                {
                    $this->trace->info(TraceCode::BAS_MISSING_RECORDS_INSERTION_COMPLETE,
                        [
                            'account_number' => $accountNumber,
                            'merchant_id'    => $this->basDetails->getMerchantId(),
                            'params'         => $updateParams,
                            'count'          => $countOfMissingRecords,
                        ]);

                    if (empty($updateParams) === false)
                    {
                        try
                        {
                            // Todo:: Same check as insertMissingStatements need to be added to check if it is automated_recon
                            BankingAccountStatementUpdate::dispatch($this->mode, $updateParams)->delay(15);

                            $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_SUCCESS,
                                [
                                    'account_number' => $accountNumber,
                                    'merchant_id'    => $this->basDetails->getMerchantId(),
                                    'params'         => $updateParams
                                ]);
                        }
                        catch(\Exception $exception)
                        {
                            $this->trace->traceException(
                                $exception,
                                null,
                                TraceCode::BAS_UPDATE_QUEUE_DISPATCH_FAILURE,
                                [
                                    'account_number' => $accountNumber,
                                    'merchant_id'    => $this->basDetails->getMerchantId(),
                                    'channel'        => $channel
                                ]
                            );
                        }
                    }
                    return [false, []];
                }
                else
                {
                    $this->trace->info(TraceCode::BAS_MISSING_RECORDS_INSERTION_COUNT,
                        [
                            'account_number'             => $accountNumber,
                            'params'                     => $updateParams,
                            'count_of_records_to_insert' => $countOfMissingRecords,
                            'merchant_id'                => $this->basDetails->getMerchantId()
                        ]);

                    $insertLimit = self::DEFAULT_RX_MISSING_STATEMENTS_INSERTION_LIMIT;

                    $missingStatements = array_slice($missingStatements, 0, $insertLimit);

                    $this->isStatementUnderFix = true;

                    $this->setBasDetailsForStatementFix($accountNumber, $channel);

                    if ($variant === 'on')
                    {
                        $insertedBasEntities = $this->optimiseSaveMissingAccountStatements($accountNumber, $channel, $missingStatements);
                    }
                    else
                    {
                        $insertedBasEntities = $this->saveMissingAccountStatements($accountNumber, $channel, $missingStatements);
                    }

                    $this->pushMissingStatementsLinkingEventsToLedger($accountNumber, $channel, $insertedBasEntities);

                    $noOfStatementsInserted = count($insertedBasEntities);

                    if ((empty($insertedBasEntities) === true) and
                        (empty($updateParams) === true))
                    {
                        //if no inserted entities then we need to remove under_maintenance mode for merchant.
                        $this->releaseBasDetailsFromStatementFix($accountNumber, $channel);

                        return [false, []];
                    }
                    else
                    {
                        if (empty($insertedBasEntities) === true)
                        {
                            return [true, $updateParams];
                        }

                        $basIdToAmountMap = [];

                        $createdAt = $insertedBasEntities[0]->getCreatedAt();

                        $latestCorrectedId = $insertedBasEntities[0]->getId();

                        foreach ($insertedBasEntities as $basEntity)
                        {
                            $basIdToAmountMap[$basEntity->getId()] = $basEntity->getNetAmountBasedOnTransactionType();

                            $createdAt = min($createdAt, $basEntity->getCreatedAt());

                            $latestCorrectedId = min($latestCorrectedId, $basEntity->getId());
                        }

                        $delay = 5;

                        if (empty($updateParams) === false)
                        {
                            $updateParams['update_before'] = Carbon::now()->getTimestamp() + $delay;

                            $amountBasIdMap = $updateParams['bas_id_to_amount_map'];

                            $updateParams['bas_id_to_amount_map'] = array_merge($amountBasIdMap, $basIdToAmountMap);

                            $updateParams['created_at'] = min($updateParams['created_at'], $createdAt);

                            $updateParams['last_corrected_id'] = min($updateParams['last_corrected_id'], $latestCorrectedId);

                        }
                        else
                        {
                            $updateParams = [
                                'channel'              => $channel,
                                'account_number'       => $accountNumber,
                                'merchant_id'          => $this->basDetails->getMerchantId(),
                                'balance_id'           => $this->basDetails->getBalanceId(),
                                'bas_id_to_amount_map' => $basIdToAmountMap,
                                'created_at'           => $createdAt,
                                'update_before'        => Carbon::now()->getTimestamp() + $delay,
                                'latest_corrected_id'  => $latestCorrectedId,
                                'batch_number'         => 0
                            ];
                        }

                        return [true, $updateParams];

                    }
                }
            },
            1800,
            ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
        );

        $insertEndTime = microtime(true);

        $this->trace->info(TraceCode::BAS_MISSING_STATEMENT_INSERTED_SUCCESSFULLY, [
            'merchant_id'         => $merchantId,
            'response_time'       => $insertEndTime - $insertStartTime,
            'variant'             => $variant,
            'statements_inserted' => $noOfStatementsInserted,
        ]);

        try
        {
            if (empty($missingStatementsBeforeDedupe) === false)
            {
                $this->removeInsertedMissingRecordsForAccountFromRedis(
                    $accountNumber,
                    $channel,
                    $merchantId,
                    $missingStatementsBeforeDedupe);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_FAILURE,
                [
                    'account_number' => $accountNumber,
                    'channel'        => $channel,
                    'merchant_id'    => $this->basDetails->getMerchantId()
                ]
            );

            $response['message'] = 'Missing statements got inserted and linked successfully.
                                    Dispatched for updating BAS entities.
                                    Removal of inserted missing statements from redis got failed.';

            $this->trace->count(Metric::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_FAILURE);
        }

        if ($hasMore === true)
        {
            $this->trace->info(TraceCode::BAS_INSERT_QUEUE_DISPATCH_INIT,
                               [
                                   'account_number' => $accountNumber,
                                   'params'         => $updateParams,
                                   'merchant_id'    => $this->basDetails->getMerchantId()
                               ]);

            try
            {
                BankingAccountMissingStatementInsert::dispatch($this->mode, $input, $updateParams)->delay(5);

                $this->trace->info(TraceCode::BAS_INSERT_QUEUE_DISPATCH_SUCCESS,
                                   [
                                       'account_number' => $accountNumber,
                                       'params'         => $updateParams,
                                       'merchant_id'    => $this->basDetails->getMerchantId()
                                   ]);
            }
            catch(\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::BAS_INSERT_QUEUE_DISPATCH_FAILURE,
                    [
                        'account_number' => $accountNumber,
                        'channel'        => $channel,
                        'merchant_id'    => $this->basDetails->getMerchantId()
                    ]
                );

                $response['message'] = 'Missing statements previous batch successfully inserted and linked.
                                    Dispatch for insertion of further batch got FAILED.';
            }
        }

        return $response;
    }

    public function insertMissingStatements(
        string $accountNumber,
        string $channel,
        string $merchantId,
        array $missingStatements,
        bool $dryRunMode = false)
    {
        $insertStartTime = microtime(true);

        $noOfStatementsInserted = 0;

        $insertLimit = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_MISSING_STATEMENTS_INSERTION_LIMIT
            ]);

        if (empty($insertLimit) === true)
        {
            $insertLimit = self::DEFAULT_RX_MISSING_STATEMENTS_INSERTION_LIMIT;
        }

        $missingStatements = array_slice($missingStatements, 0, $insertLimit);

        $missingStatementsBeforeDedupe = $missingStatements;

        $basDetails = $this->getBasDetails($accountNumber, $channel);

        if (isset($basDetails) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $variant = $this->app->razorx->getTreatment(
            $basDetails->getMerchantId(),
            Merchant\RazorxTreatment::OPTIMISE_INSERTION_LOGIC,
            $this->mode ?? Constants\Mode::LIVE,
            2
        );

        [$response, $params] = $this->mutex->acquireAndRelease(
            'banking_account_statement_fetch_' . $accountNumber . '_' . $channel,
            function () use ($channel, $accountNumber, $missingStatements, $dryRunMode, &$noOfStatementsInserted, $variant)
            {
                // setting the variable to true to customize the later flow (linking the statement to source entity)
                $this->isStatementUnderFix = true;

                $this->isDryRunModeActiveForStatementFix = $dryRunMode;

                $this->setBasDetailsForStatementFix($accountNumber, $channel);

                if ($variant === 'on')
                {
                    $insertedBasEntities = $this->optimiseSaveMissingAccountStatements($accountNumber, $channel, $missingStatements);
                }
                else
                {
                    $insertedBasEntities = $this->saveMissingAccountStatements($accountNumber, $channel, $missingStatements);
                }

                $this->pushMissingStatementsLinkingEventsToLedger($accountNumber, $channel, $insertedBasEntities);

                if (empty($insertedBasEntities) === true)
                {
                    $response = [
                        'message' => 'All the missing statements found where already inserted.'
                    ];

                    return [$response, []];
                }

                $basIdToAmountMap = [];

                $createdAt = $insertedBasEntities[0]->getCreatedAt();

                $latestCorrectedId = $insertedBasEntities[0]->getId();

                foreach ($insertedBasEntities as $basEntity)
                {
                    $basIdToAmountMap[$basEntity->getId()] = $basEntity->getNetAmountBasedOnTransactionType();

                    $createdAt = min($createdAt, $basEntity->getCreatedAt());

                    $latestCorrectedId = min($latestCorrectedId, $basEntity->getId());
                }

                // Adding delay of 2 secs in updated_before so that all inserted statements are updated
                // And no overlap during updation occurs
                $delay = 2;

                $params = [
                    'channel'              => $channel,
                    'account_number'       => $accountNumber,
                    'merchant_id'          => $this->basDetails->getMerchantId(),
                    'balance_id'           => $this->basDetails->getBalanceId(),
                    'bas_id_to_amount_map' => $basIdToAmountMap,
                    'created_at'           => $createdAt,
                    'update_before'        => Carbon::now()->getTimestamp() + $delay,
                    'latest_corrected_id'  => $latestCorrectedId,
                    'batch_number'         => 0
                ];

                $response =  [
                    'message'            => 'Missing statements got inserted and linked successfully.
                                             Dispatched for updating BAS entities.',
                    'insertedStatements' => json_encode($missingStatements),
                ];

                $noOfStatementsInserted = count($missingStatements);

                return [$response, $params];
            },
            1800,
            ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
        );

        $insertEndTime = microtime(true);

        $this->trace->info(TraceCode::BAS_MISSING_STATEMENT_INSERTED_SUCCESSFULLY, [
            'response_time'       => $insertEndTime - $insertStartTime,
            'variant'             => $variant,
            'statements_inserted' => $noOfStatementsInserted,
        ]);

        $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_INIT,
            [
                'account_number' => $accountNumber,
                'params'         => $params
            ]);

        if ($this->isDryRunModeActiveForStatementFix === true)
        {
            $this->releaseBasDetailsFromStatementFix($accountNumber, $channel);

            $response['dry_run'] = true;

            return $response;
        }

        $jobName = app('worker.ctx')->getJobName() ?? null;

        // Set last_reconciled_at if the inserted statments have been found to be 0. This means, all the statements have been
        // already inserted and the merchant is properly reconciled till T-1
        if (($jobName === BASConstants::BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO) and
            ($noOfStatementsInserted === 0))
        {
            $basDetails->reload();

            $lastReconciledAt = Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp();

            $presentLastReconciledAt = $basDetails->getLastReconciledAt();

            if ((isset($presentLastReconciledAt) === false) or
                ($presentLastReconciledAt < $lastReconciledAt))
            {
                $basDetails->setLastReconciledAt($lastReconciledAt);

                $this->repo->saveOrFail($basDetails);
            }
        }

        try
        {
            if (empty($params) === false)
            {
                if ($jobName === BASConstants::BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO)
                {
                    $params[BASConstants::IS_AUTOMATED_CLEANUP] = true;
                }

                BankingAccountStatementUpdate::dispatch($this->mode, $params)->delay(10);

                $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_SUCCESS,
                                   [
                                       'account_number' => $accountNumber,
                                       'params'         => $params
                                   ]);
            }
        }
        catch(\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::BAS_UPDATE_QUEUE_DISPATCH_FAILURE,
                [
                    'account_number' => $accountNumber,
                    'channel'        => $channel
                ]
            );

            $response['message'] = 'Missing statements got inserted and linked successfully.
                                    Dispatch for updating BAS entities got FAILED.';

            $this->trace->count(Metric::BAS_UPDATE_QUEUE_DISPATCH_FAILURE);
        }

        try
        {
            $this->removeInsertedMissingRecordsForAccountFromRedis($accountNumber, $channel, $merchantId, $missingStatementsBeforeDedupe);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_FAILURE,
                [
                    'account_number' => $accountNumber,
                    'channel'        => $channel
                ]
            );

            $response['message'] = 'Missing statements got inserted and linked successfully.
                                    Dispatched for updating BAS entities.
                                    Removal of inserted missing statements from redis got failed.';

            $this->trace->count(Metric::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_FAILURE);
        }

        if (empty($params) === true)
        {
            $this->releaseBasDetailsFromStatementFix($accountNumber, $channel);
        }

        return $response;
    }

    // finds the insertion point and then saves it accordingly
    protected function saveMissingAccountStatements(string $accountNumber, string $channel, array & $missingStatements)
    {
        return $this->repo->transaction(function() use ($accountNumber, $channel, & $missingStatements)
        {
            $basDetailEntity = $this->getBasDetails($accountNumber, $channel, [Details\Status::UNDER_MAINTENANCE]);

            $merchant = $basDetailEntity->merchant;

            $merchantId = $merchant->getId();

            $accountStatementApiVersion = $this->getAccountStatementApiVersion($basDetailEntity);

            $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

            $countOfStatementsBeforeDedupe = count($missingStatements);

            $missingStatements = $processor->checkForDuplicateTransactions($missingStatements, $channel, $accountNumber, $merchant);

            $this->trace->info(TraceCode::BAS_MISSING_RECORD_ALREADY_EXISTS, [
                'account_number'                   => $accountNumber,
                'channel'                          => $channel,
                'count_of_statement_before_dedupe' => $countOfStatementsBeforeDedupe,
                'count_of_statement_after_dedupe'  => count($missingStatements),
            ]);

            $missingStatementsCollection = new Base\Collection($missingStatements);

            $groupedMissingStatements = $missingStatementsCollection->groupBy('posted_date');

            $insertedBasEntities    = [];
            $insertedStatements     = [];
            $insertedBasIds         = [];
            $insertedTransactionIds = [];

            $lastTransaction = $this->repo->banking_account_statement
                                               ->findLatestByAccountNumberAndChannel($accountNumber, $channel);

            $postedDateOfLastTransaction = isset($lastTransaction) === true ? $lastTransaction[Entity::POSTED_DATE] : null;

            foreach ($groupedMissingStatements as $postedDate => $groupOfStatements)
            {
                $insertionDetails = $this->getInsertionDetailsForMissingStatement(
                    $merchantId, $accountNumber, $channel, $groupOfStatements[0], $postedDateOfLastTransaction);

                $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_INSERTION_DETAILS, [
                    'account_number'    => $accountNumber,
                    'channel'           => $channel,
                    'posted_date'       => $postedDate,
                    'insertion_details' => $insertionDetails,
                    'dry_run_mode'      => $this->isDryRunModeActiveForStatementFix,
                ]);

                $previousBasId         = $insertionDetails[Entity::ID];
                $previousTransactionId = $insertionDetails[Entity::TRANSACTION_ID];

                foreach ($groupOfStatements as $statement)
                {
                    $nextId = $this->generateNextUnUsedIdForEntity($previousBasId,
                                                                   $insertedBasIds,
                                                                   Constants\Entity::BANKING_ACCOUNT_STATEMENT);

                    $basEntity = (new Entity)->build($statement);

                    $basEntity->setId($nextId);

                    if (empty($basEntity->getUtr()) === true)
                    {
                        $utr = $processor->getUtrForChannel($basEntity);

                        $basEntity->setUtr($utr);
                    }

                    $basEntity->merchant()->associate($merchant);

                    $missingStatementCreatedAt = max($insertionDetails[Entity::CREATED_AT], $statement[Entity::POSTED_DATE], $statement[Entity::TRANSACTION_DATE]);

                    $basEntity->setCreatedAt($missingStatementCreatedAt);

                    $basEntity->setUpdatedAt($missingStatementCreatedAt);

                    $balanceChange = $basEntity->getNetAmountBasedOnTransactionType();

                    $basEntity->setBalance($insertionDetails[Entity::BALANCE] + $balanceChange);

                    if ($this->isDryRunModeActiveForStatementFix === false)
                    {
                        $this->repo->saveOrFail($basEntity);
                    }

                    $insertedBasIds[] = $nextId;

                    $this->trace->info(TraceCode::BAS_INSERTED_ENTITY, [
                        'bank_txn_id'          => $statement[Entity::BANK_TRANSACTION_ID],
                        'bank_txn_posted_date' => $statement[Entity::POSTED_DATE],
                        'bank_txn_channel'     => $statement[Entity::CHANNEL],
                        'bas_id'               => $basEntity->getId(),
                        'account_no'           => $basEntity->getAccountNumber(),
                        'utr'                  => $basEntity->getUtr(),
                        'previous_bas_id'      => $insertionDetails[Entity::ID],
                        'dry_run_mode'         => $this->isDryRunModeActiveForStatementFix,
                        'inserted_bas_entity'  => $basEntity->toArray(),
                    ]);

                    $insertedBasEntities[] = $basEntity;

                    $insertedStatements[] = $statement;

                    $previousBasId = $nextId;

                    $generateTransactionId = $this->generateNextUnUsedIdForEntity($previousTransactionId,
                                                                                  $insertedTransactionIds,
                                                                                  Constants\Entity::TRANSACTION);

                    $this->insertedBasTransactionDetails = [
                        Transaction\Entity::ID         => $generateTransactionId,
                        Transaction\Entity::CREATED_AT => max($insertionDetails['transaction_created_at'], $statement[Entity::POSTED_DATE])
                    ];

                    try
                    {
                        if ($this->isDryRunModeActiveForStatementFix === false)
                        {
                            // the balance gets updated when we link a statement to the source entity and
                            // as a result, the actual statement entities linking gets stopped
                            // that is our desired behaviour while fixing statement
                            $insertedBasEntity = new Base\PublicCollection([$basEntity]);

                            $this->saveAccountStatementV2($insertedBasEntity, $merchant);
                        }

                        $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_PREVIOUS_TRANSACTION_DETAILS, [
                            'account_number'                   => $accountNumber,
                            'channel'                          => $channel,
                            'previous_bas_transaction_details' => $this->insertedBasTransactionDetails,
                            'dry_run_mode'                     => $this->isDryRunModeActiveForStatementFix,
                        ]);
                    }
                    catch (\Exception $exception)
                    {
                        $this->trace->traceException(
                            $exception,
                            null,
                            TraceCode::BAS_MISSING_STATEMENT_LINKING_FAILED,
                            [
                                'bas_id' => $basEntity->getId(),
                                'utr'    => $basEntity->getUtr(),
                            ]
                        );

                        throw $exception;
                    }

                    $insertedTransactionIds[] = $generateTransactionId;

                    $previousTransactionId = $generateTransactionId;
                }
            }

            $missingStatements = $insertedStatements;

            return $insertedBasEntities;
        });
    }

    // finds the insertion point optimally and then saves it accordingly
    protected function optimiseSaveMissingAccountStatements(string $accountNumber, string $channel, array & $missingStatements)
    {
        return $this->repo->transaction(function() use ($accountNumber, $channel, & $missingStatements)
        {
            $basDetailEntity = $this->getBasDetails($accountNumber, $channel, [Details\Status::UNDER_MAINTENANCE]);

            $merchant = $basDetailEntity->merchant;

            $merchantId = $merchant->getId();

            $accountStatementApiVersion = $this->getAccountStatementApiVersion($basDetailEntity);

            $processor = $this->getProcessor($channel, $accountNumber, $basDetailEntity, $accountStatementApiVersion);

            $countOfStatementsBeforeDedupe = count($missingStatements);

            $missingStatements = $processor->checkForDuplicateTransactions($missingStatements, $channel, $accountNumber, $merchant);

            $this->trace->info(TraceCode::BAS_MISSING_RECORD_ALREADY_EXISTS, [
                'account_number'                   => $accountNumber,
                'channel'                          => $channel,
                'count_of_statement_before_dedupe' => $countOfStatementsBeforeDedupe,
                'count_of_statement_after_dedupe'  => count($missingStatements),
            ]);

            $missingStatementsCollection = new Base\Collection($missingStatements);

            $groupedMissingStatements = $missingStatementsCollection->groupBy('posted_date');

            $groupedStatementsBasedOnInsertion = [];

            $lastTransaction = $this->repo->banking_account_statement
                ->findLatestByAccountNumberAndChannel($accountNumber, $channel);

            $postedDateOfLastTransaction = isset($lastTransaction) === true ? $lastTransaction[Entity::POSTED_DATE] : null;

            foreach ($groupedMissingStatements as $postedDate => $groupOfStatements)
            {
                $insertionDetails = $this->getInsertionDetailsForMissingStatement(
                    $merchantId, $accountNumber, $channel, $groupOfStatements[0], $postedDateOfLastTransaction);

                $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_INSERTION_DETAILS, [
                    'account_number'    => $accountNumber,
                    'channel'           => $channel,
                    'posted_date'       => $postedDate,
                    'insertion_details' => $insertionDetails,
                    'dry_run_mode'      => $this->isDryRunModeActiveForStatementFix,
                ]);

                $encodedInsertionDetails = json_encode($insertionDetails);

                if (array_key_exists($encodedInsertionDetails, $groupedStatementsBasedOnInsertion) === true)
                {
                    $groupedStatementsBasedOnInsertion[$encodedInsertionDetails] =
                        array_merge($groupedStatementsBasedOnInsertion[$encodedInsertionDetails], $groupOfStatements->toArray());
                }
                else
                {
                    $groupedStatementsBasedOnInsertion[$encodedInsertionDetails] = $groupOfStatements->toArray();
                }
            }

            $insertedBasEntities    = [];
            $insertedStatements     = [];
            $insertedBasIds         = [];
            $insertedTransactionIds = [];

            foreach ($groupedStatementsBasedOnInsertion as $encodedInsertionDetails => $groupOfStatements)
            {
                $insertionDetails = json_decode($encodedInsertionDetails, true);

                $previousBasId         = $insertionDetails[Entity::ID];
                $previousTransactionId = $insertionDetails[Entity::TRANSACTION_ID];

                foreach ($groupOfStatements as $statement)
                {
                    $nextId = $this->generateNextUnUsedIdForEntity(
                        $previousBasId,
                        $insertedBasIds,
                        Constants\Entity::BANKING_ACCOUNT_STATEMENT,
                        true);

                    $basEntity = (new Entity)->build($statement);

                    $basEntity->setId($nextId);

                    if (empty($basEntity->getUtr()) === true)
                    {
                        $utr = $processor->getUtrForChannel($basEntity);

                        $basEntity->setUtr($utr);
                    }

                    $basEntity->merchant()->associate($merchant);

                    $missingStatementCreatedAt = max($insertionDetails[Entity::CREATED_AT], $statement[Entity::POSTED_DATE], $statement[Entity::TRANSACTION_DATE]);

                    $basEntity->setCreatedAt($missingStatementCreatedAt);

                    $basEntity->setUpdatedAt($missingStatementCreatedAt);

                    $balanceChange = $basEntity->getNetAmountBasedOnTransactionType();

                    $basEntity->setBalance($insertionDetails[Entity::BALANCE] + $balanceChange);

                    if ($this->isDryRunModeActiveForStatementFix === false)
                    {
                        $this->repo->saveOrFail($basEntity);
                    }

                    $insertedBasIds[] = $nextId;

                    $this->trace->info(TraceCode::BAS_INSERTED_ENTITY, [
                        'bank_txn_id'          => $statement[Entity::BANK_TRANSACTION_ID],
                        'bank_txn_posted_date' => $statement[Entity::POSTED_DATE],
                        'bank_txn_channel'     => $statement[Entity::CHANNEL],
                        'bas_id'               => $basEntity->getId(),
                        'account_no'           => $basEntity->getAccountNumber(),
                        'utr'                  => $basEntity->getUtr(),
                        'previous_bas_id'      => $insertionDetails[Entity::ID],
                        'dry_run_mode'         => $this->isDryRunModeActiveForStatementFix,
                        'inserted_bas_entity'  => $basEntity->toArray(),
                    ]);

                    $insertedBasEntities[] = $basEntity;

                    $insertedStatements[] = $statement;

                    $previousBasId = $nextId;

                    $generateTransactionId = $this->generateNextUnUsedIdForEntity(
                        $previousTransactionId,
                        $insertedTransactionIds,
                        Constants\Entity::TRANSACTION,
                        true);

                    $this->insertedBasTransactionDetails = [
                        Transaction\Entity::ID         => $generateTransactionId,
                        Transaction\Entity::CREATED_AT => max($insertionDetails['transaction_created_at'], $statement[Entity::POSTED_DATE])
                    ];

                    try
                    {
                        if ($this->isDryRunModeActiveForStatementFix === false)
                        {
                            // the balance gets updated when we link a statement to the source entity and
                            // as a result, the actual statement entities linking gets stopped
                            // that is our desired behaviour while fixing statement
                            $insertedBasEntity = new Base\PublicCollection([$basEntity]);

                            $this->saveAccountStatementV2($insertedBasEntity, $merchant);
                        }

                        $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_PREVIOUS_TRANSACTION_DETAILS, [
                            'account_number'                   => $accountNumber,
                            'channel'                          => $channel,
                            'previous_bas_transaction_details' => $this->insertedBasTransactionDetails,
                            'dry_run_mode'                     => $this->isDryRunModeActiveForStatementFix,
                        ]);
                    }
                    catch (\Exception $exception)
                    {
                        $this->trace->traceException(
                            $exception,
                            null,
                            TraceCode::BAS_MISSING_STATEMENT_LINKING_FAILED,
                            [
                                'bas_id' => $basEntity->getId(),
                                'utr'    => $basEntity->getUtr(),
                            ]
                        );

                        throw $exception;
                    }

                    $insertedTransactionIds[] = $generateTransactionId;

                    $previousTransactionId = $generateTransactionId;
                }
            }

            $missingStatements = $insertedStatements;

            return $insertedBasEntities;
        });
    }

    public function getInsertionDetailsForMissingStatement(
        string $merchantId,
        string $accountNumber,
        string $channel,
        array $statement,
        $postedDateOfLastTransaction = null)
    {
        $postedDate = $statement[Entity::POSTED_DATE];

        [$postedDateWindow, $findingPreviousBasIdCounter] = $this->findPostedDateWindowForFetchingPreviousBasId($postedDate, $postedDateOfLastTransaction);

        do
        {
            $previousPostedDate = $postedDate - $postedDateWindow;

            $previousBasId = $this->repo->banking_account_statement->fetchPreviousBasIdToInsertMissingRecord(
                $merchantId,
                $accountNumber,
                $channel,
                $postedDate,
                $previousPostedDate);

            if (empty($previousBasId) === false)
            {
                break;
            }

            $postedDate = $previousPostedDate;

            $findingPreviousBasIdCounter--;

        } while ($findingPreviousBasIdCounter > 0);

        if (empty($previousBasId) === false)
        {
            $previousBasEntity = $this->repo->banking_account_statement->findOrFail($previousBasId);

            return [
                Entity::ID               => $previousBasEntity->getId(),
                Entity::CREATED_AT       => $previousBasEntity->getCreatedAt(),
                Entity::UPDATED_AT       => $previousBasEntity->getUpdatedAt(),
                Entity::BALANCE          => $previousBasEntity->getBalance(),
                Entity::TRANSACTION_ID   => $previousBasEntity->getTransactionId(),
                'transaction_created_at' => $previousBasEntity->transaction->getCreatedAt(),
            ];
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'merchant_id'    => $merchantId,
                    'account_number' => $accountNumber,
                    'channel'        => $channel,
                ],
                'Cannot generate previous bas entity for posted_date: ' . $postedDate);
        }
    }

    public function findPostedDateWindowForFetchingPreviousBasId($postedDate, $postedDateOfLastTransaction)
    {
        $findingPreviousBasIdCounter = 3;

        $postedDateWindow = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH
            ]);

        if (empty($postedDateWindow) === true)
        {
            $postedDateWindow = self::POSTED_DATE_WINDOW_FOR_PREVIOUS_BAS_SEARCH;
        }

        $postedDateWindowStart = $postedDate - $postedDateWindow;

        if ($postedDateOfLastTransaction < $postedDateWindowStart)
        {
            $postedDateWindow = ($postedDate - $postedDateOfLastTransaction) + $postedDateWindow;

            $findingPreviousBasIdCounter = 1;
        }

        return [$postedDateWindow, $findingPreviousBasIdCounter];
    }

    protected function generateNextUnUsedIdForEntity(string $id, array $insertedIds, string $entityName, bool $optimised = false)
    {
        $attempts = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RETRY_COUNT_FOR_ID_GENERATION
            ]);

        if (empty($attempts) === true)
        {
            $attempts = self::RETRY_COUNT_FOR_ID_GENERATION;
        }

        $maxAttempts = $attempts;

        $generatedIds = [];

        do
        {
            $id = $this->generateNextId($id);

            $generatedIds[] = $id;

            switch ($entityName)
            {
                case Constants\Entity::BANKING_ACCOUNT_STATEMENT :
                    $idExists = ((array_key_exists($id, array_flip($insertedIds)) === true) or
                                 ($this->repo->banking_account_statement->checkIfIdExists($id) === true));
                    break;

                case Constants\Entity::TRANSACTION :
                    $idExists = ((array_key_exists($id, array_flip($insertedIds)) === true) or
                                 ($this->repo->transaction->checkIfIdExists($id) === true));
                    break;
            }

            $attempts -= 1;

            if ($attempts < 0)
            {
                $this->trace->error(TraceCode::ID_GENERATION_FAILURE_FOR_RECON, [
                    'generated_ids' => $generatedIds,
                    'max_attempts'  => $maxAttempts,
                    'entity_name'   => $entityName,
                ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    'cannot generate new Id for ' . $entityName);
            }

        } while ($idExists === true);

        $this->trace->info(TraceCode::BAS_INSERTION_MAX_ITERATION, [
            'max_attempts'              => $maxAttempts,
            'attempts_taken_to_find_id' => $maxAttempts - $attempts,
            'generated_ids'             => $generatedIds,
            'entity'                    => $entityName,
            'id_generated'              => $id,
            'optimised'                 => $optimised,
        ]);

        return $id;
    }

    protected function generateNextId(string $id)
    {
        $length = strlen($id);

        if ($length === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'cannot generate new Id');
        }

        $lastChar = $id[$length-1];

        switch (true)
        {
            case (ord($lastChar) < ord('9')) :
                return substr($id,0,$length-1).chr(ord($lastChar)+1);

            case (ord($lastChar) === ord('9')) :
                return substr($id,0,$length-1).'A' ;

            case (ord($lastChar) < ord('Z')) :
                return substr($id,0,$length-1).chr(ord($lastChar)+1);

            case (ord($lastChar) === ord('Z')) :
                return substr($id,0,$length-1).'a' ;

            case (ord($lastChar) < ord('z')) :
                return substr($id,0,$length-1).chr(ord($lastChar)+1);

            case (ord($lastChar) === ord('z')) :
                return $this->generateNextId(substr($id,0,$length-1)).'0';
        }
    }

    protected function modifyTransactionEntityForMissingStatement(Entity $basEntity, Base\PublicEntity $sourceEntity)
    {
        $transactionId = $this->insertedBasTransactionDetails[Transaction\Entity::ID];

        $previousTransactionCreatedAt = $this->insertedBasTransactionDetails[Transaction\Entity::CREATED_AT];

        $transaction = $sourceEntity->transaction;

        $transaction->setBalance($basEntity->getBalance(), 0, false);

        $transaction->setCreatedAt($previousTransactionCreatedAt);

        $transaction->setId($transactionId);

        $this->repo->saveOrFail($transaction);

        $sourceEntity->transaction()->associate($transaction);

        $this->repo->saveOrFail($sourceEntity);
    }

    public function correctBalanceForStatementsEffectedByMissingStatements(array $input)
    {
        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $isAutomatedCleanUp = $input[BASConstants::IS_AUTOMATED_CLEANUP] ?? false;

        [$hasMore, $params] = $this->mutex->acquireAndRelease(
            'banking_account_statement_fetch_' . $accountNumber . '_' . $channel,
            function () use ($channel, $accountNumber, $input, $isAutomatedCleanUp)
            {
                $merchantId = $input[Entity::MERCHANT_ID];

                $balanceId = $input[Details\Entity::BALANCE_ID];

                $limitNumberOfEntitiesToUpdate = (new Admin\Service)->getConfigKey(
                    [
                        'key' => Admin\ConfigKey::RX_LIMIT_STATEMENT_FIX_ENTITIES_UPDATE
                    ]);

                if (empty($limitNumberOfEntitiesToUpdate) === true)
                {
                    $limitNumberOfEntitiesToUpdate = self::ACCOUNT_STATEMENT_BAS_ENTITIES_TO_UPDATE;
                }

                $createdAt = $input[Entity::CREATED_AT];

                $updatedAt = $input['update_before'];

                $basIdToAmountMap = $input['bas_id_to_amount_map'];

                $latestCorrectedBasId = $input['latest_corrected_id'];

                $batchNumber = $input['batch_number'];

                $basEntities = $this->repo->banking_account_statement
                                          ->fetchBASRecordsToCorrect($limitNumberOfEntitiesToUpdate,
                                                                     $accountNumber,
                                                                     $createdAt,
                                                                     $updatedAt,
                                                                     $channel,
                                                                     $latestCorrectedBasId);

                $countOfRecordsToUpdate = count($basEntities);

                $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BALANCE_FIX_INIT, [
                    'bas_id_to_amount_map'       => $basIdToAmountMap,
                    'account_number'             => $accountNumber,
                    'limit'                      => $limitNumberOfEntitiesToUpdate,
                    'update_before'              => $updatedAt,
                    'created_at'                 => $createdAt,
                    'latest_corrected_id'        => $latestCorrectedBasId,
                    'batch_number'               => $batchNumber,
                    'count_of_records_to_update' => $countOfRecordsToUpdate,
                    'merchant_id'                => $merchantId,
                    'balance_id'                 => $balanceId,
                ]);

                if ($countOfRecordsToUpdate === 0)
                {
                    $this->trace->info(TraceCode::BAS_ENTITIES_BALANCE_UPDATE_COMPLETED,
                        [
                            'account_number'             => $accountNumber,
                            'update_before'              => $updatedAt,
                            'created_at'                 => $createdAt,
                            'latest_corrected_id'        => $latestCorrectedBasId,
                            'batch_number'               => $batchNumber,
                            'count_of_records_to_update' => $countOfRecordsToUpdate,
                            'merchant_id'                => $merchantId,
                            'balance_id'                 => $balanceId,
                        ]);

                    $this->updateBasDetailsEntityConsideringMissingStatements($accountNumber, $channel, $basIdToAmountMap, $isAutomatedCleanUp);

                    $lastBankTransaction = null;

                    try
                    {
                        if ($channel === Channel::RBL)
                        {
                            $lastBankTransaction = $this->repo->banking_account_statement
                                ->findLatestByAccountNumberAndChannel($accountNumber, $channel);

                            $transactionCount = $this->repo->banking_account_statement->fetchCountOfRecordsForAGivenDayWithPostedDateRange(
                                $accountNumber, $channel, $lastBankTransaction[Entity::TRANSACTION_DATE]);

                            $rblAccountStatementV2MaxNumberOfRecords = RblGateway::DEFAULT_RBL_ACCOUNT_STATEMENT_V2_MAX_NUMBER_OF_RECORDS;

                            $attemptLimit = (int) round($transactionCount / $rblAccountStatementV2MaxNumberOfRecords) + 1;

                            // Limiting fetch to 5 to reduce risk of worker timeout. Need to change this when bigger merchants onboard onto CA
                            $fetchInput = [
                                Entity::CHANNEL        => $channel,
                                Entity::ACCOUNT_NUMBER => $accountNumber,
                                Entity::FROM_DATE      => $lastBankTransaction[Entity::TRANSACTION_DATE],
                                Entity::TO_DATE        => $lastBankTransaction[Entity::TRANSACTION_DATE] + 86399,
                                Entity::SAVE_IN_REDIS  => false,
                                'attempt_limit'        => ($attemptLimit > 5) ? 1: $attemptLimit,
                            ];

                            $this->fetchAccountStatementWithRange($fetchInput, true, true);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST_FAILED,
                            [
                                'merchant_id' => optional($lastBankTransaction)->getMerchantId()
                            ]);
                    }

                    $this->releaseBasDetailsFromStatementFix($accountNumber, $channel);

                    return [false, []];
                }
                else
                {
                    $this->updateBasRecordsConsideringMissingStatements($basEntities, $basIdToAmountMap, $latestCorrectedBasId, $createdAt);

                    $this->trace->info(TraceCode::BAS_ENTITIES_BALANCE_UPDATE_SUCCESS,
                        [
                            'account_number'             => $accountNumber,
                            'update_before'              => $updatedAt,
                            'created_at'                 => $createdAt,
                            'latest_corrected_id'        => $latestCorrectedBasId,
                            'batch_number'               => $batchNumber,
                            'count_of_records_to_update' => $countOfRecordsToUpdate,
                            'merchant_id'                => $merchantId,
                            'balance_id'                 => $balanceId,
                        ]);

                    $params = [
                        'channel'              => $channel,
                        'account_number'       => $accountNumber,
                        'bas_id_to_amount_map' => $basIdToAmountMap,
                        'created_at'           => $createdAt,
                        'update_before'        => $updatedAt,
                        'latest_corrected_id'  => $latestCorrectedBasId,
                        'batch_number'         => $batchNumber + 1,
                        'merchant_id'          => $merchantId,
                        'balance_id'           => $balanceId,
                        'is_automated_cleanup' => $isAutomatedCleanUp,
                    ];

                    return [true, $params];
                }
            },
            1800,
            ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
        );

        if ($hasMore === true)
        {
            $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_INIT,
                [
                    'account_number' => $accountNumber,
                    'params'         => $params
                ]);

            BankingAccountStatementUpdate::dispatch($this->mode, $params)->delay(10);

            $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_SUCCESS,
                [
                    'account_number' => $accountNumber,
                    'params'         => $params
                ]);
        }
    }

    protected function updateBasRecordsConsideringMissingStatements(Base\PublicCollection $basEntities, array $basIdToAmountMap, string & $latestCorrectedBasId, int & $createdAt)
    {
        $this->repo->transaction(function() use ($basEntities, $basIdToAmountMap, & $latestCorrectedBasId, & $createdAt)
        {
            $jobName = app('worker.ctx')->getJobName() ?? '';

            /** @var Entity $basEntity */
            foreach ($basEntities as $basEntity)
            {
                $id = $basEntity->getId();

                $txn = (isset($basEntity->transaction) === false) ? null: $basEntity->transaction;

                $previousBalance = $basEntity->getBalance();

                $correctionAmount = 0;

                foreach ($basIdToAmountMap as $missingId => $missingAmount)
                {
                    if ($missingId < $id)
                    {
                        $correctionAmount += $missingAmount;
                    }
                }

                $correctBalance = $previousBalance + $correctionAmount;

                $this->repo->banking_account_statement->updateBasWithContextAsComment($id, $correctBalance, $jobName, $this->mode);

                $traceData = [
                    'bas_id'            => $id,
                    'merchant_id'       => $basEntity->getMerchantId(),
                    'previous_balance'  => $previousBalance,
                    'correct_balance'   => $correctBalance,
                    'correction_amount' => $correctionAmount,
                ];

                if ($txn !== null)
                {
                    $this->repo->transaction->updateTransactionWithContextAsComment($txn->getId(), $correctBalance, $jobName, $this->mode);

                    $traceData = $traceData + ['txn_id' => $txn->getId()];
                }

                $createdAt = max($createdAt, $basEntity->getCreatedAt());

                $latestCorrectedBasId = max($id, $latestCorrectedBasId);

                $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BALANCE_FIX, $traceData);
            }
        });
    }

    public function setBasDetailsForStatementFix(string $accountNumber, string $channel)
    {
        $basDetailEntity = $this->getBasDetails($accountNumber, $channel, BASDetails\Status::getStatuses());

        $this->mutex->acquireAndRelease('banking_account_statement_details_' . $basDetailEntity->getId(),
            function () use ($accountNumber, $channel, $basDetailEntity)
            {
                $basDetailEntity->reload();

                $this->trace->info(TraceCode::LOCK_BAS_DETAILS_FOR_STATEMENT_FIX_INIT,
                    [
                        'account_number'     => $accountNumber,
                        'channel'            => $channel,
                        'bas_details_status' => $basDetailEntity->getStatus(),
                    ]);

                if ($basDetailEntity->getStatus() === Details\Status::UNDER_MAINTENANCE)
                {
                     return;
                }

                if ($basDetailEntity->getStatus() !== Details\Status::ACTIVE)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_LOCK_BAS_DETAILS_FOR_STATEMENT_FIX_FAILURE);
                }

                $basDetailEntity->setStatus(Details\Status::UNDER_MAINTENANCE);

                $this->repo->saveOrFail($basDetailEntity);

                $this->trace->info(TraceCode::LOCK_BAS_DETAILS_FOR_STATEMENT_FIX_SUCCESS,
                    [
                        'account_number'     => $accountNumber,
                        'channel'            => $channel,
                        'bas_details_status' => $basDetailEntity->getStatus(),
                    ]);
            },
            30,
            ErrorCode::BAD_REQUEST_ANOTHER_BAS_DETAILS_UPDATE_IN_PROGRESS,
            2
        ); // Retries added as async balance update might have taken mutex, which would result in failure.
    }

    public function releaseBasDetailsFromStatementFix(string $accountNumber, string $channel)
    {
        $basDetailEntity = $this->getBasDetails($accountNumber, $channel, BASDetails\Status::getStatuses());

        $this->mutex->acquireAndRelease('banking_account_statement_details_' . $basDetailEntity->getId(),
            function () use ($accountNumber, $channel, $basDetailEntity)
            {
                $basDetailEntity->reload();

                $this->trace->info(TraceCode::RELEASE_BAS_DETAILS_FROM_STATEMENT_FIX_INIT,
                    [
                        'account_number'     => $accountNumber,
                        'channel'            => $channel,
                        'bas_details_status' => $basDetailEntity->getStatus(),
                    ]);

                if ($basDetailEntity->getStatus() !== Details\Status::UNDER_MAINTENANCE)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RELEASE_BAS_DETAILS_FROM_STATEMENT_FIX_FAILURE, null, null);
                }

                $basDetailEntity->setStatus(Details\Status::ACTIVE);

                $this->repo->saveOrFail($basDetailEntity);

                $this->trace->info(TraceCode::RELEASE_BAS_DETAILS_FROM_STATEMENT_FIX_SUCCESS,
                    [
                        'account_number'     => $accountNumber,
                        'channel'            => $channel,
                        'bas_details_status' => $basDetailEntity->getStatus(),
                    ]);
            },
            30,
            ErrorCode::BAD_REQUEST_ANOTHER_BAS_DETAILS_UPDATE_IN_PROGRESS,
            2
        ); // Retries added as async balance update might have taken mutex, which would result in failure.
    }

    protected function updateBasDetailsEntityConsideringMissingStatements(
        string $accountNumber, string $channel, array $basIdToAmountMap, $isAutomatedCleanUp)
    {
        $basDetailEntity = $this->getBasDetails($accountNumber, $channel, [Details\Status::UNDER_MAINTENANCE]);

        $this->mutex->acquireAndRelease('banking_account_statement_details_' . $basDetailEntity->getId(),
            function () use ($accountNumber, $channel, $basIdToAmountMap, $basDetailEntity, $isAutomatedCleanUp)
            {
                $basDetailEntity->reload();

                $netBalanceChange = 0;

                foreach ($basIdToAmountMap as $missingId => $missingAmount)
                {
                    $netBalanceChange += $missingAmount;
                }

                $initialStatementClosingBalance = $basDetailEntity->getStatementClosingBalance();

                $basDetailEntity->setStatementClosingBalance($initialStatementClosingBalance + $netBalanceChange);

                if ($basDetailEntity->getChannel() === BASDetails\Channel::RBL)
                {
                    $basDetailEntity->setPaginationKey(null);
                }

                if ($isAutomatedCleanUp === true)
                {
                    $lastReconciledAt = Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp();

                    $presentLastReconciledAt = $basDetailEntity->getLastReconciledAt();

                    if ((isset($presentLastReconciledAt) === false) or
                        ($presentLastReconciledAt < $lastReconciledAt))
                    {
                        $basDetailEntity->setLastReconciledAt($lastReconciledAt);
                    }
                }

                $this->repo->saveOrFail($basDetailEntity);
            },
            30,
            ErrorCode::BAD_REQUEST_ANOTHER_BAS_DETAILS_UPDATE_IN_PROGRESS,
            2
        ); // Retries added as async balance update might have taken mutex, which would result in failure.
    }

    public function getMissingRecordsFromRedisForAccount(string $accountNumber, string $channel, string $merchantId)
    {
        $redisKey = sprintf(self::MISSING_STATEMENTS_REDIS_KEY, $merchantId, $accountNumber);

        $getMissingStatements = $this->app['redis']->get($redisKey);

        $merchantMissingStatementList = (isset($getMissingStatements) === true) ?
            json_decode($getMissingStatements, true) : null;

        if (empty($merchantMissingStatementList) === false)
        {
            $missingStatements = $merchantMissingStatementList;

            $this->trace->info(TraceCode::BAS_MISSING_RECORDS_TO_INSERT, [
                'merchant_id'        => $merchantId,
                'account_number'     => $accountNumber,
                'channel'            => $channel,
                'count'              => count($missingStatements),
                'missing_statements' => $missingStatements,
            ]);
        }
        else
        {
            $this->trace->info(TraceCode::BAS_NO_MISSING_RECORDS_TO_INSERT, [
                'merchant_id'    => $merchantId,
                'account_number' => $accountNumber,
                'channel'        => $channel,
            ]);

            $missingStatements = [];
        }

        return $missingStatements;
    }

    public function removeInsertedMissingRecordsForAccountFromRedis(
        string $accountNumber,
        string $channel,
        string $merchantId,
        array $insertedStatements)
    {
        $retryCount = 2;

        try
        {
            $this->mutex->acquireAndRelease('update_redis_missing_statements_recon_' . $accountNumber . '_' . $merchantId,
                function() use ($accountNumber, $channel, $merchantId, $insertedStatements) {

                    $startTime = microtime(true);

                    $redis = $this->app['redis'];

                    $redisKey = sprintf(self::MISSING_STATEMENTS_REDIS_KEY, $merchantId, $accountNumber);

                    $getMissingStatements = $redis->get($redisKey);

                    $merchantMissingStatementList = (isset($getMissingStatements) === true) ?
                        json_decode($getMissingStatements, true) : null;

                    if (empty($merchantMissingStatementList) === false)
                    {
                        $this->trace->info(TraceCode::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_INIT, [
                            'merchant_id'                => $merchantId,
                            'account_number'             => $accountNumber,
                            'inserted_statements'        => $insertedStatements,
                            'missing_statement_in_redis' => $merchantMissingStatementList
                        ]);

                        // array_diff did not work as expected for nested arrays, so statements are converted to json and then compared
                        $diff = array_values(array_diff(
                                                 array_map('json_encode', $merchantMissingStatementList),
                                                 array_map('json_encode', $insertedStatements)
                                             ));

                        $missingStatementsAfterInsertion = array_map('json_decode', $diff, array_map('boolval', $diff));

                        $redis->set($redisKey, json_encode($missingStatementsAfterInsertion));

                        $endTime = microtime(true);

                        $this->trace->info(TraceCode::REMOVAL_OF_INSERTED_STATEMENTS_FROM_REDIS_SUCCESS, [
                            'merchant_id'                        => $merchantId,
                            'account_number'                     => $accountNumber,
                            'missing_statements_after_insertion' => count($missingStatementsAfterInsertion),
                            'total_time_taken'                   => $endTime - $startTime,
                        ]);
                    }
                    else
                    {
                        $this->trace->info(TraceCode::BAS_MISSING_RECORDS_EXTERNALLY_DELETED, [
                            'merchant_id'         => $merchantId,
                            'account_number'      => $accountNumber,
                            'channel'             => $channel,
                            'inserted_statements' => $insertedStatements
                        ]);
                    }
                },
                300,
                ErrorCode::BAD_REQUEST_CANNOT_EDIT_MISSING_RECORDS_ON_REDIS,
                $retryCount
            );
        }
        catch (\Exception $exception)
        {
            $this->trace->count(Metric::MISSING_STATEMENT_REDIS_INSERT_FAILURES, [
                Metric::LABEL_CHANNEL => $channel,
                'action'              => 'updation'
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

    protected function pushMissingStatementsLinkingEventsToLedger(string $accountNumber, string $channel, array $insertedBasEntities)
    {
        if ($this->isDryRunModeActiveForStatementFix === true)
        {
            return;
        }

        $basDetailEntity = $this->getBasDetails($accountNumber, $channel, [BASDetails\Status::UNDER_MAINTENANCE]);

        $merchant = $basDetailEntity->merchant;

        foreach ($insertedBasEntities as $basEntity)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_LINKED_BAS_FOR_LEDGER,
                               [
                                   'source_entity'          => $basEntity->source,
                                   'bas_id'                 => $basEntity->getId(),
                                   'account_number'         => $basEntity->getAccountNumber(),
                                   'entity_id'              => $basEntity->source->getId(),
                                   'entity_type'            => $basEntity->getEntityType(),
                               ]);

            $sourceEntity = $basEntity->source;

            try
            {
                $this->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity);
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::BAS_LINKING_LEDGER_CALL_FAILURE,
                    [
                        'account_number' => $accountNumber,
                        'channel'        => $channel,
                        'bas_details_id' => $basDetailEntity->getId(),
                        'bas_id'         => $basEntity->getId(),
                        'source_id'      => $sourceEntity->getId(),
                    ]
                );
            }
        }
    }

    protected function dispatchJobForStatementProcessing(array $params, $retryCount = 0): void
    {
        $delay = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY]);

        if (empty($delay) == true)
        {
            $delay = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY;
        }

        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_DISPATCH_JOB_REQUEST,
                $params + ['delay' => $delay]);

            unset($params['attempt_number']);

            BankingAccountStatementProcessor::dispatch($this->mode, $params)->delay($delay);

        }
        catch (\Throwable $e)
        {
            if ($retryCount < self::MAX_RETRY_COUNT_FOR_PROCESSING_ACCOUNT_STATEMENT)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_DISPATCH_JOB_RETRY,
                    $params +
                    [
                        'delay'          => $delay,
                        'retry_count'    => $retryCount+1,
                    ]);

                $this->dispatchJobForStatementProcessing($params, $retryCount+1);

                return;
            }

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_BANKING_ACCOUNT_STATEMENT_PROCESSING_JOB);

            $this->trace->count(Metric::BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL);
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

        $basDetails = $this->getBasDetails($accountNumber, $channel, Details\Status::getStatusesForProcessing());

        $merchant = $basDetails->merchant;

        $this->isBalanceValidationRequired($basDetails->getMerchantId());

        try
        {
            $this->mutex->acquireAndRelease(
                'banking_account_statement_process_' . $accountNumber . '_' . $channel,
                function () use ($channel, $accountNumber, $input, $limit, & $saveLimit, $merchant, $basDetails)
                {
                    $this->setCreditBeforeDebitUtrsFromRedis($accountNumber);

                    while ($saveLimit > 0)
                    {
                        list($fetchUsingDefaultQuery, $basEntities) = $this->fetchUnlinkedRecordsUsingOptimizedQueryIfApplicable($accountNumber, $basDetails, $channel, $limit);

                        if ($fetchUsingDefaultQuery === true)
                        {
                            // If merchant is on Ledger reverse shadow, fetch unlikned BAS for processing by null source instead of null transaction
                            $basEntities = $this->repo->banking_account_statement->fetchUnlinkedBasRecords($accountNumber, $channel, $limit);
                        }

                        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ROWS_FETCHED, [
                            'count'             => count($basEntities),
                            'account_number'    => $accountNumber,
                            Entity::MERCHANT_ID => $merchant->getId(),
                        ]);

                        if (count($basEntities) == 0)
                            break;

                        $startTime = microtime(true);

                        $this->saveAccountStatementV2($basEntities, $merchant);

                        $endTime = microtime(true);

                        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BULK_LINKING_TIME, [
                            'account_number'       => $accountNumber,
                            Entity::MERCHANT_ID    => $merchant->getId(),
                            'time_to_link_records' => $endTime - $startTime,
                        ]);

                        $saveLimit--;
                    }
                },
                1800,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
            );

            if ($saveLimit == 0)
            {
                $this->dispatchJobForStatementProcessing([
                    BASDetails\Entity::ACCOUNT_NUMBER => $basDetails->getAccountNumber(),
                    BASDetails\Entity::CHANNEL        => $basDetails->getChannel(),
                    BASDetails\Entity::BALANCE_ID     => $basDetails->getBalanceId()
                ]);
            }
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
                        Entity::MERCHANT_ID => $merchant->getId(),
                        'message'           => $e->getMessage(),
                    ]);
            }
            else
            {
                throw $e;
            }
        }
    }

    protected int $isBalanceValidationRequired = 0;
    protected function isBalanceValidationRequired(string $merchantId): bool
    {
        if ($this->isBalanceValidationRequired == 0)
        {
            if (!$this->shouldValidateBalanceForStatements($merchantId))
            {
                $this->isBalanceValidationRequired = 2;
            }
            else
            {
                $this->isBalanceValidationRequired = 1;
            }
        }

        if ($this->isBalanceValidationRequired == 2)
        {
            return false;
        }

        return true;
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
            $accountNumber,
            $merchant);

        $lastBankTxn = $this->repo->banking_account_statement->findLatestByAccountNumber($accountNumber);

        $previousClosingBalance = $lastBankTxn == null ? 0 : $lastBankTxn->getBalance();

        $this->checkAndUpdateBalanceForExistingAccounts($lastBankTxn, $bankTransactions, $previousClosingBalance, $merchant);

        $basEntitiesToSave = [];
        $totalRecordCount = 0;
        $initialOffset = 0;

        $validateBalance = $this->isBalanceValidationRequired($basDetails->getMerchantId());

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
                                   'bank_txn_id'                  => $bankTransaction[Entity::BANK_TRANSACTION_ID],
                                   'bank_txn_posted_date'         => $bankTransaction[Entity::POSTED_DATE],
                                   'bank_txn_channel'             => $bankTransaction[Entity::CHANNEL],
                                   'bas_id'                       => $basEntity->getId(),
                                   'account_no'                   => $basEntity->getAccountNumber(),
                                   BASDetails\Entity::MERCHANT_ID => $basDetails->getMerchantId(),
                                   BASDetails\Entity::BALANCE_ID  => $basDetails->getBalanceId(),
                                   'utr'                          => $basEntity->getUtr(),
                               ]);

            $basEntity->merchant()->associate($merchant);

            if ($this->validateRecordBalance($previousClosingBalance, $basEntity) == false)
            {
                $this->trace->count(Metric::STATEMENT_BALANCES_DO_NOT_MATCH, [
                    Metric::LABEL_CHANNEL => $channel,
                    'is_processing'       => false,
                ]);

                if ($validateBalance)
                {
                    throw new Exception\LogicException('Statement record balance is not in correct order',
                        ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                        [
                            'account_number'    => $accountNumber,
                            'merchant_id'       => $merchant->getId(),
                            'channel'           => $channel,
                            'row_balance'       => $basEntity->getBalance(),
                            'previous_balance'  => $previousClosingBalance,
                            'bas_amount'        => $basEntity->getAmount(),
                            'bas_type'          => $basEntity->getType(),
                        ]);
                }
                else
                {
                    $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                        [
                            'account_number'    => $accountNumber,
                            'merchant_id'       => $merchant->getId(),
                            'channel'           => $channel,
                            'row_balance'       => $basEntity->getBalance(),
                            'previous_balance'  => $previousClosingBalance,
                            'bas_amount'        => $basEntity->getAmount(),
                            'bas_type'          => $basEntity->getType(),
                        ]);
                }
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
                                       'account_number'               => $accountNumber,
                                       'time_to_save_records'         => $endTime - $startTime,
                                       BASDetails\Entity::MERCHANT_ID => $basDetails->getMerchantId(),
                                       BASDetails\Entity::BALANCE_ID  => $basDetails->getBalanceId(),
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
                    BASDetails\Entity::MERCHANT_ID    => $basDetails->getMerchantId(),
                    BASDetails\Entity::BALANCE_ID     => $basDetails->getBalanceId(),
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

    protected function shouldValidateBalanceForStatements(string $merchantId): bool
    {
        try {
            $merchantIds = (new AdminService)->getConfigKey(
                ['key' => ConfigKey::ACCOUNT_STATEMENT_SKIP_VALIDATE_BALANCE]);
        }
        catch (\Throwable $exception)
        {
            $merchantIds = [];
        }


        if ((in_array('*', $merchantIds, true) === true) or
            (in_array($merchantId, $merchantIds, true) === true))
        {
            return false;
        }

        return true;
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

        $object = new UploadedFile($path, $originalName, $mimeType, $error, $test);

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
        $payoutServiceTxnVariant = $this->app->razorx->getTreatment($merchant->getId(),
                                                    Merchant\RazorxTreatment::PAYOUT_SERVICE_TXN_RECON,
                                                    $this->mode);

        /** @var Entity $basEntity */
        foreach ($basEntities as $basEntity)
        {
            if ($basEntity->getId() == $basEntity->getTransactionId())
            {
                continue;
            }

            $basEntity->setConnection($this->mode);

            try
            {
                list($sourceEntity, $isSourceAlreadyCreated) = $this->linkAccountStatementRecord($basEntity, $merchant);

                // trigger mail only if account type is of direct , transaction is created, payout is in processed state
                if (($sourceEntity->getEntityName() === Constants\Entity::PAYOUT) and
                    ($sourceEntity->isBalanceAccountTypeDirect() === true) and
                    ($sourceEntity->isOfMerchantTransaction() === true) and
                    ($sourceEntity->getStatus() === Status::PROCESSED) and
                    !($payoutServiceTxnVariant == 'on' and
                      $sourceEntity->getIsPayoutService() == true and
                      $basEntity->getType() == Type::DEBIT))
                {
                    $event = "payout.processed";
                    (new Transaction\Notifier($sourceEntity->transaction, $event))->notify();
                }

                // for statement under fix we send event to ledger after inserting all the missing statements outside the transaction
                // For payout service payout linked with debit, we dont send event to ledger since we make a call to ps which already does this
                if ($this->isStatementUnderFix === false and
                    !($payoutServiceTxnVariant == 'on' and
                      $sourceEntity->getEntityName() === Constants\Entity::PAYOUT and
                      $sourceEntity->getIsPayoutService() == true and
                      $basEntity->getType() == Type::DEBIT)
                )
                {
                    // send event to ledger in shadow mode
                    $this->sendToLedgerPostSourceEntityProcessing($merchant, $sourceEntity, $basEntity);
                }
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
                        'merchant_id'    => $basEntity->getMerchantId(),
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
                if (!($payoutServiceTxnVariant == 'on' and
                      $sourceEntity->getEntityName() === Constants\Entity::PAYOUT and
                      $sourceEntity->getIsPayoutService() == true and
                      $basEntity->getType() == Type::DEBIT)
                )
                {
                    $this->fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated);
                }
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
                                   'merchant_id'         => $basEntity->getMerchantId(),
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
                    'merchant_id'    => $basEntity->getMerchantId(),
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
                        'merchant_id'    => $basEntity->getMerchantId(),
                        'credit_bas_id'  => $creditBas->getId(),
                        'payout_id'      => $payout->getId(),
                        'payout_status'  => $payout->getStatus()
                    ]
                ]);

            if ($payout->getStatus() !== Status::FAILED)
            {
                return;
            }

            $oldStatus = $payout->getStatus();

            $payoutCore = new Payout\Core;

            $payoutCore->handlePayoutReversed($payout,
                                                    null,
                                                    null,
                                                    $creditBas);

            $payoutCore->processTdsForPayout($payout, $oldStatus);

            if (($key = array_search($basEntity->getUtr(), $this->creditBeforeDebitUtrs)) !== false)
            {
                unset($this->creditBeforeDebitUtrs[$key]);

                $this->updateCreditBeforeDebitUtrsInRedis($basEntity->getAccountNumber());
            }
        }
    }

    protected function fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated)
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

        $this->trace->info(TraceCode::BAS_ENTRY_SOURCE_MAPPING_DETAILS, [
            'source_id'   => $sourceEntity->getPublicId(),
            'source_type' => $sourceEntity->getEntityName(),
            'bas_id'      => $basEntity->getId(),
            'account_no'  => $basEntity->getAccountNumber(),
            'merchant_id' => $basEntity->getMerchantId(),
            'remarks'     => $remarks
        ]);

        if ($this->isStatementUnderFix === true)
        {
            $this->modifyTransactionEntityForMissingStatement($basEntity, $sourceEntity);
        }

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

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_REVERSAL, [
            'bas_id'         => $basEntity->getId(),
            'account_number' => $basEntity->getAccountNumber(),
            'merchant_id'    => $basEntity->getMerchantId(),
            'reversal'       => $reversal,
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
                                   'payout_id'      => $existingPayout->getId(),
                                   'bas_id'         => $basEntity->getId(),
                                   'account_number' => $basEntity->getAccountNumber(),
                                   'merchant_id'    => $basEntity->getMerchantId(),
                               ]);

            $reverseReason = $existingPayout->getFailureReason() ?? 'REVERSAL';

            (new Payout\Core)->reversePayout($existingPayout, $reverseReason, 'FAILURE');

            (new PayoutsStatusDetailsCore())->create($existingPayout);

            $reversal = $existingPayout->reversal;

            $this->trace->info(TraceCode::AUTO_RECON_PAYOUT_REVERSAL_CREATED, [
                'payout_id'      => $existingPayout->getId(),
                'reversal_id'    => $reversal->getId(),
                'bas_id'         => $basEntity->getId(),
                'account_number' => $basEntity->getAccountNumber(),
                'merchant_id'    => $basEntity->getMerchantId(),
            ]);

            $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);
            $this->trace->info(TraceCode::REVERSAL_TRANSACTION_CREATED, [
                'reversal_id'    => $reversal->getId(),
                'transaction_id' => $reversal->transaction->getId(),
                'bas_id'         => $basEntity->getId(),
                'account_number' => $basEntity->getAccountNumber(),
                'merchant_id'    => $basEntity->getMerchantId(),
            ]);
        }

        if (($isReversalAlreadyCreated === true) and
            ($reversal !== null) and
            ($reversal->transaction === null))
        {
            $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);
            $this->trace->info(TraceCode::REVERSAL_TRANSACTION_CREATED, [
                'reversal_id'    => $reversal->getId(),
                'transaction_id' => $reversal->transaction->getId(),
                'bas_id'         => $basEntity->getId(),
                'account_number' => $basEntity->getAccountNumber(),
                'merchant_id'    => $basEntity->getMerchantId(),
            ]);
        }

        return [$reversal, $isReversalAlreadyCreated];
    }

    protected function processPayout(Entity $basEntity, & $remarks)
    {
        $createExternalSource = false;

        /** @var Payout\Entity $payout */
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
                    'bas_id'      => $basEntity->getId(),
                    'payout_id'   => $payout->getId(),
                    'merchant_id' => $basEntity->getMerchantId(),
                ]);

            Tracer::startSpanWithAttributes(Constants\HyperTrace::BAS_ENTRY_FOR_A_FAILED_PAYOUT,
                                            [
                                                'bas_id'    => $basEntity->getId(),
                                                'payout_id' => $payout->getId()
                                            ]);

            return null;
        }

        (new DownstreamProcessor('fund_account_payout', $payout, $this->mode))->processTransaction();
        $transactionId = $payout->transaction ? $payout->transaction->getID() : null;

        $payoutServiceTxnVariant = $this->app->razorx->getTreatment($payout->getMerchantId(),
                                                    Merchant\RazorxTreatment::PAYOUT_SERVICE_TXN_RECON,
                                                    $this->mode);

        if ($payoutServiceTxnVariant == 'on' and $payout->getIsPayoutService() == true)
        {
            $input = [
                Entity::BAS_ID                  => $transactionId,
                Entity::ENTITY_ID               => $payout->getId(),
                Entity::ENTITY_TYPE             => Constants\Entity::PAYOUT,
                Entity::MERCHANT_ID             => $payout->getMerchantId(),
                Entity::TRANSACTION_DATE        => $basEntity->getTransactionDate(),
                Entity::CONVERTED_FROM_EXTERNAL => $basEntity->getEntityType() == Constants\Entity::EXTERNAL
            ];

            try
            {
                $this->payoutServiceBankingAccountStatementClient->updatePayoutAfterBASRecon($input);

                $payout->setTransactionId(null);

                $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_PS_PAYOUT_TRANSACTION, [
                    'bas_id'                  => $basEntity->getId(),
                    'merchant_id'             => $basEntity->getMerchantId(),
                    'payout'                  => $payout,
                    'transaction_id'          => $transactionId,
                    'api_payout_txn_id'       => $payout->getTransactionId(),
                    'converted_from_external' => $input[Entity::CONVERTED_FROM_EXTERNAL]
                ]);

                return $payout;
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_PAYOUT_TRANSACTION_ON_PS_FAILURE, [
                    'input' => $input,
                    'error_message' => $e->getMessage(),
                    'error' => $e
                ]);
            }
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_PAYOUT_TRANSACTION, [
            'bas_id'         => $basEntity->getId(),
            'account_number' => $basEntity->getAccountNumber(),
            'merchant_id'    => $basEntity->getMerchantId(),
            'payout'         => $payout,
            'transaction_id' => $transactionId
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
    protected function fetchExistingReversalIfPresent(Entity $basEntity,
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

        $this->trace->info(TraceCode::BAS_REVERSALS_FETCHED_VIA_UTR, [
            'utr'                                    => $utr,
            'reversal_ids'                           => $reversals->getQueueableIds(),
            'bas_id'                                 => $basEntity->getId(),
            'account_number'                         => $basEntity->getAccountNumber(),
            'merchant_id'                            => $basEntity->getMerchantId(),
            'reversals_fetched_via_utr_mapping_time' => (microtime(true) - $startTime) * 1000
        ]);

        $countOfLinkedReversals = 0;

        foreach ($reversals as $key => $reversal)
        {
            if ($reversal->getTransactionId() !== null)
            {
                $countOfLinkedReversals += 1;

                $data = [
                    'channel'                    => $basEntity->getChannel(),
                    'amount'                     => $basEntity->getAmount(),
                    'merchant_id'                => $basEntity->getMerchantId(),
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

        if (($countOfLinkedReversals >= 1) and
            (count($unlinkedReversals) === 0))
        {
            $createExternalSource = true;
            $remarks              = 'multiple linked reversals with same utr with no unlinked reversals present ' . $utr;

            $data = [
                'channel'      => $basEntity->getChannel(),
                'amount'       => $basEntity->getAmount(),
                'merchant_id'  => $basEntity->getMerchantId(),
                'reversal_ids' => $reversals->getQueueableIds(),
                'utr'          => $utr
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_DUPLICATE_REVERSAL_FOUND_FOR_SAME_UTR, [
                'data' => $data,
            ]);

            return null;
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
                'merchant_id'  => $basEntity->getMerchantId(),
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

    public function fetchExistingPayoutForAccountStatementForMappingCredits(Entity $basEntity,
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
                                   'utr'                                  => $utr,
                                   'payout_ids'                           => $payouts->getQueueableIds(),
                                   'bas_id'                               => $basEntity->getId(),
                                   'account_no'                           => $basEntity->getAccountNumber(),
                                   'merchant_id'                          => $basEntity->getMerchantId(),
                                   'payouts_fetched_via_utr_mapping_time' => (microtime(true) - $startTime) * 1000
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
                            'channel'     => $basEntity->getChannel(),
                            'amount'      => $basEntity->getAmount(),
                            'merchant_id' => $basEntity->getMerchantId(),
                            'payout_ids'  => $payouts->getQueueableIds(),
                            'utr'         => $utr
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

            [$shouldFetchUsingGatewayRefNumber, $gatewayRefNo] = $this->getGatewayRefNumberAvailabilityAndValue($description, $basEntity->getChannel());

            if ($shouldFetchUsingGatewayRefNumber === true)
            {
                // this will be used to send slack notifications if required.
                $identifier = 'gateway ref no';

                $txnDateTime = null;

                $txnDateTimeBefore = null;

                $mode = null;

                $isGatewayRefNoCaseSensitive = false;

                if ($basEntity->getChannel() === Channel::RBL)
                {
                    // for rbl, gateway ref number is present only for IFT mode and we need to check for payouts within the last 4 hours of BAS posted date
                    $txnDateTime = $basEntity->getPostedDate();
                    $txnDateTimeBefore = $bankTimeBeforePostedDate;
                    $mode = Payout\Mode::IFT;
                    $isGatewayRefNoCaseSensitive = true;
                }

                // we are checking both linked and unlinked payouts because debit row might have already been
                // processed.
                $payouts = $this->repo->payout->fetchPayoutsFromGatewayRefNumber(
                    $gatewayRefNo,
                    $txnDateTime,
                    $txnDateTimeBefore,
                    $basEntity->getAmount(),
                    $balance->getId(),
                    $mode,
                    $isGatewayRefNoCaseSensitive
                );

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_GATEWAY_REF_NUMBER_FOR_CREDIT,
                    [
                        'cms_ref_no' => $bankTxnId,
                        'channel' => $basEntity->getChannel(),
                        'gateway_ref_no' => $gatewayRefNo,
                        'payout_ids' => $payouts->getQueueableIds(),
                        'bas_id' => $basEntity->getId(),
                        'merchant_id' => $basEntity->getMerchantId(),
                        'payouts_fetched_via_gateway_ref_number_for_credit_mapping_time' => (microtime(true) - $startTime) * 1000
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

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_IFT_FOR_CREDIT_MAPPING, [
                    'cms_ref_no'                                          => $bankTxnId,
                    'payout_ids'                                          => $payouts->getQueueableIds(),
                    'bas_id'                                              => $basEntity->getId(),
                    'merchant_id'                                         => $basEntity->getMerchantId(),
                    'account_number'                                      => $basEntity->getAccountNumber(),
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
                'channel'     => $basEntity->getChannel(),
                'amount'      => $basEntity->getAmount(),
                'merchant_id' => $basEntity->getMerchantId(),
                'payout_ids'  => $payouts->getQueueableIds(),
                'cms_ref_no'  => $bankTxnId
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

        return null;

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

            $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_UTR_FOR_DEBIT_MAPPING, [
                'utr'                                  => $utr,
                'payout_ids'                           => $payouts->getQueueableIds(),
                'bas_id'                               => $basEntity->getId(),
                'account_number'                       => $basEntity->getAccountNumber(),
                'merchant_id'                          => $basEntity->getMerchantId(),
                'payouts_fetched_via_utr_mapping_time' => (microtime(true) - $startTime) * 1000
            ]);

            foreach ($payouts as $key => $payout)
            {
                if ($payout->getTransactionId() !== null)
                {
                    $data = [
                        'channel'                  => $basEntity->getChannel(),
                        'amount'                   => $basEntity->getAmount(),
                        'merchant_id'              => $basEntity->getMerchantId(),
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
                    'channel'     => $basEntity->getChannel(),
                    'amount'      => $basEntity->getAmount(),
                    'merchant_id' => $basEntity->getMerchantId(),
                    'payout_ids'  => $payouts->getQueueableIds(),
                    'utr'         => $utr
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

            [$shouldFetchUsingGatewayRefNumber, $gatewayRefNo] = $this->getGatewayRefNumberAvailabilityAndValue($description, $basEntity->getChannel());

            if ($shouldFetchUsingGatewayRefNumber === true)
            {
                // this will be used to send slack notifications if required.
                $identifier = 'gateway ref no';

                $txnDateTime = null;

                $txnDateTimeBefore = null;

                $isGatewayRefNoCaseSensitive = false;

                $mode = null;

                if ($basEntity->getChannel() === Channel::RBL)
                {
                    // for rbl, gateway ref number is present only for IFT mode and we need to check for payouts within the last 4 hours of BAS posted date
                    $txnDateTime = $basEntity->getPostedDate();
                    $txnDateTimeBefore = $bankTimeBeforePostedDate;
                    $mode = Payout\Mode::IFT;
                    $isGatewayRefNoCaseSensitive = true;
                }

                // fetch only unlinked payouts i.e which do not have txn_id, since we are trying to map given bas
                // record with payout.
                $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromGatewayRefNumber(
                    $gatewayRefNo,
                    $txnDateTime,
                    $txnDateTimeBefore,
                    $basEntity->getAmount(),
                    $balance->getId(),
                    $mode,
                    $isGatewayRefNoCaseSensitive
                );

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_GATEWAY_REF_NUMBER_FOR_DEBIT,
                    [
                        'cms_ref_no' => $bankTxnId,
                        'channel' => $basEntity->getChannel(),
                        'gateway_ref_no' => $gatewayRefNo,
                        'payout_ids' => $payouts->getQueueableIds(),
                        'bas_id' => $basEntity->getId(),
                        'merchant_id' => $basEntity->getMerchantId(),
                        'payouts_fetched_via_gateway_ref_number_for_debit_mapping_time' => (microtime(true) - $startTime) * 1000
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

                $this->trace->info(TraceCode::BAS_PAYOUTS_FETCHED_VIA_CMS_REF_NO_FOR_IFT_FOR_DEBIT_MAPPING, [
                    'cms_ref_no'                                          => $bankTxnId,
                    'payout_ids'                                          => $payouts->getQueueableIds(),
                    'bas_id'                                              => $basEntity->getId(),
                    'account_number'                                      => $basEntity->getAccountNumber(),
                    'merchant_id'                                         => $basEntity->getMerchantId(),
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
                'channel'     => $basEntity->getChannel(),
                'amount'      => $basEntity->getAmount(),
                'merchant_id' => $basEntity->getMerchantId(),
                'payout_ids'  => $payouts->getQueueableIds(),
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

        return null;
    }

    protected function validateBalance(Entity $basEntity, Base\PublicEntity $sourceEntity)
    {
        $balanceCalculated = $sourceEntity->transaction->getBalance();

        $balanceAtBankSide = $basEntity->getBalance();

        if ($balanceCalculated !== $balanceAtBankSide)
        {
            $this->trace->count(Metric::STATEMENT_BALANCES_DO_NOT_MATCH, [
                Metric::LABEL_CHANNEL => $basEntity->getChannel(),
                'is_processing'       => true,
            ]);

            if ($this->isBalanceValidationRequired($basEntity->getMerchantId()))
            {
                throw new Exception\LogicException(
                    'Balance at channel does not match with our balance',
                    ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                    [
                        'rzp_balance'     => $balanceCalculated,
                        'channel_balance' => $balanceAtBankSide,
                        'merchant_id'     => $basEntity->getMerchantId(),
                        'account_number'  => $basEntity->getAccountNumber(),
                        'bank_txn_id'     => $basEntity->getBankTransactionId(),
                    ]
                );
            }
            else
            {
                $this->updateBalanceAccordingToStatement($basEntity, $sourceEntity->transaction);
            }
        }
    }

    protected function updateBalanceAccordingToStatement(Entity $bas, Transaction\Entity $transaction)
    {
        $transaction->setBalance($bas->getBalance(), 0, false);

        /** @var Merchant\Balance\Entity $balance */
        $balance = $transaction->accountBalance;

        $balance->setBalance($bas->getBalance());

        $this->repo->balance->saveOrFail($balance);
        $this->repo->transaction->saveOrFail($transaction);

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
    //    INACTIVE_TIME is calculated as the difference between CURRENT_TIME and max(GATEWAY_BALANCE_CHANGE_AT, STATEMENT_CLOSING_BALANCE_CHANGE_AT)
    // 5. If the merchant is inactive for more than a limit (default being 1 hour), and statement has been fetched once between the
    //    current time and max(GATEWAY_BALANCE_CHANGE_AT, STATEMENT_CLOSING_BALANCE_CHANGE_AT) then the merchant is allowed to be satisfy
    //    criteria. Hence Points 1,2,3,4 need to satisfy this criteria so that merchants with permanent discrepancies don't block
    //    bandwidth for other merchants
    //
    // Create and dispatch jobs to pull data for those MIDs
    // Return accountNumbers dispatched for processing for the route response
    //
    public function dispatchAccountNumberForChannel(string $channel, array $input)
    {
        if (($channel === BASDetails\Channel::ICICI) and
            ($this->checkIfIciciStatementFetchEnabled() === false))
        {
            return [
                'accounts_processed' => [],
                'reason'             => 'ICICI Statement Fetch does not happen during this period.',
            ];
        }

        $limit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT]);

        $limit = (empty($input['limit']) === false) ? $input['limit'] : $limit;

        // Reserve Banking account statement fetch for some percentage of merchants which satisfy statement fetch criteria in one run
        $reservePercentage = (empty($input['reserve_percent']) === false) ? $input['reserve_percent'] : null;

        // Shifting priority during statement fetch from merchants who are inactive for than this limit in seconds, but still have a
        // balance mismatch. This was earlier leading to other merchants not getting picked for statement fetch
        $inactiveDurationLimit = (empty($input['inactive_duration_limit']) === false) ? $input['inactive_duration_limit'] : null;

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT;
        }

        if (empty($reservePercentage) === true)
        {
            $reservePercentage = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_RESERVE_PERCENTAGE;
        }

        if (empty($inactiveDurationLimit) === true)
        {
            $inactiveDurationLimit = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_INACTIVE_DURATION_RATE_LIMIT;
        }

        $blacklistStatementFetch = (empty($input['blacklist_fetch']) === false) ? $input['blacklist_fetch'] : false;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON_INITIATED,
            [
                'channel'                              => $channel,
                'banking_account_statement_rate_limit' => $limit,
                'reserve_percentage'                   => $reservePercentage,
                'inactive_limit_in_seconds'            => $inactiveDurationLimit,
                'input'                                => $input,
            ]);

        $accountType = array_pull($input, BASDetails\Entity::ACCOUNT_TYPE, BASDetails\AccountType::DIRECT);

        $bankingAccountDetails = $this->repo->banking_account_statement_details->fetchAccountNumbersByChannelOrderByLastStatementAttemptAt($channel, $accountType);

        $accountNumbersToDispatch = [];

        $otherAccounts = [];

        $numberOfAccountsSelected = 0;

        $minimumNumberOfOtherAccountsAllowed = (int) (round(((100 - $reservePercentage)/100) * $limit));

        $minimumNumberOfCriteriaSatisfyingAccountsAllowed = (int) (round(($reservePercentage)/100) * $limit);

        $currentTime = Carbon::now()->getTimestamp();

        foreach ($bankingAccountDetails as $bankingAccountDetail)
        {
            if ((boolval($blacklistStatementFetch) === true) and
                ($this->checkIfBlackListedMerchant($bankingAccountDetail->merchant->getId()) === true))
            {
                continue;
            }

            if (($this->checkIfAccountNumberSatisfiesSelectionCriteria($bankingAccountDetail, $currentTime, $inactiveDurationLimit) === true) and
                ($numberOfAccountsSelected < $minimumNumberOfCriteriaSatisfyingAccountsAllowed))
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

            if (($numberOfAccountsSelected >= $minimumNumberOfCriteriaSatisfyingAccountsAllowed) and
                (count($otherAccounts) > $minimumNumberOfOtherAccountsAllowed))
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

    public function checkIfAccountNumberSatisfiesSelectionCriteria($bankingAccountDetail, $currentTime, $inactiveLimit)
    {
        $gatewayBalance = $bankingAccountDetail->getGatewayBalance();

        $statementClosingBalance = $bankingAccountDetail->getStatementClosingBalance();

        $gatewayBalanceChangeAt = $bankingAccountDetail->getGatewayBalanceChangeAt();

        $statementClosingBalanceChangeAt = $bankingAccountDetail->getStatementClosingBalanceChangeAt();

        $lastStatementAttemptAt = $bankingAccountDetail->getLastStatementAttemptAt();

        // Checking this to avoid merchants with permanent discrepancies to be picked up by the cron
        // and taking other merchants bandwidth
        $inactiveTime = $currentTime - max($gatewayBalanceChangeAt, $statementClosingBalanceChangeAt);

        $isCriteriaSatisfiedWithBalanceMismatch = ($gatewayBalance !== $statementClosingBalance);

        $isCriteriaSatisfiedWithBalanceMatch = (($gatewayBalance === $statementClosingBalance) and
                                                ($gatewayBalanceChangeAt > $statementClosingBalanceChangeAt) and
                                                ($gatewayBalanceChangeAt > $lastStatementAttemptAt));

        $isMerchantInactive = (($inactiveTime > $inactiveLimit) and
                               ($lastStatementAttemptAt > max($gatewayBalanceChangeAt, $statementClosingBalanceChangeAt)));

        return ((($isCriteriaSatisfiedWithBalanceMismatch === true) or
                 ($isCriteriaSatisfiedWithBalanceMatch === true)) and
                ($isMerchantInactive === false));
    }

    public function checkIfIciciStatementFetchEnabled($considerBankHolidays = false, $checkRedisForStatementFetch = false)
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $startOfDay = Carbon::now(Timezone::IST)->startOfDay();
        $endOfDay = Carbon::now(Timezone::IST)->endOfDay();

        // Check if current time lies between 10 pm and 12 am today
        $isInTheSecondHalf = (($currentTime >= $endOfDay->clone()->subHours(2)->getTimestamp())
                              and ($currentTime <= $endOfDay->clone()->getTimestamp()));

        // Check if current time lies between 12 am and 6 am today
        $isInTheFirstHalf = (($currentTime >= $startOfDay->clone()->getTimestamp())
                             and ($currentTime <= $startOfDay->clone()->addHours(6)->getTimestamp()));

        $this->trace->info(TraceCode::ICICI_STATEMENT_FETCH_ENABLED, [
            'current_time'     => Carbon::now(Timezone::IST)->format('d M y h:i A'),
            'is_between_10_12' => $isInTheSecondHalf,
            'is_between_12_6'  => $isInTheFirstHalf,
            'is_enabled'       => !(($isInTheSecondHalf === true) or ($isInTheFirstHalf === true)),
        ]);

        // Icici statement should not happen between 10 pm and 6 am everyday
        if (($isInTheSecondHalf === true) or
            ($isInTheFirstHalf === true))
        {
            if ($checkRedisForStatementFetch === true)
            {
                $statementFetchEnabled = (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_ENABLE_IN_OFF_HOURS]);

                if (boolval($statementFetchEnabled) === true)
                {
                    return true;
                }
            }

            return false;
        }

        // Todo:: Add redis support for bank Holidays from FTS for ICICI.

        return true;
    }

    public function fetchMissingAccountStatementsForChannel($channel, $input, $isNewCron = false, $isMonitoring = false)
    {
        $merchantId = $input[Entity::MERCHANT_ID] ?? null;

        $this->trace->info(TraceCode::FETCH_MISSING_ACCOUNT_STATEMENTS_INITIATED, [
            'channel'     => $channel,
            'input'       => $input,
            'merchant_id' => $merchantId,
        ]);

        $countOfStatements = $this->repo->banking_account_statement->getCountOfStatementsInGivenPostedDateRange($channel, $input);

        [$expectedAttempts, $allowedToFetch] = $this->getExpectedAttemptsForChannel($channel, $countOfStatements);

        $this->trace->info(TraceCode::FETCH_MISSING_ACCOUNT_STATEMENTS_ATTEMPTS, [
            'expected_attempts'   => $expectedAttempts,
            'count_of_statements' => $countOfStatements,
            'allowed_to_fetch'    => $allowedToFetch,
            'merchant_id'         => $merchantId,
        ]);

        if ($allowedToFetch === false)
        {
            $this->trace->info(TraceCode::FETCH_MISSING_ACCOUNT_STATEMENT_LIMIT_ALERT, [
                'merchant_id'         => $merchantId,
                'expected_attempts'   => $expectedAttempts,
                'count_of_statements' => $countOfStatements,
                'channel'             => $channel
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'count_of_statements' => $countOfStatements,
                    'allowed_to_fetch'    => $allowedToFetch,
                    'merchant_id'         => $merchantId,
                ],
                'No of statements to be fetched for date range is greater than threshold'
            );
        }

        $delay         = array_pull($input, self::DELAY, 0);
        $paginationKey = ($channel === Channel::RBL) ? null : '';

        $this->trace->info(
            TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_DISPATCH_JOB_REQUEST,
            [
                Entity::CHANNEL                 => $channel,
                Entity::ACCOUNT_NUMBER          => $input[Entity::ACCOUNT_NUMBER],
                BASConstants::EXPECTED_ATTEMPTS => $expectedAttempts,
                Entity::FROM_DATE               => $input[Entity::FROM_DATE],
                Entity::TO_DATE                 => $input[Entity::TO_DATE],
                self::DELAY                     => $delay,
                BASConstants::PAGINATION_KEY    => $paginationKey,
                Entity::SAVE_IN_REDIS           => $input[Entity::SAVE_IN_REDIS],
                Entity::MERCHANT_ID             => $merchantId,
            ]);

        unset($input[Entity::MERCHANT_ID]);

        if ($isNewCron === true)
        {
            BankingAccountStatementReconNeo::dispatch($this->mode, [
                Entity::CHANNEL                 => $channel,
                Entity::ACCOUNT_NUMBER          => $input[Entity::ACCOUNT_NUMBER],
                Entity::FROM_DATE               => $input[Entity::FROM_DATE],
                Entity::TO_DATE                 => $input[Entity::TO_DATE],
                BASConstants::EXPECTED_ATTEMPTS => $expectedAttempts,
                BASConstants::PAGINATION_KEY    => $paginationKey,
                Entity::SAVE_IN_REDIS           => $input[Entity::SAVE_IN_REDIS],
            ])->delay($delay);
        }
        else
        {
            BankingAccountStatementRecon::dispatch($this->mode, [
                Entity::CHANNEL                 => $channel,
                Entity::ACCOUNT_NUMBER          => $input[Entity::ACCOUNT_NUMBER],
                Entity::FROM_DATE               => $input[Entity::FROM_DATE],
                Entity::TO_DATE                 => $input[Entity::TO_DATE],
                BASConstants::EXPECTED_ATTEMPTS => $expectedAttempts,
                BASConstants::PAGINATION_KEY    => $paginationKey,
                Entity::SAVE_IN_REDIS           => $input[Entity::SAVE_IN_REDIS],
            ],                                     $isMonitoring)->delay($delay);
        }

        $this->trace->info(TraceCode::FETCH_MISSING_ACCOUNT_STATEMENTS_JOB_DISPATCHED);

        return ['expected_attempts' => $expectedAttempts, 'dispatched' => 'success'];
    }

    public function getExpectedAttemptsForChannel($channel, $countOfStatements)
    {
        $expectedAttempts = 0;

        $allowedToFetch = true;

        switch ($channel)
        {
            case Channel::RBL:
                $rblAccountStatementV2MaxNumberOfRecords = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS]);

                if (empty($rblAccountStatementV2MaxNumberOfRecords) === true)
                {
                    $rblAccountStatementV2MaxNumberOfRecords = RblGateway::DEFAULT_RBL_ACCOUNT_STATEMENT_V2_MAX_NUMBER_OF_RECORDS;
                }

                $expectedAttempts = (int) round($countOfStatements / $rblAccountStatementV2MaxNumberOfRecords) + 1;

                $rblMissingStatementMaxRecordsFetch = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS]);

                if (empty($rblMissingStatementMaxRecordsFetch) === true)
                {
                    $rblMissingStatementMaxRecordsFetch = self::ACCOUNT_STATEMENT_RECORDS_TO_FETCH_MISSING_STATEMENTS_FOR_RBL;
                }

                if ($countOfStatements > $rblMissingStatementMaxRecordsFetch)
                {
                    $allowedToFetch = false;
                }

                break;

            case Channel::ICICI:
                $expectedAttempts = round($countOfStatements / IciciGateway::ICICI_STATEMENT_FETCH_API_MAX_RECORDS) + 1;

                $iciciMissingStatementMaxRecordsFetch = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_MISSING_STATEMENT_FETCH_MAX_RECORDS]);

                if (empty($iciciMissingStatementMaxRecordsFetch) === true)
                {
                    $iciciMissingStatementMaxRecordsFetch = self::ACCOUNT_STATEMENT_RECORDS_TO_FETCH_MISSING_STATEMENTS_FOR_ICICI;
                }

                if ($countOfStatements > $iciciMissingStatementMaxRecordsFetch)
                {
                    $allowedToFetch = false;
                }

                break;

            default:
                break;
        }

        return [$expectedAttempts, $allowedToFetch];
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

    public function automateAccountStatementsReconByChannel(string $channel, array $input)
    {
        $startTime = microtime(true);

        $accountNumbers = $input[BASConstants::ACCOUNT_NUMBERS] ?? [];

        $isNewCron = ((isset($input[BASConstants::NEW_CRON_SETUP]) === true) and
                      (boolval(BASConstants::NEW_CRON_SETUP) === true));

        $isMonitoringCron = ((isset($input[BASConstants::MONITORING_CRON]) === true) and
                             (boolval(BASConstants::MONITORING_CRON) === true));

        $inputFromDate = $input[Entity::FROM_DATE] ?? null;

        $inputToDate = $input[Entity::TO_DATE] ?? null;

        $reconLimit = $input[BASConstants::RECON_LIMIT] ?? null;

        $lastReconciledAtLimit = $input[BASConstants::LAST_RECONCILED_AT_LIMIT] ?? null;

        $exclusionMerchantList = $input[BASConstants::CRON_EXCLUSION_MERCHANT_LIST] ?? null;

        $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_INITIATED, [
            Entity::CHANNEL                       => $channel,
            BASConstants::ACCOUNT_NUMBERS_PRESENT => count($accountNumbers),
        ]);

        // Get Account numbers from gateway_balance_change_at which are recently changed if no account numbers are
        // provided beforehand in the input.
        $accountNumbers = $this->getAccountNumbersForAutomatedCARecon($accountNumbers, $channel, $reconLimit, $exclusionMerchantList);

        // Decide From and To Date from last_reconciled_at from basDetails
        $reconDetails = $this->getFromAndToDateFromLastReconciledAt($accountNumbers, $channel, $lastReconciledAtLimit);

        $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
            Entity::CHANNEL    => $channel,
            'recon_limit'      => $reconLimit,
            'msg'              => 'filter by last reconciled at method',
            'priority_acc_nos' => count($reconDetails),
        ]);

        $response = [];

        foreach ($reconDetails as $reconDetail)
        {
            try
            {
                $fromDate = (int) ($inputFromDate ?? $reconDetail[Entity::FROM_DATE]);

                $toDate = (int) ($inputToDate ?? $reconDetail[Entity::TO_DATE]);

                $fetchInput = [
                    Entity::CHANNEL        => $channel,
                    Entity::ACCOUNT_NUMBER => $reconDetail[Entity::ACCOUNT_NUMBER],
                    Entity::MERCHANT_ID    => $reconDetail[Entity::MERCHANT_ID],
                    Entity::FROM_DATE      => $fromDate,
                    Entity::TO_DATE        => $toDate,
                    Entity::SAVE_IN_REDIS  => $input[Entity::SAVE_IN_REDIS] ?? true,
                ];

                $this->fetchMissingAccountStatementsForChannel($channel, $fetchInput, $isNewCron, $isMonitoringCron);

                $response[$reconDetail[Entity::ACCOUNT_NUMBER]][BASConstants::FETCH_MISSING_STATEMENT] = BASConstants::SUCCESS;
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FETCH_DISPATCH_FAILED,
                    [
                        Entity::ACCOUNT_NUMBER => $reconDetail[Entity::ACCOUNT_NUMBER],
                        Entity::CHANNEL        => $channel,
                        Entity::MERCHANT_ID    => $reconDetail[Entity::MERCHANT_ID]
                    ]
                );

                $response[$reconDetail[Entity::ACCOUNT_NUMBER]][BASConstants::FETCH_MISSING_STATEMENT] = BASConstants::FAILURE;
            }
        }

        $endTime = microtime(true);

        $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FETCH_DISPATCH_SUCCESS, [
            Entity::CHANNEL            => $channel,
            'response_time'            => $endTime - $startTime,
            'count_of_account_numbers' => count($reconDetails),
        ]);

        return $response;
    }

    public function getAccountNumbersForAutomatedCARecon($accountNumbers, $channel, $reconlimit, $exclusionMerchantList)
    {
        // Give priority to account number from Cron input if passed.
        if (empty($accountNumbers) === false)
        {
            $this->filterAccountNumbersWithPaginationKeyPresent($accountNumbers, $channel);
        }
        else
        {
            $priorityAccountNumbers = (new AdminService)->getConfigKey(['key' => ConfigKey::CA_RECON_PRIORITY_ACCOUNT_NUMBERS]);

            if (empty($priorityAccountNumbers) === false)
            {
                $reconlimit -= count($priorityAccountNumbers);

                $reconlimit = max($reconlimit, 0);
            }

            $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
                Entity::CHANNEL    => $channel,
                'recon_limit'      => $reconlimit,
                'priority_acc_nos' => count($priorityAccountNumbers),
            ]);

            // Find Account numbers on the basis of gateway_balance_change_at from start of T-1 day to current time
            $accountNumbers = $this->repo->banking_account_statement_details->getAccountNumbersWhereGatewayBalanceIsUpdatedRecently(
                $channel, $reconlimit);

            $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
                Entity::CHANNEL    => $channel,
                'recon_limit'      => $reconlimit,
                'msg'              => 'filter by gateway balance',
                'account_numbers'  => count($accountNumbers),
            ]);

            $accountNumbers = array_unique(array_merge($accountNumbers, $priorityAccountNumbers));

            if(empty($exclusionMerchantList) === false)
            {
                $accountNumbers = array_diff($accountNumbers, $exclusionMerchantList);
            }

            $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
                Entity::CHANNEL    => $channel,
                'recon_limit'      => $reconlimit,
                'msg'              => 'filter by array unique',
                'account_numbers'  => count($accountNumbers),
            ]);

            $this->filterAccountNumbersWithPaginationKeyPresent($accountNumbers, $channel);
        }

        return array_values($accountNumbers);
    }

    public function filterAccountNumbersWithPaginationKeyPresent(&$accountNumbers, $channel)
    {
        if (in_array($channel, basDetails\Channel::getChannelsWithNullPaginationKey()) === false)
        {
            $accountNumbersWithPaginationKeyNull = $this->repo->banking_account_statement_details
                ->getByAccountNumbersAndPaginationKeyNull($channel, $accountNumbers);

            if (count($accountNumbersWithPaginationKeyNull) > 0)
            {
                $this->trace->info(TraceCode::AUTOMATED_RECON_FOUND_ACCOUNT_NUMBERS_WITH_NULL_PAGINATION_KEY, [
                    Entity::CHANNEL               => $channel,
                    BasConstants::ACCOUNT_NUMBERS => $accountNumbersWithPaginationKeyNull,
                    'count_of_account_numbers'    => count($accountNumbersWithPaginationKeyNull)
                ]);

                $this->trace->count(Metric::MISSING_STATEMENT_RECON_PAGINATION_KEY_ALREADY_NULL,
                                    [Entity::CHANNEL => $channel], count($accountNumbersWithPaginationKeyNull));

                $accountNumbers = array_diff($accountNumbers, $accountNumbersWithPaginationKeyNull);

                $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
                    Entity::CHANNEL    => $channel,
                    'msg'              => 'filter by pagination_key',
                    'account_numbers'  => count($accountNumbers),
                ]);
            }
        }
    }

    public function getFromAndToDateFromLastReconciledAt($accountNumbers, $channel, $lastReconciledAtLimit)
    {
        $reconDetails = [];

        $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

        $merchantIdAccNumberAndLastReconciledAtDetails = $this->repo->banking_account_statement_details
            ->getByAccountNumbersAndLastReconciledAt($channel, $accountNumbers);

        $this->trace->info(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
            Entity::CHANNEL    => $channel,
            'msg'              => 'filter after reconciled_at',
            'priority_acc_nos' => count($merchantIdAccNumberAndLastReconciledAtDetails),
        ]);

        foreach ($merchantIdAccNumberAndLastReconciledAtDetails as $merchantIdAccNumberAndLastReconciledAtDetail)
        {
            $reconDetail = [];

            $reconDetail[basDetails\Entity::LAST_RECONCILED_AT] = $lastReconciledAt =
                $merchantIdAccNumberAndLastReconciledAtDetail[basDetails\Entity::LAST_RECONCILED_AT];

            $reconDetail[basDetails\Entity::ACCOUNT_NUMBER] =
                $merchantIdAccNumberAndLastReconciledAtDetail[basDetails\Entity::ACCOUNT_NUMBER];

            $reconDetail[basDetails\Entity::MERCHANT_ID] =
                $merchantIdAccNumberAndLastReconciledAtDetail[basDetails\Entity::MERCHANT_ID];

            /**
             * If the merchant's reconciled_at is set to be 23rd May, it means all the statements are reconciled till
             * 23rd May, 11:59:59 PM.
             * if current start of day is greater than last_reconciled_at + 86400 (no of seconds in a day),
             *   then from_date is set to be last_reconciled_at
             * else
             *   from_date is set to be T-1 day's start of the day.
             */
            if ((isset($lastReconciledAt) === true) and
                (($lastReconciledAt + $secondsPerDay) < Carbon::now(Timezone::IST)->startOfDay()->getTimestamp()))
            {
                if ((isset($lastReconciledAtLimit) === true) and
                    ($lastReconciledAtLimit < (Carbon::now(Timezone::IST)->getTimestamp() - $lastReconciledAt))) {

                    $this->trace->error(TraceCode::LAST_RECONCILED_AT_LIMIT_EXCEEDED_FOR_RECON, [
                        'last_reconciled_at_limit' => $lastReconciledAtLimit,
                        'recon_details'            => $reconDetail,
                    ]);

                    continue;
                }

                $reconDetail[Entity::FROM_DATE] = Carbon::createFromTimestamp($lastReconciledAt, Timezone::IST)->addDay()->startOfDay()->getTimestamp();

                $reconDetail[Entity::TO_DATE] = Carbon::now(Timezone::IST)->subDay()->endOfDay()->getTimestamp();

                $this->trace->error(TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FILTER, [
                    'recon_details' => $reconDetail,
                ]);
            }

            if ((isset($reconDetail[Entity::FROM_DATE]) === false) or
                (isset($reconDetail[Entity::TO_DATE]) === false) or
                ($reconDetail[Entity::FROM_DATE] >= $reconDetail[Entity::TO_DATE]))
            {
                $this->trace->error(TraceCode::DEFAULT_FROM_AND_TO_DATE_CHOOSEN_FOR_RECON, [
                    'recon_details' => $reconDetail,
                ]);

                $reconDetail[Entity::FROM_DATE] = Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp();

                $reconDetail[Entity::TO_DATE] = Carbon::now(Timezone::IST)->subDay()->endOfDay()->getTimestamp();
            }

            $reconDetails[] = $reconDetail;
        }

        return $reconDetails;
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
                Entity::MERCHANT_ID               => optional($this->basDetails)->getMerchantId(),
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

    protected function linkPayoutToDebitBas(Payout\Entity $payout, $debit_bas)
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

            if ($payout->isOfMerchantTransaction() === true)
            {
                $event = "payout.processed";
                (new Transaction\Notifier($payout->transaction, $event))->notify();
            }
        }
        else
        {
            $oldStatus = $payout->getStatus();

            $payoutCore = new Payout\Core;

            $payoutCore->handlePayoutProcessed($payout, $debit_bas);

            $payoutCore->processTdsForPayout($payout, $oldStatus);
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
            $oldStatus = $payout->getStatus();

            $payoutCore = new Payout\Core;

            $payoutCore->handlePayoutReversed($payout, null, null, $credit_bas);

            $payoutCore->processTdsForPayout($payout, $oldStatus);
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

    public function retryBasSourceLinkingForProcessedPayout(array $params)
    {
        $payoutId = $params[Payout\Constants::PAYOUT_ID];

        /* @var \RZP\Models\Payout\Entity $payout */
        $payout = $this->repo->payout->findOrFail($payoutId);

        $this->trace->info(
            TraceCode::BAS_SOURCE_LINKING_RETRY_PAYOUT,
            [
                'payout_id' => $payout->getId(),
                'status'    => $payout->getStatus()
            ]);

        if ($payout->isStatusProcessed() === false)
        {
            return null;
        }

        $debitBas = (new Payout\Core)->handlePayoutTransactionForDirectBanking($payout);

        $this->trace->info(
            TraceCode::BAS_SOURCE_LINKING_RETRY_SUCCESS,
            [
                'payout_id'    => $payout->getId(),
                'status'       => $payout->getStatus(),
                'debit_bas_id' => optional($debitBas)->getId(),
            ]);
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
                'balance_id'            => $sourceEntity->getBalanceId(),
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
            else if ($sourceEntity->getPurpose() === Purpose::RZP_CHARGE_COLLECTIONS)
            {
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::CHARGE_COLLECTIONS_DEBIT_PROCESSED, $sourceEntity);
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
            else if ($payout->getPurpose() === Purpose::RZP_CHARGE_COLLECTIONS)
            {
                $this->processLedgerPayoutForDirect($merchant, Transaction\Processor\Ledger\Payout::CHARGE_COLLECTIONS_DEBIT_REVERSED, $sourceEntity);
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

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function setConfigKeys(array $input): array
    {
        (new AdminValidator)->validateInput('set_config_keys', $input);

        $result = [];

        foreach ($input as $key => $value)
        {
            $result[] = $this->setConfigKey($key, $value);
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $newValue
     *
     * @return array
     */
    public function setConfigKey(string $key, $newValue): array
    {
        $oldValue = Cache::get($key);

        Cache::forever($key, $newValue);

        $data = [
            'key'       => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ];

        if (ConfigKey::isSensitive($key) === false)
        {
            $this->trace->info(TraceCode::REDIS_KEY_SET, $data);
        }

        return $data;
    }

    public function shouldBlockNon2faAndNonBaasMerchants(array $params)
    {
        /** @var Merchant\Entity $merchant */
        $merchant      = $this->repo->merchant->findOrFail($params['merchant_id']);
        $is2FAEnabled  = $merchant->isFeatureEnabled(FeatureConstants::ICICI_2FA);
        $isBaasEnabled = $merchant->isFeatureEnabled(FeatureConstants::ICICI_BAAS);

        if (($is2FAEnabled === false) and
            ($isBaasEnabled === false))
        {
            $blockFetching = (bool) Admin\ConfigKey::get(Admin\ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA, false);

            if ($blockFetching === true)
            {
                $calledClass = get_called_class();

                if (strpos($calledClass, 'BankingAccountStatement') !== false)
                {
                    $this->trace->warning(
                        TraceCode::BLOCKED_STATEMENT_FETCH_FOR_NON_2FA_NON_BAAS_MERCHANTS,
                        [
                            'merchant_id' => $params['merchant_id'],
                            'channel'     => $params['channel'],
                        ]);
                }

                if (strpos($calledClass, 'GatewayBalanceUpdate') !== false)
                {
                    $this->trace->warning(
                        TraceCode::BLOCKED_BALANCE_FETCH_FOR_NON_2FA_NON_BAAS_MERCHANTS,
                        [
                            'merchant_id' => $params['merchant_id'],
                            'channel'     => $params['channel'],
                        ]);
                }

                return true;
            }
        }

        return false;
    }

    protected function checkGatewayRefNumberPatternFor($channel, $basDescription, &$matches)
    {
        $regex = self::GATEWAY_REF_NUMBER_PATTERN[$channel][BASConstants::DEFAULT];

        $match = preg_match($regex, $basDescription, $matches);

        return $match === 1;
    }

    protected function getGatewayRefNumberAvailabilityAndValue($basDescription, $channel)
    {
        $matches = [];

        $gatewayRefNum = null;

        $isGatewayRefNumFound = $this->checkGatewayRefNumberPatternFor($channel, $basDescription, $matches);

        if ($isGatewayRefNumFound === true)
        {
            $gatewayRefNum = $matches[0];

            if ($channel === Channel::RBL)
            {
                $gatewayRefNum = substr($matches[0], 4, 10);
            }
        }

        return [$isGatewayRefNumFound, $gatewayRefNum];
    }

    public function storeFailedUpdateParamsInRedis(array $params)
    {
        $accountNumber = $params[Entity::ACCOUNT_NUMBER];

        return $this->mutex->acquireAndRelease(
            'update_redis_failed_batch_params_' . $accountNumber,
            function () use ($params) {
                try
                {
                    $this->trace->info(TraceCode::BAS_UPDATE_PARAMS_REDIS_WRITE_REQUEST,
                        [
                            'params' => $params,
                        ]
                    );

                    $redisKey = $params[Entity::MERCHANT_ID] . '_' . $params[Entity::ACCOUNT_NUMBER] . '_bas_update_params';

                    $failedUpdateParams = json_encode($params);

                    $this->app['redis']->set($redisKey, $failedUpdateParams);
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        null,
                        TraceCode::BAS_UPDATE_PARAMS_REDIS_WRITE_FAILED,
                        [
                            'params' => $params,
                        ]
                    );
                }
            },
            60,
            ErrorCode::BAD_REQUEST_FAILED_UPDATE_BATCH_PARAMS_UPDATION_IN_PROGRESS,
            3
        );
    }

    public function handleMissingStatementUpdateBatchFailure(array $input): array
    {
        $response = [];

        $accountNumbers = $input[BASConstants::ACCOUNT_NUMBERS];

        foreach ($accountNumbers as $accountNumber)
        {
            try
            {
                $basDetailEntity = $this->getBasDetails($accountNumber, $input['channel'], [Details\Status::UNDER_MAINTENANCE]);

                if (empty($basDetailEntity) === true)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_DETAILS_FOR_ACCOUNT_IS_NOT_ACTIVE,
                        null,
                        ['account_number' => $accountNumber]);
                }

                $merchantId = $basDetailEntity->getMerchantId();

                $redisKey = $merchantId. '_' . $accountNumber . '_bas_update_params';

                $params = json_decode($this->app['redis']->get($redisKey), true);

                $this->trace->info(
                    TraceCode::BAS_MISSING_STATEMENT_BALANCE_UPDATE_TRIGGER_REQUEST,
                    [
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        'params'               => $params
                    ]);

                if (empty($params) === false)
                {
                    BankingAccountStatementUpdate::dispatch($this->mode, $params);

                    $this->trace->info(TraceCode::BAS_UPDATE_QUEUE_DISPATCH_SUCCESS,
                        [
                            Entity::ACCOUNT_NUMBER => $accountNumber,
                            'params'               => $params
                        ]);

                    $response[$accountNumber][BASConstants::UPDATE_MISSING_STATEMENT] = BASConstants::SUCCESS;
                }
                else
                {
                    $response[$accountNumber][BASConstants::UPDATE_MISSING_STATEMENT] = BASConstants::FAILURE;
                }
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::BAS_UPDATE_QUEUE_DISPATCH_FAILURE,
                    [
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        'params'               => $params,
                    ]
                );

                $response[$accountNumber][BASConstants::UPDATE_MISSING_STATEMENT] = BASConstants::FAILURE;
            }
        }

        return $response;
    }

    public function findMatchingBASInFetchedStatements(Entity $basEntity, $fetchedStatements)
    {
        $accountStatementApiVersion = $this->getAccountStatementApiVersion($this->basDetails);

        $processor = $this->getProcessor($basEntity->getChannel(), $basEntity->getAccountNumber(), $this->basDetails, $accountStatementApiVersion);

        if (count($fetchedStatements) === 0)
        {
            return [null, null];
        }

        [$matchedBASFromBank, $existingBAS] = $processor->compareAndReturnMatchedBASFromFetchedStatements($fetchedStatements);

        if ($matchedBASFromBank->getId() === null)
        {
            return [null, null];
        }

        return [$matchedBASFromBank, $existingBAS];
    }

    public function dispatchIntoQueueAndRetryIfFailure($queueName, $input, $delay = 0)
    {
        $queueRetryLimit = 2;
        $attempt         = 0;
        $dispatchSuccess = false;

        do {
            try
            {
                $queueName = "RZP\\Jobs\\".$queueName;

                $queueName::dispatch($this->mode, $input)->delay($delay);

                $dispatchSuccess = true;

                break;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::BAS_RECON_QUEUE_DISPATCH_FAILURE,
                    [
                        'queueName' => $queueName,
                        'input'     => $input,
                        'delay'     => $delay,
                    ]
                );

                // Push into prometheus

                $attempt++;
            }
        } while ($attempt < $queueRetryLimit);

        if ($dispatchSuccess === true)
        {
            $this->trace->info(TraceCode::BAS_RECON_QUEUE_DISPATCH_SUCCESS, [
                'queueName' => $queueName,
                'input'     => $input,
                'delay'     => $delay,
            ]);
        }
    }

    public function checkCleanUpConfigIfFetchIsRequired(array $cleanUpConfig, $channel, $accountNumber)
    {
        if ((array_key_exists(BASConstants::MISMATCH_DATA, $cleanUpConfig) === true) and
            (empty($cleanUpConfig[BASConstants::MISMATCH_DATA]) === false))
        {
            $input = [
                Entity::CHANNEL        => $channel,
                Entity::ACCOUNT_NUMBER => $accountNumber,
                Entity::FROM_DATE      => (int) ($cleanUpConfig[BASConstants::MISMATCH_DATA][0][Entity::FROM_DATE] ?? null),
                Entity::TO_DATE        => (int) ($cleanUpConfig[BASConstants::MISMATCH_DATA][0][Entity::TO_DATE] ?? null),
            ];

            try
            {
                // Validation of the cleanup Config
                (new Validator())->validateInput('cleanUpConfig', $input);
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::BAS_INVALID_CLEANUP_CONFIG_ERROR, [
                    'clean_up_config'          => $cleanUpConfig,
                ]);

                return [false, null];
            }

            $countOfStatements = $this->repo->banking_account_statement->getCountOfStatementsInGivenPostedDateRange($channel, $input);

            [$expectedAttempts, $allowedToFetch] = $this->getExpectedAttemptsForChannel($channel, $countOfStatements);

            $this->trace->info(TraceCode::FETCH_MISSING_ACCOUNT_STATEMENTS_ATTEMPTS, [
                'expected_attempts'   => $expectedAttempts,
                'count_of_statements' => $countOfStatements,
                'allowed_to_fetch'    => $allowedToFetch,
            ]);

            $paginationKey = ($channel === Channel::RBL) ? null : '';

            $this->trace->info(TraceCode::MISSING_BANKING_ACCOUNT_STATEMENT_FETCH_DISPATCH_JOB_REQUEST, [
                Entity::CHANNEL                 => $channel,
                Entity::ACCOUNT_NUMBER          => $input[Entity::ACCOUNT_NUMBER],
                BASConstants::EXPECTED_ATTEMPTS => $expectedAttempts,
                Entity::FROM_DATE               => $input[Entity::FROM_DATE],
                Entity::TO_DATE                 => $input[Entity::TO_DATE],
                self::DELAY                     => 0,
                BASConstants::PAGINATION_KEY    => $paginationKey,
                Entity::SAVE_IN_REDIS           => true,
            ]);

            $input[BASConstants::EXPECTED_ATTEMPTS] = $expectedAttempts;
            $input[BASConstants::PAGINATION_KEY]    = $paginationKey;
            $input[Entity::SAVE_IN_REDIS]           = true;

            unset($input[Entity::CHANNEL]);
            unset($input[Entity::ACCOUNT_NUMBER]);

            return [true, $input];
        }

        return [false, null];
    }

    public function updateCleanUpConfigWhenFetchIsFinished(array &$cleanUpConfig, $fetchInput)
    {
        if ((array_key_exists(BASConstants::MISMATCH_DATA, $cleanUpConfig) === true) and
            (empty($cleanUpConfig[BASConstants::MISMATCH_DATA]) === false))
        {
            $mismatchData = $cleanUpConfig[BASConstants::MISMATCH_DATA];

            foreach ($mismatchData as $key => $mismatchPeriod)
            {
                $fromDate = $mismatchPeriod[Entity::FROM_DATE] ?? null;
                $toDate   = $mismatchPeriod[Entity::TO_DATE] ?? null;

                if (($fromDate === $fetchInput[Entity::FROM_DATE]) and
                    ($toDate === $fetchInput[Entity::TO_DATE]))
                {
                    unset($cleanUpConfig[BASConstants::MISMATCH_DATA][$key]);

                    $cleanUpConfig[BASConstants::MISMATCH_DATA] = array_values($cleanUpConfig[BASConstants::MISMATCH_DATA]);

                    return true;
                }
            }
        }

        $this->trace->error(TraceCode::BAS_CLEANUP_CONFIG_UPDATE_ERROR, [
            'clean_up_config' => $cleanUpConfig,
            'fetch_input'     => $fetchInput
        ]);

        return false;
    }

    public function fetchMissingStatementConfigFor(string $accountNumber, $channel)
    {
        $configKey = Admin\ConfigKey::PREFIX . 'rx_ca_missing_statement_detection_' . $channel;

        $config = (new Admin\Service())->getConfigKey(['key' => $configKey]);

        if ((empty($config) === true) or
            (array_key_exists($accountNumber, $config) === false))
        {
            return null;
        }

        return $config[$accountNumber];
    }

    public function updateMissingStatementConfigFor(string $accountNumber, string $channel, array $config)
    {
        return $this->mutex->acquireAndRelease(
            'update_redis_missing_statement_detect_' . $channel,
            function() use ($accountNumber, $config, $channel) {
                $configKey = Admin\ConfigKey::PREFIX . 'rx_ca_missing_statement_detection_' . $channel;

                $existingConfigs = (new Admin\Service())->getConfigKey(['key' => $configKey]);

                $oldConfig = $existingConfigs;

                if ($existingConfigs === null)
                {
                    $existingConfigs = [];
                }

                $existingConfigs[$accountNumber] = $config;

                $this->setConfigKeys([$configKey => $existingConfigs]);

                $this->trace->info(TraceCode::MISSING_STATEMENT_DETECTION_UPDATE_CONFIG, [
                    'channel'       => $channel,
                    'old_config'    => $oldConfig,
                    'new_config'    => $existingConfigs,
                ]);

                return $existingConfigs[$accountNumber];
            },
            60,
            ErrorCode::BAD_REQUEST_MISSING_STATEMENT_DETECTION_UPDATE_IN_PROGRESS,
            3
        );
    }

    public function triggerMissingStatementFetchForIdentifiedTimeRange($merchantId, $accountNumber, $channel, $missingStatementConfig)
    {
        try
        {
            // Remove months where there is no change in mismatch
            $modifiedMissingStatementConfig = $this->removeRangeWithNoMismatchIn($missingStatementConfig);

            if ($modifiedMissingStatementConfig === null)
            {
                $this->trace->error(TraceCode::MISSING_STATEMENT_DETECTION_JOB_FETCH_TRIGGER_FAILURE, [
                    'merchant_id' => $merchantId,
                    'channel'     => $channel,
                    'config'      => $modifiedMissingStatementConfig,
                    'reason'      => 'no_config_found_for_fetch'
                ]);

                return;
            }

            $totalMismatchAmount = $modifiedMissingStatementConfig[BASConstants::MISMATCH_DATA][0]['mismatch_amount'];

            // sort data and send in ascending order
            usort($modifiedMissingStatementConfig[BASConstants::MISMATCH_DATA], function ($a, $b)
            {
                return $a[Entity::TO_DATE] > $b[Entity::TO_DATE];
            });

            $queueInput = [
                Entity::CHANNEL                     => $channel,
                Entity::MERCHANT_ID                 => $merchantId,
                Entity::ACCOUNT_NUMBER              => $accountNumber,
                BASConstants::FETCH_INPUT           => null,
                BASConstants::CLEAN_UP_CONFIG       => $modifiedMissingStatementConfig,
                BASConstants::FETCH_IN_PROGRESS     => false,
                BASConstants::MISMATCH_AMOUNT_FOUND => 0,
                BASConstants::TOTAL_MISMATCH_AMOUNT => $totalMismatchAmount,
            ];

            $this->updateMissingStatementConfigFor($accountNumber, $channel, $modifiedMissingStatementConfig);

            BankingAccountStatementCleanUp::dispatch($this->mode, $queueInput);

            $this->trace->info(
                TraceCode::MISSING_STATEMENT_DETECTION_FETCH_TRIGGER,
                [
                    'merchant_id' => $merchantId,
                    'channel'     => $channel,
                    'queue_input' => $queueInput,
                ]);
        }
        catch(\Exception $ex)
        {
            $this->trace->error(TraceCode::MISSING_STATEMENT_DETECTION_JOB_FETCH_TRIGGER_FAILURE, [
                'merchant_id'   => $merchantId,
                'channel'       => $channel,
                'config'        => $missingStatementConfig,
                'exception'     => $ex
            ]);
        }
    }

    private function removeRangeWithNoMismatchIn($missingStatementConfig)
    {
        if ((array_key_exists(BASConstants::MISMATCH_DATA, $missingStatementConfig) === false) or
            (empty($missingStatementConfig[BASConstants::MISMATCH_DATA]) === true))
        {
            return null;
        }

        usort($missingStatementConfig[BASConstants::MISMATCH_DATA], function ($a, $b)
        {
            return $a[Entity::TO_DATE] < $b[Entity::TO_DATE];
        });

        $configCount = count($missingStatementConfig[BASConstants::MISMATCH_DATA]);

        for ($i = 0; $i < $configCount - 1; $i++)
        {
            if ($missingStatementConfig[BASConstants::MISMATCH_DATA][$i]['mismatch_amount'] === $missingStatementConfig[BASConstants::MISMATCH_DATA][$i + 1]['mismatch_amount'])
            {
                continue;
            }

            $modifiedConfig[] = $missingStatementConfig[BASConstants::MISMATCH_DATA][$i];
        }

        $modifiedConfig[] = $missingStatementConfig[BASConstants::MISMATCH_DATA][$configCount - 1];

        $missingStatementConfig[BASConstants::MISMATCH_DATA] = $modifiedConfig;

        return $missingStatementConfig;
    }

    /**
     * @param                $accountNumber
     * @param Details\Entity $basDetails
     * @param                $channel
     * @param int            $limit
     *
     * @return array
     */
    private function fetchUnlinkedRecordsUsingOptimizedQueryIfApplicable($accountNumber, Details\Entity $basDetails, $channel, int $limit): array
    {
        $variant = $this->app->razorx->getTreatment($accountNumber,
                                                    Merchant\RazorxTreatment::BANKING_ACCOUNT_STATEMENT_FETCH_UNLINKED_QUERY_OPTIMIZE,
                                                    $this->mode);

        $fetchUsingDefaultQuery = true;

        $basEntities = null;

        try
        {
            if ($variant == 'on')
            {
                $txns = $this->repo->transaction->fetchLatestTxnForBalanceId($basDetails->getBalanceId());

                if (count($txns) > 0)
                {
                    $basRecords = $this->repo->banking_account_statement->fetchBASLinkedWithTransaction($accountNumber, $txns[0]->getId());

                    if (count($basRecords) > 0)
                    {
                        $basEntities = $this->repo->banking_account_statement->fetchUnlinkedBasRecordsPostGivenId($basRecords[0]->getId(), $accountNumber, $channel, $limit);
                        $fetchUsingDefaultQuery = false;
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::BAS_FETCH_USING_OPTIMIZED_QUERY_FAILURE,
                [
                    'channel'       => $channel,
                    'merchant_id'   => $basDetails->getMerchantId(),
                    'error'         => $e,
                    'error_message' => $e->getMessage()
                ]);
        }

        return array($fetchUsingDefaultQuery, $basEntities);
    }

    public function processDualWrite(array $input)
    {
        (new Validator)->validateInput(Validator::BAS_DUAL_WRITE_INPUT, $input);

        $basId = $input[Entity::ENTITY_ID];

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $metadata = $this->getMetaDataFromPayoutServiceForDualWrite($basId);

        if (empty($metadata) == false)
        {
            $timestamp = get_object_vars(json_decode($metadata['meta_value']))['timestamp'];

            if ($input['timestamp'] < $timestamp)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_NO_ACTION,
                    $input
                );

                return;
            }
        }

        (new DualWrite\Processor)->dualWriteDataForBasId($basId);

        $this->upsertMetaDataInPayoutServiceForDualWrite($basId, $currentTime, $metadata);
    }

    public function getMetaDataFromPayoutServiceForDualWrite($payoutId)
    {
        $metadata = $this->repo->payout->getPayoutServicePayoutMetaDataForDualWrite($payoutId, self::DUAL_WRITE_META_NAME);

        if (count($metadata) === 0)
        {
            return [];
        }

        $metadata = $metadata[0];

        return get_object_vars($metadata);
    }

    public function upsertMetaDataInPayoutServiceForDualWrite(string $basId, $currentTime, $metadata)
    {
        $tableName = self::PAYOUT_SERVICE_TEMPORARY_METADATA_TABLE;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        if (empty($metadata) === true)
        {
            $data = [
                Entity::ID         => Entity::generateUniqueId(),
                self::PAYOUT_ID    => $basId,
                'meta_name'        => self::DUAL_WRITE_META_NAME,
                'meta_value'       => json_encode(['timestamp' => $currentTime]),
                Entity::CREATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
                Entity::UPDATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            ];

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_INSERT_METADATA,
                $data
            );

            $this->repo->payout->insertIntoPayoutServiceDB($tableName, $data);
        }
        else
        {
            $id = $metadata[Entity::ID];

            $data = [
                'meta_value'       => json_encode(['timestamp' => $currentTime]),
                Entity::UPDATED_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            ];

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_UPDATE_METADATA,
                $data
            );

            $this->repo->payout->updateInPayoutServiceDB($tableName, $id, $data);
        }
    }
}
