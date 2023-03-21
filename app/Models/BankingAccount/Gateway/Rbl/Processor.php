<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use Carbon\Carbon;

use RZP\Diag\EventCode;
use RZP\Services\FTS;
use RZP\Models\Admin;
use RZP\Services\Mozart;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Constants;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccountStatement\Details as BASDetails;

class Processor extends BankingAccount\Gateway\Processor
{
    const PINCODES_REDIS_KEY            = 'rbl_pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE   = 'banking_account_creds';

    const DATE_FORMAT = 'd-m-Y';

    const FETCH_GATEWAY_BALANCE_TIMEOUT = 10;

    const FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT = 5;

    const MAX_MOZART_RETRIES            = 1;

    const MAX_BANK_REFERENCE_NUMBER     = 100000;

    // RBL expects the reference number to be 5 digit number, we are not sure
    // if RBL will take 000001 as a valid reference number. To avoid such confusions
    // we are starting the reference number from 10000
    const START_BANK_REFERENCE_NUMBER = 10000;

    const GATEWAY_ERROR_PREFIX = 'RBL Gateway Error: ';

    protected $mozartRetryCode = [
        TraceCode::MOZART_SERVICE_REQUEST_FAILED,
        TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
        ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
    ];

    protected $mozartGatewayErrorCodes = ['ER022', 'ERR_PG_003'];

    protected $mozartErrorInformation = ['UserID or Password Not Correct '];

    /** @var Entity $bankingAccount */
    protected $bankingAccount;

    public function __construct(array $input = [])
    {
        parent::__construct();

        if (empty($input) === false)
        {
            $merchantId = $input[Entity::MERCHANT_ID];

            $channel = $input[Entity::CHANNEL];

            /** @var Entity $bankingAccount */
            $this->bankingAccount = $this->repo->banking_account->getActiveBankingAccountByMerchantIdAndChannel($merchantId, $channel);
        }
    }

    public function processActivation(Entity $bankingAccount, array $input): Entity
    {
        $balance = $this->fetchGatewayBalanceForBankingAccount($bankingAccount);

        $this->checkBalanceForActivation($balance);

        $this->createAccountMappingForFts($bankingAccount);

        $input = [
            Entity::STATUS     => BankingAccount\Status::ACTIVATED,
            Entity::SUB_STATUS => BankingAccount\Status::UPI_CREDS_PENDING,
        ];

        $bankingAccount->fill($input);

        return $bankingAccount;
    }

    public function preProcessAccountInfoNotification(array $input)
    {
        (new Validator)->validateInput(Validator::PRE_ACCOUNT_INFO_WEBHOOK, $input);

        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        // RBL sends Phone Number and Account Number fields in the below format
        // Phone no. and Account No. This dot is replaced by Laravel to =>
        // following array notation as a result of which the validations
        // start failing. So we are modifying the input to convert above
        // field to Account No and Phone no
        $content = $this->modifyWebhookInput($input);

        (new Validator)->validateInput(Validator::ACCOUNT_INFO_WEBHOOK, $content);
    }

    public function processAccountInfoNotification(array $input): array
    {
        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $activationDate = $this->parseAndFormatRblDate($attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE]);

        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $activationDate;

        $attributes[BankingAccount\Entity::STATUS] = BankingAccount\Status::PROCESSED;

        $attributes[BankingAccount\Entity::SUB_STATUS] = BankingAccount\Status::API_ONBOARDING_PENDING;

        $attributes[BankingAccount\Entity::BANK_INTERNAL_STATUS] = Status::CLOSED;

        $attributes['activation_detail'][BankingAccount\Activation\Detail\Entity::ASSIGNEE_TEAM] = 'ops';

        $attributes['activation_detail'][BankingAccount\Activation\Detail\Entity::ADDITIONAL_DETAILS]
                    [BankingAccount\Activation\Detail\Entity::ACCOUNT_OPENING_WEBHOOK_DATE] = Carbon::now()->timestamp;

        return $attributes;
    }

    public function getWebhookDataToReset(Entity $bankingAccount, BankingAccount\State\Entity $stateChangeLogBeforeProcessedState): array
    {
        return [
            Entity::ACCOUNT_NUMBER => null,
            Entity::BENEFICIARY_NAME => null,
            Entity::BANK_INTERNAL_REFERENCE_NUMBER => null,
            Entity::ACCOUNT_ACTIVATION_DATE => null,
            Entity::BANK_REFERENCE_NUMBER => $bankingAccount->getBankReferenceNumber(),
            Entity::ACCOUNT_IFSC => null,
            Entity::BENEFICIARY_ADDRESS1 => null,
            Entity::BENEFICIARY_ADDRESS2 => null,
            Entity::BENEFICIARY_ADDRESS3 => null,
            Entity::BENEFICIARY_CITY => null,
            Entity::BENEFICIARY_STATE => null,
            Entity::BENEFICIARY_COUNTRY => null,
            Entity::BENEFICIARY_PIN => null,
            Entity::BENEFICIARY_MOBILE=> null,
            Entity::BENEFICIARY_EMAIL => null,
            Entity::STATUS=> $stateChangeLogBeforeProcessedState['status'],
            Entity::SUB_STATUS=> $stateChangeLogBeforeProcessedState['sub_status'],
            Entity::BANK_INTERNAL_STATUS=> $stateChangeLogBeforeProcessedState['bank_status'],
            Entity::ACTIVATION_DETAIL => [
                Entity::ASSIGNEE_TEAM => $stateChangeLogBeforeProcessedState['assignee_team'],
                BankingAccount\Activation\Detail\Entity::ADDITIONAL_DETAILS => [
                    BankingAccount\Activation\Detail\Entity::ACCOUNT_OPENING_WEBHOOK_DATE => null,
                ],
                Comment\Entity::COMMENT => [
                    Comment\Entity::COMMENT            => Constants::BANKING_ACCOUNT_RESET_WEBHOOK_COMMENT,
                    Comment\Entity::SOURCE_TEAM_TYPE   => Constants::BANKING_ACCOUNT_SOURCE_TEAM_OR_TYPE_AS_INTERNAL,
                    Comment\Entity::SOURCE_TEAM        => Constants::X_OPS_TEAM,
                    Comment\Entity::ADDED_AT           => time(),
                    Comment\Entity::TYPE               => Constants::BANKING_ACCOUNT_SOURCE_TEAM_OR_TYPE_AS_INTERNAL
                ]
            ]
        ];
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

    public function postProcessNotifyWebhookFailureToOps(array $input, string $errorMessage,Entity|null $bankingAccount): void
    {
        $bankReferenceNumber = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY][Fields::RZP_REFERENCE_NUMBER] ?? 'Missing';

        $merchant = optional($bankingAccount)->merchant ?? null;
        $this->app['diag']->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_RBL_WEBHOOK_FAILURE, $merchant, null, [
            'input'          => $input,
            'failure_reason' => $errorMessage
        ]);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_BANK_WEBHOOK_FAILURE_NOTIFICATION,
            [
                'bank_reference_number'         => $bankReferenceNumber,
                'channel'                       => BankingAccount\Channel::RBL,
                'message'                       => 'Event fired'
            ]);
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
    }

    public function validateStatusMapping(string $bankInternalStatus, $status, $substatus)
    {
        Status::checkRblToInternalStatusMapping($bankInternalStatus, $status, $substatus);
    }

    public function formatInputParametersIfRequired(array $input)
    {
        if (isset($input[Fields::ACTIVATION_DATE]) === true)
        {
            $timestamp = $this->parseAndFormatRblDate($input[Fields::ACTIVATION_DATE]);

            $input[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $timestamp;
        }

        $input = $this->tokenizeSensitiveFields($input);

        return $input;
    }

    /**
     *
     * @return int
     *
     * @throws BadRequestException
     */
    public function fetchGatewayBalanceForBankingAccount(BankingAccount\Entity $bankingAccount): int
    {
        $response = $this->verifyCredentials($bankingAccount);

        $balance = $this->fetchBalanceFromMozartResponse($response);

        return $balance;
    }

    /**
     *
     * @return int
     *
     * @throws BadRequestException
     */
    public function fetchGatewayBalance(): int
    {
        $balance = $this->fetchGatewayBalanceForBankingAccount($this->bankingAccount);

        $this->bankingAccount->setGatewayBalance($balance);

        $this->bankingAccount->setBalanceLastFetchedAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($this->bankingAccount);

        return $balance;
    }

    public function generateRequestForSourceAccount(BankingAccount\Entity $bankingAccount)
    {
        $rblConfig = $this->config['gateway']['mozart']['razorpayx']['direct']['rbl'];

        $credentials = [
            Fields::USERNAME                  => $bankingAccount->getUsername(),
            Fields::PASSWORD                  => $bankingAccount->getPassword(),
            Fields::CORP_ID                   => $bankingAccount->getReference1(),
            Fields::CLIENT_ID                 => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_ID),
            Fields::CLIENT_SECRET             => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_SECRET),
        ];

        $config = [
            FTS\Constants::BENEFICIARY_REQUIRED     => false,
        ];

        $mozartIdentifier = $rblConfig[Fields::MOZART_IDENTIFIER];

        $body = [
            FTS\Constants::CREDENTIALS                      => $credentials,
            FTS\Constants::MOZART_IDENTIFIER                => $mozartIdentifier,
            FTS\Constants::CONFIGURATION                    => $config,
            FTS\Constants::SOURCE_ACCOUNT_TYPE_IDENTIFIER   => BankingAccount\AccountType::DIRECT,
            FTS\Constants::BANK_ACCOUNT_TYPE                => strtoupper(BankingAccount\AccountType::CURRENT),

        ];

        return $body;
    }

    public function validateAccountDetails(array $input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_DETAILS_UPDATE, $input);
    }

    public function formatAccountDetails(array $input)
    {
        $input = $this->tokenizeSensitiveFields($input);

        return $input;
    }

    protected function tokenizeSensitiveFields(array $input)
    {
        foreach ($input as $key => $value)
        {
            if (in_array($key, Fields::$sensitiveAccountDetails, true) === true)
            {
                $input[$key] = $this->tokenizeKey($value);
            }
        }

        return $input;
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

    protected function getRblGatewayDescriptionFromException(GatewayErrorException $ex)
    {
        $gatewayErrorDesc = $ex->getGatewayErrorDesc();

        $data = $ex->getData();

        // If no error description is mapped, then check for the moreInformation field
        if ($gatewayErrorDesc === Mozart::NO_ERROR_MAPPING_DESCRIPTION)
        {
            if ((isset($data['data']) === true)
                and (isset($data['data']['moreInformation']) === true))
            {
                $gatewayErrorDesc = $data['data']['moreInformation'];
            }
        }

        if (empty($gatewayErrorDesc) === false)
        {
            return self::GATEWAY_ERROR_PREFIX . $gatewayErrorDesc;
        }

        return self::GATEWAY_ERROR_PREFIX . "Unknown";
    }

    protected function appendUrlVersion(array & $request)
    {
        $request['url']['version'] = 'v2';
    }

    protected function verifyCredentials(BankingAccount\Entity $bankingAccount)
    {
        $request = $this->formatDataForMozartFetchBalanceApi($bankingAccount);

        $this->appendUrlVersion($request);

        $retryCount = 0;

        $response = [];

        while (true)
        {
            try
            {
                $response = $this->app->mozart->sendMozartRequest('razorpayx',
                                                                  BankingAccount\Channel::RBL,
                                                                  Action::ACCOUNT_BALANCE,
                                                                  $request,
                                                                  Mozart::DEFAULT_MOZART_VERSION,
                                                                  false,
                                                                  self::FETCH_GATEWAY_BALANCE_TIMEOUT,
                                                                  self::FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT
                );

                return $response;
            }
            catch (GatewayErrorException $ex)
            {
                $traceRequest = $this->unsetSensitiveDetails($request);

                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $traceRequest,
                        'channel' => BankingAccount\Channel::RBL,
                    ]);

                // throwing a generic error with which includes the gateway error description from Mozart
                $errorDescription = $this->getRblGatewayDescriptionFromException($ex);

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                    null,
                    ['data' => $ex->getData(), 'channel' => BankingAccount\Channel::RBL],
                    $errorDescription);
            }
            catch (\Throwable $exception)
            {
                $traceRequest = $this->unsetSensitiveDetails($request);

                $this->trace->traceException(
                    $exception,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $traceRequest,
                        'channel' => BankingAccount\Channel::RBL
                    ]);

                $errorCode = $exception->getCode();

                if (($this->shouldRetryMozartRequest($errorCode) === true) and

                    ($retryCount < self::MAX_MOZART_RETRIES))
                {
                    $this->trace->info(
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

    protected function formatDataForMozartFetchBalanceApi(BankingAccount\Entity $bankingAccount)
    {
        $data = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
                Fields::ID                      => $bankingAccount->getBankReferenceNumber(),
                Fields::CREDENTIALS             => [
                    Fields::AUTH_USERNAME           => $bankingAccount->getUsername(),
                    Fields::AUTH_PASSWORD           => $bankingAccount->getPassword(),
                    Fields::CORP_ID                 => $bankingAccount->getReference1(),
                    Fields::CLIENT_ID               => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_ID),
                    Fields::CLIENT_SECRET           => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_SECRET),
                ],
            ],
        ];

        return $data;
    }

    protected function validateInputForAccountCreation(array $input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_AVAILABILITY, $input);
    }

    protected function preProcessInputForAccountCreation(array $input)
    {
        //
        // Commented this because currently we're not rejecting requests based on pincodes
        // This can be useful later when product prioritizes this
        //
        // $availability =  $this->isPincodeServiceable($input[BankingAccount\Entity::PINCODE]);

        $mutex = $this->app['api.mutex'];

        $bankReferenceNumber = $mutex->acquireAndRelease(
                                    BankingAccount\Channel::RBL,
                                    function()
                                    {
                                        return $this->generateBankReferenceNumber();
                                    },
                                    60,
                                    ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                                    3);

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
        $isInCorrectFormat = Carbon::hasFormat($date, self::DATE_FORMAT);
        if(!$isInCorrectFormat)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_INCORRECT_FORMAT_FOR_ACCOUNT_OPEN_DATE,
                null,
                [
                    'account_activation_date'  => $date
                ],
                'Account activation date: ' . $date . ' in the payload is in incorrect format.'
            );
        }

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

    public function transformBankStatusFromExternalToInternal(string $bankStatus)
    {
        return Status::transformFromExternalToInternal($bankStatus);
    }

    protected function shouldRetryMozartRequest(string $errorCode): bool
    {
        if (in_array($errorCode, $this->mozartRetryCode, true) === true)
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

    protected function modifyWebhookInput(array $input)
    {
        $input[Fields::ACCOUNT_NO] = $input[Fields::ACCOUNT_NUMBER];

        $input[Fields::PHONE_NO] = $input[Fields::PHONE_NUM];

        unset($input[Fields::ACCOUNT_NUMBER]);

        unset($input[Fields::PHONE_NUM]);

        return $input;
    }

    protected function validateBeforeActivation(Entity $bankingAccount, array $input = [])
    {
        $input = [];

        $input[Fields::CLIENT_ID] = $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_ID);

        $input[Fields::CLIENT_SECRET] = $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_SECRET);

        $fieldsRequired = Validator::$accountActivateRules;

        foreach ($fieldsRequired as $key => $val)
        {
            $functionName = 'get' . studly_case($key);

            if (method_exists($bankingAccount, $functionName))
            {
                $input[$key] = $bankingAccount->$functionName();
            }
        }

        (new Validator)->validateInput(Validator::ACCOUNT_ACTIVATE, $input);
    }

    protected function unsetSensitiveDetails(array $request)
    {
        if (isset($request[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS]) === true)
        {
            unset($request[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS]);
        }

        return $request;
    }
}
