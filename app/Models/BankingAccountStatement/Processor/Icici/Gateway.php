<?php

namespace RZP\Models\BankingAccountStatement\Processor\Icici;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccountStatement\Processor\Source;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\BankingAccountStatement\Processor\Base as BaseProcessor;
use RZP\Models\BankingAccountStatement\Core as BankingAccountStatementCore;
use RZP\Models\BankingAccountStatement\Processor\Icici\RequestResponseFields as Fields;

class Gateway extends BaseProcessor
{
    const DATE_TIME_FORMAT = 'd-m-Y H:i:s';

    const DATE_FORMAT = 'd-m-Y';

    const DEFAULT_ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT = 3;

    const ICICI_ACCOUNT_STATEMENT_DISPATCH_DELAY = 120;

    const DEFAULT_ICICI_STATEMENT_FETCH_RETRY_LIMIT = 3;

    const ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT = 200;

    /**
     * IMPS credit remarks => "MMT/IMPS/105400750777/TestIciciProd06/harsh     /HDFC0000004",
     */
    const CREDIT_REGEX_IMPS = '/^MMT\/IMPS\/(.*?)\//';

    /**
     * NEFT credit remarks => "NEFT-RETURN-23629988951DC-AYUSH MITTAL-ACCOUNT DOES NOT EXIST  R03"
     * utr is 023629988951
     */
    const CREDIT_REGEX_NEFT = '/^NEFT-RETURN-(.*?)-/';

    /**
     * IMPS debit remarks => "MMT/IMPS/105400750777/TestIciciProd06/harsh     /HDFC0000004",
     */
    const DEBIT_REGEX_IMPS = '/^MMT\/IMPS\/(.*?)\//';

    /**
     * NEFT debit remarks => "INF/NEFT/023629988951/SBIN0050103/TestIciciProd03/Ayush Mittal",
     */
    const DEBIT_REGEX_NEFT = '/^INF\/NEFT\/(.*?)\//';

    /**
     * IFT debit remarks => "INF/INFT/023652565741/TestIciciProd06/Raja",
     */
    const DEBIT_REGEX_IFT = '/^INF\/INFT\/(.*?)\//';

    /**
     * RTGS debit remarks => "RTGS/ICICR42021042600532758/YESB0000022/RZPX pvtltd",
     */
    const DEBIT_REGEX_RTGS = '/^RTGS\/(.*?)\//';

    protected $mozartNonRetriableCode = [
        TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTIONS_DO_NOT_EXIST_WITH_THE_GIVEN_CRITERIA,
    ];

    const MAX_ATTEMPTS_TO_FETCH_CREDENTIALS_FROM_BAS = 3;

    /** @var BasDetails\Entity */
    protected $basDetails;

    protected $statementRecordsToMatch = [
        Entity::ACCOUNT_NUMBER,
        Entity::CHANNEL,
        Entity::POSTED_DATE,
        Entity::TYPE,
        Entity::DESCRIPTION,
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

    protected function preValidationUpdates($bankResponse)
    {
        if (is_associative_array(($bankResponse[Fields::DATA][Fields::RECORD])) === true)
        {
            $bankResponse[Fields::DATA][Fields::RECORD] = [$bankResponse[Fields::DATA][Fields::RECORD]];
        }

        return $bankResponse;
    }

    protected function shouldRetryMozartRequest(string $errorCode): bool
    {
        if (in_array($errorCode, $this->mozartNonRetriableCode, true) === true)
        {
            return false;
        }

        return true;
    }

    protected function sendRequestAndGetResponse(array $input)
    {
        $attemptCount = 0;

        // Retry logic is placed to retry when gateway exceptions are caught. Retry limit is in place for upper bound.
        $statementRetry = 0;

        $finalFormattedResponse = [];

        // get last bank transaction from banking account statement and set lastFormattedResponse
        $lastBankTransactionData = $this->getLastBankTransaction();

        $lastBankTransaction = $lastBankTransactionData? $lastBankTransactionData->toArray() : [];

        $merchantId = $this->basDetails->getMerchantId();

        $attemptLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT]);

        if (empty($attemptLimit) === true)
        {
            $attemptLimit = self::DEFAULT_ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT;
        }

        $statementRetryLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_RETRY_LIMIT]);

        if (empty($statementRetryLimit) === true)
        {
            $statementRetryLimit = self::DEFAULT_ICICI_STATEMENT_FETCH_RETRY_LIMIT;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_ATTEMPT_AND_RETRY_LIMITS,
            [
                'merchant_id'    => $merchantId,
                'channel'        => $this->channel,
                'account_number' => $this->accountNumber,
                'attempt_count'  => $attemptCount,
                'attempt_limit'  => $attemptLimit,
                'retry_limit'    => $statementRetryLimit,
            ]);

        $recordNumber = 1;

        $previousLasttrid = null;

        $credentials = $this->getCredentialsFromBAS();

        do
        {
            $isRetriableGatewayException = true;
            // We don't have any bank response for the first request.
            $lastFormattedResponse = last($finalFormattedResponse) ?: $lastBankTransaction;

            $requestData = $this->getRequestDataForMozart($lastFormattedResponse, $previousLasttrid, $credentials);

            try
            {
                $bankResponse = $this->app->mozart->sendMozartRequest(self::MOZART_NAMESPACE,
                                                                      $this->getChannel(),
                                                                      self::MOZART_ACTION,
                                                                      $requestData);

                $bankResponse = $this->preValidationUpdates($bankResponse);

                $this->validateMozartResponse($bankResponse);
            }
            catch (\Throwable $ex)
            {
                if ($ex instanceof GatewayErrorException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST_FAILED,
                        [
                            Entity::ACCOUNT_NUMBER => $this->accountNumber,
                            Entity::CHANNEL        => $this->channel,
                        ]);

                    $errorCodeAndDescription = $ex->getGatewayErrorCodeAndDesc();
                    $errorCode = $errorCodeAndDescription[0];

                    $shouldRetry = $this->shouldRetryMozartRequest($errorCode);

                    if ($shouldRetry === true)
                    {
                        if ($statementRetry < $statementRetryLimit)
                        {
                            $this->trace->info(
                                TraceCode::MOZART_SERVICE_RETRY,
                                [
                                    'message'              => $ex->getMessage(),
                                    'data'                 => $ex->getData(),
                                    Entity::ACCOUNT_NUMBER => $this->accountNumber,
                                    Entity::CHANNEL        => $this->channel
                                ]);

                            $statementRetry++;

                            $fetchMore = true;

                            $isRetriableGatewayException = true;

                            continue;
                        }
                        else
                        {
                            throw $ex;
                        }
                    }
                    else
                    {
                        $fetchMore = false;

                        $isRetriableGatewayException = false;

                        continue;
                    }
                }

                if ($ex instanceof BadRequestValidationFailureException)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_STATEMENT_INVALID_MOZART_RESPONSE,
                        [
                            Entity::ACCOUNT_NUMBER => $this->accountNumber,
                            Entity::CHANNEL        => $this->channel,
                            'response'             => $bankResponse ?? [],
                        ]);

                    throw $ex;
                }
            }

            $previousLasttrid = $this->getLasttridFromBankResponse($bankResponse, $previousLasttrid);

            $formattedResponse = $this->getFormattedResponse($bankResponse['data'], $recordNumber);

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

            $fetchMore = (($this->hasMoreData($bankResponse) === true) and ($attemptCount < $attemptLimit));

        } while ($fetchMore);

        // Adding a dispatch delay of 120 seconds as account statement process takes
        // around 1 min for processing and save.
        if (($isRetriableGatewayException === true) and
            ($this->hasMoreData($bankResponse) === true))
        {
            $delay = self::ICICI_ACCOUNT_STATEMENT_DISPATCH_DELAY;

            $data = [
                BasDetails\Entity::CHANNEL        => $this->basDetails->getChannel(),
                BasDetails\Entity::ACCOUNT_NUMBER => $this->accountNumber,
                BasDetails\Entity::BALANCE_ID     => $this->basDetails->getBalanceId(),
                'delay'                           => $delay
            ];

            (new BankingAccountStatementCore)->dispatchBankingAccountStatementJob($data);
        }

        return $finalFormattedResponse;
    }

    // Account Credentials are stored in Banking Account Service.
    // Credentials are fetched by making request to the service.
    protected function getCredentialsFromBAS()
    {
        $attempts = self::MAX_ATTEMPTS_TO_FETCH_CREDENTIALS_FROM_BAS;

        /** @var \RZP\Services\BankingAccountService $bas */
        $bas = $this->app['banking_account_service'];

        do
        {
            $retry = false;

            try
            {
                $credentials = $bas->fetchBankingCredentials($this->basDetails->getMerchantId(), $this->channel, $this->accountNumber);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::BANKING_ACCOUNT_STATEMENT_CREDENTIALS_FETCH_FROM_BAS_FAILURE,
                    [
                        Entity::ACCOUNT_NUMBER => $this->accountNumber,
                        Entity::CHANNEL        => $this->channel,
                    ]);

                $attempts--;

                if ($attempts <= 0)
                {
                    throw $ex;
                }

                $retry = true;
            }

        } while (($retry === true) and ($attempts > 0));

        $this->validateCredentialsResponse($credentials);

        return $credentials;
    }

    protected function validateCredentialsResponse(array $input)
    {
        (new Validator)->validateInput('icici_credentials', $input);
    }

    protected function getRequestDataForMozart(array $lastTransaction, $previousLasttrid, array $credentials)
    {
        $from_date = $this->getStatementStartTime($lastTransaction);

        $to_date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $data = [
            Fields::ATTEMPT => [
                Fields::FROM_DATE => $from_date,
                Fields::TO_DATE   => $to_date,
                Fields::CONFLG    => 'N',
            ],
            Fields::SOURCE_ACCOUNT => [
                Fields::ACCOUNT_NUMBER => $this->accountNumber,
                Fields::CREDENTIALS => [
                    Fields::CORP_ID                  => $credentials[Icici\Fields::CORP_ID],
                    Fields::USER_ID                  => $credentials[Icici\Fields::CORP_USER],
                    Fields::AGGR_ID                  => $this->config['banking_account']['icici'][Fields::AGGR_ID_CONFIG],
                    Fields::URN                      => $credentials[Icici\Fields::URN],
                    Fields::ACCOUNT_STATEMENT_APIKEY => $this->config['banking_account']['icici'][Fields::ACCOUNT_STATEMENT_API_KEY_CONFIG],
                ]
            ],
            Fields::LAST_TRANSACTION => [
                Fields::LASTTRID => ''
            ]
        ];

        if ($previousLasttrid === null)
        {
            $lasttrid = $this->getLasttrid($lastTransaction);
        }
        else
        {
            $lasttrid = $previousLasttrid;
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PAGINATION_DETAILS,
                           [
                               Entity::CHANNEL                  => Channel::ICICI,
                               RequestResponseFields::LASTTRID  => $lasttrid,
                               'is_previous_lasttrid'           => $previousLasttrid != null,
                               RequestResponseFields::FROM_DATE => $from_date,
                               RequestResponseFields::TO_DATE   => $to_date,
                           ]);

        if ($lasttrid !== '')
        {
            $data[Fields::LAST_TRANSACTION][Fields::LASTTRID] = $lasttrid;

            $data[Fields::ATTEMPT][Fields::CONFLG] = 'Y';
        }

        return $data;
    }

    protected function validateMozartResponse(array $response)
    {
        (new Validator)->validateInput('icici_response', $response['data']);
    }

    protected function getLasttrid($lastTransaction)
    {
        // construct lasttrid from last transaction
        if (empty($lastTransaction) === false)
        {
            $valueDateTimestamp = $lastTransaction[Entity::TRANSACTION_DATE];
            $valueDate          = $this->getDateTimeStringFromTimestamp($valueDateTimestamp, self::DATE_TIME_FORMAT);

            $postedDateTimestamp = $lastTransaction[Entity::POSTED_DATE];
            $postedDate          = $this->getDateTimeStringFromTimestamp($postedDateTimestamp, self::DATE_TIME_FORMAT);

            $currency = $lastTransaction[Entity::BALANCE_CURRENCY];
            $balance  = $lastTransaction[Entity::BALANCE];

            $balanceInSomeNotation = stringify($balance);
            // bank converts into exponential
            if($balance > 10000000000)
            {
                $balanceInSomeNotation = Util::convertAmountInPaiseToScientificNotation($balance);
            }
            else
            {
                $balanceInSomeNotation = Util::convertAmountInPaiseToINR($balance);
            }

            $lasttrid_constructed = '1|' .
                                    $lastTransaction[Entity::BANK_TRANSACTION_ID] .
                                    '|' .
                                    $valueDate .
                                    '|' .
                                    $currency .
                                    '|' .
                                    $balanceInSomeNotation .
                                    '|' .
                                    $postedDate;

            return $lasttrid_constructed;
        }

        return '';

    }

    protected function getStatementStartTime(array $lastTransaction)
    {
        // BAS Details entity is created at the time of activation. Hence when we fetch statement of the merchant
        // for the first time, starting fetching of statement from 2 months before activation. 2 months is decided
        // assuming all accounts onboarded will be new accounts and not existing accounts.
        $startTime = Carbon::createFromTimestamp($this->basDetails->getCreatedAt())->subMonths(2)->getTimestamp();

        if (empty($lastTransaction) === false)
        {
            // for icici this column stores output of value_date from bank's response
            $startTime = $lastTransaction[Entity::TRANSACTION_DATE];
        }

        $startTime = $this->getDateTimeStringFromTimestamp($startTime, self::DATE_FORMAT);

        return $startTime;
    }

    public function hasMoreData($bankResponse)
    {
        $responseBody = $bankResponse[Fields::DATA];

        return ((array_key_exists(Fields::LASTTRID_RESPONSE, $responseBody) === true) and
                (empty($responseBody[Fields::LASTTRID_RESPONSE]) === false));
    }

    /**
     * @param $bankResponse
     *
     * @return mixed
     */
    protected function getLasttridFromBankResponse($bankResponse, $previousLasttrid)
    {
        if ($this->hasMoreData($bankResponse) === true)
        {
            $responseBody = $bankResponse[Fields::DATA];

            $previousLasttrid = $responseBody[Fields::LASTTRID_RESPONSE];
        }

        return $previousLasttrid;
    }

    public function getFormattedResponse(array $responseData, int & $recordNumber)
    {
        $transactionsData = $responseData[Fields::RECORD] ?? [];

        $transactions = [];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_RESPONSE_COUNT,
            [
                Entity::ACCOUNT_NUMBER => $this->accountNumber,
                'txn_count'            => count($transactionsData)
            ]);

        foreach ($transactionsData as $transactionData)
        {
            //
            // Logging it here even though it's logged in Mozart Service since that
            // log is most probably going to be truncated due to large amount of data.
            //
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_DATA,
                               [
                                   'record_no'            => $recordNumber,
                                   Entity::CHANNEL        => $this->getChannel(),
                                   Entity::ACCOUNT_NUMBER => $this->accountNumber
                               ] + $transactionData
            );

            $recordNumber++;

            $transactions[] = [
                Entity::CHANNEL             => $this->getChannel(),
                Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                Entity::BANK_TRANSACTION_ID => $this->getBankTransactionIdFromResponse($transactionData),
                Entity::BANK_SERIAL_NUMBER  => $this->getBankTransactionIdFromResponse($transactionData),
                Entity::AMOUNT              => $this->getAmountFromResponse($transactionData),
                Entity::CURRENCY            => Currency::INR,
                Entity::TYPE                => $this->getTypeFromResponse($transactionData),
                Entity::DESCRIPTION         => $this->getDescriptionFromResponse($transactionData),
                Entity::BALANCE             => $this->getBalanceFromResponse($transactionData),
                Entity::BALANCE_CURRENCY    => Currency::INR,
                Entity::POSTED_DATE         => $this->getPostedDateFromResponse($transactionData),
                Entity::TRANSACTION_DATE    => $this->getTransactionDateFromResponse($transactionData),
                Entity::BANK_INSTRUMENT_ID  => null,
                Entity::CATEGORY            => null,
            ];
        }

        return $transactions;
    }


    // ------------------ Getters for extracting fields from bank response ---------------------------
    protected function getBankTransactionIdFromResponse(array $transaction): string
    {
        return trim($transaction[Fields::TRANSACTION_ID]);
    }

    protected function getAmountFromResponse(array $transaction): int
    {
        $amount = $transaction[Fields::AMOUNT];

        $amount = intval(number_format(str_replace(',', '', $amount) * 100, 0, '.', ''));

        return $amount;
    }

    public function getTypeFromResponse(array $transaction): string
    {
        $type = $transaction[Fields::TYPE];

        if ($type === TransactionType::CREDIT)
        {
            return Type::CREDIT;
        }
        else if ($type === TransactionType::DEBIT)
        {
            return Type::DEBIT;
        }

        throw new IntegrationException(
            "Invalid txnType found as $type",
            null,
            [
                'bank_transaction_id'   => $transaction[Fields::TRANSACTION_ID],
            ]);
    }

    protected function getDescriptionFromResponse($transaction)
    {
        return trim($transaction[Fields::REMARKS]);
    }

    protected function getBalanceFromResponse(array $transaction): int
    {
        $amount = $transaction[Fields::BALANCE];

        $amount = intval(number_format(str_replace(',', '', $amount) * 100, 0, '.', ''));

        return $amount;
    }

    protected function getPostedDateFromResponse(array $transaction)
    {
        $timestamp = $this->getTimestampFromDateString($transaction[Fields::TRANSACTION_DATE]);

        return $timestamp;
    }

    protected function getTransactionDateFromResponse(array $transaction)
    {
        $timestamp = $this->getTimestampFromDateString($transaction[Fields::VALUEDATE]);

        return $timestamp;
    }

    // ------------------ End Getters for extracting fields from bank response ---------------------------

    public function checkForDuplicateTransactions(array $bankTransactions, string $channel, string $accountNumber)
    {
        $recordsToCheck = [];
        $totalRecordCount = count($bankTransactions);
        $totalRecords = 0;
        $skippedRecordCount = 0;
        $processedRecordCount = 0;
        $bankTransactionRecords = [];

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT;
        }

        foreach ($bankTransactions as $index => $bankTransaction)
        {
            $recordsToCheck[] = [
                $bankTransaction[Entity::BANK_TRANSACTION_ID],
                $bankTransaction[Entity::BANK_SERIAL_NUMBER],
                $bankTransaction[Entity::TRANSACTION_DATE],
                $bankTransaction[Entity::AMOUNT],
                $bankTransaction[Entity::CHANNEL],
                $bankTransaction[Entity::ACCOUNT_NUMBER],
            ];

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
                $existingRecords = $this->repo->banking_account_statement
                    ->findExistingStatementRecordsForBank($recordsToCheck);

                /** @var Entity $record */
                foreach ($existingRecords as $record)
                {
                    $record->setDescription(trim($record->getDescription()));

                    $recordToMatch = $this->formBankTransactionRecordToMatch($record->toArray());

                    $isPresent = array_search($recordToMatch, $bankTransactionRecords);

                    if ($isPresent !== false)
                    {
                        // when first record in response is a duplicate record it is observed that the successive transactions
                        // are having discrepancies in closing balance. The difference is observed to be +/- amount of duplicate
                        // transaction depending on credit or debit.
                        if ($isPresent === 0)
                        {
                            if ($record->getType() === Type::CREDIT)
                            {
                                $difference = $record->getAmount();
                            }
                            else
                            {
                                $difference = -1 * $record->getAmount();
                            }

                            if($bankTransactions[0][Entity::BALANCE]-$record->getBalance() === $difference)
                            {
                                foreach ($bankTransactions as $index => $bankTransaction)
                                {
                                    $bankTransactions[$index][Entity::BALANCE]= $bankTransaction[Entity::BALANCE] - $difference;
                                }
                            }
                        }

                        unset($bankTransactions[$isPresent]);
                        $skippedRecordCount++;
                    }
                }

                $recordsToCheck = [];
                $bankTransactionRecords = [];

                $processedRecordCount = 0;
            }
        }

        if ($skippedRecordCount !== 0)
        {
            $data = [
                'channel'              => Channel::ICICI,
                'skipped_record_count' => $skippedRecordCount,
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_RECORDS_FOUND, [
                'data' => $data,
            ]);

            $operation = 'existing records found while fetching the statement for ICICI';

            //TODO: add separate icici channel for alert
            (new SlackNotification)->send(
                $operation,
                $data,
                null,
                1,
                'rx_ca_rbl_alerts');
        }
        else
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_NO_EXISTING_RECORDS_FOUND,
                               [
                                   'channel'        => Channel::ICICI,
                                   'account_number' => $accountNumber,
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

    public function getUtrForChannel(Entity $basEntity)
    {
        $utr = null;

        if ($basEntity->isTypeCredit() === true)
        {
            $utr = $this->getCreditUtr($basEntity);
        }
        else
        {
            $utr = $this->getDebitUtr($basEntity);
        }

        if ($utr === null)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_NO_REGEX_MATCH_FOUND_FOR_UTR,
                [
                    Entity::TYPE           => $basEntity->getType(),
                    Entity::CHANNEL        => $basEntity->getChannel(),
                    Entity::ACCOUNT_NUMBER => $basEntity->getAccountNumber(),
                    Entity::DESCRIPTION    => $basEntity->getDescription(),
                    'bas_id'               => $basEntity->getId()
                ]
            );
        }

        return $utr;
    }

    protected function getCreditUtr(Entity $basEntity)
    {
        $description = $basEntity->getDescription();

        $regex = self::CREDIT_REGEX_IMPS;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            return $matches[1];
        }

        $regex = self::CREDIT_REGEX_NEFT;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            $utr =  $matches[1];
            $utr = '0' . substr($utr, 0, strlen($utr) -2);

            return $utr;
        }

        return null;
    }

    protected function getDebitUtr(Entity $basEntity)
    {
        $description = $basEntity->getDescription();

        $regex = self::DEBIT_REGEX_NEFT;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            return $matches[1];
        }

        $regex = self::DEBIT_REGEX_IMPS;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            return $matches[1];
        }

        $regex = self::DEBIT_REGEX_IFT;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            return $matches[1];
        }

        $regex = self::DEBIT_REGEX_RTGS;

        if (($match = preg_match($regex, $description, $matches)) === 1)
        {
            return $matches[1];
        }

        return null;
    }
}
