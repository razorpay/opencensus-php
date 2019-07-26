<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use Carbon\Carbon;

use RZP\Services\FTS;
use RZP\Services\Mozart;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;

class Processor extends BankingAccount\Gateway\Processor
{
    const PINCODES_REDIS_KEY            = 'rbl_pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE   = 'banking_account_creds';

    const DATE_FORMAT = 'd-M-Y';

    const MAX_MOZART_RETRIES            = 1;

    const MAX_BANK_REFERENCE_NUMBER     = 100000;

    // RBL expects the reference number to be 5 digit number, we are not sure
    // if RBL will take 000001 as a valid reference number. To avoid such confusions
    // we are starting the reference number from 10000
    const START_BANK_REFERENCE_NUMBER = 10000;

    protected $mozartRetryCode = [
        TraceCode::MOZART_SERVICE_REQUEST_FAILED,
        TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
        ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
    ];

    protected $mozartGatewayErrorCodes = ['ER022', 'ERR_PG_003'];

    protected $mozartErrorInformation = ['UserID or Password Not Correct '];

    public function preProcessAccountInfoNotification(array $input)
    {
        (new Validator)->validateInput(Validator::PRE_ACCOUNT_INFO_WEBHOOK, $input);

        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        (new Validator)->validateInput(Validator::ACCOUNT_INFO_WEBHOOK, $input);
    }

    public function processAccountInfoNotification(array $input): array
    {
        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $activationDate = $this->parseAndFormatRblDate($attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE]);

        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $activationDate;

        $attributes[BankingAccount\Entity::STATUS] = BankingAccount\Status::PROCESSED;

        $attributes[BankingAccount\Entity::BANK_INTERNAL_STATUS] = Status::CLOSED;

        $attributes[BankingAccount\Entity::BANK_REFERENCE_NUMBER] = $input[Fields::RZP_REFERENCE_NUMBER];

        return $attributes;
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        $tranId = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::HEADER][Fields::TRAN_ID] ?? null;

        $bankStatus = Status::getInternalStatusForBankWebhook($status);

        if (empty($tranId) === true)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_WEBHOOK_MISSING_TRANSACTION_ID,
                [
                    'channel' => BankingAccount\Channel::RBL,
                    'input'   => $input,
                ]);

            $bankStatus = Status::FAILURE;
        }

        $response = [
            Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                Fields::HEADER =>
                    [
                        Fields::TRAN_ID => $tranId,
                    ],
                Fields::BODY   =>
                    [
                        Fields::STATUS => $bankStatus
                    ]
            ],
        ];

        return $response;
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATUS_TO_INTERNAL_STATUS_CHECK,
            [
                'input'     => $input,
                'channel'   => BankingAccount\Channel::RBL,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::ACCOUNT_UPDATE, $input);

        Status::checkRblToInternalStatusMapping($input);
    }

    public function formatInputParametersIfRequired(array $input)
    {
        if (isset($input[Fields::ACTIVATION_DATE]) === true)
        {
            $timestamp = $this->parseAndFormatRblDate($input[Fields::ACTIVATION_DATE]);

            $input[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $timestamp;
        }

        return $input;
    }

    /**
     * @param BankingAccount\Entity $bankingAccount
     * @param array                 $input
     *
     * @return void
     * @throws BadRequestException
     * @throws \Requests_Exception
     * @throws \Throwable
     */
    public function storeCredentials(BankingAccount\Entity $bankingAccount, array $input)
    {
        (new Validator)->validateInput(Validator::ADD_CREDENTIALS, $input);

        $input[Fields::SUBCORP_USER_PASSWORD] = $this->tokenizeCredentials($input[Fields::SUBCORP_USER_PASSWORD]);

        $response = $this->verifyCredentials($bankingAccount, $input);

        $balance = $this->fetchBalanceFromMozartResponse($response);

        $this->checkBalanceForActivation($balance);

        // we will save credentials only once the fetch balance call is successful
        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $bankingAccount->fill($attributes);

        $this->repo->banking_account->saveOrFail($bankingAccount);
    }
    
    public function generateRequestForSourceAccount(BankingAccount\Entity $bankingAccount)
    {
        $rbl = $this->config['gateway']['mozart']['razorpayx']['direct']['rbl'];

        $credentials = [
            Fields::USERNAME                  => $rbl[Fields::AUTH_USERNAME],
            Fields::PASSWORD                  => $rbl[Fields::AUTH_PASSWORD],
            Fields::CLIENT_ID                 => $rbl[Fields::CLIENT_ID],
            Fields::CLIENT_SECRET             => $rbl[Fields::CLIENT_SECRET],
            Fields::SUBCORP_ID                => $bankingAccount->getReference1(),
            Fields::SUBCORP_USER_ID           => $bankingAccount->getUsername(),
            Fields::SUBCORP_USER_PASSWORD     => $bankingAccount->getPassword(),
        ];

        $mozartIdentifier = $rbl[Fields::MOZART_IDENTIFIER];

        $body = [
            FTS\Constants::CREDENTIALS       => $credentials,
            FTS\Constants::MOZART_IDENTIFIER => $mozartIdentifier
        ];

        return $body;
    }

    public function getBalanceAttributesToSave()
    {
        $attributes = [
            Balance\Entity::ACCOUNT_TYPE        => Balance\AccountType::DIRECT,
            Balance\Entity::CHANNEL             => Balance\Channel::RBL
        ];

        return $attributes;
    }

    // Currently we will be activating accounts even if there is previous balance
    protected function checkBalanceForActivation($balance)
    {
        if ($balance !== 0)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_NON_ZERO_OPENING_BALANCE,
                ['balance' => $balance, 'channel' => Balance\Channel::RBL]
            );
        }
    }

    protected function verifyCredentials(BankingAccount\Entity $bankingAccount, array $input)
    {
        $request = $this->formatDataForMozartFetchBalanceApi($bankingAccount, $input);

        $retryCount = 0;

        $response = [];

        while (true)
        {
            try
            {
                $response = $this->app->mozart->sendMozartRequest('razorpayx',
                                                                   BankingAccount\Channel::RBL,
                                                                   Action::ACCOUNT_BALANCE,
                                                                   $request);

                return $response;
            }
            catch (GatewayErrorException $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $request,
                        'channel' => BankingAccount\Channel::RBL,
                    ]);

                $content = $ex->getData();

                $error = $content[Mozart::ERROR];

                $data = $content[Mozart::DATA];

                $shouldInformUser = $this->shouldInformUserForErrorFromMozartResponse($data, $error);

                if ($shouldInformUser === true)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR_WRONG_BANKING_ACCOUNT_CREDENTIALS,
                        null,
                        ['response' => $response, 'channel' => BankingAccount\Channel::RBL]);
                }

                // throwing a generic error since there is no issue with user entered information
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                    null,
                    ['response' => $response, 'channel' => BankingAccount\Channel::RBL]);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $request,
                        'channel' => BankingAccount\Channel::RBL
                    ]);

                $errorCode = $exception->getCode();

                if (($this->shouldRetryMozartRequest($errorCode) === true) and

                    ($retryCount < self::MAX_MOZART_RETRIES))
                {
                    $this->trace-info(
                        TraceCode::MOZART_SERVICE_RETRY,
                        [
                            'message' => $exception->getMessage(),
                            'data'    => $exception->getData(),
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED
                    );
                }
            }
        }
    }

    protected function getFormattedAmount($amount)
    {
        return intval(number_format($amount * 100, 0, '.', ''));
    }

    protected function formatDataForMozartFetchBalanceApi(BankingAccount\Entity $bankingAccount, array $input)
    {
        $credentials = $this->getAccountCredentials();
        
        $merchantCredentials = [
            Fields::SUBCORP_ID                => $input[Fields::SUBCORP_ID],
            Fields::SUBCORP_USER_ID           => $input[Fields::SUBCORP_USER_NAME],
            Fields::SUBCORP_USER_PASSWORD     => $input[Fields::SUBCORP_USER_PASSWORD]
        ];

        $credentials = array_merge($credentials, $merchantCredentials);

        $data = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
                Fields::ID                      => $bankingAccount->getBankReferenceNumber(),
                Fields::CREDENTIALS             => $credentials,
            ],
        ];

        return $data;
    }

    protected function getAccountCredentials()
    {
        $config = $this->config['gateway']['mozart']['razorpayx']['direct']['rbl'];

        $credentials = [
            Fields::USERNAME      => $config[Fields::AUTH_USERNAME],
            Fields::PASSWORD      => $config[Fields::AUTH_PASSWORD],
            Fields::CLIENT_ID     => $config[Fields::CLIENT_ID],
            Fields::CLIENT_SECRET => $config[Fields::CLIENT_SECRET],
        ];

        return $credentials;
    }

    protected function validateInputForAccountCreation(array $input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_AVAILABILITY, $input);
    }

    protected function preProcessInputForAccountCreation(array $input)
    {
        $availability =  $this->isPincodeServiceable($input[BankingAccount\Entity::PINCODE]);

        $mutex = $this->app['api.mutex'];

        $bankReferenceNumber = $mutex->acquireAndRelease(
                                    BankingAccount\Channel::RBL,
                                    function()
                                    {
                                        return $this->generateBankReferenceNumber();
                                    });

        return [
            BankingAccount\Entity::STATUS                   => BankingAccount\Status::CREATED,
            BankingAccount\Entity::BANK_REFERENCE_NUMBER    => $bankReferenceNumber
        ];
    }

    protected function generateBankReferenceNumber()
    {
        $bankingAccount = $this->repo
                               ->banking_account
                               ->getLatestInsertedBankingAccountEntity(BankingAccount\Channel::RBL);

        if ($bankingAccount !== null)
        {
            $referenceNumber = (int) $bankingAccount->getBankReferenceNumber() + 1;

            if ($referenceNumber >= self::MAX_BANK_REFERENCE_NUMBER)
            {
                throw new LogicException(
                    'Rbl maximum account number limit reached',
                    ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_NUMBER_LIMIT_REACHED,
                    [
                        BankingAccount\Entity::CHANNEL => BankingAccount\Channel::RBL
                    ]);
            }
        }
        else
        {
            $referenceNumber = self::START_BANK_REFERENCE_NUMBER;
        }

        return $referenceNumber;
    }

    protected function getMappedAttributes($map, array $input)
    {
        $attr = [];

        foreach ($input as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey        = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function parseAndFormatRblDate(string $date)
    {
        $epochDate = Carbon::createFromFormat(self::DATE_FORMAT, $date, Timezone::IST)
                            ->getTimestamp();

        return $epochDate;
    }

    protected function checkRblToInternalStatusMapping(array $input)
    {
        if (isset($input[BankingAccount\Entity::BANK_INTERNAL_STATUS]) === false)
        {
            return;
        }

        $bankInternalStatus = $input[BankingAccount\Entity::BANK_INTERNAL_STATUS];

        $status = $input[BankingAccount\Entity::STATUS];

        Status::validateInternalBankStatusMappingToStatus($bankInternalStatus, $status);
    }

    protected function shouldRetryMozartRequest(string $errorCode): bool
    {
        if (in_array($errorCode, $this->mozartRetryCode, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function handleErrorForFetchBalance(array $response)
    {
        if ($this->shouldInformUserForErrorFromMozartResponse($response) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_WRONG_BANKING_ACCOUNT_CREDENTIALS,
                null,
                ['response' => $response, 'channel' => BankingAccount\Channel::RBL]);
        }

        // throwing a generic error since there is no issue with user entered information
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
            null,
            ['response' => $response, 'channel' => BankingAccount\Channel::RBL]);
    }

    protected function shouldInformUserForErrorFromMozartResponse(array $data, array $error)
    {
        $gatewayErrorCode = $error[Mozart::GATEWAY_ERROR_CODE] ?? 'gateway_error_code';

        $errorInformation = $data[Mozart::MORE_INFORMATION] ?? 'moreInformation';

        // adding this dirty check for now to prompt the user with appropriate error message
        if ((in_array($errorInformation, $this->mozartErrorInformation, true) === true) or
            (in_array($gatewayErrorCode, $this->mozartGatewayErrorCodes, true) === true))
        {
            return true;
        }

        return false;
    }

    protected function fetchBalanceFromMozartResponse(array $response)
    {
        $balance = $response[Fields::DATA][Fields::GET_ACCOUNT_BALANCE]
                   [Fields::BODY][Fields::BAL_AMOUNT][Fields::AMOUNT_VALUE];

        return $this->getFormattedAmount($balance);
    }
}
