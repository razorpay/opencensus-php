<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\BankingAccountStatement\Category;
use RZP\Models\BankingAccountStatement\Processor\Source;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccountStatement\Processor\Base as BaseProcessor;
use RZP\Models\BankingAccountStatement\Processor\Rbl\RequestResponseFields as Fields;

class Gateway extends BaseProcessor
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s.000';

    const STATEMENT_START_TIME_DATE_FORMAT = 'Y-m-d';

    const DEFAULT_RBL_STATEMENT_FETCH_ATTEMPT_LIMIT = 3;

    public function __construct(string $channel, string $accountNumber)
    {
        $this->setSource(Source::FETCH_API);

        parent::__construct($channel, $accountNumber);
    }

    protected function sendRequestAndGetResponse(array $input)
    {
        //
        // Since there could be a lot of data, currently, we are fetching only
        // 3 times and let the remaining run in the next run. Should fix this logic.
        //
        $attemptCount = 0;

        $finalFormattedResponse = [];

        // TODO: This whole thing needs to be re-looked at. How we fetch the details.

        $attemptLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_STATEMENT_FETCH_ATTEMPT_LIMIT]);

        if (empty($attemptLimit) === true)
        {
            $attemptLimit = self::DEFAULT_RBL_STATEMENT_FETCH_ATTEMPT_LIMIT;
        }

        do
        {
            // We don't have any bank response for the first request.
            $lastFormattedResponse = last($finalFormattedResponse) ?: [];

            $requestData = $this->getRequestDataForMozart($input, $lastFormattedResponse);

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

            $formattedResponse = $this->getFormattedResponse($bankResponse['data']);

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

        } while (($this->hasMoreData($bankResponse) === true) and
                 ($attemptCount < $attemptLimit));

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
                Fields::TO_DATE                     => Carbon::today()->toDateString(),
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

        if (empty($bankTransaction) === false)
        {
            $startTime = $bankTransaction->getTransactionDate();
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
            Fields::AMOUNT => $this->getFormattedAmountForRequest($lastTransaction),
            Fields::CURRENCY => $this->getFormattedCurrencyForRequest($lastTransaction),
            Fields::POSTED_DATE => $this->getFormattedPostedDateForRequest($lastTransaction),
            Fields::TRANSACTION_DATE => $this->getFormattedTransactionDateForRequest($lastTransaction),
            Fields::TRANSACTION_ID => $this->getFormattedBankTransactionIdForRequest($lastTransaction),
            Fields::SERIAL_NUMBER => $this->getFormattedSerialNumberForRequest($lastTransaction),
        ];
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

    public function getFormattedResponse(array $responseData)
    {
        $responseBody = $responseData[Fields::PAYMENT_GENERIC_RESPONSE][Fields::BODY];

        $transactionsData = $responseBody[Fields::TRANSACTION_DETAILS] ?? [];

        $transactions = [];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_RESPONSE_COUNT,
            [
                'txn_count' => count($transactionsData)
            ]);

        foreach ($transactionsData as $transactionData)
        {
            //
            // Logging it here even though it's logged in Mozart Service since that
            // log is most probably going to be truncated due to large amount of data.
            //
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_DATA, $transactionData);

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
                Entity::BALANCE             => $this->getBalanceFromResponse($transactionData),
                Entity::BALANCE_CURRENCY    => $this->getBalanceCurrencyFromResponse($transactionData),
                Entity::POSTED_DATE         => $this->getPostedDateFromResponse($transactionData),
                Entity::TRANSACTION_DATE    => $this->getTransactionDateFromResponse($transactionData),
            ];
        }

        return $transactions;
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

    protected function getBalanceFromResponse(array $transaction): int
    {
        $amount = $transaction[Fields::TRANSACTION_BALANCE][Fields::AMOUNT_VALUE];

        $amount = intval(number_format($amount * 100, 0, '.', ''));

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
}
