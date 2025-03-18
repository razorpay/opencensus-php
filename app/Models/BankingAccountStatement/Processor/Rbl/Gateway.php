<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Metric;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\BankingAccountStatement\Category;
use RZP\Models\BankingAccountStatement\Processor\Source;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccount\Service as BankingAccountService;
use RZP\Models\BankingAccountStatement\Processor\Base as BaseProcessor;
use RZP\Models\BankingAccountStatement\Core as BankingAccountStatementCore;
use RZP\Models\BankingAccountStatement\Processor\Rbl\RequestResponseFields as Fields;

class Gateway extends BaseProcessor
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s.000';

    const STATEMENT_START_TIME_DATE_FORMAT = 'Y-m-d';

    const STATEMENT_START_TIME_DATE_FORMAT_V2 = 'd-m-Y';

    const DEFAULT_RBL_STATEMENT_FETCH_ATTEMPT_LIMIT = 10;

    const RBL_ACCOUNT_STATEMENT_DISPATCH_DELAY = 120;

    const DEFAULT_RBL_STATEMENT_FETCH_RETRY_LIMIT = 3;

    const ACCOUNT_STATEMENT_V2_BANK_RESPONSE_HEADER_LINE = 'TRAN_ID,PTSN_NUM,TRAN_DATE,PSTD_DATE,TRAN_TYPE,C/D,TRAN_PARTICULAR,TRAN_AMT,TRAN_BALANCE';

    const RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT = 200;

    const DEFAULT_RBL_ACCOUNT_STATEMENT_V2_MAX_NUMBER_OF_RECORDS = 5000;

    const PAGINATION_KEY_TTL_IN_WEEKS = 4;

    // regex to fetch utr from description
    // sample NEFT/SFMS RTN  -  NEFT/SFMS RTN/000311505156/MAGICBRICKS REALTY SERV
    const CREDIT_REGEX = '/^(RTGS\/|NEFT\/SFMS RTN\/|NEFT\/|UPI\/|R\/UPI\/|R-|IMPS )(.*?)(\/|-| )/';

    // sample IMPS - 209821868111_IMPSIN
    const IMPS_CREDIT_REGEX = '/^([0-9]{12})_IMPS/';

    // sample IMPS - 010617021414-QCREDIT 234412
    const IMPS_DEBIT_REGEX = '/^(.*?)-/';

    // sample NEFT - NEFT/000119662132/maYANK SHARMA
    // sample RTGS - RTGS/UTIBH20106341692/RAZORPAY SOFTWARE PRIVATE LI
    const NEFT_RTGS_DEBIT_REGEX = '/^(RTGS\/|NEFT\/)(.*?)(\/)/';

    const OFFSET_FOR_SAVING_RECORD = 60;

    // sample UPI- UPI/120310176379/Test transfer RAZORPAY/razorpayx.
    const UPI_DEBIT_REGEX = '/^(UPI\/)(.*?)(\/)/';

    const PAGINATION_KEY_REGEX = '/^[1-9][0-9]{9}_/';

    protected $rblAccountStatementV2MaxNumberOfRecords;

    protected $fromDate = null;

    protected $toDate = null;

    protected $savePaginationKey = true;

    protected $statementRecordsToMatch = [
        Entity::ACCOUNT_NUMBER,
        Entity::CHANNEL,
        Entity::POSTED_DATE,
        Entity::TYPE,
        Entity::BANK_SERIAL_NUMBER,
        Entity::AMOUNT,
        Entity::BANK_TRANSACTION_ID
    ];

    public function __construct(string $channel,
                                string $accountNumber,
                                BasDetails\Entity $basDetails,
                                $version)
    {
        $this->setSource(Source::FETCH_API);

        $this->basDetails = $basDetails;

        $this->setVersion($version);

        parent::__construct($channel, $accountNumber);
    }

    protected function sendRequestAndGetResponse(array  $input)
    {
        switch ($this->version){
            case Entity::ACCOUNT_STATEMENT_FETCH_API_VERSION_2:
                return $this->sendRequestAndGetResponseV2($input);

            default :
                return $this->sendRequestAndGetResponseV1($input);
        }
    }

    public function checkForDuplicateTransactions(array $bankTransactions,
                                                  string $channel,
                                                  string $accountNumber,
                                                  $merchant)
    {
        $this->alterStatementColumnsToMatch();

        $totalRecordCount = count($bankTransactions);
        $totalRecords = 0;
        $skippedRecordCount = 0;
        $processedRecordCount = 0;
        $bankTransactionRecords = [];
        $bankTransactionIds = [];
        $queryDate = null;

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT;
        }

        $dedupeStartTime = microtime(true);

        foreach ($bankTransactions as $index => $bankTransaction)
        {
            $record = $this->arrangeColumnsToFindDuplicates($bankTransaction);

            $bankTransactionIds[] = $record[Entity::BANK_TRANSACTION_ID];

            $queryDate = (isset($queryDate) === false) ? $record[Entity::TRANSACTION_DATE]
                : min($queryDate, $record[Entity::TRANSACTION_DATE]);

            $bankTransactionRecords[$index] = $this->formBankTransactionRecordToMatch($bankTransaction);

            $totalRecords++;
            $processedRecordCount++;

            // either the records are in batches of the limit or the leftover records
            // second if condition takes care of the case when all records have been processed and
            // there are some records which are less than the limit and won't give a 0 on mod by
            // the limit
            if ((($processedRecordCount % $limit) === 0) or
                (($totalRecords === $totalRecordCount) and ($processedRecordCount % $limit) !== 0))
            {
                if ($this->basDetails->getAccountType() === BasDetails\AccountType::DIRECT)
                {
                    $existingRecords = $this->repo->banking_account_statement
                        ->findExistingStatementRecordsForBankWithDate($bankTransactionIds, $accountNumber, $queryDate);
                }
                else
                {
                    $existingRecords = $this->repo->banking_account_statement_pool_rbl
                        ->findExistingStatementRecordsForBankWithDate($bankTransactionIds, $accountNumber, $queryDate);
                }

                /** @var Entity $record */
                foreach ($existingRecords as $record)
                {
                    $record->setDescription(trim($record->getDescription()));

                    $recordToMatch = $this->formBankTransactionRecordToMatch($record->toArray());

                    $isPresent = array_search($recordToMatch, $bankTransactionRecords);

                    if ($isPresent !== false)
                    {
                        unset($bankTransactions[$isPresent]);
                        $skippedRecordCount++;
                    }
                }

                $bankTransactionRecords = [];
                $bankTransactionIds     = [];
                $queryDate              = null;
                $processedRecordCount   = 0;
            }
        }

        $dedupeEndTime = microtime(true);

        $this->trace->info(
            TraceCode::BAS_DEDUPE_CHECK_ANALYSIS,
            [
                'channel'        => $channel,
                'account_number' => $accountNumber,
                'merchant_id'    => $merchant->getId(),
                'time_taken'     => $dedupeEndTime - $dedupeStartTime,
                'total_records'  => $totalRecordCount,
            ]);

        if ($skippedRecordCount !== 0)
        {
            $data = [
                'channel'                    => Channel::RBL,
                'merchant_id'                => $merchant->getId(),
                'skipped_record_count'       => $skippedRecordCount,
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_RECORDS_FOUND, [
                'data' => $data,
            ]);
        }
        else
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_NO_EXISTING_RECORDS_FOUND,
                [
                    'channel'                    => Channel::RBL,
                    'account_number'             => $accountNumber,
                    'merchant_id'                => $merchant->getId(),
                ]);
        }

        return $bankTransactions;
    }

    // instead of matching all fields of the duplicate record we will match only some selected fields which will uniquely identify the record.
    public function formBankTransactionRecordToMatch($bankTransaction)
    {
        $tempBankTransactionRecord = [];

        foreach ($this->statementRecordsToMatch as $statementRecordToMatch)
        {
            $tempBankTransactionRecord[$statementRecordToMatch] = $bankTransaction[$statementRecordToMatch];
        }

        return $tempBankTransactionRecord;
    }

    protected function sendRequestAndGetResponseV1(array $input)
    {
        //
        // Since there could be a lot of data, currently, we are fetching only
        // 3 times and let the remaining run in the next run. Should fix this logic.
        //
        $attemptCount = 0;

        // Retry logic is placed to retry when gateway exceptions are caught. Retry limit is in place for upper bound.
        $statementRetry = 0;

        $finalFormattedResponse = [];

        // get last bank transaction from banking account statement and set lastFormattedResponse
        // this is being used for pagination on RBL side.
        $lastBankTransaction = $this->getLastBankTransaction() ? $this->getLastBankTransaction()->toArray() : [];

        list($attemptLimit, $statementRetryLimit) = $this->setAttemptLimitAndRetryLimit();

        $recordNumber = 1;

        do
        {
            // We don't have any bank response for the first request.
            $lastFormattedResponse = last($finalFormattedResponse) ?: $lastBankTransaction;

            $requestData = $this->getRequestDataForMozart($input, $lastFormattedResponse);

            $requestTime = Carbon::now();

            try
            {
                $bankResponse = $this->app->mozart->sendMozartRequest(self::MOZART_NAMESPACE,
                                                                      $this->getChannel(),
                                                                      self::MOZART_ACTION,
                                                                      $requestData);

                $this->modifyBankResponse($bankResponse);

                $this->validateMozartResponse($bankResponse);
            }
            catch (\Throwable $ex)
            {
                if ($ex instanceof Exception\GatewayErrorException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST_FAILED,
                        [
                            Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                            Entity::CHANNEL             => $this->channel,
                        ]);

                    $statementRetry ++ ;

                    if ($statementRetry <= $statementRetryLimit )
                    {
                        $fetchMore = true;

                        continue;
                    }
                }

                if ($ex instanceof Exception\BadRequestValidationFailureException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_INVALID_MOZART_RESPONSE,
                        [
                            Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                            Entity::CHANNEL             => $this->channel,
                            RequestResponseFields::DATA => $bankResponse ?? [],
                        ]);
                }

                throw $ex;
            }

            $formattedResponse = $this->getFormattedResponse($bankResponse['data'], $recordNumber, $requestTime);

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

            $fetchMore = (($this->hasMoreData($bankResponse) === true) and ($attemptCount < $attemptLimit));

        } while ($fetchMore);

        // TODO: Thinking of moving the logic of dispatching job again in case of
        // more data in job itself. But not sure if this logic is generic for all
        // bank as of now

        // Adding a dispatch delay of 120 seconds as account statement process takes
        // around 1 min for processing and save.
        if (($this->hasMoreData($bankResponse) === true))
        {
            $delay = self::RBL_ACCOUNT_STATEMENT_DISPATCH_DELAY;

            $data = [
                BasDetails\Entity::CHANNEL        => $this->basDetails->getChannel(),
                BasDetails\Entity::ACCOUNT_NUMBER => $this->accountNumber,
                BasDetails\Entity::BALANCE_ID     => $this->basDetails->getBalanceId(),
                'delay'                           => $delay
            ];

            (new BankingAccountStatementCore)->dispatchBankingAccountStatementJob($data, $this->basDetails->getAccountType());
        }

        return $finalFormattedResponse;
    }

    protected function modifyBankResponse(array & $response)
    {
        $txnDetails = $response[Fields::DATA][Fields::PAYMENT_GENERIC_RESPONSE]
                               [Fields::BODY][Fields::TRANSACTION_DETAILS] ?? [];

        if (empty($txnDetails) === true)
        {
            return;
        }

        if (is_associative_array($txnDetails) === true)
        {
            $response[Fields::DATA][Fields::PAYMENT_GENERIC_RESPONSE]
                     [Fields::BODY][Fields::TRANSACTION_DETAILS] = [$txnDetails];
        }
    }

    protected function setAttemptLimitAndRetryLimit()
    {
        $merchantId = $this->basDetails->getMerchantId();

        // TODO: This whole thing needs to be re-looked at. How we fetch the details.

        $requestPayload = [
            "id" => $merchantId,
            "experiment_name" =>  Merchant\RazorxTreatment::BANKING_ACCOUNT_STATEMENT_SPECIAL_ATTEMPT_LIMIT,
             'request_data'  => json_encode(['id' =>  $merchantId])
        ];

        if ((new Merchant\Core)->isSplitzExperimentEnable($requestPayload,Merchant\RazorxTreatment::VARIANT_ENABLE))
        {
            $attemptLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_SPECIAL_ATTEMPT_LIMIT]);
        }

        if (empty($attemptLimit) === true)
        {
            $attemptLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_ATTEMPT_LIMIT]);
        }

        if (empty($attemptLimit) === true)
        {
            $attemptLimit = self::DEFAULT_RBL_STATEMENT_FETCH_ATTEMPT_LIMIT;
        }

        $statementRetryLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_RETRY_LIMIT]);

        if (empty($statementRetryLimit) === true)
        {
            $statementRetryLimit = self::DEFAULT_RBL_STATEMENT_FETCH_RETRY_LIMIT;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_ATTEMPT_AND_RETRY_LIMITS,
            [
                'merchant_id'         => $merchantId,
                'channel'             => $this->channel,
                'account_number'      => $this->accountNumber,
                'attempt_limit'       => $attemptLimit,
                'retry_limit'         => $statementRetryLimit,
            ]);

        return [$attemptLimit, $statementRetryLimit];
    }

    protected function sendRequestAndGetResponseV2(array $input)
    {
        //
        // Since there could be a lot of data, currently, we are fetching only
        // 3 times and let the remaining run in the next run. Should fix this logic.
        //
        $attemptCount = 0;

        // Retry logic is placed to retry when gateway exceptions are caught. Retry limit is in place for upper bound.
        $statementRetry = 0;

        $isRetry = false;

        $formattedResponse = [];

        $finalFormattedResponse = [];

        // TODO: This whole thing needs to be re-looked at. How we fetch the details.

        // request obtained before while loop. Apart from from_date, to_date, bucket_number all other request data is constant.
        $requestData = $this->getRequestDataForMozartV2($input);

        list($attemptLimit, $statementRetryLimit) = $this->setAttemptLimitAndRetryLimit();

        $this->rblAccountStatementV2MaxNumberOfRecords = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS]);

        if (empty($this->rblAccountStatementV2MaxNumberOfRecords) === true)
        {
            $this->rblAccountStatementV2MaxNumberOfRecords = self::DEFAULT_RBL_ACCOUNT_STATEMENT_V2_MAX_NUMBER_OF_RECORDS;
        }

        $recordNumber = 1;

        // This is for backward compatibility.
        $this->updatePaginationKeyIfRequired();

        do
        {
            // We can fetch upto 1 month using pagination key. So when merchant doesn't have transactions in more than a 4 weeks, we will not use pagination key to fetch statement.
            $paginationKey = $this->fetchPaginationKey();

            // Rbl api supports 2 formats of requests.
            //     1. using from_date and to_date in api request
            //     2. using next_key in api request.
            // If for a merchant pagination key is not available, we use 1st format else 2nd format is used.
            // Bank will be returning next_key in every successful api call.
            $this->selectApiAndModifyRequest($requestData, $paginationKey, $isRetry);

            $requestTime = Carbon::now();

            try
            {
                $startTime = microtime(true);

                $bankResponse = $this->app->mozart->sendMozartRequest(self::MOZART_NAMESPACE,
                                                                      $this->getChannel(),
                                                                      self::MOZART_ACTION,
                                                                      $requestData,
                                                                      $this->version);

                $endTime = microtime(true);

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_RBL_V2_RESPONSE_TIME,
                    [
                        'merchant_id'         => optional($this->basDetails)->getMerchantId(),
                        'channel'             => $this->channel,
                        'account_number'      => $this->accountNumber,
                        'response_time'       => $endTime - $startTime
                    ]);

                $this->modifyBankResponseV2($bankResponse);

                $this->validateMozartResponseV2($bankResponse);
            }
            catch (\Throwable $ex)
            {
                if ($ex instanceof Exception\GatewayErrorException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST_FAILED_V2,
                        [
                            Entity::MERCHANT_ID    => optional($this->basDetails)->getMerchantId(),
                            Entity::ACCOUNT_NUMBER => $this->accountNumber,
                            Entity::CHANNEL        => $this->channel,
                        ]);

                    $statementRetry++ ;

                    if ($statementRetry <= $statementRetryLimit )
                    {
                        $isRetry = true;

                        $fetchMore = true;

                        continue;
                    }
                }
                if ($ex instanceof Exception\BadRequestValidationFailureException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_INVALID_MOZART_RESPONSE_V2,
                        [
                            Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                            Entity::CHANNEL             => $this->channel,
                            Entity::MERCHANT_ID         => optional($this->basDetails)->getMerchantId(),
                            RequestResponseFields::DATA => $bankResponse ?? [],
                        ]);
                }

                throw $ex;
            }

            $isRetry = false;

            $formattedResponse = $this->getFormattedResponseV2($bankResponse[Fields::DATA], $recordNumber, $requestTime);

            if ((count($formattedResponse) > 0) and ($this->savePaginationKey === true))
            {
                $paginationKeyToSave =  last($formattedResponse)[Entity::POSTED_DATE] . '_' .
                                        $bankResponse[Fields::DATA][Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE][Fields::HEADER][Fields::NEXT_KEY];

                $this->basDetails->setPaginationKey($paginationKeyToSave);
            }

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

            $fetchMore = $this->hasMoreDataV2(count($formattedResponse), $requestData);

        } while (($fetchMore === true) and ($attemptCount < $attemptLimit));

        // Adding a dispatch delay of 120 seconds as account statement process takes
        // around 1 min for processing and save.
        if (count($formattedResponse) == $this->rblAccountStatementV2MaxNumberOfRecords)
        {
            $delay = self::RBL_ACCOUNT_STATEMENT_DISPATCH_DELAY;

            $data = [
                BasDetails\Entity::CHANNEL        => $this->channel,
                BasDetails\Entity::ACCOUNT_NUMBER => $this->accountNumber,
                BasDetails\Entity::BALANCE_ID     => $this->basDetails->getBalanceId(),
                'delay'                           => $delay
            ];

            (new BankingAccountStatementCore)->dispatchBankingAccountStatementJob($data, $this->basDetails->getAccountType());
        }

        return $finalFormattedResponse;
    }

    public function sendRequestToFetchStatement(array $input)
    {
        // Retry logic is placed to retry when gateway exceptions are caught. Retry limit is in place for upper bound.
        $statementRetry      = 0;
        $statementRetryLimit = 1;
        $attemptCount        = 0;
        $attemptLimit        = $input['attempt_limit'] ?? 1;

        if (array_key_exists(Entity::FROM_DATE, $input) === true)
        {
            $this->fromDate = $input[Entity::FROM_DATE];
        }

        if (array_key_exists(Entity::TO_DATE, $input) === true)
        {
            $this->toDate = $input[Entity::TO_DATE];
        }

        $isRetry = false;

        $formattedResponse = [];

        $finalFormattedResponse = [];

        $paginationKeyToSave = null;

        $requestData = $this->getRequestDataForMozartV2($input);

        $this->rblAccountStatementV2MaxNumberOfRecords = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS]);

        if (empty($this->rblAccountStatementV2MaxNumberOfRecords) === true)
        {
            $this->rblAccountStatementV2MaxNumberOfRecords = self::DEFAULT_RBL_ACCOUNT_STATEMENT_V2_MAX_NUMBER_OF_RECORDS;
        }

        $recordNumber = 1;

        if ((array_key_exists('pagination_key', $input) === true) and
            ($input['pagination_key'] !== null))
        {
            $paginationKeyToSave = $input['pagination_key'];
        }

        do
        {
            $paginationKey = substr($paginationKeyToSave, 11);

            // Rbl api supports 2 formats of requests.
            //     1. using from_date and to_date in api request
            //     2. using next_key in api request.
            // If for a merchant pagination key is not available, we use 1st format else 2nd format is used.
            // Bank will be returning next_key in every successful api call.
            $this->modifyRequestForFetchingStatement($requestData, $paginationKey, $isRetry);

            $requestTime = Carbon::now();

            try
            {
                $startTime = microtime(true);

                $this->trace->count(Metric::BAS_RECON_MOZART_REQUESTS_TOTAL, [
                    'gateway'   => $this->getChannel(),
                    'action'    => self::MOZART_ACTION,
                    'version'   => $this->version,
                    'namespace' => self::MOZART_NAMESPACE,
                ]);

                // Increasing the timeout here to 80 secs because sometimes mozart times more time to
                // load the response
                $bankResponse = $this->app->mozart->sendMozartRequest(self::MOZART_NAMESPACE,
                                                                      $this->getChannel(),
                                                                      self::MOZART_ACTION,
                                                                      $requestData,
                                                                      $this->version,
                                                                      false,
                                                                      80);

                $endTime = microtime(true);

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_RBL_V2_RESPONSE_TIME,
                    [
                        'merchant_id'    => $this->basDetails->getMerchantId(),
                        'channel'        => $this->channel,
                        'account_number' => $this->accountNumber,
                        'response_time'  => $endTime - $startTime
                    ]);

                $this->modifyBankResponseV2($bankResponse);

                $this->validateMozartResponseV2($bankResponse);
            }
            catch (\Throwable $ex)
            {
                if ($ex instanceof Exception\GatewayErrorException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST_FAILED_V2,
                        [
                            Entity::ACCOUNT_NUMBER => $this->accountNumber,
                            Entity::CHANNEL        => $this->channel,
                        ]);

                    $statementRetry++;

                    if ($statementRetry <= $statementRetryLimit)
                    {
                        $isRetry = true;

                        $fetchMore = true;

                        continue;
                    }
                }
                if ($ex instanceof Exception\BadRequestValidationFailureException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_INVALID_MOZART_RESPONSE_V2,
                        [
                            Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                            Entity::CHANNEL             => $this->channel,
                            RequestResponseFields::DATA => $bankResponse ?? [],
                        ]);
                }


                // If gateway exception after retries and validation exception is thrown,
                // then we delete the job and send a slack notification.
                throw $ex;
            }

            $isRetry = false;

            $formattedResponse = $this->getFormattedResponseV2($bankResponse[Fields::DATA], $recordNumber, $requestTime);

            if ((count($formattedResponse) > 0) and ($this->savePaginationKey === true))
            {
                $paginationKeyToSave = last($formattedResponse)[Entity::POSTED_DATE] . '_' .
                                       $bankResponse[Fields::DATA][Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE][Fields::HEADER][Fields::NEXT_KEY];

            }

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

            $fetchMore = $this->hasMoreDataV2(count($formattedResponse), $requestData);

        } while (($fetchMore === true) and ($attemptCount < $attemptLimit));

        return [$finalFormattedResponse, $fetchMore, $paginationKeyToSave];
    }

    // In case of pagination key already stored in DB which don't have timestamp attached,
    // we will pick last transaction and append it's posted_date.
    protected function updatePaginationKeyIfRequired()
    {
        $paginationKey = $this->basDetails->getPaginationKey();

        if (($paginationKey === null) or
            (preg_match(self::PAGINATION_KEY_REGEX, $paginationKey,$matches) === 1))
        {
            return;
        }

        $bankTransaction = $this->getLastBankTransaction();

        if (empty($bankTransaction) === true)
        {
            return;
        }

        $newPaginationKey = strval($bankTransaction->getPostedDate()) . '_' . $paginationKey;

        $this->basDetails->setPaginationKey($newPaginationKey);

        $this->repo->banking_account_statement_details->saveOrFail($this->basDetails);
    }

    protected function fetchPaginationKey()
    {
        $paginationKey = $this->basDetails->getPaginationKey();

        if (preg_match(self::PAGINATION_KEY_REGEX, $paginationKey,$matches) === 1)
        {
            $timestamp = intval(substr($paginationKey, 0, 10));

            if ($timestamp < Carbon::today(Timezone::IST)->subWeeks(self::PAGINATION_KEY_TTL_IN_WEEKS)->getTimestamp())
            {
                return null;
            }

            return substr($paginationKey, 11);
        }

        return $paginationKey;
    }

    protected function hasMoreDataV2(int $numberTransactions, array $request)
    {
        if ($numberTransactions >= $this->rblAccountStatementV2MaxNumberOfRecords)
        {
            return true;
        }

        if (array_key_exists(Fields::NEXT_KEY, $request[Fields::ATTEMPT]) === false)
        {
            $previousStatementEndTime = $this->getTimestampFromDateString($request[Fields::ATTEMPT][Fields::TO_DATE]);

            if ($previousStatementEndTime < Carbon::today(Timezone::IST)->getTimestamp())
            {
                return true;
            }
        }

        return false;
    }

    // Rbl V2 api supports 2 formats of requests.
    //     1. using from_date and to_date in api request
    //     2. using next_key in api request.
    // If for a merchant pagination key is not available, we use 1st format else 2nd format is used.
    // Bank will be returning next_key in every successful api call.
    protected function selectApiAndModifyRequest(array & $request, $paginationKey, bool $isRetry = false)
    {
        // $isRetry is set when a request fails and we want to retry the same request. In such scenario same request is returned.
        if ($isRetry === true)
        {
            return;
        }

        if (empty($paginationKey) === false)
        {
            $request[Fields::ATTEMPT][Fields::NEXT_KEY] = $paginationKey;

            unset($request[Fields::ATTEMPT][Fields::TO_DATE]);

            unset($request[Fields::ATTEMPT][Fields::FROM_DATE]);

            return;
        }

        $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

        // Bank has kept a constraint that max difference between from_date and to_date can be 365 days.
        $allowedDateDiff = 365 * $secondsPerDay;

        $statementEndTime = Carbon::now()->getTimestamp();

        // In case there are no transactions for the merchant in our DB then we will fetch statement from start of financial year.
        $statementStartTime = $this->getStartTime()->getTimestamp();

        if (array_key_exists(Fields::TO_DATE, $request[Fields::ATTEMPT]) === true)
        {
            $statementStartTime = $this->getTimestampFromDateString($request[Fields::ATTEMPT][Fields::TO_DATE]) + $secondsPerDay;
        }
        else
        {
            $bankTransaction = $this->getLastBankTransaction();

            if (empty($bankTransaction) === false)
            {
                $statementStartTime = $bankTransaction->getTransactionDate();

                // from date should be one day before the txn date of the last record.
                // This came up in rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
                $statementStartTime -= $secondsPerDay;
            }
        }

        $request[Fields::ATTEMPT][Fields::FROM_DATE] = $this->getDateTimeStringFromTimestamp(
            $statementStartTime,
            self::STATEMENT_START_TIME_DATE_FORMAT_V2);

        $request[Fields::ATTEMPT][Fields::TO_DATE] = $this->getDateTimeStringFromTimestamp(
            min($statementEndTime, $statementStartTime + $allowedDateDiff),
            self::STATEMENT_START_TIME_DATE_FORMAT_V2);
    }

    protected function modifyRequestForFetchingStatement(array & $request, $paginationKey, bool $isRetry = false)
    {
        // $isRetry is set when a request fails and we want to retry the same request. In such scenario same request is returned.
        if ($isRetry === true)
        {
            return;
        }

        if (empty($paginationKey) === false)
        {
            $request[Fields::ATTEMPT][Fields::NEXT_KEY] = $paginationKey;

            unset($request[Fields::ATTEMPT][Fields::TO_DATE]);

            unset($request[Fields::ATTEMPT][Fields::FROM_DATE]);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PAGINATION_DETAILS,
                               [
                                   Entity::CHANNEL                  => Channel::RBL,
                                   RequestResponseFields::NEXT_KEY  => $paginationKey,
                                   RequestResponseFields::FROM_DATE => $request[Fields::ATTEMPT][Fields::FROM_DATE] ?? null,
                                   RequestResponseFields::TO_DATE   => $request[Fields::ATTEMPT][Fields::TO_DATE] ?? null,
                               ]);

            return;
        }

        $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

        // Bank has kept a constraint that max difference between from_date and to_date can be 365 days.
        $allowedDateDiff = 365 * $secondsPerDay;

        $request[Fields::ATTEMPT][Fields::FROM_DATE] = $this->getDateTimeStringFromTimestamp(
            $this->fromDate,
            self::STATEMENT_START_TIME_DATE_FORMAT_V2);

        $request[Fields::ATTEMPT][Fields::TO_DATE] = $this->getDateTimeStringFromTimestamp(
            min($this->toDate, $this->fromDate + $allowedDateDiff),
            self::STATEMENT_START_TIME_DATE_FORMAT_V2);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PAGINATION_DETAILS,
                           [
                               Entity::CHANNEL                  => Channel::RBL,
                               RequestResponseFields::NEXT_KEY  => $paginationKey,
                               RequestResponseFields::FROM_DATE => $request[Fields::ATTEMPT][Fields::FROM_DATE] ?? null,
                               RequestResponseFields::TO_DATE   => $request[Fields::ATTEMPT][Fields::TO_DATE] ?? null,
                           ]);
    }

    protected function modifyBankResponseV2(array & $response)
    {
        $encodedTxnDetails = $response[Fields::DATA][Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE]
                               [Fields::ACCOUNT_STATEMENT_DATA][Fields::FILE_DATA];

        $decodedTxnDetails = [];

        // when no records are found, the file data field is empty.
        if ($encodedTxnDetails === null)
        {
            $response[Fields::DATA][Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE]
                     [Fields::ACCOUNT_STATEMENT_DATA][Fields::FILE_DATA] = $decodedTxnDetails;
            return;
        }

        // bank sends base64 encoded data of transactions.
        $decodedTxnDetailsString = base64_decode($encodedTxnDetails);

        $transactionsData = explode("\n",$decodedTxnDetailsString);

        // always first line is list of fields, hence removing that from further processing.
        if($transactionsData[0] === self::ACCOUNT_STATEMENT_V2_BANK_RESPONSE_HEADER_LINE)
        {
            unset($transactionsData[0]);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException("response header line incorrect", null, $transactionsData[0]);
        }

        foreach ($transactionsData as $transactionData)
        {
            $decodedTransaction = explode("," , $transactionData);

            //there should be 9 fields for every transaction.
            if (sizeof($decodedTransaction) !== 9)
            {
                throw new Exception\BadRequestValidationFailureException("number of fields in a statement row not equal to 9", null, $transactionData);
            }

            // mapping from field names rbl use to that used in rzp.
            $transactionDetail[Fields::TRANSACTION_ID_RESPONSE]   = $decodedTransaction[0];    // TRAN_ID
            $transactionDetail[Fields::TRANSACTION_SERIAL_NUMBER] = $decodedTransaction[1];    // PTSN_NUM
            $transactionDetail[Fields::TRANSACTION_DATE]          = $decodedTransaction[2];    // TRAN_DATE
            $transactionDetail[Fields::TRANSACTION_POSTED_DATE]   = $decodedTransaction[3];    // PSTD_DATE
            $transactionDetail[Fields::TRANSACTION_CATEGORY]      = $decodedTransaction[4];    // TRAN_TYPE
            $transactionDetail[Fields::TRANSACTION_TYPE]          = $decodedTransaction[5];    // C/D
            $transactionDetail[Fields::TRANSACTION_DESCRIPTION]   = $decodedTransaction[6];    // TRAN_PARTICULAR
            $transactionDetail[Fields::TRANSACTION_AMOUNT]        = $decodedTransaction[7];    // TRAN_AMT
            $transactionDetail[Fields::TRANSACTION_BALANCE]       = $decodedTransaction[8];    // TRAN_BALANCE

            // Validation on each record is moved here as number of records can go upto 5000 and having a validation on an array of 5000 records is time consuming.
            // Slack thread for ref.: https://razorpay.slack.com/archives/C01CX0EC34M/p1628683569061000?thread_ts=1628225601.039500&cid=C01CX0EC34M
            $this->validateMozartResponseRecordV2($transactionDetail);

            array_push($decodedTxnDetails , $transactionDetail);
        }

        $response[Fields::DATA][Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE]
                 [Fields::ACCOUNT_STATEMENT_DATA][Fields::FILE_DATA] = $decodedTxnDetails;
    }

    protected function validateMozartResponse(array $response)
    {
        (new Validator)->validateInput('rbl_response', $response['data']);
    }

    protected function validateMozartResponseV2(array $response)
    {
        (new Validator)->validateInput('rbl_statement_fetch_response_v2', $response['data']);
    }

    protected function validateMozartResponseRecordV2(array $record)
    {
        (new Validator)->validateInput('rbl_statement_fetch_response_v2_record', $record);
    }

    protected function getRequestDataForMozart(array $input, array $lastTransaction)
    {
        $basCredentials = (new BankingAccountService())->fetchCredentialsFromApiAndBas(
            $this->basDetails->getMerchantId(),
            $this->channel,
            $this->accountNumber
        );

        $data = [
            Fields::ATTEMPT          => [
                Fields::ID               => (string) Carbon::now()->timestamp,
                Fields::FROM_DATE        => $this->getStatementStartTime(),
                Fields::TO_DATE          => Carbon::today(Timezone::IST)->toDateString(),
                Fields::TRANSACTION_TYPE => TransactionType::BOTH,
            ],
            Fields::SOURCE_ACCOUNT   => [
                Fields::ACCOUNT_NUMBER => $this->accountNumber,
                Fields::CREDENTIALS    => [
                    Fields::AUTH_USERNAME => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_USERNAME],
                    Fields::AUTH_PASSWORD => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_PASSWORD],
                    Fields::CLIENT_ID     => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_ID],
                    Fields::CLIENT_SECRET => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_SECRET],
                    Fields::CORP_ID       => $basCredentials[Fields::CREDENTIALS][Fields::CORP_ID],
                ]
            ],
            Fields::LAST_TRANSACTION => $this->getPaginationDataForRequest($lastTransaction),
        ];

        return $data;
    }

    public function getCredentialsForMozartSessionToken(array $input)
    {
        $basCredentials = (new BankingAccountService())->fetchCredentialsFromApiAndBas(
            $this->basDetails->getMerchantId(),
            $this->channel,
            $this->accountNumber
        );
        $data = [
            Fields::SOURCE_ACCOUNT   => [
                Fields::CREDENTIALS    => [
                    Fields::AUTH_USERNAME => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_USERNAME],
                    Fields::AUTH_PASSWORD => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_PASSWORD],
                    Fields::CLIENT_ID     => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_ID],
                    Fields::CLIENT_SECRET => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_SECRET],
                    Fields::CORP_ID       => $basCredentials[Fields::CREDENTIALS][Fields::CORP_ID],
                ]
            ],
        ];
        return $data;
    }

    protected function getRequestDataForMozartV2(array $input)
    {
        $basCredentials = (new BankingAccountService())->fetchCredentialsFromApiAndBas(
            $this->basDetails->getMerchantId(),
            $this->channel,
            $this->accountNumber
        );

        return [
            Fields::ATTEMPT        => [
                Fields::ID               => (string) Carbon::now()->timestamp,
                Fields::TRANSACTION_TYPE => TransactionType::BOTH,
            ],
            Fields::SOURCE_ACCOUNT => [
                Fields::ACCOUNT_NUMBER => $this->accountNumber,
                Fields::CREDENTIALS    => [
                    Fields::AUTH_USERNAME => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_USERNAME],
                    Fields::AUTH_PASSWORD => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_PASSWORD],
                    Fields::CLIENT_ID     => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_ID],
                    Fields::CLIENT_SECRET => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_SECRET],
                    Fields::CORP_ID       => $basCredentials[Fields::CREDENTIALS][Fields::CORP_ID],
                ]
            ]
        ];
    }
    /**
     * Check last txn in BankingAccountStatement
     * If found, fetch lastTxn timestamp
     * else find acc opening timestamp in BankingAccount and use that
     *
     * @param BankingAccountEntity $bankingAccount
     *
     * @return string
     */
    protected function getStatementStartTime()
    {
        // TODO: Might want to use the transactions tables for this instead of BAS table.
        $bankTransaction = $this->getLastBankTransaction();

        // In case there are no transactions for the merchant in our DB then we will fetch statement from start of financial year.
        $startTime = $this->getStartTime()->getTimestamp();

        $secondsInDay = Carbon::SECONDS_PER_MINUTE * Carbon::MINUTES_PER_HOUR * Carbon::HOURS_PER_DAY;

        if (empty($bankTransaction) === false)
        {
            $startTime = $bankTransaction->getTransactionDate();

            // from date should be one day before the txn date of the last record.
            // This came up in rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
            $startTime -= $secondsInDay;
        }

        $startTime = $this->getDateTimeStringFromTimestamp($startTime, self::STATEMENT_START_TIME_DATE_FORMAT);

        return $startTime;
    }

    protected function getPaginationDataForRequest($lastTransaction)
    {
        // We use `last` to get the `lastTransaction` in the caller,
        // which returns back `false` if nothing is present.
        if (($lastTransaction === false) or
            (empty($lastTransaction) === true))
        {
            return [];
        }

        return [
            Fields::BALANCE => $this->getFormattedBalanceForRequest($lastTransaction),
            Fields::AMOUNT => $this->getFormattedAmountForRequest($lastTransaction),
            Fields::CURRENCY => $this->getFormattedCurrencyForRequest($lastTransaction),
            Fields::POSTED_DATE => $this->getFormattedPostedDateForRequest($lastTransaction),
            Fields::TRANSACTION_DATE => $this->getFormattedTransactionDateForRequest($lastTransaction),
            Fields::TRANSACTION_ID => $this->getFormattedBankTransactionIdForRequest($lastTransaction),
            Fields::SERIAL_NUMBER => $this->getFormattedSerialNumberForRequest($lastTransaction),
        ];
    }

    protected function getFormattedBalanceForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::BALANCE];

        $formattedData = number_format($data / 100, 2, '.', '');

        return (string) $formattedData;
    }

    protected function getFormattedAmountForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::AMOUNT];

        $formattedData = number_format($data / 100, 2, '.', '');

        return (string) $formattedData;
    }

    protected function getFormattedCurrencyForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::CURRENCY];

        return $data;
    }

    protected function getFormattedPostedDateForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::POSTED_DATE];

        $formattedData = $this->getDateTimeStringFromTimestamp($data, self::DATE_FORMAT);

        return $formattedData;
    }

    protected function getFormattedTransactionDateForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::TRANSACTION_DATE];

        $formattedData = $this->getDateTimeStringFromTimestamp($data, self::DATE_FORMAT);

        return $formattedData;
    }

    protected function getFormattedBankTransactionIdForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::BANK_TRANSACTION_ID];

        $formattedData = trim($data);

        return $formattedData;
    }

    protected function getFormattedSerialNumberForRequest(array $bankTxn)
    {
        $data = $bankTxn[Entity::BANK_SERIAL_NUMBER];

        $formattedData = trim($data);

        return $formattedData;
    }

    public function getFormattedResponse(array $responseData, int & $recordNumber, Carbon $requestTime)
    {
        $responseBody = $responseData[Fields::PAYMENT_GENERIC_RESPONSE][Fields::BODY];

        $transactionsData = $responseBody[Fields::TRANSACTION_DETAILS] ?? [];

        $transactions = [];

        $offset = $this->getOffsetForSavingRecords();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_RESPONSE_COUNT,
            [
                'txn_count' => count($transactionsData)
            ]);

        // This is used to do correction to closing balance in bank's response.
        // slack incident thread: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
        $closingBalanceDiff = 0;

        $closingBalanceDiffArray = (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_CLOSING_BALANCE_DIFF]);

        if (array_key_exists($this->accountNumber, $closingBalanceDiffArray) === true)
        {
            $closingBalanceDiff = $closingBalanceDiffArray[$this->accountNumber];
        }

        foreach ($transactionsData as $transactionData)
        {
            // Due to discrepancies on bank side where new records can appear in few seconds, we prefer not to save
            // latest records within time range set using $offset to maintain order.
            // This came up in rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
            $allowRecordToSave = $this->allowRecordToSave($requestTime, $offset, $transactionData);

            //
            // Logging it here even though it's logged in Mozart Service since that
            // log is most probably going to be truncated due to large amount of data.
            //
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_DATA,
                               [
                                   'record_no'            => $recordNumber,
                                   'record_saved'         => $allowRecordToSave,
                                   Entity::CHANNEL        => $this->getChannel(),
                                   Entity::ACCOUNT_NUMBER => $this->accountNumber
                               ] + $transactionData
            );

            $recordNumber++;

            if ($allowRecordToSave === false)
            {
                continue;
            }

            $transactions[] = [
                Entity::CHANNEL             => $this->getChannel(),
                Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                Entity::BANK_TRANSACTION_ID => $this->getBankTransactionIdFromResponse($transactionData),
                Entity::AMOUNT              => $this->getAmountFromResponse($transactionData),
                Entity::CURRENCY            => $this->getCurrencyFromResponse($transactionData),
                Entity::TYPE                => $this->getTypeFromResponse($transactionData),
                Entity::DESCRIPTION         => $this->getDescriptionFromResponse($transactionData),
                Entity::CATEGORY            => $this->getCategoryFromResponse($transactionData),
                Entity::BANK_SERIAL_NUMBER  => $this->getSerialNumberFromResponse($transactionData),
                Entity::BANK_INSTRUMENT_ID  => $this->getInstrumentIdFromResponse($transactionData),
                Entity::BALANCE             => $this->getBalanceFromResponse($transactionData, $closingBalanceDiff),
                Entity::BALANCE_CURRENCY    => $this->getBalanceCurrencyFromResponse($transactionData),
                Entity::POSTED_DATE         => $this->getPostedDateFromResponse($transactionData),
                Entity::TRANSACTION_DATE    => $this->getTransactionDateFromResponse($transactionData),
            ];
        }

        return $transactions;
    }

    public function getFormattedResponseV2(array $responseData, int & $recordNumber, Carbon $requestTime)
    {
        $responseBody = $responseData[Fields::FETCH_ACCOUNT_STATEMENT_RESPONSE][Fields::ACCOUNT_STATEMENT_DATA];

        $transactionsData = $responseBody[Fields::FILE_DATA] ?? [];

        $transactions = [];

        $offset = $this->getOffsetForSavingRecords();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_RESPONSE_COUNT_V2,
            [
                Entity::ACCOUNT_NUMBER => $this->accountNumber,
                Entity::CHANNEL        => $this->channel,
                'txn_count'            => count($transactionsData),
                Entity::MERCHANT_ID    => optional($this->basDetails)->getMerchantId(),
            ]);

        foreach ($transactionsData as $transactionData)
        {
            // Due to discrepancies on bank side where new records can appear in few seconds, we prefer not to save
            // latest records within time range set using $offset to maintain order.
            // This came up in rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
            $allowRecordToSave = $this->allowRecordToSave($requestTime, $offset, $transactionData);

            //
            // Logging it here even though it's logged in Mozart Service since that
            // log is most probably going to be truncated due to large amount of data.
            //
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_DATA_V2,
                               [
                                   'record_no'            => $recordNumber,
                                   'record_saved'         => $allowRecordToSave,
                                   Entity::CHANNEL        => $this->getChannel(),
                                   Entity::ACCOUNT_NUMBER => $this->accountNumber,
                                   Entity::MERCHANT_ID    => optional($this->basDetails)->getMerchantId(),
                               ] + $transactionData
            );

            $recordNumber++;

            if ($allowRecordToSave === false)
            {
                $this->savePaginationKey = false;

                continue;
            }

            $transactions[] = [
                Entity::CHANNEL             => $this->getChannel(),
                Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                Entity::BANK_TRANSACTION_ID => $this->getBankTransactionIdFromResponse($transactionData),
                Entity::BANK_SERIAL_NUMBER  => $this->getSerialNumberFromResponse($transactionData),
                Entity::AMOUNT              => $this->getAmountFromResponseV2($transactionData),
                Entity::CURRENCY            => Currency::INR,
                Entity::TYPE                => $this->getTypeFromResponseV2($transactionData),
                Entity::DESCRIPTION         => $this->getDescriptionFromResponseV2($transactionData),
                Entity::CATEGORY            => $this->getCategoryFromResponse($transactionData),
                Entity::BALANCE             => $this->getBalanceFromResponseV2($transactionData),
                Entity::BALANCE_CURRENCY    => Currency::INR,
                Entity::POSTED_DATE         => $this->getPostedDateFromResponse($transactionData),
                Entity::TRANSACTION_DATE    => $this->getTransactionDateFromResponseV2($transactionData),
                Entity::BANK_INSTRUMENT_ID  => ""
            ];
        }

        return $transactions;
    }

    protected function getAmountFromResponseV2(array $transaction): int
    {
        $amount = $transaction[Fields::TRANSACTION_AMOUNT];

        $amount = intval(number_format($amount * 100, 0, '.', ''));

        return $amount;
    }

    public function getTypeFromResponseV2(array $transaction): string
    {
        $type = $transaction[Fields::TRANSACTION_TYPE];

        if ($type === TransactionType::CREDIT)
        {
            return Type::CREDIT;
        }
        else if ($type === TransactionType::DEBIT)
        {
            return Type::DEBIT;
        }

        throw new Exception\IntegrationException(
            "Invalid txnType found as $type",
            null,
            [
                'bank_ref_no'   => $transaction[Fields::TRANSACTION_ID_RESPONSE],
            ]);
    }

    protected function getDescriptionFromResponseV2($transaction)
    {
        return trim($transaction[Fields::TRANSACTION_DESCRIPTION]);
    }

    protected function getBalanceFromResponseV2(array $transaction): int
    {
        $amount = $transaction[Fields::TRANSACTION_BALANCE];

        $amount = intval(number_format($amount * 100, 0, '.', ''));

        return $amount;
    }

    protected function getTransactionDateFromResponseV2(array $transaction)
    {
        $timestamp = $this->getTimestampFromDateString(
            $transaction[Fields::TRANSACTION_DATE]);

        return $timestamp;
    }

    protected function allowRecordToSave(Carbon $requestTime, int $offset, array $transactionData)
    {
        // https://stackoverflow.com/questions/34413877/php-carbon-class-changing-my-original-variable-value
        if ($requestTime->copy()->subSeconds($offset)->getTimestamp() > $this->getPostedDateFromResponse($transactionData))
        {
            return true;
        }

        return false;
    }

    protected function getOffsetForSavingRecords()
    {
        $offset = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY]);

        if (empty($offset) === true)
        {
            $offset = self::OFFSET_FOR_SAVING_RECORD;
        }

        return $offset;
    }

    protected function getBankTransactionIdFromResponse(array $transaction): string
    {
        return trim($transaction[Fields::TRANSACTION_ID_RESPONSE]);
    }

    protected function getAmountFromResponse(array $transaction): int
    {
        $amount = $transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_AMOUNT][Fields::AMOUNT_VALUE];

        $amount = intval(number_format($amount * 100, 0, '.', ''));

        return $amount;
    }

    protected function getCurrencyFromResponse(array $transaction): string
    {
        return $transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_AMOUNT][Fields::CURRENCY_CODE] ??
               Currency::INR;
    }

    public function getTypeFromResponse(array $transaction): string
    {
        $type = $transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_TYPE_RESPONSE];

        if ($type === TransactionType::CREDIT)
        {
            return Type::CREDIT;
        }
        else if ($type === TransactionType::DEBIT)
        {
            return Type::DEBIT;
        }

        throw new Exception\IntegrationException(
            "Invalid txnType found as $type",
            null,
            [
                'bank_ref_no'   => $transaction[Fields::TRANSACTION_ID_RESPONSE],
            ]);
    }

    protected function getDescriptionFromResponse($transaction)
    {
        return trim($transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_DESCRIPTION]);
    }

    protected function getCategoryFromResponse(array $transaction): string
    {
        $txnCategory = $transaction[Fields::TRANSACTION_CATEGORY];

        $internalCategory = TransactionCategory::getInternalCategory($txnCategory);

        if ($internalCategory === Category::OTHERS)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_UNEXPECTED_VALUE,
                [
                    'field'         => Entity::CATEGORY,
                    'value'         => $txnCategory,
                    'bank_ref_no'   => $transaction[Fields::TRANSACTION_ID_RESPONSE],
                ]);
        }

        return $internalCategory;
    }

    protected function getSerialNumberFromResponse(array $transaction): string
    {
        return trim($transaction[Fields::TRANSACTION_SERIAL_NUMBER]);
    }

    protected function getInstrumentIdFromResponse(array $transaction): string
    {
        return trim($transaction[Fields::TRANSACTION_SUMMARY][Fields::INSTRUMENT_ID]);
    }

    protected function getBalanceFromResponse(array $transaction, int $closingBalanceDiff = 0): int
    {
        $amount = $transaction[Fields::TRANSACTION_BALANCE][Fields::AMOUNT_VALUE];

        $amount = intval(number_format($amount * 100, 0, '.', ''));

        $amount -= $closingBalanceDiff;

        return $amount;
    }

    protected function getBalanceCurrencyFromResponse(array $transaction): string
    {
        return $transaction[Fields::TRANSACTION_BALANCE][Fields::CURRENCY_CODE] ?? Currency::INR;
    }

    protected function getPostedDateFromResponse(array $transaction)
    {
        $timestamp = $this->getTimestampFromDateString($transaction[Fields::TRANSACTION_POSTED_DATE]);

        return $timestamp;
    }

    protected function getTransactionDateFromResponse(array $transaction)
    {
        $timestamp = $this->getTimestampFromDateString(
                                    $transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_DATE_RESPONSE]);

        return $timestamp;
    }

    public function hasMoreData($bankResponse)
    {
        $responseBody = $bankResponse[Fields::DATA][Fields::PAYMENT_GENERIC_RESPONSE][Fields::BODY];

        $hasMoreData = $responseBody[Fields::HAS_MORE_DATA];

        return ($hasMoreData === 'Y');
    }

    public function getUtrForChannel(PublicEntity $basEntity)
    {
        $description = $basEntity->getDescription();

        if ($basEntity->isTypeCredit() === true)
        {
            if ($this->matchDescriptionFor(self::IMPS_CREDIT_REGEX, $description) === true)
            {
                $regex = self::IMPS_CREDIT_REGEX;
            }

            else
            {
                $regex = self::CREDIT_REGEX;
            }
        }
        else
        {
            $regex = self::IMPS_DEBIT_REGEX;

            if ($this->matchDescriptionFor(self::NEFT_RTGS_DEBIT_REGEX, $description) === true)
            {
                $regex = self::NEFT_RTGS_DEBIT_REGEX;
            }

            if ($this->matchDescriptionFor(self::UPI_DEBIT_REGEX, $description) === true)
            {
                $regex = self::UPI_DEBIT_REGEX;
            }
        }

        $match = preg_match($regex, $description, $matches);

        if ($match === 1)
        {
            $match = (($regex === self::CREDIT_REGEX) or
                      ($regex === self::NEFT_RTGS_DEBIT_REGEX) or
                      ($regex === self::UPI_DEBIT_REGEX)) ? $matches[2] : $matches[1];
        }

        // Could be an empty string match
        if (empty($match) === false)
        {
            return $match;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_NO_REGEX_MATCH_FOUND_FOR_UTR,
            [
                Entity::TYPE           => $basEntity->getType(),
                Entity::CHANNEL        => Channel::RBL,
                Entity::MERCHANT_ID    => $basEntity->getMerchantId(),
                Entity::DESCRIPTION    => $basEntity->getDescription(),
                'bas_id'               => $basEntity->getId()
            ]
        );

        return null;
    }

    protected function matchDescriptionFor(string $regex, string $description)
    {
        $match = preg_match($regex, $description, $matches);

        if ($match === 1)
        {
            return true;
        }

        return false;
    }

    public function compareAndReturnMatchedBASFromFetchedStatements($fetchedStatements = []): array
    {
        $matchedBASFromBank = new Entity();

        $existingBAS = new Entity();

        $count = count($fetchedStatements);

        while ($count > 0)
        {
            $basEntityFromBank = (new Entity)->build($fetchedStatements[$count - 1]);

            $existingBAS = $this->repo->banking_account_statement->getExistingUniqueRecord(
                $basEntityFromBank->getBankTransactionId(),
                $basEntityFromBank->getAccountNumber(),
                $basEntityFromBank->getPostedDate(),
                $basEntityFromBank->getAmount(),
                $basEntityFromBank->getType(),
                $basEntityFromBank->getChannel(),
                $basEntityFromBank->getSerialNumber()
            );

            if ($existingBAS !== null)
            {
                $matchedBASFromBank = $basEntityFromBank;

                break;
            }

            $count--;
        }

        return [$matchedBASFromBank, $existingBAS];
    }
}
