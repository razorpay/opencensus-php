<?php

namespace RZP\Models\BankingAccount\Gateway;

use App;
use Razorpay\Trace\Logger;
use Illuminate\Support\Facades\Redis;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\FTS;
use RZP\Error\ErrorCode;
use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Services\CardVault;
use RZP\Models\Card\BuNamespace;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Exception\RecordAlreadyExists;
use RZP\Services\BankingAccountService;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields as Fields;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;
use RZP\Models\BankingAccountStatement\Details as BasDetails;

abstract class Processor extends Base\Core
{
    const FTS_MAX_RETRIES    = 1;
    const MAX_MOZART_RETRIES = 1;

    const PINCODES_REDIS_KEY = 'pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE = 'nodal_certs';

    const FTS_VALIDATION_ERROR_DESCRIPTION = 'Operation failed. FTS Account could not stored because of a validation error: ';

    const GATEWAY_ERROR_PREFIX = 'Gateway Error: ';

    protected $basResponse;

    protected $accountCredentials;

    protected $accountNumber;

    protected $ftsErrorCodesToPropagate = [
        ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_FUND_ACCOUNT_CREATION_VALIDATION_FAILED,
        ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED,
        ErrorCode::BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED
    ];

    protected $mozartRetryCode = [
        TraceCode::MOZART_SERVICE_REQUEST_FAILED,
        TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
        ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
    ];

    protected function setUpForBalanceFetch($input)
    {
        if (empty($input) === false)
        {
            $app = App::getFacadeRoot();

            /* @var BankingAccountService $bas */
            $bas = $app['banking_account_service'];

            $merchantId    = $input[BasDetails\Entity::MERCHANT_ID];
            $accountNumber = $input[BasDetails\Entity::ACCOUNT_NUMBER];
            $channel       = $input[BasDetails\Entity::CHANNEL];

            $this->basResponse = $bas->fetchBankingCredentials($merchantId, $channel, $accountNumber);

            $this->accountNumber = $accountNumber;
        }
    }

    public function validateAndPreProcessInputForAccountCreation(array $input)
    {
        $this->validateInputForAccountCreation($input);

        return $this->preProcessInputForAccountCreation($input);
    }

    public function preProcessAccountInfoNotification(array $input)
    {
        return;
    }

    public function processAccountInfoNotification(array $input): array
    {
        return [];
    }

    public function getWebhookDataToReset(Entity $bankingAccount, \RZP\Models\BankingAccount\State\Entity $stateChangeLogBeforeProcessedState): array
    {
        return [];
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        return [];
    }

    public function postProcessNotifyWebhookFailureToOps(array $attributes, string $errorMessage,Entity|null $bankingAccount): void
    {
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        return;
    }

    public function validateStatusMapping(string $bankInternalStatus, $status, $substatus)
    {
        return;
    }

    public function transformBankStatusFromExternalToInternal(string $bankStatus)
    {
        return;
    }

    public function formatInputParametersIfRequired(array $input)
    {
        return $input;
    }

    public function validateAccountDetails(array $input)
    {
        return $input;
    }

    public function addServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection('mutex_redis');

        $redis->sadd(static::PINCODES_REDIS_KEY, $pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection('mutex_redis');

        $redis->srem(static::PINCODES_REDIS_KEY, $pincodes);
    }

    public function createAccountMappingForFts(Entity $bankingAccount)
    {
        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_REQUEST,
                [
                    'id' => $bankingAccount->getId()
                ]);

            $channel = $bankingAccount->getChannel();

            //these credentials fields are mandatory at FTS for account creation. Any change in the field names should
            // be communicated before making any change
            $credentials = [
                Fields::USERNAME      => $bankingAccount->getUsername(),
                Fields::PASSWORD      => $bankingAccount->getPassword(),
                Fields::CORP_ID       => $bankingAccount->getReference1(),
                Fields::CLIENT_ID     => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_ID),
                Fields::CLIENT_SECRET => $bankingAccount->getDetailsDataUsingKey(Fields::CLIENT_SECRET),
            ];

            $product = 'PAYOUT';

            $response = $this->createFtsDirectFundAccountAndSourceAccounts(
                $bankingAccount,
                $credentials,
                $product,
                $channel);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_RESPONSE,
                [
                    'response' => $response,
                    'id'       => $bankingAccount->getId()
                ]);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                \Razorpay\Trace\Logger::CRITICAL,
                TraceCode::FTS_FAILURE_EXCEPTION,
                [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);

            if ($this->shouldPropagateErrorToUser($e->getCode()))
            {
                throw $e;
            }
            else
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                    null,
                    [
                        'banking_account' => $bankingAccount->getPublicId(),
                    ]);
            }
        }
    }

    public function activate(Entity $bankingAccount, array $input): Entity
    {
        $this->validateBeforeActivation($bankingAccount, $input);

        return $this->processActivation($bankingAccount, $input);
    }

    protected function createOrFetchFtsFundAccountForMerchant(Entity $bankingAccount)
    {
        $fundAccountId = $bankingAccount->getFtsFundAccountId();

        if ($fundAccountId !== null)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FTS_MAPPING_ALREADY_PRESENT,
                ['fts_id' => $fundAccountId, 'id' => $bankingAccount->getId()]
            );

            return $fundAccountId;
        }

        $retryCount = 0;

        /** @var FTS\CreateAccount $ftsService */
        $ftsService = app('fts_create_account');

        $response = [];

        while (true)
        {
            try
            {
                $ftsService->initialize($bankingAccount->getId(),
                    Constants\Entity::BANKING_ACCOUNT,
                    Constants\Entity::PAYOUT);

                $response = $ftsService->createFundAccount();

                break;
            }
            catch(\Throwable $e)
            {
                if (($e instanceof \WpOrg\Requests\Exception) and
                    (checkRequestTimeout($e) === true) and
                    ($retryCount < self::FTS_MAX_RETRIES))
                {
                    $this->trace->info(
                        TraceCode::FTS_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'data'    => $e->getData(),
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        $this->checkFundAccountResponseForError($response, $bankingAccount);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_RESPONSE,
            ['id' => $bankingAccount->getId(), 'response' => $response]
        );

        return $response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID];
    }

    protected function createFtsDirectFundAccountAndSourceAccounts(Entity $bankingAccount, array $credentials, string $product = 'PAYOUT', string $channel = 'RBL') :array
    {
        $retryCount = 0;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_REQUEST,
            [
                'id'          => $bankingAccount->getId(),
                'channel'     => $channel,
                'credentials' => $credentials,
                'product'     => $product,
            ]);

        /** @var FTS\CreateAccount $ftsService */
        $ftsService = app('fts_create_account');

        while (true) {
            try {
                $ftsService->initialize($bankingAccount->getId(),
                    Constants\Entity::BANKING_ACCOUNT,
                    Constants\Entity::PAYOUT);

                $response = $ftsService->createFundAccountAndSourceAccounts($credentials,
                    $product,
                    $channel);

                $this->checkFtsDirectFundAccountAndSourceAccountsResponseForError($response, $bankingAccount);

                $ftsFundAccountId = $response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID];

                $ftsService->saveFtsAccountId($ftsFundAccountId);

                return $response;

            } catch (\Throwable $e) {
                if (($e instanceof \WpOrg\Requests\Exception) and
                    (checkRequestTimeout($e) === true) and
                    ($retryCount < self::FTS_MAX_RETRIES)) {
                    $this->trace->info(
                        TraceCode::FTS_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'data'    => $e->getData(),
                        ]);

                    $retryCount++;
                } else {
                    throw $e;
                }
            }
        }
    }

    protected function makeSourceAccountRequest(
        string $id,
        string $ftsAccountId,
        array $content,
        string $product = 'PAYOUT',
        string $channel = 'ICICI')
    {
        $retryCount = 0;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_CREATION_REQUEST,
            [
                'id'     => $id,
                'fts_id' => $ftsAccountId
            ]);

        /** @var FTS\CreateAccount $ftsService */
        $ftsService = app('fts_create_account');

        while (true)
        {
            try
            {
                $response = $ftsService->createSourceAccount($id,
                                                             $ftsAccountId,
                                                             $content,
                                                             $product,
                                                             $channel);

                return $this->checkSourceAccountResponseForError($response, $ftsAccountId);

            }
            catch (RecordAlreadyExists $e)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_ALREADY_PRESENT,
                    [
                        'banking_account_id'    => $id,
                        'channel'               => $channel,
                        'fts_id'                => $ftsAccountId
                    ]);

                return null;
            }
            catch (\Throwable $e)
            {
                if (($e instanceof \WpOrg\Requests\Exception) and
                    (checkRequestTimeout($e) === true) and
                    ($retryCount < self::FTS_MAX_RETRIES))
                {
                    $this->trace->info(
                        TraceCode::FTS_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'data'    => $e->getData(),
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }
    }

    protected function shouldPropagateErrorToUser(string $errorCode)
    {
        return (in_array($errorCode, $this->ftsErrorCodesToPropagate, true) === true);
    }

    protected function isValidationError($response)
    {
        return (array_key_exists(FTS\Constants::INTERNAL_ERROR, $response[FTS\Constants::BODY])
                    && array_key_exists(FTS\Constants::CODE, $response[FTS\Constants::BODY][FTS\Constants::INTERNAL_ERROR])
                    && $response[FTS\Constants::BODY][FTS\Constants::INTERNAL_ERROR][FTS\Constants::CODE] === FTS\Constants::VALIDATION_ERROR);
    }

    protected function checkSourceAccountResponseForError(array $response, $ftsAccountId)
    {
        $contextData = [
            'fts_account_id' => $ftsAccountId,
            'response'       => $response
        ];

        if (((isset($response[FTS\Constants::BODY][FTS\Constants::MESSAGE]) === true) and
            ($response[FTS\Constants::BODY][FTS\Constants::MESSAGE] === 'source account registered')))
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_CREATION_RESPONSE,
                $contextData
            );

            return null;
        }

        // If it's a validation error, we want to propagate the error back to the user (Ops user from admin dashboard)
        // in this case
        if ($this->isValidationError($response))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED,
                null,
                $contextData,
                self::FTS_VALIDATION_ERROR_DESCRIPTION
                . trim($response[FTS\Constants::BODY][FTS\Constants::INTERNAL_ERROR][FTS\Constants::MESSAGE])
            );
        }
        else
        {
            // in any other case source account creation failed. So we throw an exception here.
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_CREATION_FAILED,
                null,
                $contextData,
                'Source account creation failed, Try again'
            );
        }

    }

    protected function checkFtsDirectFundAccountAndSourceAccountsResponseForError(array $response, Entity $bankingAccount)
    {
        if (empty($response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID]) === true)
        {
            // If it's a validation error, we want to propagate the error back to the user (Ops user from admin dashboard)
            // in this case
            if ($this->isValidationError($response) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED,
                    null,
                    ['id' => $bankingAccount->getId(), 'response' => $response],
                    self::FTS_VALIDATION_ERROR_DESCRIPTION
                    .trim($response[FTS\Constants::BODY][FTS\Constants::INTERNAL_ERROR][FTS\Constants::CODE])
                );
            }

            // Throw a generic error for any other case
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_FAILED,
                null,
                ['id' => $bankingAccount->getId(), 'response' => $response],
                'FTS direct fund account and source account creation is not successful, Please try again!'
            );
        }

    }

    protected function checkFundAccountResponseForError(array $response, $bankingAccount)
    {
        if (empty($response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID]) === true)
        {
            // If it's a validation error, we want to propagate the error back to the user (Ops user from admin dashboard)
            // in this case
            if ($this->isValidationError($response) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_FUND_ACCOUNT_CREATION_VALIDATION_FAILED,
                    null,
                    ['id' => $bankingAccount->getId(), 'response' => $response],
                    self::FTS_VALIDATION_ERROR_DESCRIPTION
                    . trim($response[FTS\Constants::BODY][FTS\Constants::INTERNAL_ERROR][FTS\Constants::MESSAGE])
                );
            }

            // Throw a generic error for any other case
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_FUND_ACCOUNT_CREATION_FAILED,
                null,
                ['id' => $bankingAccount->getId(), 'response' => $response],
                'FTS fund Account Id could not stored, Please try again!'
            );
        }
    }

    protected function tokenizeKey(string $element): string
    {
        $request = $traceRequest =
            [
                'namespace'    => self::CREDENTIALS_VAULT_NAMESPACE,
                'secret'       => $element,
                'bu_namespace' => BuNamespace::RAZORPAYX_NODAL_CERTS,
            ];

        /** @var CardVault $cardVaultService */
        $cardVaultService = app('card.cardVault');

        $response = $cardVaultService->createVaultToken($request);

        return $response[CardVault::TOKEN];
    }

    protected function checkForVaultResponseErrors(array $response)
    {
        if ($response[CardVault::SUCCESS] === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                null,
                ['response' => $response]);
        }
    }

    /**
     * We are not rejecting requests based on the pincode availability for now.
     * This is being done to store all the leads we get for account creation.
     * Later we can choose to reject requests directly from here.
     *
     * @param string $pincode
     *
     * @return bool
     */
    protected function isPincodeServiceable(string $pincode): bool
    {
        $redis = Redis::connection('mutex_redis');

        $isAvailable = $redis->sismember(static::PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }

    protected function getFormattedAmount($amount)
    {
        return intval(number_format($amount * 100, 0, '.', ''));
    }

    abstract public function formatAccountDetails(array $input);

    abstract protected function validateBeforeActivation(Entity $bankingAccount, array $input);

    abstract protected function processActivation(Entity $bankingAccount, array $input): Entity;

    abstract protected function validateInputForAccountCreation(array $input);

    abstract protected function preProcessInputForAccountCreation(array $input);

    abstract protected function generateRequestForSourceAccount(Entity $bankingAccount);

    // every gateway processor must implement this function for fetching balance from gateway
    abstract public function fetchGatewayBalance();

    protected function unsetSensitiveDetails(array $request)
    {
        if (isset($request[BaseFields::SOURCE_ACCOUNT][BaseFields::CREDENTIALS]) === true)
        {
            unset($request[BaseFields::SOURCE_ACCOUNT][BaseFields::CREDENTIALS]);
        }

        return $request;
    }

    protected function shouldRetryMozartRequest(string $errorCode): bool
    {
        if (in_array($errorCode, $this->mozartRetryCode, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function handleGatewayErrorExceptionFromMozart($request, $exception, $channel)
    {
        $traceRequest = $this->unsetSensitiveDetails($request);

        $this->trace->traceException(
            $exception,
            Logger::CRITICAL,
            TraceCode::MOZART_SERVICE_REQUEST_FAILED,
            [
                'request' => $traceRequest,
                'channel' => $channel
            ]
        );

        $errorDescription = $this->getGatewayErrorDescriptionFromException($exception);

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
            null,
            [
                'data'    => $exception->getData(),
                'channel' => $channel
            ],
            $errorDescription
        );
    }

    protected function getGatewayErrorDescriptionFromException($exception)
    {
        $gatewayErrorDesc = $exception->getGatewayErrorDesc();

        if ($gatewayErrorDesc === Mozart::NO_ERROR_MAPPING_DESCRIPTION)
        {
            $gatewayErrorDesc = "Unknown";
        }

        if (empty($gatewayErrorDesc) === false)
        {
            return static::GATEWAY_ERROR_PREFIX . $gatewayErrorDesc;
        }

        return static::GATEWAY_ERROR_PREFIX . "Unknown";
    }

    protected function handleThrowableErrorExceptionFromMozart($request, $exception, $channel, &$retryCount)
    {
        $traceRequest = $this->unsetSensitiveDetails($request);

        $this->trace->traceException(
            $exception,
            Logger::CRITICAL,
            TraceCode::MOZART_SERVICE_REQUEST_FAILED,
            [
                'request' => $traceRequest,
                'channel' => $channel,
            ]);

        $errorCode = $exception->getCode();

        $shouldRetry = $this->shouldRetryMozartRequest($errorCode);

        if (($shouldRetry === true) and
            ($retryCount < static::MAX_MOZART_RETRIES))
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
