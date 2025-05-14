<?php

namespace RZP\Models\BankingAccountStatement\Processor\Icici;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\BankingAccountStatement\Metric;
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

    const ICICI_STATEMENT_FETCH_API_MAX_RECORDS = 200;

    /**
     *  NEFT credit remarks => "NEFT-RETURN-ICICN42025030751922184-LENarendraGa-Closed Account Number  AC04",
     *  utr is ICICN42025030751922184
     *  NEFT credit remarks => "NEFT-RETURN-23629988951DC-AYUSH MITTAL-ACCOUNT DOES NOT EXIST  R03"
     *  utr is 023629988951
     *  NEFT credit remarks => "NEFT-AXISCN0118376057-RAZORPAY SOFTWARE PRIVATE LIMITED-RAZORPAY SOFTWARE PVT L",
     *  utr is AXISCN0118376057
     */
    const CREDIT_REGEX_NEFT = [
        '/^NEFT-RETURN-(ICIC.*?)-/',
        '/^NEFT-RETURN-(.*?)-/',
        '/^NEFT-(.*?)-/'];
    /**
     *   NEFT credit remarks => "NEFT-RETURN-23629988951DC-AYUSH MITTAL-ACCOUNT DOES NOT EXIST  R03"
     *   utr is 023629988951
    */
    const CREDIT_REGEX_NEFT_RETURN = '/^NEFT-RETURN-(.*?)-/';

    /**
     *  IMPS credit remarks => "IMPS 204813976491 19 02 2021 BOI",
     *  IMPS credit remarks => "PRO-MMT/IMPS/313818380043/APIL",
     *  IMPS credit remarks => "FT-MMT/IMPS/312113616259/APILkK97uHZ9EWx/DIPANKARSA/FSFB0000001",
     *  IMPS credit remarks => "MMT/IMPS/105400750777/TestIciciProd06/harsh     /HDFC0000004",
     *  IMPS credit remarks => "MMT IMPS 212211671710 APIJQFQgSvI8qvN MR SATYANAR  SBIN0003281",
     *  IFT credit remarks  => "INF/INFT/025802182571/Razorpay curren/CAMPUS CONNECT",
     *  UPI credit remarks  => "UPI/RVSL509437768158/frapp wallet /aniruddhsingh16//ICIRZPUPITRANSFERQEpGt3SvOsGbMDDUEr"
     *  UPI credit remarks  => "UPI/115421282359/UPI/praveenraam06-1/DBS Bank India",
     *  RTGS credit remarks => "RTGS-AUBLR12021123000584069-FINAVRIO TECHNOLOGY PRIVATE LIMITED-212121133524511",
     *  BIL credit remarks  => "BIL/INFT/000270116851/Paid up capital/ CHIRAG V"
     */
    const CREDIT_REGEX = [
        '/^IMPS ([0-9]{12}) /',
        '/^PRO-MMT\/IMPS\/(.*?)\//',
        '/^FT-MMT\/IMPS\/(.*?)\//',
        '/^MMT\/IMPS\/(.*?)\//',
        '/^MMT IMPS ([0-9]{12}) /',
        '/^INF\/INFT\/(.*?)\//',
        '/UPI\/RVSL(.*?)\//',
        '/^UPI\/(.*?)\//',
        '/^RTGS-(.*?)-/',
        '/^BIL\/INFT\/(.*?)\//'
    ];

    /**
     *  IMPS debit remarks     => "MMT/IMPS/105400750777/TestIciciProd06/harsh     /HDFC0000004",
     *  NEFT debit remarks     => "INF/NEFT/023629988951/SBIN0050103/TestIciciProd03/Ayush Mittal",
     *  IFT debit remarks      => "INF/INFT/023652565741/TestIciciProd06/Raja",
     *  RTGS debit remarks     => "RTGS/ICICR42021042600532758/YESB0000022/RZPX pvtltd",
     *  UPI debit remarks      => "UPI/115600327157/NA/praveenraam06@d/",
     *  BIL/BPAY debit remarks => "BIL/BPAY/000000043NVN/Bangalore Electricity",
     *  BIL/ONL debit remarks  => "BIL/ONL/000286716570/Google Ads",
     */
    const DEBIT_REGEX = [
        '/^MMT\/IMPS\/(.*?)\//',
        '/^INF\/NEFT\/(.*?)\//',
        '/^INF\/INFT\/(.*?)\//',
        '/^RTGS\/(.*?)\//',
        '/^UPI\/(.*?)\//',
        '/^BIL\/(?:BPAY\/|ONL\/)(.*?)\//'
    ];

    protected $mozartNonRetriableCode = [
        TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTIONS_DO_NOT_EXIST_WITH_THE_GIVEN_CRITERIA,
    ];

    /*
     * Stores the $descriptions which are wrongly identified as temporary records and should be allowed to be saved.
     */
    protected $descriptionsToSkip = [];

    /*
     * Stores the variant value for temporary records experiment.
     */
    protected $temporaryRecordVariant = 'control';

    const MAX_ATTEMPTS_TO_FETCH_CREDENTIALS_FROM_BAS = 3;

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

    protected $allowRecordsToSave = true;

    protected $fromDate = null;

    protected $toDate = null;

    protected $previousTrid = '';

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

    protected function shouldRetryMozartRequest(string $errorCode, $requestData): bool
    {
        if (in_array($errorCode, $this->mozartNonRetriableCode, true) === true)
        {
            if ($errorCode === TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTIONS_DO_NOT_EXIST_WITH_THE_GIVEN_CRITERIA)
            {
                $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

                // Bank has kept a constraint that max difference between from_date and to_date can be 365 days.
                $allowedDateDiff = 365 * $secondsPerDay;

                if ((isset($requestData[Fields::ATTEMPT][Fields::FROM_DATE]) === true) and
                    (isset($requestData[Fields::ATTEMPT][Fields::TO_DATE]) === true))
                {
                    $fromDate = $this->getTimestampFromDateString($requestData[Fields::ATTEMPT][Fields::FROM_DATE]);

                    $toDate = $this->getTimestampFromDateString($requestData[Fields::ATTEMPT][Fields::TO_DATE]);

                    if($toDate - $fromDate >= $allowedDateDiff)
                    {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    protected function sendRequestAndGetResponse(array $input)
    {
        if (($this->channel === Channel::ICICI) and
            ((new BankingAccountStatementCore())->checkIfIciciStatementFetchEnabled() === false))
        {
            return [];
        }

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

        $requestData = null;

        $credentials = $this->getCredentialsFromBAS();

        $this->descriptionsToSkip = (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_ALLOW_DESCRIPTION]);

        $this->temporaryRecordVariant = $this->app->razorx->getTreatment(
            $this->basDetails->getMerchantId(),
            Merchant\RazorxTreatment::BANKING_ACCOUNT_STATEMENT_TEMP_RECORDS,
            $this->mode
        );

        do
        {
            $isRetriableGatewayException = true;
            // We don't have any bank response for the first request.
            $lastFormattedResponse = last($finalFormattedResponse) ?: $lastBankTransaction;

            $requestData = $this->getRequestDataForMozart($requestData, $lastFormattedResponse, $previousLasttrid, $credentials, $merchantId);

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
                            Entity::MERCHANT_ID    => $merchantId,
                            Entity::ACCOUNT_NUMBER => $this->accountNumber,
                            Entity::CHANNEL        => $this->channel,
                        ]);

                    $errorCodeAndDescription = $ex->getGatewayErrorCodeAndDesc();
                    $errorCode = $errorCodeAndDescription[0];

                    $shouldRetry = $this->shouldRetryMozartRequest($errorCode, $requestData);

                    if ($shouldRetry === true)
                    {
                        if ($statementRetry < $statementRetryLimit)
                        {
                            $this->trace->info(
                                TraceCode::MOZART_SERVICE_RETRY,
                                [
                                    'message'              => $ex->getMessage(),
                                    'data'                 => $ex->getData(),
                                    Entity::MERCHANT_ID    => $merchantId,
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
                            Entity::MERCHANT_ID    => $merchantId,
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

            $fetchMore = (($this->hasMoreData($bankResponse) === true) and
                          ($attemptCount < $attemptLimit) and
                          ($this->allowRecordsToSave === true));

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

    public function sendRequestToFetchStatement(array $input)
    {
        if (($this->channel === Channel::ICICI) and
            ((new BankingAccountStatementCore())->checkIfIciciStatementFetchEnabled(false, true) === false))
        {
            return [[], false, ''];
        }

        $statementRetry = 0;
        $statementRetryLimit = 1;
        $attemptCount = 0;
        $attemptLimit = 1;

        if (array_key_exists(Entity::FROM_DATE, $input) === true)
        {
            $this->fromDate = $input[Entity::FROM_DATE];
        }

        if (array_key_exists(Entity::TO_DATE, $input) === true)
        {
            $this->toDate = $input[Entity::TO_DATE];
        }

        if (array_key_exists('pagination_key', $input) === true)
        {
            $this->previousTrid = $input['pagination_key'];
        }

        $finalFormattedResponse = [];

        $lastBankTransaction = [];

        $merchantId = $this->basDetails->getMerchantId();

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

        $isRetry = false;

        $recordNumber = 1;

        $previousLasttrid = null;

        $credentials = $this->getCredentialsFromBAS();

        $this->descriptionsToSkip = (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_ALLOW_DESCRIPTION]);

        $this->temporaryRecordVariant = $this->app->razorx->getTreatment(
            $this->basDetails->getMerchantId(),
            Merchant\RazorxTreatment::BANKING_ACCOUNT_STATEMENT_TEMP_RECORDS,
            $this->mode
        );

        do
        {
            $lastFormattedResponse = last($finalFormattedResponse) ?: $lastBankTransaction;

            if ($isRetry === false)
            {
                $requestData = $this->modifyRequestForFetchingStatement($lastFormattedResponse, $previousLasttrid, $credentials);
            }

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
                    $errorCode               = $errorCodeAndDescription[0];

                    $shouldRetry = $this->shouldRetryMozartRequest($errorCode, $requestData);

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

                            $isRetry = true;

                            continue;
                        }
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
                }

                throw $ex;
            }

            $isRetry = false;

            $previousLasttrid = $this->getLasttridFromBankResponse($bankResponse, $previousLasttrid);

            $formattedResponse = $this->getFormattedResponse($bankResponse['data'], $recordNumber);

            $finalFormattedResponse = array_merge($finalFormattedResponse, $formattedResponse);

            $attemptCount++;

            $fetchMore = (($this->hasMoreData($bankResponse) === true) and
                          ($this->allowRecordsToSave === true));

        } while (($fetchMore === true) and ($attemptCount < $attemptLimit));

        $paginationKey = $this->getLasttrid(last($finalFormattedResponse));

        return [$finalFormattedResponse, $fetchMore, $paginationKey];
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
        (new Validator)->setStrictFalse()->validateInput('icici_credentials', $input);

        if ($this->basDetails->merchant->isFeatureEnabled(Features::ICICI_BAAS) === true)
        {
            (new Icici\Processor)->validateCredentialsForBaasMerchants($input[Icici\Fields::CREDENTIALS]);
        }
    }

    protected function getRequestDataForMozart($requestData, array $lastTransaction, $previousLasttrid, array $credentials, $merchantId)
    {
        $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

        // Bank has kept a constraint that max difference between from_date and to_date can be 365 days.
        $allowedDateDiff = 365 * $secondsPerDay;

        $startTime = $this->getStatementStartTime($lastTransaction);

        if ((isset($requestData[Fields::ATTEMPT][Fields::FROM_DATE]) === true) and
            (isset($requestData[Fields::ATTEMPT][Fields::TO_DATE]) === true))
        {
            $fromDate = $this->getTimestampFromDateString($requestData[Fields::ATTEMPT][Fields::FROM_DATE]);

            $toDate = $this->getTimestampFromDateString($requestData[Fields::ATTEMPT][Fields::TO_DATE]);

            if($toDate - $fromDate >= $allowedDateDiff)
            {
                $startTime = $toDate + $secondsPerDay;
            }
        }

        $endTime = Carbon::today(Timezone::IST)->getTimestamp();

        $from_date = $this->getDateTimeStringFromTimestamp($startTime, self::DATE_FORMAT);

        $to_date = $this->getDateTimeStringFromTimestamp(
            min($endTime, $startTime + $allowedDateDiff),
            self::DATE_FORMAT);

        $aggrId            = $this->config['banking_account']['icici'][Fields::AGGR_ID_CONFIG];
        $beneficiaryApikey = $this->config['banking_account']['icici'][Fields::ACCOUNT_STATEMENT_API_KEY_CONFIG];

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
                    Fields::AGGR_ID                  => $aggrId,
                    Fields::URN                      => $credentials[Icici\Fields::URN],
                    Fields::ACCOUNT_STATEMENT_APIKEY => $beneficiaryApikey,
                ]
            ],
            Fields::LAST_TRANSACTION => [
                Fields::LASTTRID => ''
            ],
        ];

        if ((array_key_exists(Icici\Fields::CREDENTIALS, $credentials) === true) and
            ($credentials[Icici\Fields::CREDENTIALS] !== null))
        {
            $this->modifyRequestForMerchantsOnBaasFlow($data, $credentials[Icici\Fields::CREDENTIALS], $merchantId);
        }

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

    protected function modifyRequestForFetchingStatement(array $lastTransaction, $previousLasttrid, array $credentials)
    {
        $from_date = $this->getDateTimeStringFromTimestamp($this->fromDate, self::DATE_FORMAT);

        $to_date = $this->getDateTimeStringFromTimestamp($this->toDate, self::DATE_FORMAT);

        $data = [
            Fields::ATTEMPT          => [
                Fields::FROM_DATE => $from_date,
                Fields::TO_DATE   => $to_date,
                Fields::CONFLG    => 'N',
            ],
            Fields::SOURCE_ACCOUNT   => [
                Fields::ACCOUNT_NUMBER => $this->accountNumber,
                Fields::CREDENTIALS    => [
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

        if(isset($this->previousTrid) === true)
        {
            $lasttrid = $this->previousTrid;

            $this->previousTrid = null;
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
        // In case there are no transactions for the merchant in our DB then we will fetch statement from start of financial year.
        $startTime = $this->getStartTime()->getTimestamp();

        if (empty($lastTransaction) === false)
        {
            // for icici this column stores output of value_date from bank's response
            $startTime = $lastTransaction[Entity::TRANSACTION_DATE];
        }

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
                Entity::MERCHANT_ID    => optional($this->basDetails)->getMerchantId(),
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
                                   Entity::ACCOUNT_NUMBER => $this->accountNumber,
                                   Entity::MERCHANT_ID    => optional($this->basDetails)->getMerchantId(),
                               ] + $transactionData
            );

            if (($this->temporaryRecordVariant === 'on') or
                ($this->temporaryRecordVariant === 'control'))
            {
                $this->checkForTemporaryRecord($transactionData, $this->descriptionsToSkip);
            }

            if ($this->allowRecordsToSave === false)
            {
                continue;
            }

            $recordNumber++;

            $transactions[] = [
                Entity::CHANNEL             => $this->getChannel(),
                Entity::ACCOUNT_NUMBER      => $this->accountNumber,
                Entity::BANK_TRANSACTION_ID => $this->getBankTransactionIdFromResponse($transactionData),
                Entity::BANK_SERIAL_NUMBER  => $this->getBankTransactionIdOrChequeNo($transactionData),
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

    protected function checkForTemporaryRecord($transactionData, $descriptionsToSkip = [])
    {
        $postedDate = $this->getPostedDateFromResponse($transactionData);

        $longFormatPostedDate = intval(Carbon::createFromTimestamp($postedDate, Timezone::IST)->format('YmdHis'));

        $description = $this->getDescriptionFromResponse($transactionData);

        if (preg_match("/\/(20)([0-9]{12})$/", $description, $matches) === 1)
        {
            if (in_array($description, $descriptionsToSkip, true) === true)
            {
                return;
            }

            $timeInDescription = intval(substr($description, -14));

            // if difference is less than 2 days, we won't allow the records to be saved
            if (abs($longFormatPostedDate - $timeInDescription) < 2000000)
            {
                $this->allowRecordsToSave = false;

                $this->trace->count(Metric::BANKING_ACCOUNT_STATEMENT_ICICI_TEMP_RECORD_COUNT, [
                                        Entity::MERCHANT_ID      => $this->basDetails->getMerchantId(),
                                    ]);
            }

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_ICICI_TEMP_RECORD,
                               [
                                   'allowed_record_to_save' => $this->allowRecordsToSave,
                                   Entity::MERCHANT_ID      => $this->basDetails->getMerchantId(),
                                   Entity::CHANNEL          => $this->getChannel(),
                                   Entity::ACCOUNT_NUMBER   => $this->accountNumber
                               ] + $transactionData
            );
        }
    }


    // ------------------ Getters for extracting fields from bank response ---------------------------
    protected function getBankTransactionIdFromResponse(array $transaction): string
    {
        return trim($transaction[Fields::TRANSACTION_ID]);
    }

    protected function getBankTransactionIdOrChequeNo(array $transaction): string
    {
        $chequeNo = $transaction[Fields::CHEQUENO];

        // If ChequeNo is an empty array, empty string or a string with spaces return false, else return true.
        $fillChequeNo = (empty($chequeNo) === true) ? false : (empty(trim($chequeNo)) === false);

        return ($fillChequeNo === false) ? $this->getBankTransactionIdFromResponse($transaction) : trim($chequeNo);
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

    public function checkForDuplicateTransactions(array $bankTransactions, string $channel, string $accountNumber, $merchant)
    {
        $this->alterStatementColumnsToMatch();

        $totalRecordCount = count($bankTransactions);
        $totalRecords = 0;
        $skippedRecordCount = 0;
        $processedRecordCount = 0;
        $difference = 0;
        $bankTransactionRecords = [];
        $bankTransactionIds = [];
        $queryDate = null;

        $limit = (int) (new AdminService)->getConfigKey(
            ['key' => ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE]);

        if (empty($limit) == true)
        {
            $limit = self::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE_DEFAULT;
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
                $existingRecords = $this->repo->banking_account_statement
                    ->findExistingStatementRecordsForBankWithDate($bankTransactionIds, $accountNumber, $queryDate);

                /** @var Entity $record */
                foreach ($existingRecords as $record)
                {
                    $record->setDescription(trim($record->getDescription()));

                    $recordToMatch = $this->formBankTransactionRecordToMatch($record->toArray());

                    $isPresent = array_search($recordToMatch, $bankTransactionRecords);

                    if ($isPresent !== false)
                    {
                        if ($record->getType() === Type::CREDIT)
                        {
                            $difference += $record->getAmount();
                        }
                        else
                        {
                            $difference += -1 * $record->getAmount();
                        }
                        if (($bankTransactions[$isPresent][Entity::BALANCE] - $record->getBalance() === $difference) and
                            ($difference !== 0))
                        {
                            foreach ($bankTransactions as $index => $bankTransaction)
                            {
                                $bankTransactions[$index][Entity::BALANCE] = $bankTransaction[Entity::BALANCE] - $difference;
                            }

                            // once the difference matches and we have subtracted from subsequent records amount equal
                            // to difference, need to reset difference.
                            $difference = 0;
                        }
                        // when first record in response is a duplicate record it is observed that the successive transactions
                        // are having discrepancies in closing balance. The difference is observed to be +/- amount of duplicate
                        // transaction depending on credit or debit.

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
                'channel'              => Channel::ICICI,
                'skipped_record_count' => $skippedRecordCount,
                'merchant_id'          => $merchant->getId(),
            ];

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_EXISTING_RECORDS_FOUND, [
                'data' => $data,
            ]);
        }
        else
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_NO_EXISTING_RECORDS_FOUND,
                               [
                                   'channel'        => Channel::ICICI,
                                   'account_number' => $accountNumber,
                                   'merchant_id'    => $merchant->getId(),
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

    public function getUtrForChannel(PublicEntity $basEntity)
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
                    Entity::CHANNEL        => Channel::ICICI,
                    Entity::MERCHANT_ID    => $basEntity->getMerchantId(),
                    Entity::DESCRIPTION    => $basEntity->getDescription(),
                    'bas_id'               => $basEntity->getId()
                ]
            );
        }

        return $utr;
    }

    protected function getCreditUtr(PublicEntity $basEntity)
    {
        $description = $basEntity->getDescription();

        $regexes = self::CREDIT_REGEX_NEFT;

        foreach ($regexes as $regex){
            if (($match = preg_match($regex, $description, $matches)) === 1)
            {
                $utr = $matches[1];

                if($regex == self::CREDIT_REGEX_NEFT_RETURN){

                    $utr = '0' . substr($utr, 0, strlen($utr) - 2);

                }

                return $utr;
            }
        }

        $regexes = self::CREDIT_REGEX;

        foreach ($regexes as $regex)
        {
            if (($match = preg_match($regex, $description, $matches)) === 1)
            {
                return $matches[1];
            }
        }

        return null;
    }

    protected function getDebitUtr(PublicEntity $basEntity)
    {
        $description = $basEntity->getDescription();

        $regexes = self::DEBIT_REGEX;

        foreach ($regexes as $regex)
        {
            if (($match = preg_match($regex, $description, $matches)) === 1)
            {
                return $matches[1];
            }
        }
        return null;
    }

    protected function modifyRequestForMerchantsOnBaasFlow(&$data, $credentials, $merchantId)
    {
        /*
         * Replace aggr_id and accountStatementApiKey with values from BAS, append merchant_id in request as well
         */
        $data[Fields::MERCHANT_ID] = $merchantId;
        $data[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS][Fields::AGGR_ID] = $credentials[Icici\Fields::AGGR_ID];
        $data[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS][Fields::ACCOUNT_STATEMENT_APIKEY] = $credentials[Icici\Fields::BENEFICIARY_API_KEY];
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
                $basEntityFromBank->getSerialNumber(),
                $basEntityFromBank->getDescription()
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
