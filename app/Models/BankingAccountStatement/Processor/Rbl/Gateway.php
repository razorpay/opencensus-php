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
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\BankingAccountStatement\Category;
use RZP\Models\BankingAccountStatement\Processor\Source;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccountStatement\Processor\Base as BaseProcessor;
use RZP\Models\BankingAccountStatement\Core as BankingAccountStatementCore;
use RZP\Models\BankingAccountStatement\Processor\Rbl\RequestResponseFields as Fields;

class Gateway extends BaseProcessor
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s.000';

    const STATEMENT_START_TIME_DATE_FORMAT = 'Y-m-d';

    const DEFAULT_RBL_STATEMENT_FETCH_ATTEMPT_LIMIT = 3;

    const RBL_ACCOUNT_STATEMENT_DISPATCH_DELAY = 120;

    const DEFAULT_RBL_STATEMENT_FETCH_RETRY_LIMIT = 3;

    const RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT = 200;

    // regex to fetch utr from description
    const CREDIT_REGEX = '/^(RTGS\/|NEFT\/|UPI\/|R\/UPI\/|R-)(.*?)(\/|-)/';

    // sample IMPS - 010617021414-QCREDIT 234412
    const IMPS_DEBIT_REGEX = '/^(.*?)-/';

    // sample NEFT - NEFT/000119662132/maYANK SHARMA
    // sample RTGS - RTGS/UTIBH20106341692/RAZORPAY SOFTWARE PRIVATE LI
    const NEFT_RTGS_DEBIT_REGEX = '/^(RTGS\/|NEFT\/)(.*?)(\/)/';

    const OFFSET_FOR_SAVING_RECORD = 60;

    // sample UPI- UPI/120310176379/Test transfer RAZORPAY/razorpayx.
    const UPI_DEBIT_REGEX = '/^(UPI\/)(.*?)(\/)/';

    /** @var BasDetails\Entity */
    protected $basDetails;

    public function __construct(string $channel, string $accountNumber, BasDetails\Entity $basDetails)
    {
        $this->setSource(Source::FETCH_API);

        $this->basDetails = $basDetails;

        parent::__construct($channel, $accountNumber);
    }

    public function checkForDuplicateTransactions(array $bankTransactions, string $channel, string $accountNumber)
    {
        $recordsToCheck = [];
        $totalRecordCount = count($bankTransactions);
        $totalRecords = 0;
        $skippedRecordCount = 0;
        $processedRecordCount = 0;

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT;
        }

        foreach ($bankTransactions as $bankTransaction)
        {
            $recordsToCheck[] = [
                $bankTransaction[Entity::BANK_TRANSACTION_ID],
                $bankTransaction[Entity::BANK_SERIAL_NUMBER],
                $bankTransaction[Entity::TRANSACTION_DATE],
                $bankTransaction[Entity::AMOUNT],
                $bankTransaction[Entity::CHANNEL],
                $bankTransaction[Entity::ACCOUNT_NUMBER],
            ];

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

                foreach ($existingRecords as $record)
                {
                    $isPresent = array_search($record->toArray(), $bankTransactions);

                    if ($isPresent !== false)
                    {
                        unset($bankTransactions[$isPresent]);
                        $skippedRecordCount++;
                    }
                }

                $recordsToCheck = [];

                $processedRecordCount = 0;
            }
        }

        if ($skippedRecordCount !== 0)
        {
            $data = [
                'channel'                    => Channel::RBL,
                'skipped_record_count'       => $skippedRecordCount,
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_RECORDS_FOUND, [
                'data' => $data,
            ]);

            $operation = 'existing records found while fetching the statement for RBL';

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
                    'channel'                    => Channel::RBL,
                    'account_number'             => $accountNumber,
                ]);
        }

        return $bankTransactions;
    }

    protected function sendRequestAndGetResponse(array $input)
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

        if (array_key_exists(Entity::MERCHANT_ID, $lastBankTransaction) === true)
        {
            $merchantId = $lastBankTransaction[Entity::MERCHANT_ID];
        }
        else
        {
            $merchantId = "";
        }

        // TODO: This whole thing needs to be re-looked at. How we fetch the details.

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::BANKING_ACCOUNT_STATEMENT_SPECIAL_ATTEMPT_LIMIT,
            $this->mode
        );

        if ($variant === 'on')
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

            (new BankingAccountStatementCore)->dispatchBankingAccountStatementJob($this->channel, $this->accountNumber, $delay);
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

    protected function validateMozartResponse(array $response)
    {
        (new Validator)->validateInput('rbl_response', $response['data']);
    }

    protected function getRequestDataForMozart(array $input, array $lastTransaction)
    {
        /** @var BankingAccountEntity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByAccountNumberAndChannel($this->accountNumber,
                                                                                      $this->channel);

        $data = [
            Fields::ATTEMPT => [
                Fields::ID                          => (string) Carbon::now()->timestamp,
                Fields::FROM_DATE                   => $this->getStatementStartTime($bankingAccount),
                Fields::TO_DATE                     => Carbon::today(Timezone::IST)->toDateString(),
                Fields::TRANSACTION_TYPE            => TransactionType::BOTH,
            ],
            Fields::SOURCE_ACCOUNT => [
                Fields::ACCOUNT_NUMBER              => $this->accountNumber,
                Fields::CREDENTIALS => [
                    Fields::AUTH_USERNAME           => $bankingAccount->getUsername(),
                    Fields::AUTH_PASSWORD           => $bankingAccount->getPassword(),
                    Fields::CLIENT_ID               => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_ID),
                    Fields::CLIENT_SECRET           => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_SECRET),
                    Fields::CORP_ID                 => $bankingAccount->getReference1(),
                ]
            ],
            Fields::LAST_TRANSACTION => $this->getPaginationDataForRequest($lastTransaction),
        ];

        return $data;
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
    protected function getStatementStartTime(BankingAccountEntity $bankingAccount)
    {
        // TODO: Might want to use the transactions tables for this instead of BAS table.
        $bankTransaction = $this->getLastBankTransaction();

        // TODO: fetch bank opening time from BankingAccount array
        $startTime = 1;

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
        return $transaction[Fields::TRANSACTION_SUMMARY][Fields::TRANSACTION_DESCRIPTION];
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

    public function getUtrForChannel(Entity $basEntity)
    {
        $description = $basEntity->getDescription();

        if ($basEntity->isTypeCredit() === true)
        {
            $regex = self::CREDIT_REGEX;
        }
        else
        {
            $regex = self::IMPS_DEBIT_REGEX;

            if ($this->isNeftOrRtgs($description) === true)
            {
                $regex = self::NEFT_RTGS_DEBIT_REGEX;
            }
            if ($this->isUpi($description) === true)
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

        return null;
    }

    protected function isNeftOrRtgs(string  $description)
    {
        $regex = self::NEFT_RTGS_DEBIT_REGEX;

        $match = preg_match($regex, $description, $matches);

        if ($match === 1)
        {
            return true;
        }

        return false;
    }

    protected function isUpi(string $description)
    {
        $regex = self::UPI_DEBIT_REGEX;

        $match = preg_match($regex, $description, $matches);

        if ($match === 1)
        {
            return true;
        }

        return false;
    }
}
